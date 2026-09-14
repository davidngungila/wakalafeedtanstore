// ============================================================================
// MobiControl — Wakala Feedtan Store companion
// Single-file Flutter application (mobile/lib/main.dart)
//
// Talks to https://wakala.feedtanstore.com/api/v1 using the device code.
// Listens for mobile-money SMS in the background (native SmsReceiver +
// foreground service, bridged via MethodChannel/EventChannel), matches the
// sender against the downloaded watchlist, queues the matches offline, and
// uploads them in batches of <= 500 to POST /sms/ingest.
//
// The UI mirrors the web app structure: Raleway type, sand/coffee/terracotta/
// acacia palette, stat cards, state tags, navigation rail.
// ============================================================================

import 'dart:async';
import 'dart:convert';
import 'dart:math';

import 'package:crypto/crypto.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'package:flutter_foreground_task/flutter_foreground_task.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'package:workmanager/workmanager.dart';

// ---------------------------------------------------------------------------
// 1. DESIGN TOKENS — exact palette & type from resources/views/layouts/app.blade.php
// ---------------------------------------------------------------------------
class WakalaColors {
  static const sand50 = Color(0xFFFBF7EF);
  static const sand100 = Color(0xFFF4ECDC);
  static const sand200 = Color(0xFFE9DCC0);

  static const coffee900 = Color(0xFF2A1B10);
  static const coffee800 = Color(0xFF3B2718);
  static const coffee700 = Color(0xFF4D3422);
  static const coffee500 = Color(0xFF7A5C42);
  static const coffee300 = Color(0xFFA98968);

  static const terracotta600 = Color(0xFFC2592B);
  static const terracotta500 = Color(0xFFD06B3A);
  static const terracotta100 = Color(0xFFF6E1D3);

  static const acacia600 = Color(0xFF5E6E3F);
  static const acacia500 = Color(0xFF7A8450);
  static const acacia100 = Color(0xFFE2E7D4);

  static const gold500 = Color(0xFFD4A24C);
  static const gold100 = Color(0xFFF7E9CB);

  static const ink = Color(0xFF241408);
  static const inkSoft = Color(0xFF6B5A48);
  static const line = Color(0xFFE4D7C2);
  static const white = Color(0xFFFFFFFF);

  static const danger = Color(0xFFB33A3A);
  static const danger100 = Color(0xFFF6DCDA);
  static const success = Color(0xFF3F6B3F);
}

class WakalaRadii {
  static const sm = 8.0;
  static const md = 12.0;
  static const lg = 16.0;
  static const pill = 999.0;
}

class WakalaSpacing {
  static const xs = 4.0;
  static const sm = 8.0;
  static const md = 16.0;
  static const lg = 24.0;
  static const xl = 32.0;
}

