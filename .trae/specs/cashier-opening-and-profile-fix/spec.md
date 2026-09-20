# Specification: Cashier Mandatory Daily Opening + Profile Image Display Fix

## Problem
Two distinct issues exist in the Wakala Feedtan Store application:

### 1. Cashier Daily Opening
The cashier role needs a frictionless, mandatory daily-opening workflow after sign-in so that:
- Every cashier MUST record opening cash-in-hand and per-network float before doing any transactions.
- Every transaction during the day must be linked to the daily opening and incrementally update the running cash balance, running float balance, total transaction volume, and total commission earned on that day's opening record.
- End-of-day reports (daily summary) must anchor to those recorded opening balances (opening cash + opening float per network) so variance/expected-closing calculations are consistent with what the cashier actually started with, not just the current system balance.

Currently:
- The scaffolding (`daily_openings` table, `DailyOpening` model, `EnsureDailyOpeningSet` middleware, `completeLogin` redirect) already exists.
- BUT the daily-opening create screen has a **"Skip for now"** button that lets cashiers bypass the opening form even when the middleware would otherwise enforce it. The bypass is also reachable for cashiers via the allowed-routes list in `EnsureDailyOpeningSet` (which currently whitelists `dashboard` and other pages).
- The running balance / running commission fields on transactions are populated, but the transactions list UI does not surface them to the cashier or supervisor.
- The daily-opening "show" screen variance formula has a logic bug: expected closing cash is approximated as `opening + todayCommission`, which does not account for deposit/withdrawal cash movement correctly.

### 2. Profile Image /profile
When a user uploads a profile photo on `/profile`, the image is not shown consistently across the UI. In particular:
- The sidebar footer user avatar (`sb-avatar`) always falls back to initials and never renders the uploaded `<img>`.
- The topbar user avatar uses `profile_photo_path` as the condition, but `User::avatarUrl()` returns an empty string when the file does not exist on disk — so if the DB has a stale path or the storage symlink is missing, `<img src="">` renders with a broken/empty source.
- Users may not have run `php artisan storage:link`, so even correctly-stored files under `storage/app/public/avatars/` are not served from `public/storage/avatars/`.

## Users
- **Cashier**: role `cashier` — the primary actor for the daily-opening workflow. They log in, record opening balances, run transactions, and should be blocked until opening is set.
- **Supervisor / Admin**: roles `supervisor`, `admin` — view reports, reverse transactions, and should also have their opening enforced for the cash-point they manage (currently already in the role list).
- **All authenticated users**: profile photo affects every role's display in topbar, sidebar, and `/profile` page.

## Goals
1. After login, cashiers (and supervisors/admins with a cash-point) who have not recorded today's opening balances are **non-bypassable** redirected to the opening form, where they must enter cash-in-hand and per-network float before they can transact.
2. Every transaction created during the day is linked to that day's `DailyOpening`, and that opening's running totals (volume, commission, count) plus per-transaction running cash/float balances stay accurate, including when a transaction is reversed.
3. Daily reports (Reports page, Daily Opening show page) are computed using the stored opening balances as the starting baseline and show expected vs actual closing with meaningful variance.
4. The profile image uploaded on `/profile` correctly shows up everywhere: topbar user avatar, sidebar user avatar, and the profile edit screen preview — with graceful fallback if the file is missing on disk (fall back to initials, not to a broken image).

## Non-Goals
- Not building a multi-cashier-per-shift model (a single opening per agent per day matches the current unique key).
- Not changing the file-storage driver (files remain on the `public` local disk).
- Not rewriting the accounting/ledger system; existing `NetworkBalance` and `Agent.cash_balance` updates stay as they are.
- Not changing Two-Factor flow beyond preserving the existing `completeLogin` behavior.

## Functional Requirements

### FR-1 — Mandatory opening after authentication
- After successful login (password or 2FA), any user with role ∈ {cashier, supervisor, admin} AND a configured cash-point MUST be sent to the daily-opening create screen UNLESS an open `DailyOpening` already exists for that agent + today.
- The create screen MUST NOT contain a "Skip for now" (or equivalent bypass) button for any user in those roles. If they were somehow linked there with `intended`, skipping should not be an option.
- The `EnsureDailyOpeningSet` middleware allowed-routes whitelist MUST NOT grant access to `dashboard`, `transactions.*`, `float.*`, `reconciliation.*`, `networks.*`, `cash-point.index` update actions, or any other operation page until an open DailyOpening exists. The whitelist should only include: the opening routes themselves, logout, profile read/edit/password, account security pages (for password/2FA management), and static avatar serving.

### FR-2 — Form-level recording of opening
- Opening form records `cash_opening` (numeric >= 0 required), a `float_openings[networkId]` numeric entry for each active network (>= 0), and optional notes (max 500 chars). (Already implemented — keep behavior.)
- On submit, the stored `DailyOpening` is used:
  - to initialize `Agent.cash_balance` to the entered cash_opening;
  - to initialize each `NetworkBalance.opening_balance` to the entered per-network float (keeping the existing behavior).
