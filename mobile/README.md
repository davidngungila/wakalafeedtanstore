# MobiControl — Wakala Feedtan Store companion app

Single-file Flutter application (`mobile/lib/main.dart`, ~1 544 lines) that runs on
Android handsets at agent locations. It captures mobile-money SMS in the
background, matches the sender against a server-side watchlist, queues matches
offline, and uploads them in batches to the Wakala backend for automatic
transaction recording.

---

## Table of contents

1. [Quick start](#1-quick-start)
2. [Project structure](#2-project-structure)
3. [Dependencies](#3-dependencies)
4. [Platform setup (Android)](#4-platform-setup-android)
5. [Design system](#5-design-system)
6. [Domain models](#6-domain-models)
7. [Authentication (device code)](#7-authentication-device-code)
8. [API client](#8-api-client)
9. [Server API reference](#9-server-api-reference)
10. [Offline queue & deduplication](#10-offline-queue--deduplication)
11. [Native SMS bridge](#11-native-sms-bridge)
12. [Agent orchestration](#12-agent-orchestration)
13. [Background execution](#13-background-execution)
14. [UI screens](#14-ui-screens)
15. [Local storage keys](#15-local-storage-keys)
16. [Error handling](#16-error-handling)
17. [Security](#17-security)
18. [Build & deploy](#18-build--deploy)
19. [Troubleshooting](#19-troubleshooting)
20. [Constants](#20-constants)

---

## 1. Quick start

```bash
# From the repo root
flutter create --project-name mobi_control mobile_tmp
cp mobile/lib/main.dart mobile_tmp/lib/main.dart
cd mobile_tmp

# Add dependencies (see §3) to pubspec.yaml, then:
flutter pub get

# Implement the native SMS listener (see §11), then:
flutter run          # debug on connected device
flutter build apk    # release APK
```

The file is fully self-contained — no other Dart files are required. All models,
services, API classes, and UI live in `main.dart`.

---

## 2. Project structure

```
mobile/
  lib/
    main.dart          ← the entire app (1 544 lines)
```

Internal section numbering inside `main.dart`:

| Section | Purpose |
| ------- | ------- |
| 1. Design Tokens | Colour palette, radii, spacing, theme builder |
| 2. Domain Models | DeviceInfo, SenderRule, SmsMessage, etc. |
| 3. Secure Storage + API Client | CodeStore, ApiClient, DeviceApi, SmsApi, HeartbeatApi |
| 4. Offline Queue | IngestQueue — durable, offline-first |
| 5. Native Bridge | SmsBridge — MethodChannel / EventChannel |
| 6. Orchestration | AgentService, activateAgent, WorkManager + foreground |
| 7. UI | All widgets and screens |

---

## 3. Dependencies

Add to `pubspec.yaml` under `dependencies`:

```yaml
dependencies:
  flutter:
    sdk: flutter
  flutter_secure_storage: ^9.0.0        # device code (EncryptedSharedPreferences)
  shared_preferences: ^2.3.0            # queue + watchlist cache
  flutter_foreground_task: ^8.5.0       # persistent foreground service
  workmanager: ^0.5.2                   # background watchdog / heartbeat
  device_info_plus: ^10.0.0             # model / androidVersion
  package_info_plus: ^8.0.0             # appVersion
  connectivity_plus: ^6.0.0             # network checks
  crypto: ^3.0.0                        # SHA-256 for client-side dedup hint
  http: ^1.2.0                          # HTTP client
```

### Fonts

The app uses **Raleway** (weights 400, 500, 800). Place these files under
`assets/fonts/` and declare them in `pubspec.yaml`:

```yaml
flutter:
  fonts:
    - family: Raleway
      fonts:
        - asset: assets/fonts/Raleway-400.ttf
        - asset: assets/fonts/Raleway-500.ttf
          weight: 500
        - asset: assets/fonts/Raleway-800.ttf
          weight: 800
```

`WakalaTheme.build()` sets `fontFamily: 'Raleway'` globally via the base
`ThemeData`.

---

## 4. Platform setup (Android)

### Minimum SDK

Android 12 (API 31) or higher.

### Permissions (`android/app/src/main/AndroidManifest.xml`)

```xml
<uses-permission android:name="android.permission.RECEIVE_SMS"/>
<uses-permission android:name="android.permission.READ_SMS"/>
<uses-permission android:name="android.permission.FOREGROUND_SERVICE"/>
<uses-permission android:name="android.permission.FOREGROUND_SERVICE_DATA_SYNC"/>
<uses-permission android:name="android.permission.RECEIVE_BOOT_COMPLETED"/>
<uses-permission android:name="android.permission.POST_NOTIFICATIONS"/>
```

### Notification channel

The foreground task creates a channel with id `wakala_sms` (name: *MobiControl
SMS monitor*, importance: LOW, sticky). Android requires this channel to exist
before the foreground service starts — `flutter_foreground_task` handles it
automatically when you call `FlutterForegroundTask.init(...)`.

### Battery optimisation

Whitelist the app under **Settings → Apps → MobiControl → Battery → Unrestricted**
so Doze mode does not block the foreground service and WorkManager tasks.

---

## 5. Design system

All tokens live in classes at the top of `main.dart` and mirror the web dashboard's
`resources/views/layouts/app.blade.php`.

### Colour palette (`WakalaColors`)

| Token | Hex | Usage |
| ----- | --- | ----- |
| `sand50` | `#FBF7EF` | Scaffold background |
| `sand100` | `#F4ECDC` | Offline status chip |
| `sand200` | `#E9DCC0` | — |
| `coffee900` | `#2A1B10` | Sidebar background |
| `coffee800` | `#3B2718` | Selected sidebar item |
| `coffee700` | `#4D3422` | — |
| `coffee500` | `#7A5C42` | — |
| `coffee300` | `#A98968` | Sidebar footer text, unselected icons |
| `terracotta600` | `#C2592B` | Primary colour (buttons, focused input, icons) |
| `terracotta500` | `#D06B3A` | — |
| `terracotta100` | `#F6E1D3` | Watchlist chip avatar bg |
| `acacia600` | `#5E6E3F` | Queue stat icon |
| `acacia500` | `#7A8450` | — |
| `acacia100` | `#E2E7D4` | Active status chip |
| `gold500` | `#D4A24C` | Secondary / accent, senders stat icon |
| `gold100` | `#F7E9CB` | Pending status chip |
| `ink` | `#241408` | Primary text |
| `inkSoft` | `#6B5A48` | Secondary / hint text |
| `line` | `#E4D7C2` | Borders, dividers |
| `white` | `#FFFFFF` | Cards, input fill, sidebar text |
| `danger` | `#B33A3A` | Suspended/blocked/revoked chip, unlink icon |
| `danger100` | `#F6DCDA` | Error chip bg |
| `success` | `#3F6B3F` | Active chip text |

### Radii (`WakalaRadii`)

| Token | Value |
| ----- | ----- |
| `sm` | 8 |
| `md` | 12 |
| `lg` | 16 |
| `pill` | 999 |

### Spacing (`WakalaSpacing`)

| Token | Value |
| ----- | ----- |
| `xs` | 4 |
| `sm` | 8 |
| `md` | 16 |
| `lg` | 24 |
| `xl` | 32 |

### Theme

`WakalaTheme.build()` returns a `ThemeData` with:
- Material 3 enabled, `fontFamily: 'Raleway'`
- AppBar: white bg, no elevation, left-aligned title, weight 800 / 18px
- Card: white, no elevation, `md` border radius, `line` border
- Input: white fill, `md` border radius, `line` border, `terracotta600` focused
- FilledButton: `terracotta600` bg, white text, `md` radius, weight 700

---

## 6. Domain models

### DeviceStatus (enum)

```dart
enum DeviceStatus { pending, active, suspended, blocked, revoked, offline }
```

| Value | Meaning |
| ----- | ------- |
| `pending` | Registered, awaiting admin approval. Auth works; ingest blocked. |
| `active` | Approved — the only state that may ingest SMS. |
| `suspended` | Temporarily disabled. Auth works; ingest blocked. |
| `blocked` | Permanently blocked. Returns 403. |
| `revoked` | Permanently revoked. Returns 403. |
| `offline` | Derived: `active` but no heartbeat for ≥ 10 min. Not a stored state. |

### DeviceInfo

```dart
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

  bool get canIngest => status == DeviceStatus.active;
}
```

Returned by `GET /devices/me` and `POST /devices/bootstrap`.

### SenderRule

```dart
class SenderRule {
  final String keyword;   // e.g. "MPESA"
  final String network;   // e.g. "VODACOM"
}
```

### SenderWatchlist

```dart
class SenderWatchlist {
  final DeviceInfo device;
  final List<SenderRule> senders;
  final int maxBatch;            // default 500
  final DateTime serverTime;

  bool matches(String sender);   // case-insensitive contains
}
```

Returned by `GET /sms/senders`.

### SmsMessage

```dart
class SmsMessage {
  final String sender;
  final String body;
  final String receivedAt;
  final String digest;           // SHA-256 of body (client-side dedupe hint)
}
```

Serialised as `{ sender, message, received_at }` in the ingest payload.

### IngestResult

```dart
class IngestResult {
  final bool ok;
  final int? smsId;
  final String? reference;
  final bool duplicate;
  final bool ignoredSender;
  final String? error;

  bool get acknowledged => ok || duplicate;
}
```

### IngestSummary

```dart
class IngestSummary {
  final int received;
  final int processed;
  final int duplicates;
  final int ignoredSenders;
  final int failed;
  final List<IngestResult> results;
}
```

---

## 7. Authentication (device code)

Authentication uses **only** a 6-character device code (e.g. `F7KQ2M`). There is
no token.

### Pairing flow

1. On first launch, `PairingGate` detects no stored code → shows `_CodeEntry`
   screen.
2. Operator enters the 6-char code from the admin dashboard.
3. `_CodeEntryState._submit()` writes the code to `CodeStore` (secure storage)
   and calls `AgentService.cycle()`.
4. `cycle()` calls `GET /devices/me`. On success the device profile is loaded
   and `PairingGate` transitions to `HomeShell`. On 401, `CodeStore.clear()` is
   called and the operator stays on the pairing screen.

### How requests are authenticated

`ApiClient.request()` sends exactly one auth header:

```
X-Device-Code: F7KQ2M
```

The server looks up the device by code (case-insensitive). No `Authorization`
header is sent.

### CodeStore

```dart
class CodeStore {
  static Future<String?> read();   // secure storage → memory cache
  static Future<void> write(String code);
  static Future<void> clear();
}
```

- Storage backend: `flutter_secure_storage` (EncryptedSharedPreferences on
  Android, Keychain on iOS).
- In-memory cache: static `Map<String, String>` for fast repeated reads within
  the same isolate.

---

## 8. API client

### ApiClient

```dart
class ApiClient {
  Future<Map<String, dynamic>> request(
    String method,       // 'GET' | 'POST'
    String path,         // e.g. '/devices/me'
    {Map<String, dynamic>? body}
  );
}
```

- Base URL constant: `kApiBase = 'https://wakala.feedtanstore.com/api/v1'`
- Every request includes headers: `Content-Type: application/json`,
  `Accept: application/json`, `X-Device-Code: <code>`.
- On HTTP 4xx, throws `ApiException` with `message`, `statusCode`, and optional
  `deviceStatus` (parsed from the response JSON).

### DeviceApi

| Method | Server endpoint | Returns |
| ------ | --------------- | ------- |
| `bootstrap(...)` | `POST /devices/bootstrap` | `DeviceInfo` |
| `me()` | `GET /devices/me` | `DeviceInfo` |

### SmsApi

| Method | Server endpoint | Returns |
| ------ | --------------- | ------- |
| `senders()` | `GET /sms/senders` | `SenderWatchlist` |
| `ingest(batch)` | `POST /sms/ingest` | `IngestSummary` |

### HeartbeatApi

| Method | Server endpoint | Returns |
| ------ | --------------- | ------- |
| `heartbeat(...)` | `POST /heartbeat` | void |

---

## 9. Server API reference

All endpoints require the `X-Device-Code` header. All responses are JSON.

### POST /devices/bootstrap

Pairs a phone for the first time.

**Request body:**

```json
{
  "device_uid": "1234567890123456",
  "model": "Redmi Note 12",
  "android_version": "14",
  "app_version": "1.0.0"
}
```

**Response 200:**

```json
{
  "device": {
    "id": 1,
    "name": "Redmi Note 12",
    "status": "pending",
    "agent": "Naila Beauty Shop",
    "network": "VODACOM",
    "networks": ["VODACOM"],
    "branch": "Dar es Salaam"
  }
}
```

### GET /devices/me

Returns the current device profile.

**Response 200:**

```json
{
  "device": {
    "id": 1,
    "name": "Redmi Note 12",
    "status": "active",
    "agent": "Naila Beauty Shop",
    "network": "VODACOM",
    "networks": ["VODACOM", "AIRTEL"],
    "branch": "Dar es Salaam",
    "last_sms_at": "2026-09-14T10:30:00.000000Z",
    "last_sync_at": "2026-09-14T10:31:00.000000Z",
    "last_heartbeat_at": "2026-09-14T10:32:00.000000Z"
  }
}
```

**Error responses:**

| Status | Body | Meaning |
| ------ | ---- | ------- |
| 401 | `{"message": "Missing credentials."}` | No `X-Device-Code` header |
| 401 | `{"message": "Unknown device. This device is not authorized."}` | Code does not match any device |
| 403 | `{"message": "Device is not allowed to access the system."}` | Device is blocked or revoked |
| 403 | `{"message": "Device is not active.", "device_status": "pending"}` | Device exists but is not yet approved |

### GET /sms/senders

Returns the sender watchlist scoped to the device's networks.

**Response 200:**

```json
{
  "device": { ... },
  "senders": [
    { "keyword": "MPESA", "network": "VODACOM" },
    { "keyword": "AIRTEL", "network": "AIRTEL" }
  ],
  "ingest": { "max_batch": 500 },
  "server_time": "2026-09-14T10:32:00.000000Z"
}
```

### POST /sms/ingest

Uploads a batch of captured SMS.

**Request body:**

```json
{
  "sms": [
    { "sender": "MPESA", "message": "Ksh 1,500 sent to John...", "received_at": "2026-09-14T09:00:00Z" },
    { "sender": "AIRTEL", "message": "You have received UGX 50,000...", "received_at": "2026-09-14T09:01:00Z" }
  ]
}
```

**Response 200:**

```json
{
  "summary": { "received": 2, "processed": 1, "duplicates": 0, "ignored_senders": 1, "failed": 0 },
  "results": [
    { "ok": true, "sms_id": 42, "reference": "TXN-001" },
    { "ok": false, "ignored_sender": true }
  ]
}
```

**Result fields:**

| Field | Meaning |
| ----- | ------- |
| `ok` | Successfully processed into a transaction |
| `sms_id` | Server-side SMS record id |
| `reference` | Transaction reference |
| `duplicate` | Body already seen (sha256 match) — safe to drop |
| `ignored_sender` | Sender not in the watchlist — safe to drop |
| `error` | Human-readable error (server-side parse failure, etc.) |

An item is considered **acknowledged** (removed from the queue) when `ok` or
`duplicate` is true.

### POST /heartbeat

Signals that the device is alive.

**Request body:**

```json
{
  "model": "Redmi Note 12",
  "android_version": "14",
  "app_version": "1.0.0"
}
```

**Response:** 200 (empty body).

---

## 10. Offline queue & deduplication

### IngestQueue

A singleton that holds captured SMS in memory and persists them to
`SharedPreferences` under key `wakala.queue`.

```dart
class IngestQueue {
  List<SmsMessage> get items;
  int get length;

  Future<void> load();                           // load from SharedPreferences
  Future<void> enqueue(SmsMessage sms);          // add (skip if digest exists)
  Future<void> remove(Set<String> digests);      // remove acknowledged items
}
```

**Persistence format** (JSON string in SharedPreferences):

```json
[
  { "sender": "MPESA", "body": "Ksh 1,500 sent...", "received_at": "2026-09-14T09:00:00Z" },
  ...
]
```

### Deduplication

**Client-side:** Every `SmsMessage` computes `digest = SHA-256(body)` at
construction. `enqueue()` skips items whose digest already exists in the queue.
This is a fast pre-filter; the server de-duplicates authoritatively.

**Server-side:** The server computes `sha256(message_body)` per device. If a
matching digest exists, it returns `{ ok: false, duplicate: true }` — the item
is safe to drop from the queue.

### Batch flushing

`AgentService.flushQueue()` takes up to `min(kMaxBatch, queue.length)` items
from the front of the queue, sends them in a single `POST /sms/ingest`, and
removes only those the server acknowledged. Failed items stay in the queue and
are retried on the next cycle.

---

## 11. Native SMS bridge

The Dart file communicates with a **Kotlin/Java Android BroadcastReceiver** via
two platform channels.

### Channels

| Channel | Type | Direction | Purpose |
| ------- | ---- | --------- | ------- |
| `wakala/sms` | MethodChannel | Dart → Native | `start` / `stop` receiver registration |
| `wakala/sms/events` | EventChannel | Native → Dart | Stream of incoming SMS messages |

### Dart side (SmsBridge)

```dart
class SmsBridge {
  // Start or stop the native SMS receiver
  static Future<Object?> callNative(String action);   // 'start' or 'stop'

  // Stream of incoming SMS from native code
  static Stream<SmsMessage> messages();
}
```

The event channel expects native code to push maps with keys `sender`,
`message`, and `received_at`.

### Native implementation sketch (Kotlin)

You must implement this yourself. The native receiver should:

1. Register a `BroadcastReceiver` for `android.provider.Telephony.SMS_RECEIVED`.
2. Extract `originatingAddress` (sender) and message body from the PDU.
3. Push the map `{ sender, message, received_at }` through the EventChannel
   sink.
4. Forward `start` / `stop` MethodChannel calls to register/unregister the
   receiver at runtime.

A minimal implementation:

```kotlin
class SmsReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        val messages = Telephony.Sms.Intents.getMessagesFromIntent(intent)
        for (msg in messages) {
            val map = hashMapOf(
                "sender" to msg.displayOriginatingAddress,
                "message" to msg.displayMessageBody,
                "received_at" to Date().toInstant().toString()
            )
            EventChannel.EventSink?.success(map)
        }
    }
}
```

Register it in `AndroidManifest.xml`:

```xml
<receiver android:name=".SmsReceiver" android:exported="true">
  <intent-filter>
    <action android:name="android.provider.Telephony.SMS_RECEIVED"/>
  </intent-filter>
</receiver>
```

---

## 12. Agent orchestration

### AgentService (singleton)

Central coordinator. Exposes reactive streams that the UI subscribes to.

```dart
class AgentService {
  static final AgentService instance = AgentService._();

  Stream<DeviceInfo?> get deviceStream;
  Stream<Map<String, dynamic>> get logStream;
  DeviceInfo? get device;
  SenderWatchlist? get watchlist;

  Future<void> cycle();       // idempotent — safe from UI + background
  Future<void> flushQueue();  // upload pending items
}
```

### cycle() — the main loop

Called from the UI (on tap, resume), from WorkManager, and from the foreground
task. It runs these steps in order:

1. **Read device code** — if none stored, return immediately.
2. **Refresh profile** — `GET /devices/me`. On success, pushes to
   `deviceStream`. On 401/403 (`codeInvalid` or `deviceBlocked`), clears the
   code and resets the device to null (returns to pairing screen).
3. **Refresh watchlist** — `GET /sms/senders` if stale (TTL: 5 minutes,
   checked via `wakala.watchlist_at` in SharedPreferences).
4. **Flush queue** — if device `canIngest` and queue is non-empty, upload a
   batch.
5. **Send heartbeat** — `POST /heartbeat` (failures are silently ignored).

### activateAgent()

Called once after a valid code is stored (first load or after pairing). It is
idempotent (`_agentActivated` guard). It:

1. Initializes WorkManager with `callbackDispatcher`.
2. Registers a periodic task `wakala_watchdog` (every 5 minutes, network
   required, exponential backoff).
3. Initializes the foreground service with channel `wakala_sms`, interval 5 min,
   sticky notification, LOW importance.
4. Subscribes to `SmsBridge.messages()` — each incoming SMS is enqueued.
5. Starts the native SMS receiver via `SmsBridge.callNative('start')`.
6. Runs one full `cycle()` immediately.

---

## 13. Background execution

Two mechanisms keep the app alive when the UI is killed:

### Foreground service (`flutter_foreground_task`)

- Notification channel: `wakala_sms` — "MobiControl SMS monitor"
- Priority: LOW, sticky (minimises user-visible interruption)
- Interval: 5 minutes (`ForegroundTaskOptions(interval: 5 * 60 * 1000)`)
- On each repeat: loads the queue from SharedPreferences and runs `cycle()`.

`MyForegroundTask` extends `ForegroundTaskHandler`:

```dart
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
```

### WorkManager (watchdog)

- Task name: `wakala_watchdog`, task: `wakala_cycle`
- Frequency: 5 minutes (Android enforces a minimum of 15 min for periodic tasks
  on modern Android; the actual interval may be longer)
- Constraint: network connected
- Backoff: exponential
- Entry point: `callbackDispatcher()` — a top-level function annotated with
  `@pragma('vm:entry-point')`.

```dart
@pragma('vm:entry-point')
Future<void> callbackDispatcher() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Workmanager().executeTask((task, inputData) async {
    await IngestQueue.instance.load();
    await AgentService.instance.cycle();
    return true;
  });
}
```

### Startup sequence

```
main()
  └─ WidgetsFlutterBinding.ensureInitialized()
  └─ IngestQueue.instance.load()
  └─ runApp(WakalaApp)
       └─ PairingGate
            ├─ _load(): CodeStore.read()
            │    ├─ code found → activateAgent() → HomeShell
            │    └─ code null  → _CodeEntry (pairing screen)
            └─ _onLinked(): activateAgent() → HomeShell
```

---

## 14. UI screens

### WakalaApp

Root widget. `MaterialApp` with `WakalaTheme.build()`, home is `PairingGate`.

### PairingGate

StatefulWidget that reads the stored code and either shows the pairing screen
(`_CodeEntry`) or the `HomeShell`.

```dart
class PairingGate extends StatefulWidget { ... }
class _PairingGateState extends State<PairingGate> {
  bool _checking = true;
  String? _code;   // non-null once a code is stored
}
```

### _CodeEntry

The device-code input screen shown on first launch or after unlinking.

- TextField (obscured), label "Device code"
- Helper: "Enter the device code shown by your administrator when the phone
  was registered."
- Button: "Link this phone"
- On submit: writes code → `CodeStore`, runs `cycle()`, transitions to
  `HomeShell` on success. On error: clears code, shows error message.

### HomeShell

Sidebar + topbar chrome. Navigation between four screens via `_SideNav`:

| Index | Icon | Label | Screen |
| ----- | ---- | ----- | ------ |
| 0 | `space_dashboard_outlined` | Dashboard | `DashboardScreen` |
| 1 | `sms_outlined` | Watchlist | `WatchlistScreen` |
| 2 | `list_alt_outlined` | Log | `IngestLogScreen` |
| 3 | `settings_outlined` | Settings | `SettingsScreen` |

The top bar shows: branch + device name, `DeviceStatusTag`, network badges, and
a refresh button (triggers `cycle()`).

### DashboardScreen

Subscribes to `deviceStream`. Shows:
- Overview heading with agent + network
- Stat cards: Status, Tracked senders, Queued SMS
- Device details card: ID, Networks, Last sync, Last heartbeat, Last SMS
- If `!canIngest`: gold info banner explaining the device is not yet approved

### WatchlistScreen

Displays the current sender watchlist as chips (`keyword → network`). Shows
server time and max batch size. Loads on the next sync if not yet fetched.

### IngestLogScreen

Streams `logStream` entries. Each entry shows the verb (`profile` or `ingest`)
and a JSON summary. Empty state: "Nothing yet — activity appears here once SMS
are captured and uploaded."

### SettingsScreen

| Tile | Action |
| ---- | ------ |
| SMS listener (Switch) | Calls `SmsBridge.callNative('start'`/`'stop')` |
| Sync now | Calls `AgentService.instance.cycle()` |
| Unlink this phone | Clears queue + code, navigates to `PairingGate` |

Footer: `MobiControl 1.0.0 — Wakala Feedtan Store`

### Shared widgets

| Widget | Purpose |
| ------ | ------- |
| `DeviceStatusTag` | Coloured pill badge for device status |
| `NetworkBadge` | Shows network code (e.g. "VODACOM") |
| `StatCard` | Dashboard stat card with icon, label, value |
| `_DetailRow` | Label + value row in device details |
| `_SideNav` / `_NavItem` | Sidebar navigation |
| `_TopBar` | Top bar with device info and refresh |
| `_CenteredMessage` | Empty-state placeholder with icon, title, subtitle |

---

## 15. Local storage keys

| Key | Storage | Purpose |
| --- | ------- | ------- |
| `wakala.device_code` | `flutter_secure_storage` | Device code (EncryptedSharedPreferences) |
| `wakala.queue` | `SharedPreferences` | Durable SMS queue (JSON array) |
| `wakala.watchlist_at` | `SharedPreferences` | Epoch millis of last successful watchlist fetch |

---

## 16. Error handling

### ApiException

```dart
class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final DeviceStatus? deviceStatus;

  bool get codeInvalid => statusCode != null && (statusCode == 400 || statusCode == 401);
  bool get deviceBlocked => statusCode == 403;
}
```

| Error | HTTP status | App behaviour |
| ----- | ----------- | ------------- |
| Network unreachable | — | Shows "Network unreachable. Retrying later." |
| Missing credentials | 401 | Config problem — show contact-admin message |
| Unknown device | 401 | Clear code → return to pairing screen |
| Device blocked/revoked | 403 | Clear code → return to pairing screen |
| Device not active | 403 | Keep paired; block uploads; show `Pending`/`Suspended` state |
| Validation error on ingest | 400/422 per item | Keep item, mark `failed`, do not infinitely retry |
| Network timeout | Exception | Keep queue, exponential backoff, heartbeat deferred |

### Cycle error handling

Inside `cycle()`:

```dart
try {
  _device = await _deviceApi.me();
} on ApiException catch (e) {
  if (e.codeInvalid || e.deviceBlocked) {
    await CodeStore.clear();
    _device = null;
    _statusController.add(null);
    return;
  }
  rethrow;
}
```

If `me()` returns 401 or 403, the stored code is wiped and the device is
nulled. The UI stream pushes null, causing `PairingGate` to re-show the
pairing screen.

Heartbeat failures are silently ignored (the queue persists locally).

---

## 17. Security

- The device code is stored in **`flutter_secure_storage`**
  (EncryptedSharedPreferences on Android, Keychain on iOS). Never in plain
  `SharedPreferences`.
- All API traffic goes over **HTTPS** only (`https://wakala.feedtanstore.com`).
- The app never logs message bodies or customer data.
- On revoke, the code stops working immediately. The app must handle the 403
  by clearing the local code and returning to pairing.
- SenderKeys: if an SMS matches no watchlist keyword, it is dropped locally
  (never uploaded).
- Client-side length validation: sender ≤ 30 chars, message ≤ 1 000 chars
  (server enforces the real limits).
- The device code must never be embedded in the APK — always operator-entered.

---

## 18. Build & deploy

```bash
flutter pub get
flutter run              # debug on connected device
flutter build apk        # release APK (unsigned debug)
flutter build apk --release --obfuscate --split-debug-info=build/debug-info
```

Release APK targets Android 12+ (API 31). Configure signing in
`android/app/build.gradle` (`signingConfigs`, `buildTypes`).

### Distributing

```bash
# Local install via ADB
adb install build/app/outputs/flutter-apk/app-release.apk
```

Or distribute the APK file directly to devices.

---

## 19. Troubleshooting

| Symptom | Likely cause | Fix |
| ------- | ------------ | --- |
| "Network unreachable. Retrying later." | No internet on the handset | Enable Wi-Fi or mobile data |
| Pairing screen reappears after linking | Code was rejected (401) | Re-enter the correct code; ask admin to verify status |
| "This phone is not active yet." banner | Device status is `pending` | Ask admin to approve the device on the dashboard |
| Status stays "Offline" | Heartbeat failing | Check network; ensure battery optimisation is disabled |
| Queue grows but never drains | Status not `active` | Only `active` devices can ingest; check admin approval |
| SMS not captured | Native receiver not registered | Ensure `RECEIVE_SMS` permission granted; check logcat for SmsBridge errors |
| App killed by OS after a few hours | Foreground service killed | Disable battery optimisation; enable auto-start on OEM ROMs |
| Notifications channel missing | Android 13+ notification permission | Grant "Notifications" permission in app settings |
| WorkManager not firing | Android 15+ background limits | Minimum periodic interval is 15 min; foreground service is more reliable |
| "Unknown device. This device is not authorized." | Code does not match any device | Ask admin to register the phone and re-send the code |
| 403 "Device is not allowed to access the system." | Device is blocked or revoked | Ask admin to reactivate or re-register the device |

---

## 20. Constants

| Constant | Value | Purpose |
| -------- | ----- | ------- |
| `kApiBase` | `https://wakala.feedtanstore.com/api/v1` | API base URL |
| `kMaxBatch` | `500` | Maximum SMS per ingest request |
| `kOfflineAfterMinutes` | `10` | Threshold for "offline" status display |
| WorkManager frequency | `5 minutes` | Watchdog interval (actual Android minimum is 15 min) |
| Foreground task interval | `5 minutes` | `ForegroundTaskOptions(interval: 5 * 60 * 1000)` |
| Watchlist TTL | `5 minutes` | Time before `senders()` is re-fetched |
| Notification channel | `wakala_sms` | Foreground service notification channel id |
| MethodChannel | `wakala/sms` | Native SMS start/stop commands |
| EventChannel | `wakala/sms/events` | Native → Dart SMS stream |