class WakalaTheme {
  static ThemeData build() {
    final base = ThemeData(
      useMaterial3: true,
      fontFamily: 'Raleway',
      scaffoldBackgroundColor: WakalaColors.sand50,
      colorScheme: const ColorScheme.light(
        primary: WakalaColors.terracotta600,
        secondary: WakalaColors.gold500,
        surface: WakalaColors.white,
        onSurface: WakalaColors.ink,
      ),
    );

    return base.copyWith(
      appBarTheme: base.appBarTheme.copyWith(
        backgroundColor: WakalaColors.white,
        foregroundColor: WakalaColors.ink,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: const TextStyle(
          fontFamily: 'Raleway',
          fontWeight: FontWeight.w800,
          fontSize: 18,
          color: WakalaColors.ink,
        ),
      ),
      cardTheme: base.cardTheme.copyWith(
        color: WakalaColors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(WakalaRadii.md),
          side: const BorderSide(color: WakalaColors.line),
        ),
        margin: EdgeInsets.zero,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: WakalaColors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(WakalaRadii.md),
          borderSide: const BorderSide(color: WakalaColors.line),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(WakalaRadii.md),
          borderSide: const BorderSide(color: WakalaColors.line),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(WakalaRadii.md),
          borderSide: const BorderSide(color: WakalaColors.terracotta600, width: 1.5),
        ),
        hintStyle: const TextStyle(color: WakalaColors.inkSoft),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: WakalaColors.terracotta600,
          foregroundColor: WakalaColors.white,
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 20),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(WakalaRadii.md),
          ),
          textStyle: const TextStyle(fontFamily: 'Raleway', fontWeight: FontWeight.w700),
        ),
      ),
      textTheme: base.textTheme.copyWith(
        displaySmall: const TextStyle(fontFamily: 'Raleway', fontWeight: FontWeight.w900, color: WakalaColors.ink),
        headlineSmall: const TextStyle(fontFamily: 'Raleway', fontWeight: FontWeight.w800, color: WakalaColors.ink),
        titleLarge: const TextStyle(fontFamily: 'Raleway', fontWeight: FontWeight.w800, color: WakalaColors.ink),
        titleMedium: const TextStyle(fontFamily: 'Raleway', fontWeight: FontWeight.w700, color: WakalaColors.ink),
        bodyMedium: const TextStyle(fontFamily: 'Raleway', fontWeight: FontWeight.w400, color: WakalaColors.ink),
        bodySmall: const TextStyle(fontFamily: 'Raleway', fontWeight: FontWeight.w400, color: WakalaColors.inkSoft),
        labelLarge: const TextStyle(fontFamily: 'Raleway', fontWeight: FontWeight.w700, color: WakalaColors.ink),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// 2. DOMAIN MODELS — mirrors the JSON these endpoints return
// ---------------------------------------------------------------------------
const String kApiBase = 'https://wakala.feedtanstore.com/api/v1';
const int kMaxBatch = 500;
const int kOfflineAfterMinutes = 10;

enum DeviceStatus { pending, active, suspended, blocked, revoked, offline }

DeviceStatus statusFrom(String? value) {
  switch (value) {
    case 'active':
      return DeviceStatus.active;
    case 'suspended':
      return DeviceStatus.suspended;
    case 'blocked':
      return DeviceStatus.blocked;
    case 'revoked':
      return DeviceStatus.revoked;
    case 'offline':
      return DeviceStatus.offline;
    default:
      return DeviceStatus.pending;
  }
}

class DeviceInfo {
  final int id;
  final String name;
  final DeviceStatus status;
  final String? agent;
  final String? network;
  final List<String> networks;
  final String? branch;
  final String? lastSmsAt;
  final String? lastSyncAt;
  final String? lastHeartbeatAt;

  const DeviceInfo({
    required this.id,
    required this.name,
    required this.status,
    this.agent,
    this.network,
    this.networks = const [],
    this.branch,
    this.lastSmsAt,
    this.lastSyncAt,
    this.lastHeartbeatAt,
  });

  factory DeviceInfo.fromJson(Map<String, dynamic> json) => DeviceInfo(
        id: json['id'] as int,
        name: (json['name'] ?? '').toString(),
        status: statusFrom(json['status']?.toString()),
        agent: json['agent']?.toString(),
        network: json['network']?.toString(),
        networks: ((json['networks'] as List?) ?? const []).map((e) => e.toString()).toList(),
        branch: json['branch']?.toString(),
        lastSmsAt: json['last_sms_at']?.toString(),
        lastSyncAt: json['last_sync_at']?.toString(),
        lastHeartbeatAt: json['last_heartbeat_at']?.toString(),
      );

  bool get canIngest => status == DeviceStatus.active;
}

class SenderRule {
  final String keyword;
  final String network;

  const SenderRule({required this.keyword, required this.network});

  factory SenderRule.fromJson(Map<String, dynamic> json) => SenderRule(
        keyword: json['keyword'].toString(),
        network: json['network'].toString(),
      );
}

class SenderWatchlist {
  final DeviceInfo device;
  final List<SenderRule> senders;
  final int maxBatch;
  final DateTime serverTime;

  const SenderWatchlist({
    required this.device,
    required this.senders,
    this.maxBatch = kMaxBatch,
    required this.serverTime,
  });

  factory SenderWatchlist.fromJson(Map<String, dynamic> json) => SenderWatchlist(
        device: DeviceInfo.fromJson(json['device'] as Map<String, dynamic>),
        senders: ((json['senders'] as List?) ?? const [])
            .map((e) => SenderRule.fromJson(e as Map<String, dynamic>))
            .toList(),
        maxBatch: ((json['ingest'] as Map<String, dynamic>?)?['max_batch'] as num?)?.toInt() ?? kMaxBatch,
        serverTime: DateTime.parse(json['server_time'] as String),
      );

  bool matches(String sender) {
    final s = sender.toLowerCase();
    return senders.any((r) => s.contains(r.keyword.toLowerCase()));
  }
}

/// One captured SMS before upload.
class SmsMessage {
  final String sender;
  final String body;
  final String receivedAt;
  final String digest;

  SmsMessage({required this.sender, required this.body, required this.receivedAt})
      : digest = sha256(body);

  Map<String, String> toJson() => {'sender': sender, 'message': body, 'received_at': receivedAt};
}

class IngestResult {
  final bool ok;
  final int? smsId;
  final String? reference;
  final bool duplicate;
  final bool ignoredSender;
  final String? error;

  const IngestResult({
    this.ok = false,
    this.smsId,
    this.reference,
    this.duplicate = false,
    this.ignoredSender = false,
    this.error,
  });

  bool get acknowledged => ok || duplicate;

  factory IngestResult.fromJson(Map<String, dynamic> json) => IngestResult(
        ok: json['ok'] == true,
        smsId: (json['sms_id'] as num?)?.toInt(),
        reference: json['reference']?.toString(),
        duplicate: json['duplicate'] == true,
        ignoredSender: json['ignored_sender'] == true,
        error: json['error']?.toString(),
      );
}

class IngestSummary {
  final int received;
  final int processed;
  final int duplicates;
  final int ignoredSenders;
  final int failed;
  final List<IngestResult> results;

  const IngestSummary({
    required this.received,
    required this.processed,
    required this.duplicates,
    required this.ignoredSenders,
    required this.failed,
    required this.results,
  });

  factory IngestSummary.fromJson(Map<String, dynamic> json) => IngestSummary(
        received: _num(json, ['summary', 'received']),
        processed: _num(json, ['summary', 'processed']),
        duplicates: _num(json, ['summary', 'duplicates']),
        ignoredSenders: _num(json, ['summary', 'ignored_senders']),
        failed: _num(json, ['summary', 'failed']),
        results: ((json['results'] as List?) ?? const [])
            .map((e) => IngestResult.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}

int _num(Map<String, dynamic> json, List<String> path) {
  dynamic node = json;
  for (final key in path) {
    if (node is Map<String, dynamic>) node = node[key];
  }
  return (node as num?)?.toInt() ?? 0;
}

// ---------------------------------------------------------------------------
// Small crypto helper — sha256 for local dedupe hints. The server de-dupes
// authoritatively; the client digest is only used to skip obvious replays.
// ---------------------------------------------------------------------------
final Random _rng = Random.secure();

String sha256(String input) => crypto.sha256.convert(utf8.encode(input)).toString();

String randomUid() => List.generate(16, (_) => _rng.nextInt(10)).join();

// ---------------------------------------------------------------------------
// 3. SECURE STORAGE + API CLIENT
// ---------------------------------------------------------------------------
class CodeStore {
  static const _codes = <String, String>{};
  static const _secure = FlutterSecureStorage();
  static const _prefsKey = 'wakala.device_code';

  static Future<String?> read() async {
    final cached = _codes[_prefsKey];
    if (cached != null) return cached;
    final value = await _secure.read(key: _prefsKey);
    if (value != null) _codes[_prefsKey] = value;
    return value;
  }

  static Future<void> write(String code) async {
    await _secure.write(key: _prefsKey, value: code);
    _codes[_prefsKey] = code;
  }

  static Future<void> clear() async {
    await _secure.delete(key: _prefsKey);
    _codes.remove(_prefsKey);
  }
}

class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final DeviceStatus? deviceStatus;

  ApiException(this.message, {this.statusCode, this.deviceStatus});

  bool get codeInvalid => statusCode != null && (statusCode == 400 || statusCode == 401);
  bool get deviceBlocked => statusCode == 403;

  @override
  String toString() => message;
}

class ApiClient {
  final http.Client _client;
  final String baseUrl;

  ApiClient({http.Client? client, this.baseUrl = kApiBase}) : _client = client ?? http.Client();

  /// Throws [ApiException]. Auto-refreshes nothing — the device code is the credential.
  Future<Map<String, dynamic>> request(
    String method,
    String path, {
    Map<String, dynamic>? body,
  }) async {
    final code = await CodeStore.read();
    final headers = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (code != null) 'X-Device-Code': code,
    };

    late final http.Response response;
    final uri = Uri.parse('$baseUrl$path');
    try {
      response = switch (method) {
        'GET' => await _client.get(uri, headers: headers),
        'POST' => await _client.post(uri, headers: headers, body: jsonEncode(body ?? const {})),
        _ => throw UnsupportedError('method $method'),
      };
    } on Exception {
      throw ApiException('Network unreachable. Retrying later.');
    }

    Map<String, dynamic> json = {};
    if (response.body.isNotEmpty) {
      try {
        json = jsonDecode(response.body) as Map<String, dynamic>;
      } catch (_) {
        json = <String, dynamic>{'raw': response.body};
      }
    }

    if (response.statusCode >= 400) {
      throw ApiException(
        json['message']?.toString() ?? 'HTTP ${response.statusCode}',
        statusCode: response.statusCode,
        deviceStatus: json['device_status']?.toString() == null
            ? null
            : statusFrom(json['device_status']?.toString()),
      );
    }
    return json;
  }
}

class DeviceApi {
  final ApiClient api;

  DeviceApi(this.api);

  Future<DeviceInfo> bootstrap({String? deviceUid, String? model, String? androidVersion, String? appVersion}) async {
    final json = await api.request('POST', '/devices/bootstrap', body: {
      'device_uid': deviceUid ?? randomUid(),
      'model': model,
      'android_version': androidVersion,
      'app_version': appVersion,
    });
    return DeviceInfo.fromJson(json['device'] as Map<String, dynamic>);
  }

  Future<DeviceInfo> me() async {
    final json = await api.request('GET', '/devices/me');
    return DeviceInfo.fromJson(json['device'] as Map<String, dynamic>);
  }
}

class SmsApi {
  final ApiClient api;

  SmsApi(this.api);

  Future<SenderWatchlist> senders() async {
    final json = await api.request('GET', '/sms/senders');
    return SenderWatchlist.fromJson(json);
  }

  Future<IngestSummary> ingest(List<SmsMessage> batch) async {
    final json = await api.request('POST', '/sms/ingest', body: {
      'sms': batch.map((s) => s.toJson()).toList(),
    });
    return IngestSummary.fromJson(json);
  }
}

class HeartbeatApi {
  final ApiClient api;

  HeartbeatApi(this.api);

  Future<void> heartbeat({String? model, String? androidVersion, String? appVersion}) async {
    await api.request('POST', '/heartbeat', body: {
      'model': model,
      'android_version': androidVersion,
      'app_version': appVersion,
    });
  }
}

// ---------------------------------------------------------------------------
// 4. OFFLINE QUEUE — durable; items removed only on server acknowledgement
// ---------------------------------------------------------------------------
class IngestQueue {
  IngestQueue._();
  static final IngestQueue instance = IngestQueue._();

  final List<SmsMessage> _items = [];

  List<SmsMessage> get items => List.unmodifiable(_items);
  int get length => _items.length;

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString('wakala.queue');
    if (raw == null) return;
    try {
      final decoded = jsonDecode(raw) as List;
      _items
        ..clear()
        ..addAll(decoded.map((e) {
          final m = e as Map<String, dynamic>;
          return SmsMessage(sender: m['sender'], body: m['body'], receivedAt: m['received_at']);
        }));
    } catch (_) {
      await prefs.remove('wakala.queue');
    }
  }

  Future<void> enqueue(SmsMessage sms) async {
    if (_items.any((e) => e.digest == sms.digest)) return;
    _items.add(sms);
    await _persist();
  }

  Future<void> remove(Set<String> digests) async {
    _items.removeWhere((e) => digests.contains(e.digest));
    await _persist();
  }

  Future<void> _persist() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      'wakala.queue',
      jsonEncode(_items.map((e) => {'sender': e.sender, 'body': e.body, 'received_at': e.receivedAt}).toList()),
    );
  }
}