- One and only one opening per (agent, date) — enforced by the existing unique index and controller guard.

### FR-3 — Transaction running updates
- Every new completed transaction MUST be stamped with the active `daily_opening_id` (currently done in `TransactionService::process`).
- On each new transaction creation:
  - `DailyOpening.total_volume += amount`
  - `DailyOpening.total_commission += commission`
  - `DailyOpening.total_transactions += 1`
  - `Transaction.running_cash_balance` = agent's resulting cash balance.
  - `Transaction.running_float_balance` = agent's resulting total float.
- On transaction reversal (`transactions.reverse`):
  - The linked `DailyOpening`'s totals MUST be decremented exactly (volume / commission / count), matching the existing `reverseTransactionVolume` call that is already in place.
  - Running balances on subsequent transactions are NOT re-computed retroactively; the reversal stands as its own row and the opening totals remain internally consistent.

### FR-4 — Reports baselined from opening
- **Daily opening show page**:
  - Display Opening Cash, Opening Float (total), Today's Volume, Commission Earned, Transaction Count using the stored `DailyOpening.*` fields as source of truth (fall back to on-the-fly sums only when `total_volume` is 0 and rows exist, for backward compatibility with legacy rows).
  - Expected closing cash = `opening_cash + (total deposits amount) - (total withdrawals amount) + total_commission`. Display actual current cash vs expected and highlight variance.
  - For each network, show: opening float (from daily opening), current balance (from NetworkBalance), and the delta.
  - The stat-card "Cash Variance" line must use the corrected expected-closing formula, not `opening + commission`.
