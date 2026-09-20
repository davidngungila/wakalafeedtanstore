# Mandatory 2FA After Login Implementation Plan

## Repository Research

Current state:
- **2FA setup is optional.** In [AuthController::login](file:///d:/server01/wakalafeedtanstore/app/Http/Controllers/AuthController.php#L37-L41) users with `two_factor_enabled === false` are logged in directly with no prompt. Only users who have it enabled go through the TOTP / recovery-code challenge (`two-factor.show/verify`).
- **Setup UI + flow:** [AccountController::index](file:///d:/server01/wakalafeedtanstore/app/Http/Controllers/AccountController.php#L17-L49) (route `account.index`) renders the TOTP QR + secret. It generates a pending secret into the session (`two_factor_pending_secret`) if the user doesn't already have one. Confirmation is a POST JSON endpoint `account.two-factor.confirm` which validates the code, then stores `two_factor_secret` (encrypted), flips `two_factor_enabled = true`, and saves hashed recovery codes. Disable is `account.two-factor.disable` (requires current password).
- **Existing enforcement pattern:** the app already uses the pattern `Auth → Middleware(whitelist + redirect)` with [EnsureDailyOpeningSet](file:///d:/server01/wakalafeedtanstore/app/Http/Middleware/EnsureDailyOpeningSet.php). It runs on the whole `['auth', 'daily.opening']` group in [web.php L45](file:///d:/server01/wakalafeedtanstore/routes/web.php#L45), whitelists self-service routes (`logout`, `account.*`, `profile.*`, `avatar.show`, etc.), and redirects or returns a JSON 422 with an error + redirect URL otherwise. We mirror that pattern exactly for 2FA enforcement so cashiers/admins/supervisors all hit the same whitelist/gate.
- **Post-login redirect flow:** `completeLogin()` on the base [Controller](file:///d:/server01/wakalafeedtanstore/app/Http/Controllers/Controller.php#L39-L73) already checks the daily-opening gate and redirects to `daily-opening.create` when needed; otherwise it redirects to `dashboard`. Adding the 2FA gate here as well saves a second HTTP round-trip (dashboard → middleware bounce → account), while the middleware acts as the persistent guard on every other page load (so admins can't sneakily just type `/settings` either).
- **Middleware registration lives in** [bootstrap/app.php](file:///d:/server01/wakalafeedtanstore/bootstrap/app.php#L18-L24) — aliases declared there and applied via route group string. No `app/Http/Kernel.php`.
- **No `.ai/rules/` directory exists** so no extra path-mapped rules to read.

Decision: enforce "2FA must be on" for **every logged-in user of every role**. The whitelist will let them reach exactly: logout, the account page (+ its password/2fa/sessions sub-actions), their own profile edit/update/password, and the avatar image route — nothing else until they confirm a TOTP code from their authenticator app.

## Files and Modules

- `app/Http/Middleware/EnsureTwoFactorEnabled.php` (new): Guard middleware — skip guests, skip users with `two_factor_enabled=true`, whitelist routes, otherwise redirect to `account.index` or return JSON 422.
- `bootstrap/app.php`: Register new middleware alias `two.factor`.
- `routes/web.php`: Add `two.factor` to the `['auth', …]` middleware group at L45.
- `app/Http/Controllers/Controller.php`: In `completeLogin()`, short-circuit to `account.index` with a status message when the logging-in user doesn't have 2FA enabled yet. Keeps daily-opening check (which still runs after 2FA is on).

## Implementation Steps

1. **Build middleware** `EnsureTwoFactorEnabled`
   - `handle()`: read `$user = $request->user()`. If no user or `$user->two_factor_enabled === true` → `$next($request)`.
   - Build `$allowedRoutes = ['logout', 'account.index', 'account.password', 'account.two-factor.confirm', 'account.two-factor.disable', 'account.recovery-codes', 'account.sessions.destroy', 'profile.index', 'profile.update', 'profile.password', 'avatar.show']`. If current route name in list → pass through.
   - Else: if request expects JSON → 422 with `{ success: false, message, redirect: route('account.index'), two_factor_required: true }`. Otherwise redirect to `route('account.index')->with('status', 'Set up two-factor authentication before you continue.')`.
2. **Wire alias & apply** — add `'two.factor' => EnsureTwoFactorEnabled::class` to the alias array in `bootstrap/app.php`. In `routes/web.php` change the group from `['auth', 'daily.opening']` to `['auth', 'two.factor', 'daily.opening']` so the 2FA gate runs before the cashier opening-balance gate (no point redirecting to `daily-opening.create` when the user still can't get past account setup).
3. **Post-login shortcut** in `Controller::completeLogin`: after saving `last_login_at` + recording audit login, check `if (! $user->two_factor_enabled)` → `return redirect()->intended(route('account.index'))->with('status', 'Set up two-factor authentication before you continue.');` Place it *before* the `DailyOpening` role-gated block — after 2FA is on the existing daily-opening redirect still takes effect as before.
4. **Tidy** — run Pint, clear view/config caches, run the full test suite.

## Dependencies and Considerations

- Uses only existing framework APIs (`redirect()->intended()`, `request()->route()->getName()`, `expectsJson()`, session flash `with('status', …)`). No new packages.
- Whitelist must cover every AJAX action the 2FA-setup page itself needs: `account.two-factor.confirm` (JSON POST to enable 2FA), `account.two-factor.disable`, `account.password` (users may want to change password on the same page), `account.sessions.destroy`, `account.recovery-codes` — plus `avatar.show` and `profile.*` so the avatar/profile forms keep working while they're in the "locked to account" state.
- The `logout` route is intentionally open so users stuck mid-setup can sign out and sign in with another account if needed.
- `two_factor_enabled === true` + `two_factor_secret != null` is the same "fully set up" condition already used in `TwoFactorController::verify`. We only test `two_factor_enabled` here because the setup controller itself ensures the secret is saved when it flips that boolean — no need to double-check in the middleware.
- Order matters in middleware stack on the auth group: `auth` then `two.factor` then `daily.opening`. Reasoning: if a cashier logs in without 2FA and without today's opening, they first land on `/account` to set 2FA. Once 2FA is on, the login shortcut redirects through the daily-opening check so they still go to `daily-opening.create` as before. Putting `daily.opening` after `two.factor` also avoids a flash-message race between two competing redirects.

## Validation

- Manual route checks via `php artisan route:list --name=account --name=dashboard --name=profile` to confirm the middleware group applies.
- Feature-test equivalents already exist: run `php artisan test --compact` to ensure existing Login + Dashboard tests pass.
- Manual checks (from memory of EnsureDailyOpeningSet flow):
  1. Login as user with `two_factor_enabled=0` → lands on `/account` with status banner.
  2. From `/account`, clicking any nav link (dashboard, cash-point, transactions, reports, settings) redirects back to `/account`.
  3. On `/account` QR page, confirm a TOTP code → JSON success, refresh → user stays on `/account` showing recovery codes; nav links now work.
  4. Logout / profile edit / avatar GET still reachable during lockout.
  5. Login as user with `two_factor_enabled=1` → normal login flow unchanged (goes through TOTP challenge if enabled; then lands on daily-opening form or dashboard as before).
- Run `vendor/bin/pint --dirty --format agent` to style the new middleware + edits.
- `GetDiagnostics` on changed files.

## Risks

- **Redirect loop if account/two-factor routes themselves get bounced back:** covered by `$allowedRoutes` whitelist — the middleware checks the route name before ever redirecting, and mirrors the existing `EnsureDailyOpeningSet` whitelist that's already been de-looped. Also the middleware explicitly returns early for authenticated users with `two_factor_enabled === true` so once 2FA is on it never fires again.
- **Guest middleware (`two-factor.show/verify`) is separate** from our enforcement. That only applies *pre-login* when `two_factor_enabled=true`; our middleware runs in the `auth` group on post-login pages, so no cross-contamination.
- **Users who had 2FA then disabled it (via admin-reset or the disable button):** middleware will force them to re-enable, which is the desired behavior for "mandatory for everyone". If we later want an admin exemption, that's a 1-line role check in the middleware — out of scope for "force anyone".
- **AJAX clients hitting non-whitelisted JSON endpoints** get a clean 422 with `two_factor_required: true` and a `redirect` URL (same shape as `daily_opening_required` in the existing middleware). Frontend JSON handlers can react the same way they do for that existing flag.