// ---------------------------------------------------------------------------
// 5. NATIVE BRIDGE — platform channels for background SMS capture
// ---------------------------------------------------------------------------
class SmsBridge {
  static const MethodChannel _methodChannel = MethodChannel('wakala/sms');
  static const EventChannel _eventChannel = EventChannel('wakala/sms/events');

  static Stream<SmsMessage>? _stream;

  /// Called by native code: 'start' / 'stop'.
  static Future<Object?> callNative(String action) async {
    try {
      return await _methodChannel.invokeMethod(action);
    } on MissingPluginException {
      debugPrint('SmsBridge unavailable in this build (expected on desktop).');
      return null;
    }
  }

  /// Incoming SMS pushed from the Android SmsReceiver.
  static Stream<SmsMessage> messages() {
    if (_stream == null) {
      _stream = _eventChannel.receiveBroadcastStream().map((event) {
        final raw = (event as Map).cast<String, dynamic>();
        return SmsMessage(
          sender: raw['sender'].toString(),
          body: raw['message'].toString(),
          receivedAt: raw['received_at'].toString(),
        );
      });
    }
    return _stream!;
  }
}

// ---------------------------------------------------------------------------
// 6. ORCHESTRATION — AgentService (UI + WorkManager + foreground task)
// ---------------------------------------------------------------------------
class AgentService {
  AgentService._();
  static final AgentService instance = AgentService._();

