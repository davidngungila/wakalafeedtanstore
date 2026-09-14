# MobiControl — Wakala Feedtan Store companion

Single-file Flutter app (`mobile/lib/main.dart`) that runs on Android handsets at
agent locations. It captures mobile-money SMS in the background, matches the sender
against a server-side watchlist, and uploads matched messages in batches to the
Wakala backend for automatic transaction recording.

## How it fits

Drop `lib/main.dart` into a new Flutter project (or the existing MobiControl
project). The file is self-contained — all models, services, and UI live in it.

### Dependencies (pubspec.yaml)

```yaml
dependencies:
  flutter_secure_storage: ^9.0.0        # device code (EncryptedSharedPreferences)
  shared_preferences: ^2.3.0
  flutter_foreground_task: ^8.5.0       # persistent foreground service
  workmanager: ^0.5.2                   # background watchdog + heartbeat
  device_info_plus: ^10.0.0             # model / androidVersion
  package_info_plus: ^8.0.0             # appVersion
  connectivity_plus: ^6.0.0             # network checks
  crypto: ^3.0.0                        # sha256 client-side dedupe hint
  http: ^1.2.0
```

Fonts — add `Raleway` (400, 500, 800) to `assets/fonts/` (the web dashboard uses
the same family). `WakalaTheme.build()` references it by name.

### Native bridge (platform channels)

| Channel                          | Direction | Purpose                             |
| -------------------------------- | --------- | ----------------------------------- |
| `wakala/sms` (MethodChannel)     | Dart → native | `start` / `stop` SMS receiver registration |
| `wakala/sms/events` (EventChannel) | native → Dart | stream of incoming `SmsMessage` objects |

The native SMS receiver (Kotlin/Java) is not included in this file — implement it
separately using Android's `SmsReceiver` BroadcastReceiver and forward messages
through the event channel.

---

## Authentication — device code only

The only credential is a **6-character device code** (e.g. `F7KQ2M`). There is no
token.

1. **Pairing** — on first launch the app shows the `_CodeEntry` screen. The
   operator enters the code. The app writes it to `flutter_secure_storage` (key
   `wakala.device_code`) and calls `POST /devices/bootstrap` to register.
2. **Ongoing requests** — every API call sends `X-Device-Code` as a header. The
   server looks up the device by code (case-insensitive).
3. **Code invalid / revoked** — the server returns `401`. `CodeStore.clear()` wipes
   the stored code and the app returns the operator to the pairing screen.
4. **Unlink** — the Settings tile "Unlink this phone" clears the queue and code,
   then navigates back to pairing.

---

## API endpoints used

| Method | Path              | Purpose                                    |
| ------ | ----------------- | ------------------------------------------ |
| POST   | `/devices/bootstrap` | Pair phone; returns device info           |
| GET    | `/devices/me`     | Current device profile + status            |
| GET    | `/sms/senders`    | Sender watchlist (keyword → network)       |
| POST   | `/sms/ingest`     | Upload batch of SMS (max 500 per request)  |
| POST   | `/heartbeat`      | Signal the device is alive                 |

Base URL: `https://wakala.feedtanstore.com/api/v1`

---

## Runtime architecture

```
┌──────────────────────────────────────────────────────┐
│  SmsReceiver (Android, native)                       │
│  BroadcastReceiver → MethodChannel → Dart stream    │
└───────────────────────┬──────────────────────────────┘
                        │ SmsBridge.messages()
                        ▼
┌───────────────────────┐      ┌─────────────────────┐
│  AgentService          │─────▶│  IngestQueue         │
│  - UI cycle()          │      │  - enqueue / flush   │
│  - WorkManager worker  │      │  - durable (sharedPrefs)
│  - foreground heartbeat│      │  - SHA-256 dedupe    │
└───────────────────────┘      └─────────┬───────────┘
                                         │ flush()
                                         ▼
                                  POST /sms/ingest
                                  (≤500 items/batch)
```

**Cycle loop** (runs on every heartbeat, UI resume, and WorkManager trigger):

1. `GET /devices/me` — refresh profile; 401 → wipe code, return to pairing.
2. Refresh watchlist if stale (TTL 5 min, cached in `wakala.watchlist_at`).
3. If device `canIngest` → flush queue (`POST /sms/ingest`).
4. `POST /heartbeat` with model/app metadata.

**Queue persistence**: `SharedPreferences` key `wakala.queue` stores the list of
`{sender, body, received_at}` objects. Items are removed only when the server
acknowledges `ok: true` or `duplicate: true`. Failed items are retried with
exponential backoff.

---

## UI screens

| Screen             | Purpose                                             |
| ------------------ | --------------------------------------------------- |
| `_CodeEntry`       | First-launch pairing (device code input)            |
| `HomeShell`        | Sidebar + topbar chrome                             |
| `DashboardScreen`  | Status tag, stat cards (transactions, networks, etc)|
| `WatchlistScreen`  | View captured sender rules                          |
| `IngestLogScreen`  | Log of recent uploads and queue status              |
| `SettingsScreen`   | Server info, sync trigger, unlink, clear queue      |

---

## Local storage keys

| Key                        | Storage       | Purpose                          |
| -------------------------- | ------------- | -------------------------------- |
| `wakala.device_code`       | secure        | Device code (EncryptedSharedPrefs)|
| `wakala.queue`             | sharedPrefs   | Durable SMS queue (JSON)         |
| `wakala.watchlist_at`      | sharedPrefs   | Timestamp of last watchlist fetch|

---

## Background jobs

The app uses two mechanisms to keep ingesting when the UI is dead:

1. **Foreground service** (`flutter_foreground_task`) — keeps the Dart isolate
   alive, runs `AgentService.cycle()` on a configurable interval.
2. **WorkManager** (`workmanager`) — periodic task (`15 min` Android minimum)
   acts as a watchdog: if the foreground service was killed by the OS, WorkManager
   restarts it and runs a single cycle.

### Required Android permissions

```xml
<uses-permission android:name="android.permission.RECEIVE_SMS"/>
<uses-permission android:name="android.permission.READ_SMS"/>
<uses-permission android:name="android.permission.FOREGROUND_SERVICE"/>
<uses-permission android:name="android.permission.RECEIVE_BOOT_COMPLETED"/>
```

Battery-optimisation exemption (Android Settings → Apps → MobiControl → Battery →
Unrestricted) is strongly recommended so Doze does not block SMS capture.

---

## Building

```bash
flutter pub get
flutter run          # debug
flutter build apk    # release APK
```

The release APK targets Android 12+ (API 31). Ensure the signing key is configured
in `android/app/build.gradle`.

---

## Server-side docs

The full ingestion protocol, data flow, and error matrix live in
`docs/mobile-sms-ingestion.md` at the project root. Refer there for payload
schemas, watchlist config, and the device lifecycle.