- **Reports → Daily summary (ReportController)**:
  - Each day row uses stored `DailyOpening.opening_cash`, `opening_float`, `total_volume`, `total_commission`, `total_transactions` when a record exists (already partially done — keep but ensure commission + count use the stored value first).
  - Deposits and withdrawals sums per day remain on-the-fly from transactions (they aren't stored on DailyOpening yet).

### FR-5 — Transactions list visibility of running balance
- The transactions index table adds two readable columns (or adds them to the row details drawer): Running Cash Balance and Running Float Balance, sourced from `Transaction.running_cash_balance` and `running_float_balance`. For legacy rows with `null`, display `—`.

### FR-6 — Profile image display
- `User::avatarUrl()` MUST:
  - Return the storage-served URL when both `profile_photo_path` is set AND the file actually exists on the `public` disk.
  - Return an empty string in all other cases (matches current behavior — keep it).
- All locations that render a user avatar MUST decide whether to render `<img>` based on whether `avatarUrl()` returns a non-empty string, NOT based on `profile_photo_path` being truthy in the DB. This ensures no broken `<img src="">` when the DB has a stale path.
- Locations to fix:
  - **Sidebar footer `sb-avatar`** (`layouts/app.blade.php` ~L131): currently always shows initials. Add the same conditional `<img>` logic used in the topbar avatar.
  - **Topbar `tb-user-avatar`** (`layouts/app.blade.php` ~L800): change condition from `profile_photo_path` to `avatarUrl()` non-empty.
  - **Profile edit screen preview** (`profile/index.blade.php` ~L25): same condition swap.
- Add a clear diagnostic/troubleshooting note: document the need for `php artisan storage:link` (do NOT create docs unless asked; instead include in AC evidence verification).

## Non-Functional Requirements
- **Security**: no changes to auth; daily-opening middleware continues to respect role checks. Form validation for numeric amounts and file uploads remains intact. No path-traversal risk in avatar URLs (continue using storage facade + route).
- **Performance**: running totals are per-transaction increments, no aggregate queries on hot path — acceptable. N+1 on DailyOpening eager-loaded with `dailyOpening` relationship on Transaction queries.
- **Compatibility**: daily-opening rows created before this change keep working; `total_volume/commission/transactions` already default to 0 and are backfilled on show (updateQuietly) — keep that backfill for legacy rows.
- **UX consistency**: keep the existing earth-tone/coffee UI theme. Do not change colors or layout; only the necessary logic + avatar conditionals + a column addition.

## Constraints, Dependencies, Assumptions
- PHP 8.4 + Laravel (current installed stack — see composer.json for exact versions).
- Database: the `daily_openings` table and `transactions.daily_opening_id` + `running_*` columns already exist from migrations `2026_09_20_204919` and `2026_09_20_205234`. Must NOT re-create those migrations.
- Avatars stored on `public` disk at path `avatars/<filename>`; served via `Storage::disk('public')->url(...)` which produces `/storage/avatars/...`. Requires the `public/storage → storage/app/public` symlink (Artisan `storage:link`).
- `money()` / `@money()` and `txn_type_label()` helper output format assumed unchanged.

## Open Questions (resolved with defaults below; explicit user confirmation will be asked during Approve phase only if they disagree)
1. **Who exactly is required to perform daily opening?** Default resolution: `cashier`, `supervisor`, `admin` if a cash-point exists. Roles with no cash-point (future) skip the check — same as current middleware.
2. **Can a supervisor/admin "force-close" today's opening to re-open it if the cashier entered wrong values?** Default resolution: keep existing "close once" behavior. Wrong values can be corrected via a journal entry / reconciliation; reopening is out of scope.
3. **Running balances on reversals: re-sequence all later txns, or leave as-is?** Default resolution: leave as-is (the opening totals are decremented).

## Acceptance Criteria

### rule AC-1 — Login enforces daily opening for cashiers
- Sign in as a `cashier` user whose agent has NO open `DailyOpening` for today.
- After login (password path, or 2FA path), the browser is redirected to `route('daily-opening.create')` with a status flash.
- Attempting to navigate by URL to `route('transactions.index')`, `route('dashboard')`, `route('float.index')`, `route('reconciliation.index')`, or `route('networks.index')` redirects back to the opening form; status message is present.
- Observable evidence: feature test or manual navigation sequence + session flash message.

### rule AC-2 — Opening form has no bypass for cashier
- Load `daily-opening.create` view while authenticated as a cashier.
- The form contains exactly one primary submit button ("Confirm & Start Day").
- No "Skip for now" link/button is rendered in the page DOM.
- Attempting a direct `GET dashboard` is intercepted by middleware per AC-1.

### rule AC-3 — Transaction updates DailyOpening running totals
- Create a completed `deposit` of amount 100,000 and commission 1,000.
- Assert: `DailyOpening.total_volume` increased by exactly 100,000; `total_commission` by 1,000; `total_transactions` by 1.
- Assert: `transaction.daily_opening_id` equals today's open opening id; `running_cash_balance` and `running_float_balance` are non-null decimals.
- Reverse the same transaction with a reason.
- Assert: totals return to prior values (volume, commission, count decremented).

### rule AC-4 — Daily show page reports from opening baseline + correct expected cash
- Open `daily-opening.show` with a known opening: cash=500,000, M-Pesa float=2,000,000, Tigo float=1,000,000.
- After one deposit (50,000) and one withdrawal (20,000) both completed:
  - "Opening Cash" card shows 500,000; "Opening Float" card shows 3,000,000.
  - Today's Volume = 70,000; Commission = sum of both commissions computed from rates.
  - Expected closing cash stat matches formula: opening_cash + deposits_total − withdrawals_total + commission_total. Variance card / running-balances row uses the same formula.
- Each network row in the float panel reports its stored opening float (from the JSON column, not `opening_balance`).

### rule AC-5 — Reports daily summary uses DailyOpening values
- Visit `/reports?report=daily`.
- For any day that has a `DailyOpening` row:
  - `opening_cash` column (or derived cash flow card) reads `$opening->cash_opening` (not 0).
  - `commission` value uses `$opening->total_commission` as primary, falling back to sum only when stored 0.
  - `count` uses `$opening->total_transactions` as primary.

### rule AC-6 — Transactions list surfaces running balances
- Load transactions index with at least one transaction that has non-null `running_cash_balance` and `running_float_balance`.
- The row drawer (clicking a row) OR an added table column exposes both "Running Cash" and "Running Float" formatted with `@money`.
- A legacy row with `null` running balances renders `—` for both instead of breaking.

### rule AC-7 — Profile image displays everywhere when file exists, and falls back gracefully
- Scenario A (file on disk):
  - Set a user's `profile_photo_path` to an existing file on the `public` disk in `avatars/`.
  - Load `/profile` → the preview renders `<img src="…/storage/avatars/…">` and no broken placeholder.
  - Load any app page → the topbar user avatar renders `<img>` with the correct resolved URL.
  - Load any app page → the sidebar footer `sb-avatar` ALSO renders `<img>` (currently it never does).
- Scenario B (stale DB path, file missing on disk OR null path):
  - Set `profile_photo_path` to a non-existent file path.
  - All three locations show the initials-avatar fallback; nowhere does an `<img>` with empty/missing `src` appear in the DOM.

### rubric AC-8 — Code quality & conventions (threshold ≥ 2 / 3)
- **Score anchors**:
  - 0/3: controller methods longer than 30 lines, repeated logic, no type hints, raw string URLs instead of `route()`, validation missing.
  - 1/3: Functional but one or two deviations from existing style (e.g., inline comments, missing return types, inconsistent naming).
  - 2/3: All new code follows existing patterns: `#[Fillable]` casts, explicit return types, validated form requests / inline `$request->validate()`, no raw queries, no N+1 introduced, `recordAudit` used where appropriate.
  - 3/3: Exceeds style bar with small extra cleanliness (e.g., extracted small methods, tests for the most critical path).
- **Pass threshold**: ≥ 2.
- **Evidence source**: direct code review of changed files + pint formatter green.
