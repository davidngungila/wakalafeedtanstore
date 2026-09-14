# MobiControl Mobile — Background SMS Ingestion to Wakala Feedtan Store

Complete implementation guide for building the Android app that listens for
mobile-money SMS in the background and forwards them into the Wakala Feedtan
Store system (`https://wakala.feedtanstore.com`) over the device API.

This document covers:

1.  Architecture overview
2.  Device & token lifecycle
3.  API authentication
4.  Endpoint reference (request / response / errors)
5.  Background SMS capture on Android (permissions, receivers, services, the
    Android 14+ rules, Doze / OEM battery traps)
6.  The Flutter single-file app structure (`mobile/lib/main.dart`)
7.  Data flow walkthrough (online + offline)
8.  Security checklist
9.  Error-handling matrix
10. Testing / QA checklist
11. Troubleshooting

---

## 1. Architecture overview

A phone runs the **MobiControl** app. The app:

- registers to **receive every incoming SMS** (via a native `BroadcastReceiver`),
- decides whether an SMS is worth forwarding by matching its **sender name**
  against the watchlist downloaded from `GET /api/v1/sms/senders`,
- queues the matching SMS locally (offline-safe),
- uploads them in batches of ≤ 500 to `POST /api/v1/sms/ingest`,
- parses the server response, keeps anything that failed for retry, and
  drops the rest,
- reports liveness with `POST /heartbeat` (the web dashboard marks a device
  **offline** when there is no heartbeat for **10 minutes**), and
- pairs/refreshes its identity with `POST /devices/bootstrap` and
  `GET /devices/me`.

```
  Mobile Network ──SMS──▶ SIM / Telephony Stack
                                │
                                ▼
                    Native Android SmsReceiver
                    (BroadcastReceiver, always-on)
                                │   filter: sender ∈ watchlist
                                ▼
                    Local durable queue (SQLite / shared_prefs)
                                │
                                ▼
                    Flutter AgentService (background task)
                                │  batch ≤ 500
                                ▼
         WakeLock-safe HTTPS POST https://wakala.feedtanstore.com/api/v1
                                │
                ┌───────────────┴───────────────┐
                ▼                               ▼
        SmsApi.ingest()                  HeartbeatApi()
        creates transactions           keeps device "online"
                │
                ▼
        Wakala web dashboard (Transactions, SMS monitor)
```

The server is **pull-free**: it can never push to the phone, so the phone owns
all upload decisions. The server de-duplicates by `sha256(message_body)` **per
device**, which makes "upload again later" safe.

---

## 2. Device & token lifecycle

Every phone is a **Device** managed by an administrator on the web dashboard.

| Stage            | Meaning                                                                    | Behaviour on the API                                      |
| ---------------- | -------------------------------------------------------------------------- | --------------------------------------------------------- |
| `pending`        | Registered, token generated, not yet approved                              | Auth works; `ingest` → `403 Device is not active`         |
| `active`         | Approved. This is the only state that may ingest SMS                        | All endpoints work                                        |
| `suspended`      | Temporarily disabled                                                        | Auth works; `ingest` → `403 Device is not active`         |
| `blocked`        | Permanently blocked                                                         | Auth → `403 Device is not allowed to access the system`   |
| `revoked`        | Permanently revoked, token invalidated                                      | Auth → `403 Device is not allowed to access the system`   |
| `offline`        | Not a stored state — derived when `active` but no heartbeat for 10 min      | Heartbeat overdue → dashboard shows "offline"             |

**Onboarding flow**

1. On the web app, an admin registers the phone and receives the API token
   (shown **once** — `dv_…`). The token is hashed on the server
   (`api_token_hash`); the plain token exists only on the phone.
2. On the phone, the operator enters the same token.
3. The app calls `POST /devices/bootstrap` to pair and report handset info.
4. The admin approves the device on the dashboard → `active`.
5. The app starts ingesting.

The app must handle the `pending` state gracefully: poll `GET /devices/me`
(and/or heartbeat) until `status == active`, and only then start sending SMS.

---

## 3. API authentication

Every endpoint in `/api/v1` requires a bearer token:

