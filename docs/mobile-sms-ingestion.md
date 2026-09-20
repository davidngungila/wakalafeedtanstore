# MobiControl Mobile — Background SMS Ingestion to Wakala Feedtan Store

Complete implementation guide for building the Android app that listens for
mobile-money SMS in the background and forwards them into the Wakala Feedtan
Store system (`https://wakala.feedtanstore.com`) over the device API.

This document covers:

1.  Architecture overview
2.  Device lifecycle
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

- registers to **receive every incoming SMS** on **every SIM line** (via a
  native `BroadcastReceiver`; Android exposes each message's `subscriptionId`
  so a dual-SIM phone can say which physical line it came on),
- forwards **every** SMS — the capture contract (`GET /api/v1/sms/senders`)
  sets `capture_all`, so there is no sender filtering on the phone,
- attaches the SIM slot / subscription id to each message so the server can
  attribute it to the correct **device line** and its network,
- queues the captured SMS locally (offline-safe),
- uploads them in batches of ≤ 500 to `POST /api/v1/sms/ingest`,
- parses the server response, keeps anything that failed for retry, and
- drops the rest,
- reports liveness with `POST /heartbeat` (the web dashboard marks a device
  **offline** when there is no heartbeat for **10 minutes**), and
- pairs/refreshes its identity with `POST /devices/bootstrap` and
  `GET /devices/me`. Each device code tracks every handset that connects (§4.6)
  and shows a live connection LED on the device page.

