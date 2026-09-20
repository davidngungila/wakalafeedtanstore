# Implementation Tasks: Cashier Daily Opening + Profile Image Fix

Each task maps to one or more acceptance criteria in `spec.md`. Tasks are priority-ordered and dependency-ordered.

Status legend: `pending`, `in_progress`, `completed`, `blocked`, `cancelled`.

---

## Task 1: Remove opening bypass ("Skip for now") and tighten middleware allowed routes

**Priority**: high
**Dependencies**: none
**Maps AC**: AC-1, AC-2
**Status**: pending

### Work
1. In `resources/views/daily_opening/create.blade.php`:
   - Remove the `<a href="{{ route('dashboard') }}" class="btn btn-ghost">Skip for now</a>` button/link from the submit row.
   - Only leave the primary "Confirm & Start Day" submit button.
2. In `app/Http/Middleware/EnsureDailyOpeningSet.php`:
   - Remove `'dashboard'` from the `$allowedRoutes` list — the opening form is the only authorized landing spot until opening is set.
   - Remove `'cash-point.index'`, `'daily-opening.show'` should stay (to view after create), `'daily-opening.index'` can stay (history).
   - Keep only: opening CRUD routes, `logout`, `profile.*`, `account.*`, `avatar.show`, and 2FA routes if any.
   - Make sure the whitelist explicitly disallows: `transactions.*`, `float.*`, `reconciliation.*`, `networks.*`, `reports.*`, `finance.*`, `audit.*`, `users.*`, `devices.*`, `sms.*`, `settings.*` until a daily opening exists.
   - Ensure any route that should not be reached is not accidentally whitelisted. (Audit the actual list carefully against `routes/web.php`.)

### Test Requirements (TR)
- **rule TR-1.1**: Open `daily-opening.create` view; the DOM has NO `a.btn` or button with text containing "Skip" / "Skip for now" / dashboard bypass link.
- **rule TR-1.2**: As a cashier user without today's opening, send a `GET /dashboard` → response is a redirect (302) to `daily-opening.create`.
- **rule TR-1.3**: Same scenario, `GET /transactions` → 302 redirect to `daily-opening.create`.
- **rule TR-1.4**: Same scenario, `GET /float` → 302 redirect.
- **rule TR-1.5**: Same scenario, `GET /logout` → 200/302 OK (not redirected back to opening).
- **rule TR-1.6**: Same scenario, `GET /profile` → 200 OK (can view profile).

### Completion Evidence
- Diff of the two files showing removed bypass button and tightened whitelist.
- Screenshot / curl log of redirect behavior for the above routes OR passing PHPUnit feature test assertions.

---

## Task 2: Correct Daily Opening show page expected cash/variance formula and baseline sources

**Priority**: high
**Dependencies**: Task 1 (optional, parallel)
**Maps AC**: AC-4
**Status**: pending

### Work
1. In `app/Http/Controllers/DailyOpeningController.php` method `show()`:
   - Compute additional aggregates from today's completed transactions (or reuse `$todayTransactions` already fetched):
     - `$todayDeposits = sum(amount) where type in [deposit, bank_to_wallet]`
     - `$todayWithdrawals = sum(amount) where type in [withdrawal, wallet_to_bank, send_money, bill_payment, airtime, data]` (cash-out types)
     - Define and compute `$expectedClosingCash = $dailyOpening->cash_opening + $todayDeposits - $todayWithdrawals + $todayCommission;`
   - Pass these 3 new values to the view (or pass a computed array).
   - Keep `$todayVolume`, `$todayCommission`, `$todayCount` but prefer `$dailyOpening->total_volume` / `->total_commission` / `->total_transactions` when non-zero (for rows already aggregated). If zero and transactions exist, fall back to computed. Update the `updateQuietly` call to always sync them.
2. In `resources/views/daily_opening/show.blade.php`:
   - Fix the stat-card "Cash Variance" formula (line ~55): variance = actual current cash - expectedClosingCash (NOT `$cashCurrent - $opening + $commission`).
   - In the "Running Balances" panel's cash row: display Expected Cash = the computed `$expectedClosingCash`.
   - Ensure each network's opening float comes from `$dailyOpening->getFloatOpening($network->id)` (the JSON column) — already correct; just verify and keep.

### Test Requirements
- **rule TR-2.1**: Given an opening with cash_opening=500000, today's deposit of 100000 and withdrawal of 50000, commission sum 3000:
  expectedClosingCash MUST equal 500000 + 100000 - 50000 + 3000 = 553000.