```
Authorization: Bearer dv_<48-random-chars>
Accept: application/json
Content-Type: application/json
```

The server compares `sha256(token)` against the device `api_token_hash`.

Common auth errors:

| Code | Body                                                              | Meaning                                             |
| ---- | ----------------------------------------------------------------- | --------------------------------------------------- |
| 401  | `{"message":"Missing device API token."}`                         | No `Authorization` header present                    |
| 401  | `{"message":"Unknown device. This device is not authorized."}`    | Token does not match any device                      |
| 403  | `{"message":"Device is not allowed to access the system."}`       | Device is `blocked` or `revoked`                     |

Token storage on the phone MUST be secure (see §8).

---

## 4. Endpoint reference

Base URL: `https://wakala.feedtanstore.com/api/v1`

### 4.1 `POST /devices/bootstrap` — pair the phone

Pairs the handset with its server record and refreshes reported metadata.

Request body:

```json
{
  "device_uid": "G0A3F9B2...",
  "model": "SM-A156",
  "android_version": "14",
  "app_version": "1.0.1"
}
```

| Field             | Type   | Rule                      |
| ----------------- | ------ | ------------------------- |
| `device_uid`      | string | required, max 80          |
| `model`           | string | optional, max 120         |
| `android_version` | string | optional, max 30          |
| `app_version`     | string | optional, max 30          |

Response `200`:

```json
{
  "device": {
    "id": 12,
    "name": "Samsung A15",
    "status": "pending",
    "agent": "Kilimani Cash Point",
    "network": "VODACOM",
    "networks": ["VODACOM", "AIRTEL"],
    "branch": "Moshi"
  },
  "message": "Device linked. Awaiting approval or activation."
}
```

Notes:

- `networks[]` is the list of mobile-money networks this phone is allowed to
  capture (a device may serve **more than one** network).
- `status` decides whether ingesting is allowed yet.

### 4.2 `GET /devices/me` — current profile

Refreshes the local copy of the device record (useful to detect approval).

Response `200`:

```json
{
  "device": {
    "id": 12,
    "name": "Samsung A15",
    "status": "active",
    "agent": "Kilimani Cash Point",
    "network": "VODACOM",
    "networks": ["VODACOM", "AIRTEL"],
    "branch": "Moshi",
    "last_sms_at": "2026-09-14T10:30:00+03:00",
    "last_sync_at": "2026-09-14T10:35:00+03:00",
    "last_heartbeat_at": "2026-09-14T10:35:00+03:00"
  }
}
```

### 4.3 `GET /sms/senders` — capture watchlist

Returns the **sender keywords** the phone must capture, mapped to the network
they belong to. Only senders of the networks assigned to *this* device are
returned.

Response `200`:

```json
{
  "device": {
    "id": 12,
    "name": "Samsung A15",
    "status": "active",
    "network": "VODACOM",
    "networks": ["VODACOM", "AIRTEL"]
  },
  "senders": [
    { "keyword": "MPESA",     "network": "VODACOM" },
    { "keyword": "VODACOM",   "network": "VODACOM" },
    { "keyword": "AIRTEL",    "network": "AIRTEL" },
    { "keyword": "Airtel Money", "network": "AIRTEL" }
  ],
  "ingest": { "max_batch": 500 },
  "server_time": "2026-09-14T10:35:00+03:00"
}
```

The app caches this watchlist and re-fetches it periodically (e.g. after every
successful heartbeat) so admin changes propagate.

> **Matching rule:** compare the SMS `sender` (the "from" label shown by the
> phone, e.g. `MPESA`, `AIRTEL`) **case-insensitively, substring-wise** — the
> server uses `stripos`. An SMS whose sender matches **any** keyword is
> forwarded; the server re-identifies the exact network itself, so the phone
> does not need to assign a network.

### 4.4 `POST /sms/ingest` — upload captured SMS

The core call. Sends up to **500** messages per request.

Request body:

```json
{
  "sms": [
    {
      "sender": "MPESA",
      "message": "P98765 confirmed. You have received TZS 100,000.00 from JUMA ATHUMANI 0712345678 on 14/9/2026 at 10:30.",
      "received_at": "2026-09-14 10:30:00"
    }
  ]
}
```

