# Daily Opening URL Slugs & Cash Point Card Layout — Implementation Plan

## Repository Research

### 1. Plain ID URL Problem
- **Routes** ([web.php](file:///d:/server01/wakalafeedtanstore/routes/web.php#L50-L54)): `daily-opening.show` is `GET /daily-opening/{dailyOpening}` using implicit model binding.
- **Model** ([DailyOpening.php](file:///d:/server01/wakalafeedtanstore/app/Models/DailyOpening.php#L130-L154)):
  - `getRouteKey()` returns `Y-m-d` date string (good for URL *generation* via `route('daily-opening.show', $model)`).
  - `resolveRouteBinding()` first tries to parse the URL segment as a date and look up `forAgentAndDate(cash_point()->id, $date)`.
  - **Bug:** If that date-based lookup fails *or* the segment isn't a date at all, it falls back to `parent::resolveRouteBinding($value, $field)` which matches against the numeric `id` column. This is why `/daily-opening/1` works — users can enumerate records.
- **All link-generation sites** (already use `route('daily-opening.show', $opening)` so they will emit dates once the model is consistent):
  - [cash_point/index.blade.php L33](file:///d:/server01/wakalafeedtanstore/resources/views/cash_point/index.blade.php#L33-L33)
  - [daily_opening/index.blade.php L61](file:///d:/server01/wakalafeedtanstore/resources/views/daily_opening/index.blade.php#L61-L61)
  - [daily_opening/show.blade.php L184](file:///d:/server01/wakalafeedtanstore/resources/views/daily_opening/show.blade.php#L184-L184)
  - [DailyOpeningController.php](file:///d:/server01/wakalafeedtanstore/app/Http/Controllers/DailyOpeningController.php) redirects on `store`, `create` (when today exists), and `close`.

### 2. Cash Point Session Card Problem
User pasted a snippet of the rendered "Today's Session" panel from [cash_point/index.blade.php L30-L80](file:///d:/server01/wakalafeedtanstore/resources/views/cash_point/index.blade.php#L30-L80) showing the text clipped mid-word: `· C`.

Root cause (observed in the view):
- Line 32: The status tag is rendered via inline PHP echo `{{ $todayStats['is_closed'] ? '...' : '...' }}` inside the `<h3>` string. Blade escapes HTML by default, so the `<span class="tag tag-green">Open</span>` literal is **escaped to text** (`&lt;span …`) not rendered as a tag.
- Line 9 same pattern in [show.blade.php](file:///d:/server01/wakalafeedtanstore/resources/views/daily_opening/show.blade.php#L9-L9).
- Lines 43–50: Cash-in-hand summary is a flex-wrap row of 6 `<span>` items of varying width. The last items (Expected / Current) can overflow a narrow viewport and visually clip behind the variance number because the outer `activity-row` uses a rigid two/three-column layout without a min-width guard on the text column.
- The sub-title line under the agent name at line 9–13 renders the status label then an un-spaced `· Single cash point — whole system manages this wakala only` — the tags run together on narrow widths.

## Files and Modules
- `app/Models/DailyOpening.php` — remove ID fallback in `resolveRouteBinding`, strengthen date-only resolution, add `getRouteKeyName` for clarity.
- `resources/views/cash_point/index.blade.php` — (a) use `{!! !!}` for the Open/Closed badge inside `<h3>` so HTML renders unescaped; (b) same fix for status tag in view-head subtitle; (c) widen/shield the activity-text column in the cash-in-hand row so the 6 summary spans don't collide with the variance column; (d) add spacing between the subtitle tags and the "Single cash point" suffix.
- `resources/views/daily_opening/show.blade.php` — same unescaped badge fix in the `<p class="sub">` line at line 9.
- `app/Http/Controllers/DailyOpeningController.php` — add route parameter pattern constraint to require a date-like segment (optional, defense-in-depth — actually done via model resolver so no change needed here; just verify redirects still land on the date URL).

## Implementation Steps
1. **Harden URL resolution in `DailyOpening` model**
   - Remove the final `parent::resolveRouteBinding($value, $field)` fallback at the end of `resolveRouteBinding`.
   - If the URL segment parses as a valid `Y-m-d` date but no row exists for `(agent, date)`, return `null` (→ 404) — do NOT fall back to primary key.
   - If the URL segment is a plain integer (or anything not parseable as `Y-m-d`), also return `null` (→ 404) so `/daily-opening/1`, `/daily-opening/999` hard-404 instead of leaking any record.
   - Keep the explicit-field branch (`if ($field !== null) return parent::…`) untouched so explicit binding columns still work (future proofing).

2. **Verify route key name is explicit & consistent**
   - Confirm `getRouteKey()` continues to return the formatted date. Consider adding the complementary `getRouteKeyName()` that returns the `opening_date` column name OR just keep the custom resolver — either way, the goal is `route('daily-opening.show', $opening)` always produces `/daily-opening/2026-09-20` never `/daily-opening/1`.

3. **Fix session badge & clipped cash-in-hand layout on cash point dashboard**
   - In `cash_point/index.blade.php` line 32: swap `{{` / `}}` around the ternary that emits `<span class="tag tag-…">…</span>` for `{!!` / `!!}` so the badge actually renders as HTML.
   - Same swap on the agent subtitle line 11–12 for the agent level and status tags.
   - Line 13 prefix the `· Single cash point — whole system manages this wakala only` string with a non-breaking space / leading space inside a `<span>` so it isn't glued directly to the last tag.
   - Lines 43–55: The cash-in-hand activity row wraps 6 spans + a variance column. Add `min-width: 0; flex: 1 1 auto;` to `.activity-text` wrapper, and either (a) make the outer `.activity-row` wrap on small viewports (convert last-child variance to a full-width row below via `flex-wrap: wrap` + `width: 100%` on mobile), or (b) give the variance column `flex-shrink: 0; margin-left: auto;` so it never crowds the 6 stat spans. Keep a compact look per user preference.

4. **Fix same badge-escape bug in daily opening show page**
   - In `daily_opening/show.blade.php` line 9: swap `{{` / `}}` around the tag-ternary for `{!!` / `!!}` so the Open/Closed tag is real HTML not escaped text.

5. **Format & verify**
   - Run `vendor/bin/pint --dirty --format agent` on any PHP files changed (model + controllers if touched).
   - Run `GetDiagnostics` for blade/PHP lints.
   - If tests exist for DailyOpening or CashPoint, run the narrowest set via `php artisan test --filter DailyOpening` and `--filter CashPoint`.

## Dependencies and Considerations
- `cash_point()` helper is assumed non-null inside auth-routes guarded by the `daily.opening` middleware for show/close; resolver still handles it defensively for the index page etc.
- Date parsing must be strict to `Y-m-d` so a numeric id like `1` can NEVER accidentally parse as a valid date (Carbon's `createFromFormat` with `Y-m-d` rejects short integers, so strict parsing already guards this — we just need to *not* fall back when parsing fails).
- Blade `{!! !!}` is safe here because the tag HTML is a literal string we control, not user input.

## Validation
- **URL checks:**
  - `/daily-opening/1` → 404 (not found / not leaked).
  - `/daily-opening/999` → 404.
  - `/daily-opening/2026-09-20` for a day with a recorded opening → 200, resolves to the correct row for the current agent.
  - `/daily-opening/2026-09-19` when no opening exists for that date → 404 (not silently showing some other row).
  - `route('daily-opening.show', $opening)` from tinker/view → yields `/daily-opening/YYYY-MM-DD`.
- **Rendering checks:**
  - Cash-point view-head subtitle shows actual `tag-green` / `tag-*` badges, not `<span>` text.
  - "Today's Session" panel heading shows real `<span class="tag tag-green">Open</span>` (not escaped).
  - Cash-in-hand row shows all 6 spans (Opening · Deposits · Withdrawals · Commission · Expected · Current) without any "· C" style clipping on normal viewport widths.
  - Variance amount on the right of the cash-in-hand row stays aligned and visible.
- **Formatting:** `vendor/bin/pint --dirty --format agent` passes with no further diffs.
- **Tests:** pass `php artisan test --filter=DailyOpening` / `CashPoint`.

## Risks
- **Risk — stale/existing links/bookmarks with IDs:** any bookmarked `/daily-opening/1` URL will now 404. Mitigation: this is the intended behaviour (the user asked to stop plain IDs); if needed later, a redirect from `/daily-opening/\d+` to the canonical date URL can be added — out of scope for this change.
- **Risk — over-escaping vs XSS:** `{!! !!}` used only for static tag markup we render (contains no user strings). User-visible fields (agent name, code, amounts) continue to be escaped via `{{ }}` / `@money`.
- **Risk — layout regressions on mobile:** the flex-wrap change on the activity-row should improve, not worsen, mobile rendering; visually confirm on narrow widths if reviewing via browser.