  final ApiClient _api = ApiClient();
  late final DeviceApi _deviceApi = DeviceApi(_api);
  late final SmsApi _smsApi = SmsApi(_api);
  late final HeartbeatApi _heartbeatApi = HeartbeatApi(_api);

  DeviceInfo? _device;
  SenderWatchlist? _watchlist;
  final _statusController = StreamController<DeviceInfo?>.broadcast();
  final _logController = StreamController<Map<String, dynamic>>.broadcast();

  Stream<DeviceInfo?> get deviceStream => _statusController.stream;
  Stream<Map<String, dynamic>> get logStream => _logController.stream;

  DeviceInfo? get device => _device;
  SenderWatchlist? get watchlist => _watchlist;

  String? get codeValid => _device?.status != null ? 'ok' : null;

  /// Idempotent. Safe to call from the UI and from background workers.
  Future<void> cycle() async {
    final code = await CodeStore.read();
    if (code == null) return;

    try {
      _device = await _deviceApi.me();
      _statusController.add(_device);
      _log('profile', {'status': _device!.status.name, 'networks': _device!.networks});
    } on ApiException catch (e) {
      if (e.codeInvalid || e.deviceBlocked) {
        await CodeStore.clear();
        _device = null;
        _statusController.add(null);
        return;
      }
      rethrow;
    }

    await _refreshWatchlistIfStale();

    if (_device != null && _device!.canIngest) {
      await flushQueue();
    }

    await _sendHeartbeat();
  }

  Future<void> _refreshWatchlistIfStale() async {
    final prefs = await SharedPreferences.getInstance();
    final lastFetch = prefs.getInt('wakala.watchlist_at') ?? 0;
    if (DateTime.now().millisecondsSinceEpoch - lastFetch < 5 * 60 * 1000) return;

    try {
      _watchlist = await _smsApi.senders();
      await prefs.setInt('wakala.watchlist_at', DateTime.now().millisecondsSinceEpoch);
    } catch (_) {
      // keep the previous watchlist; the next cycle retries
    }
  }

  Future<void> flushQueue() async {
    final queue = IngestQueue.instance;
    if (_watchlist == null || queue.items.isEmpty) return;

    final batch = queue.items.take(min(kMaxBatch, queue.items.length)).toList();
    final summary = await _smsApi.ingest(batch);

    final acknowledged = <String>{};
    for (var i = 0; i < summary.results.length && i < batch.length; i++) {
      final result = summary.results[i];
      if (result.acknowledged) acknowledged.add(batch[i].digest);
    }
    queue.remove(acknowledged);

    _log('ingest', {
      'received': summary.received,
      'processed': summary.processed,
      'duplicates': summary.duplicates,
      'failed': summary.failed,
      'queued_remaining': queue.length,
    });
  }

  Future<void> _sendHeartbeat() async {
    try {
      await _heartbeatApi.heartbeat();
    } catch (_) {
      // offline heartbeat is fine — queue persists
    }
  }

  void _log(String verb, Map<String, dynamic> data) {
    final line = {'at': DateTime.now().toIso8601String(), 'verb': verb, ...data};
    _logController.add(line);
  }
}