| Field          | Type   | Rule                                   |
| -------------- | ------ | -------------------------------------- |
| `sender`       | string | required, max 30                       |
| `message`      | string | required, the raw SMS body             |
| `received_at`  | string | optional ISO-style / `Y-m-d H:i:s` date |

Response `200`:

```json
{
  "summary": {
    "received": 3,
    "processed": 2,
    "duplicates": 1,
    "ignored_senders": 0,
    "failed": 0
  },
  "results": [
    {
      "ok": true,
      "sms_id": 452,
      "status": "processed",
      "reference": "P98765",
      "type": "deposit",
      "amount": 100000.0,
      "network": "VODACOM",
      "transaction_reference": "TXN-2026-09-14-0001"
    },
    {
      "ok": true,
      "sms_id": 453,
      "status": "processed",
      "reference": "P98766",
      "type": "withdrawal",
      "amount": 5000.0,
      "network": "VODACOM",
      "transaction_reference": "TXN-2026-09-14-0002"
    },
    {
      "ok": false,
      "duplicate": true,
      "reference": "P98766",
      "error": "Duplicate SMS ignored."
    }
  ]
}
```

**Per-message result shape:**

| Outcome               | Flags                                                              | Meaning                                   |
| --------------------- | ------------------------------------------------------------------ | ----------------------------------------- |
| Processed             | `ok: true`                                                         | Created a transaction                     |
| Duplicate             | `ok:false, duplicate:true`                                          | Same body already ingested → safe to drop |
| Ignored sender        | `ok:false, ignored_sender:true`                                     | Sender not mapped to a known network      |
| Parse failed          | `ok:false` (no special flag)                                        | Sender known but SMS not a financial msg  |

**Business rules the phone must respect**

- Only an **`active`** device may ingest. Otherwise:

  ```
  403
  {"message":"Device is not active. Approved devices only.","device_status":"pending"}
  ```

- Do **not** resend items the server already accepted (`ok:true`) or flagged as
  `duplicate:true`. Only retry `failed` items (and optionally `ignored_sender`
  once, in case config grew).
- Requests are capped at `max_batch` (500) — chunk larger queues.

**Server-side processing** (what happens per accepted SMS): the server computes
`sha256(message_body)`, checks the per-device duplicate index, identifies the
network from the sender, parses the SMS template, creates a `Transaction`
(status `completed`), adjusts float/cash balances, links the SMS to the
transaction, and returns the `transaction_reference`. All inside a transaction.

### 4.5 `POST /heartbeat` — liveness + metadata report

Called on a schedule (every ~5 minutes — well under the 10-minute offline
threshold) while the app is alive.

Request body (all optional):

```json
{
  "device_uid": "G0A3F9B2...",
  "model": "SM-A156",
  "android_version": "14",
  "app_version": "1.0.1"
}
```

Response `200`:

```json
{ "ok": true, "status": "active", "server_time": "2026-09-14T10:35:00+03:00" }
```

The server stamps `last_heartbeat_at`, `last_sync_at` and `last_ip`, and adopts
any changed handset metadata. A gap of more than **`sms.offline_after_minutes`
(= 10)** marks the device **offline** on the dashboard.

---

## 5. Background SMS capture on Android

Android does not let a random app read every SMS silently in the background.
Getting reliable, always-on capture is the hardest part of this feature. This
section is the complete recipe in order of reliability.

### 5.1 Choose your capture path (recommended first)

| Path                              | Reliability | Effort           | Notes                                                   |
| --------------------------------- | ----------- | ---------------- | ------------------------------------------------------- |
| **`SMS_RECEIVED` BroadcastReceiver + foreground service** | High on most devices | Medium | Standard, no default-SMS-app requirement on most OS versions. |
| Default SMS app (`RoleManager`)   | Highest     | High             | Grants `SMS_DELIVER`; intrusive — replaces the user's SMS app. Use only if necessary. |
| Accessibility service             | High        | High             | Reads Notification bar / content; needs accessibility ON. Fallback only. |

Recommended: **BroadcastReceiver + persistent foreground service + WorkManager
retry**. Below is that recipe.