- **rule TR-2.2**: The "Cash Variance" stat card uses `cashCurrent − expectedClosingCash` as its formula.
- **rubric TR-2.3**: No duplicate aggregate queries are introduced; all numbers derive from a single fetched collection of today's transactions OR the stored DailyOpening fields. (0=dupes N+1, 1=minor reuse, 2=single fetch, 3=also caches per-type sums on the collection without re-querying). Pass threshold ≥ 2.

### Completion Evidence
- Controller and view diffs.
- Manual test: seeded opening + two txns → match expected values to 2 decimals.

---

## Task 3: Ensure ReportController fully prioritizes DailyOpening stored values over on-the-fly sums

**Priority**: medium
**Dependencies**: none
**Maps AC**: AC-5
**Status**: pending

### Work
1. In `app/Http/Controllers/ReportController.php` `__invoke()`:
   - For each day in the `$daily` map: currently `volume`, `commission`, `count` already prefer `$opening?->total_volume` etc. Keep and ensure the fallback to `$completedTxns` sum happens ONLY when the opening row exists AND stored values are all zero (or opening missing).
   - Ensure `opening_cash` and `opening_float` are displayed in the daily summary table (or at minimum are present in the data passed to view; expose them if the table doesn't currently show them — add columns to reports daily table for Opening Cash / Opening Float if missing).
2. In `resources/views/reports/index.blade.php`:
   - If missing, add two columns to the daily report table: "Opening Cash" and "Opening Float".
   - Add one more column "Net Revenue (Comm − Fees)" per day row if not already present; derive from opening.total_commission − day fees sum.

### Test Requirements
- **rule TR-3.1**: For a day with existing `DailyOpening.cash_opening=300000`, `total_volume=800000`, `total_commission=25000`, `total_transactions=40`, the report day row outputs exactly: opening_cash=300000, volume=800000 (not recomputed from txns), commission=25000, count=40.
- **rule TR-3.2**: For a day with NO `DailyOpening` row but with txns, row still renders correctly with opening_cash=0, opening_float=0, and values recomputed from txns as fallback.

### Completion Evidence
- Diff of ReportController + reports index blade.
- Screenshot or data dump of a report row matching the seeded opening values.

---

## Task 4: Transactions list — expose Running Cash and Running Float balances

**Priority**: medium
**Dependencies**: none
**Maps AC**: AC-3 (visibility of fields already persisted), AC-6
**Status**: pending

### Work
1. In `app/Http/Controllers/TransactionController.php` `index()`:
   - Already eager-loads `dailyOpening`; keep.
2. In `resources/views/transactions/index.blade.php`:
   - Option A (table columns — preferred, keep compact): add two new `<th>`: "Running Cash" and "Running Float" after the "Commission" column.
   - In each `<td>`, render:
     - `$txn->running_cash_balance !== null ? money($txn->running_cash_balance) : '—'`
     - Same for running_float_balance.
   - Optionally narrow other columns to avoid overly wide table (use compact numeric formatting).
   - ALSO add these two to the row-details drawer (the `bindRowClick` / viewTxn receipt) so even if columns are hidden, the data appears when clicking a row.
3. Update the per-txn JSON used by drawer details in the scripts section to include `running_cash_balance` and `running_float_balance`.

### Test Requirements
- **rule TR-4.1**: A transaction with stored `running_cash_balance=500000.00`, `running_float_balance=3000000.00` shows those exact formatted values in the new table columns and in the row-details drawer entries.
- **rule TR-4.2**: A legacy transaction with both null shows "—" for both, no PHP undefined errors.

### Completion Evidence
- Blade diff showing new columns + drawer integration.
- Screenshot of transactions page with visible running balances.

---

## Task 5: Fix profile image display — sidebar, topbar, and profile preview

**Priority**: high
**Dependencies**: none
**Maps AC**: AC-7
**Status**: pending

### Work
1. Sidebar footer avatar in `resources/views/layouts/app.blade.php`:
   - Locate the `.sb-footer .sb-avatar` block (~L131).
   - Replace the always-initials rendering with the same conditional pattern used by the topbar avatar:
     - If `$currentUser->avatarUrl()` is a non-empty string → render an `<img>` with that src inside `.sb-avatar` (keep the circle sizing css, cover fit, radius 50%).
     - Else → render the initials `${$initials}` inside as today.
2. Topbar avatar `.tb-user-avatar` (~L800): change the PHP condition from `@if ($currentUser->profile_photo_path)` to `@if ($currentUser->avatarUrl())` — so stale DB paths with missing files no longer produce `<img src="">`.
3. Profile edit screen preview in `resources/views/profile/index.blade.php` (~L25): swap the same condition: `@if ($user->avatarUrl())` instead of `@if ($user->profile_photo_path)`.
4. Ensure `User::avatarUrl()` logic is correct — no change needed to the method; verify it returns a URL only when the file exists on disk. If the public disk check fails, return empty string. Keep current implementation.
5. (Supporting checklist item for verification — not a code change): In the project, ensure `public/storage` symlink exists OR at least document that `php artisan storage:link` must be run. Do NOT commit the symlink. During implementation verification, run `php artisan storage:link` if the target folder does not exist under `public/`.

### Test Requirements
- **rule TR-5.1**: Upload an avatar on `/profile`; confirm on any page load:
  - topbar avatar shows image `<img>` with URL starting `/storage/avatars/`.
  - sidebar footer `.sb-avatar` shows the same image `<img>`.
  - `/profile` preview shows the image `<img>`.
- **rule TR-5.2**: After manually deleting the file from disk (keeping DB path) OR setting an invalid `profile_photo_path`:
  - All three locations fall back to initials.
  - No element has `<img src="">` (empty src) in the DOM.
  - No `<img>` with broken 404 path appears (since `avatarUrl()` returns empty → img not rendered).
- **rule TR-5.3**: `public/storage` symlink OR directory exists after running `storage:link`; confirm in filesystem listing during verify step.

### Completion Evidence
- Diff of layouts/app.blade.php (two locations) + profile/index.blade.php.
- Screenshot showing avatar in both topbar and sidebar after upload; screenshot of initials-only when file is missing.

---

## Task 6: Verify transaction reversal correctly decrements DailyOpening totals + lint

**Priority**: medium
**Dependencies**: Task 2 (parallel), Task 1 (parallel)
**Maps AC**: AC-3, AC-8
**Status**: pending

### Work
1. Audit `app/Http/Controllers/TransactionController.php` `reverse()` method:
   - Line ~136 calls `$opening->reverseTransactionVolume(...)`.
   - Confirm that after reversal, the stored `total_volume`, `total_commission`, `total_transactions` on the opening row match expected values (e.g., volume = prior volume minus that txn's amount; commission similarly; count − 1, floored at 0 — already correct via `max(0,...)` in model method).
   - No code change expected. Add an inline test via factory/seed in existing tests, OR a 1-line manual verification recipe.
2. Run `vendor/bin/pint --dirty --format agent` on ALL touched PHP files after all tasks are done (run once after all).

### Test Requirements
- **rule TR-6.1**: Starting with opening (volume=100000, commission=1000, count=1). Reverse the single linked txn. Assert opening fields become (volume=0, commission=0, count=0) and not negative.
- **rule TR-6.2**: `vendor/bin/pint --format agent` exits 0 against all changed PHP files.
- **rubric TR-6.3 → maps AC-8**: Style / quality score for the whole change set. (0–3, pass ≥ 2)

### Completion Evidence
- DB values before/after reverse (tinker log OR test assertion log).
- Pint success output.

---

## Task 7: End-to-end verify all ACs and run existing test suite

**Priority**: high
**Dependencies**: Tasks 1–6 completed
**Maps AC**: AC-1..AC-8
**Status**: pending

### Work
1. Run existing PHPUnit tests `php artisan test --compact`.
2. Manual checklist for each AC:
   - AC-1 login redirect without opening → to daily-opening.create.
   - AC-2 no skip button on create view.
   - AC-3 create + reverse txn updates stored totals correctly.
   - AC-4 show page: expected closing cash uses correct formula; opening baselines match.
   - AC-5 report day row uses stored opening/count/commission/volume.
   - AC-6 transactions list shows running cash & float.
   - AC-7 upload avatar → visible everywhere; missing file → initials everywhere.
3. Fix any regressions found.

### Test Requirements
- **rule TR-7.1**: `php artisan test --compact` returns 0 OR any pre-existing failures are unchanged (document which tests failed before, which after; no NEW failures introduced).
- **rule TR-7.2**: All 7 rule-based ACs (AC-1 through AC-7) have at least one passing manual or automated observation recorded in completion evidence.
- **rubric TR-7.3**: rubric AC-8 score ≥ 2.

### Completion Evidence
- Screenshot/log of test run.
- Manual checklist table with each AC pass/fail and brief evidence (e.g., curl response, page screenshot, DB row dump).