// ---------------------------------------------------------------------------
// Foreground task + WorkManager entry (runs headless as well as with UI)
// ---------------------------------------------------------------------------
@pragma('vm:entry-point')
Future<void> callbackDispatcher() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Workmanager().executeTask((task, inputData) async {
    await IngestQueue.instance.load();
    await AgentService.instance.cycle();
    return true;
  });
}

bool _agentActivated = false;

/// Called once a device code is stored. Idempotent: registers the WorkManager
/// watchdog, starts the foreground task, subscribes the SMS bridge and runs
/// one full cycle immediately.
Future<void> activateAgent() async {
  if (_agentActivated) return;
  _agentActivated = true;

  await Workmanager().initialize(callbackDispatcher);
  await Workmanager().registerPeriodicTask(
    'wakala_watchdog',
    'wakala_cycle',
    frequency: const Duration(minutes: 5),
    constraints: Constraints(networkType: NetworkType.connected),
    backoffPolicy: BackoffPolicy.exponential,
    existingWorkPolicy: ExistingPeriodicWorkPolicy.keep,
  );

  await FlutterForegroundTask.init(
    androidNotificationOptions: AndroidNotificationOptions(
      channelId: 'wakala_sms',
      channelName: 'MobiControl SMS monitor',
      channelDescription: 'Listens for mobile-money SMS and syncs to Wakala',
      channelImportance: NotificationChannelImportance.LOW,
      priority: NotificationPriority.LOW,
      isSticky: true,
    ),
    iosNotificationOptions: const IOSNotificationOptions(showNotification: false),
    foregroundTaskOptions: const ForegroundTaskOptions(interval: 5 * 60 * 1000),
  );
  FlutterForegroundTask.addTask(MyForegroundTask());

  SmsBridge.messages().listen((sms) => IngestQueue.instance.enqueue(sms));
  await SmsBridge.callNative('start');
  await AgentService.instance.cycle();
}

class MyForegroundTask extends ForegroundTaskHandler {
  @override
  void onRepeatEvent() async {
    await IngestQueue.instance.load();
    await AgentService.instance.cycle();
  }

  @override
  void onStart(DateTime timestamp, TaskStarter starter) async {
    await SmsBridge.callNative('start');
    super.onStart(timestamp, starter);
  }
}

// ---------------------------------------------------------------------------
// 7. UI
// ---------------------------------------------------------------------------
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await IngestQueue.instance.load();
  runApp(const WakalaApp());
}

class WakalaApp extends StatefulWidget {
  const WakalaApp({super.key});

  @override
  State<WakalaApp> createState() => _WakalaAppState();
}

class _WakalaAppState extends State<WakalaApp> {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'MobiControl',
      theme: WakalaTheme.build(),
      debugShowCheckedModeBanner: false,
      home: const PairingGate(),
    );
  }
}

/// Shows the device-code entry screen until a device code is stored.
class PairingGate extends StatefulWidget {
  const PairingGate({super.key});

  @override
  State<PairingGate> createState() => _PairingGateState();
}

class _PairingGateState extends State<PairingGate> {
  bool _checking = true;
  String? _code;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final code = await CodeStore.read();
    if (!mounted) return;
    if (code == null) {
      setState(() => _checking = false);
      return;
    }
    unawaited(activateAgent());
    setState(() {
      _checking = false;
      _code = code;
    });
  }

  void _onLinked() {
    unawaited(activateAgent());
    setState(() {
      _code = 'set'; // code store now populated
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_checking) return const Scaffold(body: Center(child: CircularProgressIndicator()));
    return _code == null
        ? Scaffold(body: Center(child: SingleChildScrollView(child: _CodeEntry(onLinked: _onLinked))))
        : const HomeShell();
  }
}

class _CodeEntry extends StatefulWidget {
  final VoidCallback onLinked;
  const _CodeEntry({required this.onLinked});

  @override
  State<_CodeEntry> createState() => _CodeEntryState();
}