### 5.2 Permissions (`AndroidManifest.xml`)

```xml
<uses-permission android:name="android.permission.RECEIVE_SMS" />
<uses-permission android:name="android.permission.READ_SMS" />
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.WAKE_LOCK" />
<uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
<uses-permission android:name="android.permission.FOREGROUND_SERVICE" />
<uses-permission android:name="android.permission.FOREGROUND_SERVICE_DATA_SYNC" />
<uses-permission android:name="android.permission.RECEIVE_BOOT_COMPLETED" />
<uses-permission android:name="android.permission.REQUEST_IGNORE_BATTERY_OPTIMIZATIONS" />
<uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
<uses-permission android:name="android.permission.SCHEDULE_EXACT_ALARM" />
```

Runtime permissions to request from the user:

- `POST_NOTIFICATIONS` (Android 13+ — required or the foreground-service
  notification is hidden and the system may kill the service).
- `RECEIVE_SMS` (requested at first launch; this is the critical one).
- `READ_SMS` (optional; helps backfill missed messages).

Battery-optimization exemption should be requested after the service is
running (see §5.6).

> **Android 4.4+ note:** non-default SMS apps can **receive** the
> `SMS_RECEIVED` broadcast but cannot write to the SMS provider
> (`Telephony.Sms`). MobiControl does **not** write to the provider — it only
> observes and uploads — so this is fine. Do not remove user messages from the
> inbox; that is what breaks with non-default apps.

### 5.3 Manifest receivers & service

```xml
<receiver
    android:name=".SmsReceiver"
    android:exported="false"
    android:permission="android.permission.BROADCAST_SMS">
    <intent-filter>
        <action android:name="android.provider.Telephony.SMS_RECEIVED" />
    </intent-filter>
</receiver>

<receiver android:name=".BootReceiver" android:exported="false">
    <intent-filter>
        <action android:name="android.intent.action.BOOT_COMPLETED" />
        <action android:name="android.intent.action.MY_PACKAGE_REPLACED" />
    </intent-filter>
</receiver>

<service
    android:name=".SmsForegroundService"
    android:exported="false"
    android:foregroundServiceType="dataSync" />
```

- `SmsReceiver` → on each SMS, wake the queue (see 5.4).
- `BootReceiver` → restart the foreground service after reboot / app update.
- `SmsForegroundService` → keeps the process alive and holds a WakeLock while
  uploading.

### 5.4 `SmsReceiver` — capture + hand-off to Flutter

Kotlin sketch (the upload itself lives in Dart; this receiver only hands raw
SMS over the platform channel / queue):

```kotlin
class SmsReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        val messages = Telephony.Sms.Intents.getMessagesFromIntent(intent)
        messages.forEachIndexed { index, message ->
            val sender = message.displayOriginatingAddress ?: ""
            val body = message.messageBody ?: ""
            val receivedAt = Date(message.timestampMillis).run {
                SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).format(this)
            }
            // Hand to Flutter (EventChannel) and/or persist to the local queue.
            SmsBridge.sink.add(SmsItem(sender, body, receivedAt))
        }
        // Trigger an immediate flush attempt.
        SmsServiceKick.request(context)
    }
}
```

> **Do the heavy lifting in Dart.** Flutter background Isolates / engines on
> Android are limited; the recommended split is:
>
> - **Native (thin):** capture raw SMS → push into a durable local queue
>   (SQLite) → wake the engine.
> - **Dart (single-file app):** owns matching, batch building, upload, retry,
>   watchlist refresh, heartbeat, and the UI (see §6).

### 5.5 Foreground service + WorkManager

Two layers keep the pipeline alive:

1. **Foreground service** (`dataSync`) with a low-priority notification
   ("MobiControl is monitoring mobile-money SMS"). Launched on login, on
   `BOOT_COMPLETED`, and on `MY_PACKAGE_REPLACED`.
2. **WorkManager** periodic worker — a safe "watchdog" that restarts the
   foreground service, flushes the queue, refreshs the watchlist, and sends the
   heartbeat, even if the service was killed.