```
  Mobile Network ──SMS──▶ SIM / Telephony Stack
                                │
                                ▼
                    Native Android SmsReceiver
                    (BroadcastReceiver, always-on)
                                │   forward all SMS (capture_all)
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

## 2. Device lifecycle

Every phone is a **Device** managed by an administrator on the web dashboard. The
only credential is the **device code**.

A device belongs to one physical handset and its **SIM lines**. Each line maps a
SIM slot (1, 2, 3, 4) to a network chip and optionally a phone number and the
Android **subscription id**. A typical agent phone is **2 SIM lines per handset**,
each serving a different network:

```
Phone (Device "Samsung A15", code F7KQ2M)
├── SIM line 1  → SIM slot 1 · VODACOM · 0754…
└── SIM line 2  → SIM slot 2 · TIGOPESA · 0755…
```

The admin configures lines in the device page. The app reads them from
`GET /sms/senders` (`lines[]`) and tags every captured SMS with its slot /
subscription id; the server then attributes the SMS to the right line and network.

| Stage            | Meaning                                                                    | Behaviour on the API                                      |
| ---------------- | -------------------------------------------------------------------------- | --------------------------------------------------------- |
| `pending`        | Registered, code generated, not yet approved                               | Auth works; `ingest` → `403 Device is not active`         |
| `active`         | Approved. This is the only state that may ingest SMS                        | All endpoints work                                        |
| `suspended`      | Temporarily disabled                                                        | Auth works; `ingest` → `403 Device is not active`         |
| `blocked`        | Permanently blocked                                                         | Auth → `403 Device is not allowed to access the system`   |
| `revoked`        | Permanently revoked                                                         | Auth → `403 Device is not allowed to access the system`   |
| `offline`        | Not a stored state — derived when `active` but no heartbeat for 10 min      | Heartbeat overdue → dashboard shows "offline"             |

**Onboarding flow**

Registration is a **three-step wizard** on the web app
(`/devices/register`, reached from the "+ Register device" button):

1. **Details** — the admin enters the phone's name, networks, number / SIM /
   branch. The device is created as `pending` with its device code. The
   technical details (model, Android & app versions) are **not** entered here:
   the phone reports them itself when it connects.
2. **Connect** — the wizard shows the device's QR code and its 6-character
   **device code**. The phone **must** connect using this code before it can be
   authorized:
   - The operator opens the **MobiControl** app and taps **Scan QR** (or types
     the code manually).
   - The QR encodes a deep link the app parses to get the device code **and**
     the server host:

     ```
     mobicontrol://connect?code=F7KQ2M&host=https%3A%2F%2Fwakala.feedtanstore.com
     ```

     The app should register the `mobicontrol` URI scheme, read `code` and `host`,
     store both (the code in secure storage, §8), and prefill the pairing screen.
   - The app calls `POST /devices/bootstrap` to pair and report handset info.
     The wizard polls `GET /devices/{device}/connect-status` and, the moment a
     phone pairs, shows the handset's **real** model / manufacturer name and
     Android / app version in the next step.
3. **Authorize** — the wizard shows the details the app reported about the
   physical phone (model, device UID, Android & app version, IP, last seen).
   The admin clicks **Authorize & activate** → the device becomes `active` and
   the phone starts ingesting SMS.

The device code is shown **once** (the credential is never retrievable again).
If an admin leaves the wizard mid-way, they can resume with
`/devices/register?device=<encrypted-id>` and the wizard picks up at the
correct step (connect, or authorize once the phone has paired).

The app must handle the `pending` state gracefully: poll `GET /devices/me`
(and/or heartbeat) until `status == active`, and only then start sending SMS.

---

## 3. API authentication

Every endpoint in `/api/v1` requires the device code on every request:

```
X-Device-Code: F7KQ2M
Accept: application/json
Content-Type: application/json
```

- The device is looked up by `device_code` (exact match, case-insensitive).
- A missing or unknown code returns `401`; the app must wipe its stored code
  and return the operator to the pairing screen.

Common auth errors:

| Code | Body                                                        | Meaning                                             |
| ---- | ----------------------------------------------------------- | --------------------------------------------------- |
| 401  | `{"message":"Missing credentials."}`                        | `X-Device-Code` header missing   |
| 401  | `{"message":"Unknown device. This device is not authorized."}` | Device code does not match any device               |
| 403  | `{"message":"Device is not allowed to access the system."}` | Device is `blocked` or `revoked`                    |

> The admin can **Regenerate code** on the dashboard. After that, the app's old
> code stops working — treat a `401` as "re-pair required". Storage on the phone
> MUST be secure (see §8).

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

`device_uid` (plus `model`/`android_version`/`app_version`) also creates the
first **handset session** in the Connected phones panel (see §4.6). Subsequent
bootstrap/heartbeat calls with the same `device_uid` refresh it.
| `model`           | string | optional, max 120         |
| `android_version` | string | optional, max 30          |
| `app_version`     | string | optional, max 30          |

Response `200`:

```json
{
  "device": {
    "id": 12,
    "device_code": "F7KQ2M",
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
    "device_code": "F7KQ2M",
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

Returns the **capture contract**: whether the phone must forward every SMS
(`capture_all`) plus the reference list of known mobile-money sender keywords,
mapped to the network they belong to.

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
  "lines": [
    {
      "sim_slot": 1,
      "subscription_id": "10",
      "phone_number": "0754123456",
      "network": "VODACOM"
    },
    {
      "sim_slot": 2,
      "subscription_id": "12",
      "phone_number": "0755123456",
      "network": "TIGOPESA"
    }
  ],
  "capture_all": true,
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

| Field                  | Type   | Meaning                                                    |
| ---------------------- | ------ | ---------------------------------------------------------- |
| `lines[].sim_slot`     | int    | SIM slot of the line (1-based)                             |
| `lines[].subscription_id` | string/null | Android subscription id for this SIM (may be empty)  |
| `lines[].phone_number` | string/null | Phone number of that line                                   |
| `lines[].network`      | string | Network code this line's SIM serves (`VODACOM`, `TIGOPESA`, …) |

The app caches this contract and re-fetches it periodically (e.g. after every
successful heartbeat) so admin changes propagate.

> **SIM-line mapping rule:** the app should use `lines[].subscription_id` to map
> an SMS to a line when available (Android 5.1+/DSDS provides `getSubscriptionId()`
> on the raw SMS). If the app cannot read a subscription id, it should fall back
> to the physical slot it already asked the operator to assign in its SIM settings.
> The server accepts **either** field (see §4.4).

> **Capture rule:** `capture_all: true` (the current server contract) tells the
> phone to forward **every** SMS regardless of sender. The `senders` list is
> then reference-only: it maps the known mobile-money keywords to their
> networks. When `capture_all` is `false`, the phone falls back to
> case-insensitive substring matching (the server uses `stripos`) and only
> forwards SMS whose sender contains a keyword. The server re-identifies the
> exact network itself; the phone never needs to assign one.

### 4.4 `POST /sms/ingest` — upload captured SMS

The core call. Sends up to **500** messages per request.

Request body:

```json
{
  "sms": [
    {
      "sender": "MPESA",
      "message": "P98765 confirmed. You have received TZS 100,000.00 from JUMA ATHUMANI 0712345678 on 14/9/2026 at 10:30.",
      "received_at": "2026-09-14 10:30:00",
      "sim_slot": 1,
      "subscription_id": "10"
    }
  ]
}
```

| Field            | Type   | Rule                                   |
| ---------------- | ------ | -------------------------------------- |
| `sender`         | string | required, max 30                       |
| `message`        | string | required, the raw SMS body             |
| `received_at`    | string | optional ISO-style / `Y-m-d H:i:s` date |
| `sim_slot`       | int    | optional, 1–4 — SIM slot the SMS arrived on |
| `subscription_id`| string | optional, max 30 — Android subscription id for the SIM |

> On a **dual-SIM phone**, include the slot (or subscription id) on every SMS so
> the server attributes it to the right network. The server resolves the line by
> `subscription_id` first, then by `sim_slot`. When the line is found, its network
> is used as the attribution fallback (even for senders the parser doesn't know).

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
      "sim_slot": 1,
      "line": "SIM 1",
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

| Outcome                         | Flags               | Meaning                                             |
| ------------------------------- | ------------------- | --------------------------------------------------- |
| Processed                       | `ok: true`          | Created a transaction                               |
| Duplicate                       | `ok:false, duplicate:true` | Same body already ingested → safe to drop     |
| Not a financial message         | `ok:false`          | Stored but no transaction — template didn't match   |
| Device has no network assigned  | `ok:false`          | Stored but failed — admin must assign a network     |

**Business rules the phone must respect**

- Only an **`active`** device may ingest. Otherwise:

  ```
  403
  {"message":"Device is not active. Approved devices only.","device_status":"pending"}
  ```

- Do **not** resend items the server already accepted (`ok:true`) or flagged as
  `duplicate:true`. Only retry `failed` items.
- Requests are capped at `max_batch` (500) — chunk larger queues.

**Server-side processing** (what happens per accepted SMS): the server computes
`sha256(message_body)`, checks the per-device duplicate index, resolves the SIM
line from `subscription_id` / `sim_slot`, identifies the network from the sender —
falling back to the matching line's network, then to the device's first assigned
network, when the sender is not a known keyword — parses the SMS template, creates a `Transaction`
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

When `device_uid` is sent, the server also records a **handset session** for the
pair `(device, device_uid)` — see §4.6. Sending it on every heartbeat keeps the
handset's live status fresh.

---

### 4.6 Connected handsets & live status (LED)

Both `bootstrap` (§4.1) and `heartbeat` (§4.5) register or refresh a handset
session whenever `device_uid` is supplied. Each unique phone that pairs with a
device code appears in the **Connected phones** panel on the device page:

| Session rules                                |                                                                          |
| -------------------------------------------- | ------------------------------------------------------------------------ |
| Key                                          | `device_uid` per device code (one device can pair several handsets)      |
| First contact                                | Creates the session (`first_seen_at`)                                    |
| Every bootstrap/heartbeat with `device_uid`  | Updates `model`, `android_version`, `app_version`, `ip`, `last_seen_at`  |
| Online window                                | `last_seen_at` within `sms.offline_after_minutes` (= 10)                 |
| Repeated `device_uid` beats                  | Update the **same** row — one session per handset                        |

The **LED** (#C0) next to each phone pulses **green** while the handset is
online and turns **grey** when it goes stale; the panel and LEDs refresh every
15 seconds with no page reload. A device ported to a new physical phone (new
`device_uid`) shows **both** handsets, so admins can see the old pairing age
out.

> For the app: always echo the *actual* Android provisioning ID (e.g.
> `Settings.Secure.ANDROID_ID`) as `device_uid`. Two devices sharing the same
> device code will show as two separate rows; reusing the same `device_uid`
> across phones collapses them into one and is incorrect.

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
            // Android applies the message to its own subscription. On a
            // dual-SIM phone this is the ONLY reliable way to know which SIM
            // line the SMS arrived on, so the server attributes it correctly.
            val subscriptionId = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP_MR1) {
                message.subscriptionId?.toString()
            } else {
                subIdFor(subscription, message)
            }
            // Hand to Flutter (EventChannel) and/or persist to the local queue.
            SmsBridge.sink.add(SmsItem(sender, body, receivedAt, subscriptionId))
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
  flutter_secure_storage: ^9.0.0      # device code (EncryptedSharedPreferences)
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
  the UI is dead: reload the device code, flush `IngestQueue`, refresh the watchlist,
  and heartbeat.
- **IngestQueue** appends every captured SMS, then flushes: build chunks of
  `max_batch` (500), call `SmsApi.ingest`, and remove only `ok:true` /
  `duplicate:true` items. Failed items stay queued (with an attempt counter and
  exponential backoff).
- **SIM line tagging:** `SmsItem` carries the subscription id / slot from the
  native receiver; the flush maps subscription id → slot using the cached
  `lines[]` contract and sends both (`sim_slot` + `subscription_id`) on every
  item so a dual-SIM phone attributes each message to the correct network.

### 6.4 Capture + dedupe discipline

- Capture decision: `capture_all` (the current contract) uploads every SMS;
  `SenderWatchlist.matches()` keeps the old keyword check
  (`sender.toLowerCase().contains(keyword.toLowerCase())`) as a fallback for
  servers that do not set `capture_all`.
- Keep a local bloom/set of `sha256(message)` per device to skip obvious
  replays before uploading (the server still de-dupes authoritatively).
- Never drop an item on network failure — only on server acknowledgement.

---

## 7. Data flow walkthrough

### 7.1 Happy path

1. SMS arrives → native `SmsReceiver` fires.
2. Every SMS is enqueued (`capture_all`) — no sender filtering on the phone.
3. `AgentService` notices the queue is non-empty → builds a batch → `POST
   /sms/ingest` with the device code header.
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

1. Operator enters the **device code** → `bootstrap`
   returns `status: pending` and a message "Awaiting approval".
2. UI shows a gold `Pending` tag; `AgentService` polls `me` + heartbeat but
   does **not** upload SMS.
3. Admin approves → next poll returns `active` → app switches to green `Active`
   and begins uploads.

---

## 8. Security checklist

- Store the **device code** in **`flutter_secure_storage`**
  (EncryptedSharedPreferences / Keystore). Never in plain `SharedPreferences`,
  logs, or analytics.
- Talk HTTPS only (`https://wakala.feedtanstore.com`); add network-security-config
  to reject cleartext.
- Never log message bodies or customer data in the app's logcat.
- On `revoke`, the device code stops working immediately — the app must handle
  `403` by clearing the local code and returning the operator to the pairing
  screen.
- Forward **every** SMS: the server accepts all senders (`capture_all`). The
  sender list is reference-only (keyword → network) and never gates uploads;
  unknown senders are attributed to the device's assigned network.
- Validate lengths client-side (`sender ≤ 30`, etc.) to fail fast, but rely on
  the server for correctness.
- Do not embed the code in the APK; always operator-entered (typed or
  QR-scanned). The QR is a per-device deep link the operator scans at pair time.

---

## 9. Error-handling matrix

| Scenario                                    | HTTP / signal            | App action                                                         |
| ------------------------------------------- | ------------------------ | ------------------------------------------------------------------ |
| No code stored                              | —                        | Show pairing screen (code entry)                                   |
| `401 Missing credentials.`                  | 401                      | Server config problem; show contact-admin message                  |
| `401 Unknown device. This device is not authorized.` | 401              | Device code invalid → clear code → pairing screen                 |
| `403 Device is not allowed…`                | 403                      | Device blocked/revoked → clear code → pairing screen (+ hint)      |
| `403 Device is not active.`                 | 403 (+ `device_status`)  | Stay paired, block uploads, show `Pending`/`Suspended` state       |
| `4xx` validation on ingest                  | 400/422 per item         | Keep item, mark `failed`, do not infinitely retry (max attempts)   |
| Network timeout / connection                | Exception                | Keep queue, exponential backoff, heartbeat deferred                |
| `results[i].ok:false, duplicate:true`       | 200                      | Drop item (already known)                                          |
| `results[i].ok:false` — "Device has no network assigned." | 200      | Keep item, log; admin must assign a network to the device         |
| `results[i].ok:false` — "SMS did not match any financial template." | 200 | Drop item, log; wording not covered by the provider parsers |
| Server 5xx / 429                            | 5xx                      | Backoff, never clear queue                                         |

Batch safety rule: **an item is removed only when the server acknowledges it**
(`ok:true` or `duplicate:true`). Everything else is retried.

---

## 10. Testing / QA checklist

- [ ] Fresh install → pairing (code entry) → bootstrap shows `pending` → no upload until approved.
- [ ] **Registration wizard:** "/devices/register" → Step 1 details → Step 2 shows QR/code and waits → scan the QR with the app's "Scan QR" → the wizard detects the phone and shows its real model (Step 3) → Authorize & activate → device `active`.
- [ ] **QR pairing (existing path):** tap "Connect phone" on a device → scan the QR with the app's "Scan QR" → app captures the `code` and `host`, pre-fills pairing, bootstrap succeeds. Resuming an in-flight registration via `/devices/register?device=…` picks up at the correct step.
- [ ] Approval on dashboard → app flips to `Active` (≤ next heartbeat) → first SMS uploads and appears on `/sms` and `/transactions`.
- [ ] Send 600+ SMS in a burst → chunks of ≤ 500 are sent, all recorded, no loss.
- [ ] Kill the app (swipe) → SMS still captured & uploaded via WorkManager watchdog.
- [ ] Reboot → `BootReceiver` restarts service; device returns online.
- [ ] Turn off Wi-Fi/data → queue grows; re-enable → drains with **no duplicates** created.
- [ ] Resend the same SMS body → `duplicate` result, transaction count unchanged.
- [ ] Two phones on the same network upload the same M-Pesa SMS (same provider reference) → only **one** transaction is recorded; the second upload returns `duplicate:true`.
- [ ] Unknown sender (e.g. bank OTP) → captured, uploaded and stored; no transaction unless it matches a financial template.
- [ ] Revoke device → next API call returns 403 → code cleared, pairing screen shown.
- [ ] Battery saver / Doze after 30 min idle → device still heartbeat within 10 min of going offline? (Use the visual **offline** check.) Then run the OEM auto-start fix.
- [ ] A device serving 2 networks (VODACOM + AIRTEL) captures MPESA **and** AIRTEL senders and creates transactions on both networks.
- [ ] **Dual-SIM:** a phone with SIM1 = Vodacom and SIM2 = Tigo captures a Vodacom SMS (slot 1) and a Tigo SMS (slot 2) in the same batch → both appear on the dashboard attributed to `SIM 1 · Vodacom` and `SIM 2 · Tigo Pesa` respectively, on the correct networks.
- [ ] Server resolves a message with only `subscription_id` (no `sim_slot`) and one with only `sim_slot` — both attribute to the right line.
- [ ] `GET /sms/senders` returns `capture_all: true` → app shows the "Capturing all senders" banner and forwards every SMS, even from senders not in the reference list.
- [ ] New admin edits a device's lines (add/remove line) → next `senders` poll shows the updated `lines[]`; messages from a removed line still ingest (attributed via fallback) but show no line.
- [ ] **Connected phones / LED:** pair one phone → device page **Connected phones** shows it with a pulsing green LED within 15 s. Swap the SIM into another phone (new `device_uid`) → a second row appears. Force-stop the app ≥ 10 min → that row's LED turns grey and its "Last seen" freezes, while the other handset stays green.

---

## 11. Troubleshooting

| Symptom                          | Likely cause                                            | Fix                                                              |
| -------------------------------- | ------------------------------------------------------- | ---------------------------------------------------------------- |
| Device shows **offline**         | No heartbeat for > 10 min (Doze, OEM kill, no network)  | Battery exemption + Auto-start; enable notifications; re-verify in app self-test |
| A handset's **LED** is grey but the device is **online** | That handset stopped sending `device_uid` (or was replaced by another phone under the same code) | Keep sending the real Android ID in bootstrap/heartbeat; each phone gets its own row (§4.6) |
| SMS captured but never uploaded  | Queue stuck / no connectivity                     | Check Ingest log & error; connectivity |
| "Device has no network assigned." in ingest results | Device has no networks assigned | Assign at least one network in the admin screens |
| Dual-SIM SMS attributed to the **wrong** network | App sent no `sim_slot`/`subscription_id`, or the server's line map doesn't match the phone | Send the subscription id with each SMS; verify the device's SIM lines config matches the phone's actual slots |
| Unknown sender stored, no transaction | Sender not a known keyword and message not a financial template | Expected with `capture_all`; extend the provider parsers if it should parse |
| App dead after reboot            | Auto-start disabled on OEM ROM                          | Enable manufacturer auto-start; confirm `BootReceiver` registered |
| Duplicate transactions           | Items re-sent after a successful upload but before removal | Check the queue removes on `ok:true`; server dedupe is authoritative and hash-based |
| "SMS did not match any financial template." | Template doesn't cover this bank's phrasing   | Extend the provider parser for that sender (backend)             |

---

## 12. Related backend configuration

- `config/sms.php` → `senders` map (keyword ⇒ network code) is a best-effort
  hint for network attribution. Every SMS is accepted; when no keyword matches,
  the message is attributed to the device's first assigned network.
  `providers` routes a sender to its provider parser; `offline_after_minutes
  => 10` drives the offline label.
- Parsing is handled by **provider-specific parsers** in
  `app/Services/Parsers/` (`mpesa`, `airtel`, `tigo`, `halopesa`, `mixx`,
  `bank`, `generic`). The sender keyword picks the parser; unknown senders fall
  back to the generic parser, so no single SMS format is load-bearing. A
  provider that changes its format is fixed inside its own parser class.
- Device ↔ networks is a **many-to-many** relationship (`device_network`
  pivot). A device with **no** assigned network cannot process SMS — every
  message returns `Device has no network assigned.` Assign networks in the
  Devices admin screen.
- Device ↔ SIM lines is a **one-to-many** relationship (`device_lines`):
  `sim_slot`, `network_id`, `phone_number`, `subscription_id`. SMS ingest
  stores `device_line_id` + `sim_slot`; messages show the line everywhere it is
  listed. The line's network is used as the attribution fallback ahead of the
  device's assigned networks.
- Device ↔ handsets is a **one-to-many** relationship (`device_phones`): keyed
  on `(device_id, device_uid)` with `model`, `android_version`, `app_version`,
  `ip`, `first_seen_at`, `last_seen_at`. The device page reads from here for the
  **Connected phones** panel and LEDs; `GET /devices/{device}/phones/status`
  feeds the 15-second live refresh.
- SMS lifecycle statuses (column `processing_status`) follow the spec
  vocabulary: `RECEIVED` → `PARSED` → `RECORDED`, plus `NEEDS_REVIEW` (captured
  but no financial template match), `FAILED` (real errors), and `DUPLICATE`.
  Duplicate protection is two-layered: `sms_hash = sha256(sender | body |
  received_at)` unique per device, plus a unique index on
  `(network_id, provider_reference)` so the same provider transaction is never
  recorded twice, even when it arrives from a second phone.