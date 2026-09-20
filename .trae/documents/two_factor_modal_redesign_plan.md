# Two-Factor Authentication Card-to-Modal Redesign Plan

## Repository Research

Current state of [account/index.blade.php](file:///d:/server01/wakalafeedtanstore/resources/views/account/index.blade.php):

- **Enabled state (lines 60–68):** Compact summary + 2 buttons — "Regenerate recovery codes" and "Disable two-factor" — both already use the existing password-confirm modal pattern. That part is fine.
- **Disabled state (lines 69–102):** The entire 2FA setup experience lives inline inside the panel body:
  1. Intro paragraph ("Add an extra layer of security…").
  2. 3-step setup instructions ordered list: install app, add account, verify code.
  3. Full setup card with dashed border: QR code SVG (rendered by `/vendor/qrcode/qrcode.js`), setup key grouped in quads with "Copy key" button, otpauth URI monospaced paragraph.
  4. Bottom form with 6-digit "Verification code" input + a page-level "Verify & enable" submit button.
  5. The inline form is wired via `[data-2fa-confirm-form]` listener at lines 273–282. It POSTs JSON to `route('account.two-factor.confirm')`, uses the shared `submitForm()` helper (app.blade.php L1008+), and on success pipes recovery codes into `showRecoveryCodes()` modal.
- **Modal infrastructure:** Page already ships 2 modal patterns we reuse:
  1. Generic password-gated action modal (`passwordConfirmModal`) used by regenerate/disable.
  2. Recovery codes result modal (`recoveryCodesModal`) used on confirm/regenerate success.
  3. Helpers: `openModal(id)`, `closeModal(id)`, `submitForm(form, …)`, `copyText(text, btn)`, `toast()`, `CSRF_TOKEN` — all available from the app layout.
  4. QR generator: `qrcode(0, 'M')` from `/vendor/qrcode/qrcode.js` via `@section('scripts')`.
- **Backend unchanged by redesign:** `AccountController::confirmTwoFactor` and `disableTwoFactor` stay exactly as-is because the request shape is unchanged (`code` for confirm, `current_password` for disable). We only change how the frontend prompts for them.
- No `.ai/rules` directory, so no extra rules to load.

User's intent: **Turn the disabled-state inline setup into a popup modal**. The 2FA panel itself should become a compact "Off / Add an extra layer… / [Enable 2FA]" card, and clicking that primary CTA should pop up a modal that contains all the QR, setup key, otpauth URI, and Verify & enable form. Keep the enabled-state panel, password-confirm modal, and recovery-codes result modal exactly as they are today (no scope creep on disable/regen flows).

## Files and Modules

- `resources/views/account/index.blade.php`: Rewrite 2FA panel's disabled branch + add a new `enableTwoFactorModal` markup, plus add modal-open JavaScript and move QR generation + 2FA confirm submission into the modal. Leave enabled branch, password modal, and recovery codes modal unchanged unless touched by modal refactor.

## Implementation Steps

1. **Shrink the disabled-state panel** to a compact card (mirror the enabled-state compactness):
   - Title row `Two-factor authentication` + `tag-gold Off` badge (unchanged).
   - Panel body: the intro paragraph ("Add an extra layer of security. Once enabled, every sign-in will also require a six-digit code from an authenticator app.").
   - Single primary CTA button: "Set up two-factor" → opens the new modal.

2. **Add new modal markup (bottom of page, next to password/recovery modals)** with id `enableTwoFactorModal`:
   - **Modal head** title "Set up two-factor authentication" + close ✕.
   - **Modal body** (what used to live in the panel body):
     a. 3-step ordered list: install authenticator app → add new account by key or scan URI → enter 6-digit code to verify & enable.
     b. Setup card (keep existing `background:var(--sand-100);border:1px dashed var(--line);…` styling):
        - Centered `#otpauthQr` SVG block + subtitle "Scan this code with your authenticator app."
        - "Setup key (Manual entry)" row with quadded code + "Copy key" button.
        - "otpauth URI (apps that offer \"Scan with camera\")" + `<code>` URI block.
     c. Verify form (`data-2fa-confirm-form` as today, POST to `route('account.two-factor.confirm')`):
        - Field label "Verification code".
        - Input `inputmode=numeric maxlength=6 placeholder="6-digit code"` (unchanged).
        - Primary submit button "Verify & enable".
   - **Modal foot** (optional, for UX consistency):
     - "Cancel" secondary → closes modal.
     - Keep the submit button at the bottom near the input instead — matches the user's pasted mock ("Verify & enable" aligned with the verification code row, not a separate modal-foot row). So move it above the modal-foot area and make the foot a secondary cancel-only area.

3. **Refactor the scripts block accordingly:**
   - Add a small opener function or inline `onclick="openModal('enableTwoFactorModal')"` on the new "Set up two-factor" button.
   - The QR generator script currently reads `otpauthQr` unconditionally on page load. Since the element now lives in a hidden modal, keep it as-is — `qrcode.js` generates the SVG fine inside `display:none` because it only touches the DOM tree, not layout. The SVG dimensions are explicit from `createSvgTag(4,2)`.
   - Keep the existing `[data-2fa-confirm-form]` listener lines 273–282 **unchanged** because the form's POST request shape and `done: showRecoveryCodes` callback still apply identically. Confirming a code in the modal uses the same endpoint and same success path.

4. **Tidy-up**: ensure:
   - The `otpauthQr` element still has `data-uri="{{ TwoFactor::otpauthUri($pendingSecret, $user->email) }}"` (required for JS QR generation).
   - The "Copy key" `onclick="copyText('{{ $pendingSecret }}', this)"` is preserved verbatim.
   - The 2FA confirm form still POSTs to `route('account.two-factor.confirm')` with CSRF.
   - Modal backdrop/markup matches existing modal classes (`modal-backdrop` wrapping `.modal`, with `.modal-head/.modal-body/.modal-foot`), so the existing `openModal` / `closeModal` + backdrop styles apply identically.

## Dependencies and Considerations

- No backend changes — endpoint request/response shapes are untouched, so the existing `two_factor_required` middleware guard and confirm/disable controllers are unaffected.
- The `$pendingSecret` session variable (set by `AccountController::index` if missing) is still required in every case. Because the Account page itself is **already whitelisted** in `EnsureTwoFactorEnabled`, a user forced here by mandatory login lockout will have `$pendingSecret` ready before they ever click "Set up two-factor" → no stale-secret risk.
- **Accessibility / UX:** Modal now opens only on explicit user click. The status banner ("Set up two-factor authentication before you continue.") already tells mandatory-lockout users what to do, so the inline-off card + one primary CTA is the most compact + clearest path.
- Recovery codes modal still triggers exactly when it used to (on successful verify-code submission) because the existing `submitForm(...) done: showRecoveryCodes(data.recovery_codes)` callback is reused verbatim.
- Password modal + disable/regen buttons: untouched in this refactor; they only appear in the enabled state, which we leave alone.
- `submitForm()` and CSRF token are provided by `layouts/app.blade.php`; no new imports needed.

## Validation

- Manual smoke tests:
  1. Login as user with `two_factor_enabled=0` → `/account` shows compact "Off" card, no QR in page body.
  2. Click "Set up two-factor" → modal appears with all 3 steps, QR, setup key, Copy key button, otpauth URI, verification code input + Verify & enable button.
  3. Click "Copy key" → button shows "Copied ✓" for ~1.6 s, clipboard has contiguous base32 secret.
  4. Enter valid 6-digit code → Verify & enable → POST succeeds → enable modal closes → recovery codes modal pops up showing the 8 codes → "I've saved my codes" reloads → page now shows 2FA "Enabled" card with Regenerate/Disable buttons.
  5. Enter invalid 6-digit code → toast error, no modal close, form keeps state so user can retry without restarting.
  6. Cancel button and ✕ close: modal goes away. Reopening regenerates the **same** QR (pendingSecret is session-scoped, not per-click) which is desired — apps "add account" only once.
- Run `php artisan test --compact`. No backend changes, so 84/85 tests still pass (only unrelated ExampleTest failure).
- Run `vendor/bin/pint --dirty --format agent` + `GetDiagnostics` on the edited blade file.
- Clear `view:clear` cache so the refactored blade is picked up immediately in staging.

## Risks

- **QR generates inside hidden modal:** QR library writes raw SVG; it doesn't measure layout so this is fine. If it *did* render empty (won't happen here), we'd move the `qrcode()` call into a run-on-open block inside a "first open" listener. Not expected to be needed.
- **Modal not closing on success:** Reusing existing `submitForm` callback that called `showRecoveryCodes` without closing the setup modal previously. Since both modals use the shared `modal-backdrop` + z-index stack we want the setup modal to close before showing recovery codes. We add a one-line call: in the 2FA confirm form's `done` callback, before the existing `setTimeout(showRecoveryCodes,…)` line, also call `closeModal('enableTwoFactorModal')` so the user only sees the recovery codes modal on top. This is an improvement over the old inline-form UX, not a behavior regression.
- **Stale `$pendingSecret` when user reloads:** Account page intentionally resets/keeps the secret session-wide. If a user loses it they can reload `/account` and get the same one — that's already the current behavior and not affected by the modal move.
- **Backdrop click closing:** Existing `openModal/closeModal` only toggle `.show` class; whether clicking backdrop dismisses depends on CSS. All other modals use this same pattern so we inherit existing behavior, no surprises.
- **Scope creep:** Enabled state buttons (Regenerate/Disable), their password modal, and the sessions panel are explicitly out of scope. If the user wants those redesigned too, it's a separate pass.