```kotlin
Constraints(
    setRequiredNetworkType(NetworkType.CONNECTED),
    setRequiresBatteryNotLow(false)
)
PeriodicWorkRequestBuilder<MobiWatchdog>(5, TimeUnit.MINUTES)
    .setConstraints(Constraints.Builder()...build())
    .setBackoffCriteria(BackoffPolicy.EXPONENTIAL, 10, TimeUnit.MINUTES)
    .build()
```

This 5-minute cadence guarantees the dashboard's 10-minute offline rule is
never exceeded while the phone has network.

### 5.6 Android 13 / 14 rules (must-follow)

- **Android 13+:** `POST_NOTIFICATIONS` runtime permission controls
  foreground-service notifications. If denied, the service is visible to Android
  as a background restriction risk and can be killed. Request it, and fall back
  gracefully (offer the "allow notifications" Settings screen).
- **Android 14 (API 34):** a `dataSync` foreground service **must** declare
  `FOREGROUND_SERVICE_DATA_SYNC` and may only be started from allowed contexts
  (`BOOT_COMPLETED`, user action, etc. — *not* from random background events).
  The WorkManager watchdog launches it within permitted contexts.
- **Exact alarms** (`SCHEDULE_EXACT_ALARM`) are denied by default on
  Android 12+; prefer WorkManager, which does not need them.
- Do **not** try to hold the process alive "forever" — respect
  `START_NOT_STICKY`/`START_STICKY` and rely on WorkManager for resurrection.

### 5.7 Doze, App Standby & OEM battery savers

The realistic reasons phones go "offline":

| Trap                     | Symptom                          | Mitigation                                                         |
| ------------------------ | -------------------------------- | ------------------------------------------------------------------ |
| Doze (Android 6+)        | heartbeat stops when screen off  | Foreground service + partial WakeLock during upload; request battery-exemption. |
| App Standby buckets      | throttled network                | `REQUEST_IGNORE_BATTERY_OPTIMIZATIONS` dialog; keep queue persistent so nothing is lost. |
| Manufacturer auto-start  | app never starts on boot         | Guide the operator to enable Auto-start on Xiaomi, Oppo, Vivo, OnePlus (and "App power management", "App battery saver" on Samsung, Huawei). |
| Aggressive memory killer | service killed                   | WorkManager watchdog restarts; persistent queue means zero data loss. |
| Notification permission off | service hidden → killed         | Re-prompt `POST_NOTIFICATIONS`; instruct via Settings.             |

Export a simple **self-test** in the app (see §10) so an agent can verify
"messages received → uploaded" without the dashboard.

---

## 6. Flutter single-file app structure

Everything mobile lives in **one file**: `mobile/lib/main.dart`. It mirrors
the Wakala design system exactly (Raleway type, sand/coffee/terracotta/acacia
palette, stat cards, tags, chips, sidebar nav) and is organised in the same
order as this system's sections.

### 6.1 The file layout (top → bottom)

```
main.dart
│
├── 1  Design tokens            WakalaColors, WakalaTheme, spacing, radii, shadows
│
├── 2  Domain models            DeviceInfo, SenderRule, IngestItem,
│                               IngestResult, IngestSummary
│
├── 3  Secure storage + API     ApiClient (Dio-style wrapper over http),
│                               DeviceApi, SmsApi, HeartbeatApi
│
├── 4  Offline queue            IngestQueue (persistent, SQLite/shared_prefs),
│                               batching (≤ 500), retry policy
│
├── 5  Native bridge            SmsBridge (MethodChannel start/stop listener,
│                               EventChannel incoming SMS stream)
│
├── 6  Orchestration            AgentService (bootstrap → me → cycle:
│                               refresh watchlist → flush queue → heartbeat)
│
└── 7  UI                       WakalaApp, TokenGate, HomeShell,
                                DashboardScreen, WatchlistScreen,
                                IngestLogScreen, SettingsScreen, widgets
```

### 6.2 Supported packages (add to `pubspec.yaml`)