class _CodeEntryState extends State<_CodeEntry> {
  final _controller = TextEditingController();
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final code = _controller.text.trim();
    if (code.isEmpty) {
      setState(() => _error = 'Enter the device code from the admin dashboard.');
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });

    await CodeStore.write(code);
    try {
      await AgentService.instance.cycle();
      if (!mounted) return;
      if (AgentService.instance.device != null) {
        widget.onLinked();
      }
    } on ApiException catch (e) {
      await CodeStore.clear();
      if (mounted) setState(() => _error = e.message);
    }
    if (mounted) setState(() => _busy = false);
  }

  @override
  Widget build(BuildContext context) {
    return ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 420),
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(WakalaSpacing.lg),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text('Wakala — MobiControl', style: Theme.of(context).textTheme.headlineSmall),
              const SizedBox(height: 4),
              Text(
                'Enter the device code shown by your administrator '
                'when the phone was registered.',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: WakalaColors.inkSoft),
              ),
              const SizedBox(height: WakalaSpacing.md),
              TextField(
                controller: _controller,
                obscureText: true,
                autocorrect: false,
                enableSuggestions: false,
                decoration: const InputDecoration(labelText: 'Device code'),
              ),
              if (_error != null) ...[
                const SizedBox(height: WakalaSpacing.sm),
                Text(_error!, style: const TextStyle(color: WakalaColors.danger)),
              ],
              const SizedBox(height: WakalaSpacing.md),
              FilledButton(
                onPressed: _busy ? null : _submit,
                child: _busy
                    ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: WakalaColors.white))
                    : const Text('Link this phone'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// HomeShell — sidebar + topbar, mirrors the web dashboard layout
// ---------------------------------------------------------------------------
class HomeShell extends StatefulWidget {
  const HomeShell({super.key});

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _index = 0;

  @override
  void initState() {
    super.initState();
    AgentService.instance.cycle();
  }

  @override
  Widget build(BuildContext context) {
    final screens = const [
      DashboardScreen(),
      WatchlistScreen(),
      IngestLogScreen(),
      SettingsScreen(),
    ];
    return Scaffold(
      body: Row(
        children: [
          _SideNav(
            current: _index,
            onSelected: (i) => setState(() => _index = i),
          ),
          Expanded(
            child: Column(
              children: [
                const _TopBar(),
                Expanded(child: screens[_index]),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SideNav extends StatelessWidget {
  final int current;
  final ValueChanged<int> onSelected;
  const _SideNav({required this.current, required this.onSelected});

  @override
  Widget build(BuildContext context) {
    const items = [Icons.space_dashboard_outlined, Icons.sms_outlined, Icons.list_alt_outlined, Icons.settings_outlined];
    const labels = ['Dashboard', 'Watchlist', 'Log', 'Settings'];

    return Container(
      width: 232,
      color: WakalaColors.coffee900,
      child: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.all(WakalaSpacing.md),
              child: Row(
                children: [
                  Container(
                    width: 34,
                    height: 34,
                    decoration: BoxDecoration(
                      color: WakalaColors.gold500,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.account_balance_wallet_outlined, color: WakalaColors.coffee900, size: 20),
                  ),
                  const SizedBox(width: WakalaSpacing.sm),
                  const Text(
                    'MobiControl',
                    style: TextStyle(fontFamily: 'Raleway', fontWeight: FontWeight.w800, color: WakalaColors.sand50),
                  ),
                ],
              ),
            ),
            const SizedBox(height: WakalaSpacing.md),
            for (var i = 0; i < items.length; i++)
              _NavItem(
                icon: items[i],
                label: labels[i],
                selected: i == current,
                onTap: () => onSelected(i),
              ),
            const Spacer(),
            const Padding(
              padding: EdgeInsets.all(WakalaSpacing.md),
              child: Text(
                'Syncs every heartbeat.\nServer: wakala.feedtanstore.com',
                style: TextStyle(fontSize: 11, color: WakalaColors.coffee300),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _NavItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback onTap;
  const _NavItem({required this.icon, required this.label, required this.selected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        decoration: BoxDecoration(
          color: selected ? WakalaColors.coffee800 : Colors.transparent,
          borderRadius: BorderRadius.circular(WakalaRadii.md),
        ),
        child: Row(
          children: [
            Icon(icon, size: 20, color: selected ? WakalaColors.gold500 : WakalaColors.coffee300),
            const SizedBox(width: WakalaSpacing.md),
            Text(
              label,
              style: TextStyle(
                fontFamily: 'Raleway',
                fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                color: selected ? WakalaColors.sand50 : WakalaColors.coffee300,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TopBar extends StatelessWidget {
  const _TopBar();

  @override
  Widget build(BuildContext context) {
    final device = AgentService.instance.device;
    return Container(
      height: 64,
      padding: const EdgeInsets.symmetric(horizontal: WakalaSpacing.lg),
      decoration: const BoxDecoration(color: WakalaColors.white, border: Border(bottom: BorderSide(color: WakalaColors.line))),
      child: Row(
        children: [
          Text(
            device?.branch != null ? '${device!.branch} · ${device.name}' : 'Dashboard',
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const Spacer(),
          if (device != null) ...[
            DeviceStatusTag(status: device.status),
            const SizedBox(width: WakalaSpacing.sm),
            for (final n in device.networks) NetworkBadge(code: n),
          ],
          const SizedBox(width: WakalaSpacing.sm),
          IconButton(
            onPressed: () => AgentService.instance.cycle(),
            icon: const Icon(Icons.refresh),
            tooltip: 'Sync now',
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Shared widgets
// ---------------------------------------------------------------------------
class DeviceStatusTag extends StatelessWidget {
  final DeviceStatus status;
  const DeviceStatusTag({super.key, required this.status});

  @override
  Widget build(BuildContext context) {
    final (Color bg, Color fg, String label) = switch (status) {
      DeviceStatus.active => (WakalaColors.acacia100, WakalaColors.success, 'Active'),
      DeviceStatus.offline => (WakalaColors.sand100, WakalaColors.inkSoft, 'Offline'),
      DeviceStatus.pending => (WakalaColors.gold100, WakalaColors.gold500, 'Pending'),
      DeviceStatus.suspended => (WakalaColors.danger100, WakalaColors.danger, 'Suspended'),
      DeviceStatus.blocked || DeviceStatus.revoked => (WakalaColors.danger100, WakalaColors.danger, status.name),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(WakalaRadii.pill)),
      child: Text(
        label,
        style: TextStyle(
          fontFamily: 'Raleway',
          fontWeight: FontWeight.w700,
          fontSize: 12,
          color: fg,
        ),
      ),
    );
  }
}

class NetworkBadge extends StatelessWidget {
  final String code;
  const NetworkBadge({super.key, required this.code});

  @override
  Widget build(BuildContext context) {
    final Color bg = switch (code.toUpperCase()) {
      'VODACOM' => const Color(0xFFDEE9F4),
      'AIRTEL' => const Color(0xFFFFE3E3),
      'TIGO' => const Color(0xFFE6F0E0),
      'HALOPESA' => const Color(0xFFE8E6F8),
      _ => WakalaColors.sand100,
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(WakalaRadii.pill)),
      child: Text(
        code,
        style: const TextStyle(fontFamily: 'Raleway', fontWeight: FontWeight.w700, fontSize: 11, color: WakalaColors.coffee700),
      ),
    );
  }
}

class StatCard extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color? tint;
  const StatCard({super.key, required this.label, required this.value, required this.icon, this.tint});

  @override
  Widget build(BuildContext context) {
    final color = tint ?? WakalaColors.terracotta600;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(WakalaSpacing.md),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(10)),
              child: Icon(icon, color: color, size: 20),
            ),
            const SizedBox(width: WakalaSpacing.md),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: Theme.of(context).textTheme.bodySmall),
                Text(value, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontSize: 22)),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Dashboard
// ---------------------------------------------------------------------------
class DashboardScreen extends StatelessWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return StreamBuilder<DeviceInfo?>(
      stream: AgentService.instance.deviceStream,
      initialData: AgentService.instance.device,
      builder: (context, snapshot) {
        final device = snapshot.data ?? AgentService.instance.device;
        if (device == null) {
          return const _CenteredMessage(
            icon: Icons.link_off,
            title: 'Not linked',
            subtitle: 'The device code is missing. Re-enter it in Settings.',
          );
        }

        final watchlist = AgentService.instance.watchlist;
        return ListView(
          padding: const EdgeInsets.all(WakalaSpacing.lg),
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Overview', style: Theme.of(context).textTheme.headlineSmall),
                      const SizedBox(height: 2),
                      Text(
                        '${device.agent ?? 'Device'} · ${device.network ?? '—'}',
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                    ],
                  ),
                ),
                DeviceStatusTag(status: device.status),
              ],
            ),
            const SizedBox(height: WakalaSpacing.md),
            Wrap(
              spacing: WakalaSpacing.md,
              runSpacing: WakalaSpacing.md,
              children: [
                SizedBox(
                  width: 220,
                  child: StatCard(label: 'Status', value: device.status.name, icon: Icons.business_center_outlined),
                ),
                SizedBox(
                  width: 220,
                  child: StatCard(
                    label: 'Tracked senders',
                    value: '${watchlist?.senders.length ?? 0}',
                    icon: Icons.sms_outlined,
                    tint: WakalaColors.gold500,
                  ),
                ),
                SizedBox(
                  width: 220,
                  child: StatCard(
                    label: 'Queued SMS',
                    value: '${IngestQueue.instance.length}',
                    icon: Icons.schedule_send_outlined,
                    tint: WakalaColors.acacia600,
                  ),
                ),
              ],
            ),
            const SizedBox(height: WakalaSpacing.lg),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(WakalaSpacing.md),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Device details', style: Theme.of(context).textTheme.titleMedium),
                    const SizedBox(height: WakalaSpacing.sm),
                    _DetailRow(label: 'ID', value: '${device.id}'),
                    _DetailRow(label: 'Networks', value: device.networks.join(', ')),
                    _DetailRow(label: 'Last sync', value: device.lastSyncAt ?? '—'),
                    _DetailRow(label: 'Last heartbeat', value: device.lastHeartbeatAt ?? '—'),
                    _DetailRow(label: 'Last SMS', value: device.lastSmsAt ?? '—'),
                    if (!device.canIngest)
                      Padding(
                        padding: const EdgeInsets.only(top: WakalaSpacing.sm),
                        child: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: WakalaColors.gold100,
                            borderRadius: BorderRadius.circular(WakalaRadii.sm),
                          ),
                          child: const Row(
                            children: [
                              Icon(Icons.hourglass_top, color: WakalaColors.gold500, size: 18),
                              SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  'This phone is not active yet. Ask your administrator to approve it '
                                  'and SMS upload will begin automatically.',
                                  style: TextStyle(fontFamily: 'Raleway', color: WakalaColors.inkSoft),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  const _DetailRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(width: 130, child: Text(label, style: Theme.of(context).textTheme.bodySmall)),
          Expanded(
            child: Text(value, style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600)),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Watchlist — senders the phone currently captures
// ---------------------------------------------------------------------------
class WatchlistScreen extends StatelessWidget {
  const WatchlistScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return StreamBuilder<DeviceInfo?>(
      stream: AgentService.instance.deviceStream,
      initialData: AgentService.instance.device,
      builder: (context, snapshot) {
        final watchlist = AgentService.instance.watchlist;
        if (watchlist == null) {
          return const _CenteredMessage(
            icon: Icons.cloud_download_outlined,
            title: 'Watchlist not fetched yet',
            subtitle: 'It loads during the next sync. Pull to refresh.',
          );
        }
        return ListView(
          padding: const EdgeInsets.all(WakalaSpacing.lg),
          children: [
            Text('Capture watchlist', style: Theme.of(context).textTheme.headlineSmall),
            const SizedBox(height: 4),
            Text(
              'SMS from these senders is forwarded to the server. Only networks assigned '
              'to this device appear here.',
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: WakalaSpacing.md),
            Wrap(
              spacing: WakalaSpacing.sm,
              runSpacing: WakalaSpacing.sm,
              children: [
                for (final rule in watchlist.senders)
                  Chip(
                    avatar: CircleAvatar(
                      backgroundColor: WakalaColors.terracotta100,
                      child: Text(
                        rule.keyword.substring(0, 1).toUpperCase(),
                        style: const TextStyle(color: WakalaColors.terracotta600, fontSize: 12),
                      ),
                    ),
                    label: Text('${rule.keyword} → ${rule.network}'),
                    side: const BorderSide(color: WakalaColors.line),
                    backgroundColor: WakalaColors.white,
                  ),
              ],
            ),
            const SizedBox(height: WakalaSpacing.md),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(WakalaSpacing.md),
                child: Text(
                  'Server time: ${watchlist.serverTime.toIso8601String()}\n'
                  'Max batch per upload: ${watchlist.maxBatch} messages',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}

// ---------------------------------------------------------------------------
// Ingest log — activity stream (matches the web app's /sms monitor feel)
// ---------------------------------------------------------------------------
class IngestLogScreen extends StatelessWidget {
  const IngestLogScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return StreamBuilder<Map<String, dynamic>>(
      stream: AgentService.instance.logStream,
      builder: (context, snapshot) {
        final entries = snapshot.data == null ? <Map<String, dynamic>>[] : [snapshot.data!];
        return ListView(
          padding: const EdgeInsets.all(WakalaSpacing.lg),
          children: [
            Text('Ingest & sync activity', style: Theme.of(context).textTheme.headlineSmall),
            const SizedBox(height: WakalaSpacing.md),
            if (entries.isEmpty)
              const _CenteredMessage(
                icon: Icons.inbox_outlined,
                title: 'Nothing yet',
                subtitle: 'Activity appears here once SMS are captured and uploaded.',
              )
            else
              for (final entry in entries)
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(WakalaSpacing.md),
                    child: Row(
                      children: [
                        Icon(
                          entry['verb'] == 'ingest' ? Icons.upload_outlined : Icons.sync,
                          color: WakalaColors.acacia600,
                          size: 20,
                        ),
                        const SizedBox(width: WakalaSpacing.md),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(entry['verb'].toString(), style: Theme.of(context).textTheme.titleMedium),
                              const SizedBox(height: 2),
                              Text(entry.toString(), maxLines: 3, style: Theme.of(context).textTheme.bodySmall),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
          ],
        );
      },
    );
  }
}

// ---------------------------------------------------------------------------
// Settings
// ---------------------------------------------------------------------------
class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(WakalaSpacing.lg),
      children: [
        Text('Settings', style: Theme.of(context).textTheme.headlineSmall),
        const SizedBox(height: WakalaSpacing.md),
        Card(
          child: Column(
            children: [
              ListTile(
                leading: const Icon(Icons.sms_outlined, color: WakalaColors.terracotta600),
                title: const Text('SMS listener'),
                subtitle: const Text('Foreground service that captures mobile-money SMS'),
                trailing: Switch(
                  value: true,
                  onChanged: (v) => SmsBridge.callNative(v ? 'start' : 'stop'),
                ),
              ),
              const Divider(height: 1, color: WakalaColors.line),
              ListTile(
                leading: const Icon(Icons.sync_outlined, color: WakalaColors.terracotta600),
                title: const Text('Sync now'),
                subtitle: const Text('Flush the queue and beat heart now'),
                onTap: () => AgentService.instance.cycle(),
              ),
              const Divider(height: 1, color: WakalaColors.line),
              ListTile(
                leading: const Icon(Icons.link_off, color: WakalaColors.danger),
                title: const Text('Unlink this phone'),
                subtitle: const Text('Clear the stored device code'),
                onTap: () async {
                  await IngestQueue.instance
                      .remove(IngestQueue.instance.items.map((e) => e.digest).toSet());
                  await CodeStore.clear();
                  if (context.mounted) {
                    Navigator.of(context).pushAndRemoveUntil(
                      MaterialPageRoute(builder: (_) => const PairingGate()),
                      (route) => false,
                    );
                  }
                },
              ),
            ],
          ),
        ),
        const SizedBox(height: WakalaSpacing.md),
        const Text(
          'MobiControl 1.0.0 — Wakala Feedtan Store\nhttps://wakala.feedtanstore.com/api/v1',
          style: TextStyle(fontFamily: 'Raleway', color: WakalaColors.inkSoft, fontSize: 12),
        ),
      ],
    );
  }
}

class _CenteredMessage extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  const _CenteredMessage({required this.icon, required this.title, required this.subtitle});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(WakalaSpacing.xl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 48, color: WakalaColors.inkSoft),
            const SizedBox(height: WakalaSpacing.md),
            Text(title, style: Theme.of(context).textTheme.titleLarge, textAlign: TextAlign.center),
            const SizedBox(height: 4),
            Text(subtitle, style: Theme.of(context).textTheme.bodySmall, textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }
}