```yaml
dependencies:
  flutter:
    sdk: flutter
  http: ^1.2.0
  shared_preferences: ^2.3.0
  flutter_foreground_task: ^8.5.0     # persistent dataSync foreground service
  workmanager: ^0.5.2                 # watchdog / heartbeat / retry
  device_info_plus: ^10.0.0           # androidVersion / model
  package_info_plus: ^8.0.0           # appVersion
  connectivity_plus: ^6.0.0           # network checks
  crypto: ^3.0.0                      # sha256 of body (client-side hint)
  flutter_secure_storage: ^9.0.0      # device token (EncryptedSharedPreferences)
```

Fonts (Raleway — the exact family the web app uses):

```yaml
flutter:
  fonts:
    - family: Raleway
      fonts:
        - asset: assets/fonts/Raleway-400.ttf
        - asset: assets/fonts/Raleway-500.ttf
        - asset: assets/fonts/Raleway-700.ttf
        - asset: assets/fonts/Raleway-800.ttf
        - asset: assets/fonts/Raleway-900.ttf
```

Android minimum config:

```kotlin
// android/app/src/main/kotlin/.../MainActivity.kt
// register FlutterForegroundTask.initCommunicationPort() + process
// separately for any isolate, and initialise widgetBinding from background.
```

### 6.3 How the pieces talk

- **SmsBridge** exposes `start()` / `stop()` (MethodChannel → native receiver
  registration) and a Dart stream that yields captured SMS.
- **AgentService** is a `CallbackDispatcher` for WorkManager. It runs even when
  the UI is dead: reload the token, flush `IngestQueue`, refresh the watchlist,
  and heartbeat.
- **IngestQueue** appends every captured SMS, then flushes: build chunks of
  `max_batch` (500), call `SmsApi.ingest`, and remove only `ok:true` /
  `duplicate:true` items. Failed items stay queued (with an attempt counter and
  exponential backoff).

### 6.4 Matching + dedupe discipline

- Match: `sender.toLowerCase().contains(keyword.toLowerCase())`.
- Keep a local bloom/set of `sha256(message)` per device to skip obvious
  replays before uploading (the server still de-dupes authoritatively).
- Never drop an item on network failure — only on server acknowledgement.

---

## 7. Data flow walkthrough

### 7.1 Happy path

1. SMS arrives → native `SmsReceiver` fires.
2. Sender matches watchlist (`MPESA` ⊂ `mpesa`, device serves VODACOM) → enqueue.
3. `AgentService` notices the queue is non-empty → builds a batch → `POST
   /sms/ingest` with the bearer token.
4. Server returns `results[i].ok:true` with `transaction_reference` → item
   removed from the queue.
5. The web dashboard transaction list and the phone's **Ingest log** both show
   the new entry.

### 7.2 Offline path

1. No network → upload fails (HTTP error / timeout).
2. Items remain in the queue; a WorkManager watchdog keeps retrying with
   backoff while `connectivity_plus` reports connectivity.
3. When connectivity returns, the queue flushes; the server de-dupes
   (`duplicate:true`) anything already processed, so nothing double-counts.
4. Meanwhile the heartbeat is deferred → if ≥ 10 min offline, the dashboard
   shows the device **offline**. Admins see this and investigate (often an OEM
   battery saver, see §5.7).

### 7.3 New device (pending) path

1. Operator enters the token → `bootstrap` returns `status: pending` and a
   message "Awaiting approval".
2. UI shows a gold `Pending` tag; `AgentService` polls `me` + heartbeat but
   does **not** upload SMS.
3. Admin approves → next poll returns `active` → app switches to green `Active`
   and begins uploads.

---

## 8. Security checklist

- Store the device token in **`flutter_secure_storage`** (EncryptedSharedPreferences /
  Keystore). Never in plain `SharedPreferences`, logs, or analytics.
- Talk HTTPS only (`https://wakala.feedtanstore.com`); add network-security-config
  to reject cleartext.
- Never log message bodies or customer data in the app's logcat.
- On `revoke`, the server nulls the hash and the token stops working
  immediately — the app must handle `403` by clearing the local token and
  returning the operator to the token screen.
- Keep the SenderKeys fluent: if an SMS matches **no** watchlist keyword, drop
  it locally (don't upload unknown senders).
- Validate lengths client-side (`sender ≤ 30`, etc.) to fail fast, but rely on
  the server for correctness.
- Do not embed tokens in the APK; always operator-entered.

---

## 9. Error-handling matrix

| Scenario                                    | HTTP / signal            | App action                                                         |
| ------------------------------------------- | ------------------------ | ------------------------------------------------------------------ |
| No token stored                             | —                        | Show token entry screen                                            |
| `401 Missing device API token.`             | 401                      | Server config problem; show contact-admin message                  |
| `401 Unknown device. This device is not authorized.` | 401              | Token invalid/regenerated → clear token → token entry screen       |
| `403 Device is not allowed…`                | 403                      | Device blocked/revoked → clear token → token screen (+ hint)       |
| `403 Device is not active.`                 | 403 (+ `device_status`)  | Stay paired, block uploads, show `Pending`/`Suspended` state       |
| `4xx` validation on ingest                  | 400/422 per item         | Keep item, mark `failed`, do not infinitely retry (max attempts)   |
| Network timeout / connection                | Exception                | Keep queue, exponential backoff, heartbeat deferred                |
| `results[i].ok:false, duplicate:true`       | 200                      | Drop item (already known)                                          |
| `results[i].ok:false, ignored_sender:true`  | 200                      | Drop item, log; re-check watchlist freshness (config may have grown) |
| Server 5xx / 429                            | 5xx                      | Backoff, never clear queue                                         |

Batch safety rule: **an item is removed only when the server acknowledges it**
(`ok:true` or `duplicate:true`). Everything else is retried.

---

## 10. Testing / QA checklist

- [ ] Fresh install → token entry → bootstrap shows `pending` → no upload until approved.
- [ ] Approval on dashboard → app flips to `Active` (≤ next heartbeat) → first SMS uploads and appears on `/sms` and `/transactions`.
- [ ] Send 600+ SMS in a burst → chunks of ≤ 500 are sent, all recorded, no loss.
- [ ] Kill the app (swipe) → SMS still captured & uploaded via WorkManager watchdog.
- [ ] Reboot → `BootReceiver` restarts service; device returns online.
- [ ] Turn off Wi-Fi/data → queue grows; re-enable → drains with **no duplicates** created.
- [ ] Resend the same SMS body → `duplicate` result, transaction count unchanged.
- [ ] Unknown sender (e.g. bank OTP) → captured and locally dropped (never uploaded unless in watchlist).
- [ ] Revoke device → next API call returns 403 → token cleared, token screen shown.
- [ ] Battery saver / Doze after 30 min idle → device still heartbeat within 10 min of going offline? (Use the visual **offline** check.) Then run the OEM auto-start fix.
- [ ] A device serving 2 networks (VODACOM + AIRTEL) captures MPESA **and** AIRTEL senders and creates transactions on both networks.

---

## 11. Troubleshooting

| Symptom                          | Likely cause                                            | Fix                                                              |
| -------------------------------- | ------------------------------------------------------- | ---------------------------------------------------------------- |
| Device shows **offline**         | No heartbeat for > 10 min (Doze, OEM kill, no network)  | Battery exemption + Auto-start; enable notifications; re-verify in app self-test |
| SMS captured but never uploaded  | Watchlist mismatch / queue stuck / no connectivity      | Refresh watchlist (`GET /sms/senders`); check Ingest log & error; connectivity |
| `unknown sender` in log          | Sender not in config SSenders or device lacks the network | Assign the network to the device; add the sender in `config/sms.php` |
| App dead after reboot            | Auto-start disabled on OEM ROM                          | Enable manufacturer auto-start; confirm `BootReceiver` registered |
| Duplicate transactions           | Items re-sent after a successful upload but before removal | Check the queue removes on `ok:true`; server dedupe is authoritative and hash-based |
| "SMS did not match any financial template." | Template doesn't cover this bank's phrasing   | Extend `config('sms.templates')` (backend)                      |

---

## 12. Related backend configuration

- `config/sms.php` → `senders` map (keyword ⇒ network code) drives both the
  watchlist and server-side network identification; `templates` drives parsing;
  `offline_after_minutes => 10` drives the offline label.
- Device ↔ networks is a **many-to-many** relationship (`device_network`
  pivot); assign networks in the Devices admin screen to widen capture scope.