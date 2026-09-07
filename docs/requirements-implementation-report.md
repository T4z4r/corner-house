# Requirements Implementation Report

**Project:** Corner House
**Last updated:** 7 September 2026
**Scope:** Booking rules, seasonal/weekend pricing, dynamic pricing, automated guest messages.

## Status summary

| # | Requirement | Status |
|---|---|---|
| 1 | Booking rules (24h notice, no same-day check-in, checkout-day block, holiday min-stay, long-stay discounts) | **Implemented** — all gaps closed on 7 Sep 2026 |
| 2 | Seasonal/weekend pricing (5% UK holiday uplift) | **Implemented** for UK bank holidays + festive window + school holidays; client-specific seasonal dates still to confirm |
| 3 | Dynamic pricing | Rule engine implemented (incl. manual override); automated demand pricing is a scope decision pending |
| 4 | Automated guest messages | Implemented for email; Beds24/OTA outbound messaging not yet wired (flow pending agreement) |

---

## Fix round — 7 September 2026 (what changed)

All verification performed against a copy of the live database; every change is covered by a test.

### 1. Checkout-day turnaround now enforced (was a gap)
- `AvailabilityService::isRoomAvailable` rejects any check-in that falls on a date when an active reservation checks out of the same room, so the departing guest's checkout day stays blocked — `app/Services/Availability/AvailabilityService.php:41-52`.
- Every booking stage reuses this check (search listing, details page, pay/hold via `BookingHoldService::createHold` → `assertAvailable`), so one rule protects the whole direct-booking flow.
- Admin edit/rename flows are unaffected (`ignoredReservationIds`); the existing today-edge message ("being turned over") still runs first — `app/Http/Controllers/Website/BookingController.php:84-93`.

### 2. Advance-notice copy aligned to the enforced 24 hours (was a mismatch)
- Enforcement has always been 24h (`min_advance_days = 1`); the public copy said 48h.
- Copy fixed in `database/seeders/PropertySeeder.php:40` and `resources/views/website/property.blade.php:118`.
- Live database updated to match: `min_advance_days` 2 → 1 and property `custom_rules` 48h → 24h text.

### 3. Long-stay discounts activated in production and published
- The four `length_of_stay` pricing rules (4n −10%, 7n −25%, 14n −30%, 28n −35%) were seeded into the live database (they previously existed only in `PricingSeeder`/tests) — mirrors `database/seeders/PricingSeeder.php:74-77,82-108`.
- Discount tiers now appear on the public booking-rules page — `database/seeders/SettingsSeeder.php:230` (Length of stay group).

### 4. School holidays treated as part of the 5% weekend uplift
- New JSON setting `school_holiday_periods` (`{label, start, end}`) seeded with representative England term holidays for 2026/2027 — `database/seeders/SettingsSeeder.php:45`, defaults in `defaultSchoolHolidayPeriods()` `:190-206`. Admin-editable from Settings → Pricing (JSON textarea).
- Engine method renamed `isUKBankHolidayWeekend` → `isWeekendUpliftPeriod`; a weekend (Fri–Sun) inside a school-holiday window now receives the same +5% as a bank-holiday weekend — `app/Services/Pricing/PricingEngine.php:518-544`; window membership via `isSchoolHolidayDay()` `:546-570`.
- Uplift still never stacks on a manual override, explicit calendar rate, or event/holiday rule (`PricingEngine.php:268-278`).
- Live database updated: the uplift settings that were missing (`holiday_weekend_uplift_enabled = 1`, `holiday_weekend_uplift = 5`) were inserted, and `school_holiday_periods` created.

### 5. Live database changes applied
Updated via a temp-copy workflow (writes on a copy, then swapped in) — `database/database.sqlite` is not tracked by git. Original file backed up at `%TEMP%\opencode\live-db-original-backup.sqlite`.
- Settings: `min_advance_days` → `1`; inserted `holiday_weekend_uplift_enabled = 1`, `holiday_weekend_uplift = 5`, `school_holiday_periods`.
- Pricing rules: 4 × `length_of_stay` tiers (property-wide, recurring).
- Property `custom_rules`: 48h → 24h advance notice.
- `website_booking_rules` JSON: long-stay discount bullet added to the Length of stay group.

### 6. Tests added
- `tests/Feature/PublicBookingTest.php:64` `test_check_in_on_the_checkout_day_is_rejected`
- `tests/Feature/PublicBookingTest.php:89` `test_check_in_the_day_after_checkout_is_allowed` (control)
- `tests/Feature/PublicBookingTest.php:111` `test_hold_is_refused_when_check_in_falls_on_the_checkout_day`
- `tests/Feature/PricingEngineTest.php:545` `test_weekend_uplift_applies_during_school_holidays`
- `tests/Feature/PricingEngineTest.php:565` `test_school_holiday_uplift_is_off_when_disabled`

Verification: `PricingEngineTest` (26) and `PublicBookingTest` (9) pass, plus all booking/hold/calendar suites. Full Feature run: 312 passed / 8 failed — all 8 are pre-existing, unrelated to these changes (2× AdminResourcesTest mailto asserts, Beds24 vrbo nav-active, iCal `DTEND`/cache-header asserts, weather chat fixture drift, Beds24 network 500 in MessageInbox, and the in-progress enquiries widget date-serialization test). Pint (`--dirty`) clean.

---

## 1. Booking rules

### 1a. 24-hour advance booking notice — implemented
- Setting `min_advance_days = 1` — `SettingsSeeder.php:31`.
- Enforced at both direct-booking stages: check-in rejected when before `today + min_advance_days` — `BookingController.php:78-82` (details) and `:145-149` (holdAndPay).
- Copy now matches (24 hours): `PropertySeeder.php:40`, `property.blade.php:118`, live `min_advance_days` = 1.

### 1b. No same-day check-in — implemented (as the booking-level minimum)
- Same-day check-in is impossible because `min_advance_days` requires check-in ≥ tomorrow; combined with the checkout-day block (1c) this is fully enforced.

### 1c. Checkout day kept blocked (no same-day turnaround) — automated
- `AvailabilityService::isRoomAvailable` rejects check-in on an active reservation's checkout date — `AvailabilityService.php:41-52`.
- Covers search, details, pay/hold; respected by admin edits via `ignoredReservationIds`; today-edge message in `BookingController.php:84-93`.
- Tests: `PublicBookingTest.php:64` (rejected), `:111` (hold refused), `:89` (day-after control).

### 1d. Minimum 3-night stay on UK bank holidays, Christmas and New Year — implemented
- Settings: `min_stay_nights = 2`, `min_stay_bank_holiday_nights = 3` — `SettingsSeeder.php:32-33`.
- Raised to 3 when any night falls on a bank-holiday weekend or the festive window (24 Dec–1 Jan) — `PricingEngine.php` `minimumStayForRange()` `:90`, `isBankHolidayWeekend()` `:447`, `isFestivePeriod()` `:572`.
- Enforced on the website booking flow — `BookingController.php:97-99`, `:171-173`.
- Public copy reflects it — `SettingsSeeder.php:229`.
- Tests: `PricingEngineTest.php:495` `test_festive_period_raises_minimum_stay_to_three`; recurring rule min-stay `:418`.

### 1e. Long-stay discounts (4n 10%, 7n 25%, 14n 30%, 28n 35%) — implemented and live
- Seeded tiers, largest qualifying tier wins — `PricingSeeder.php:74-77,82-108` (property-wide, recurring, −10/−25/−30/−35%).
- Applied per stay and stacked with the direct-booking discount — `PricingEngine.php:63-72`, `lengthOfStayDiscountPercent()` `:210`.
- Test: `PricingEngineTest.php:440` `test_length_of_stay_rule_applies_highest_qualifying_discount_tier`.
- Published in public copy — `SettingsSeeder.php:230`; rules live in the production database (see Fix round §3).

---

## 2. Seasonal / weekend pricing — implemented; client dates outstanding

- 5% uplift configured (`holiday_weekend_uplift_enabled = 1`, `holiday_weekend_uplift = 5` — `SettingsSeeder.php:43-44`); now present in the live database.
- Engine applies **+5% on Fri/Sat/Sun** within UK bank-holiday weekends, the festive window (20 Dec–2 Jan), or a school-holiday window — `PricingEngine.php:268-278`, `isWeekendUpliftPeriod()` `:518`.
- School holidays are a JSON setting `school_holiday_periods`, default England 2026/2027 windows, admin-editable (Settings → Pricing) — `SettingsSeeder.php:45`, `:190-206`.
- Never stacked over a manual override, calendar price block, or event/holiday rule — `PricingEngine.php:268-278`.
- UK bank holidays computed programmatically (E&W, incl. weekend substitutes) — `PricingEngine.php` `ukBankHolidaysForYear()` `:483`, `isBankHolidayDate()` `:499`.
- Tests: `PricingEngineTest.php:518` (bank-holiday weekend), `:578` (skips event/explicit rates), `:545` (school holiday), `:565` (disabled).
- Base seasonal/weekend prices are regularly rule-driven (weekday £550, weekend £625 → £645 from Apr 2027) — `PricingSeeder.php:34-52`.

> **Action needed from client:** review the seeded school-holiday windows (Settings → Pricing) and supply any client-specific seasonal date windows as recurring `seasonal` rules (admin Pricing, incl. AI-assisted generation) or per-night calendar blocks.

---

## 3. Dynamic pricing — mechanics implemented; automated demand forecasting is a scope decision

What exists:
- Rule priority: **manual override > event > holiday > seasonal > occupancy > demand > competitor > base** — `PricingEngine.php:20-28` (documented on the admin Pricing page).
- Rule types: `seasonal, event, holiday, occupancy, last_minute, competitor, demand, length_of_stay, weekday` — `database/migrations/2026_08_28_161215_create_pricing_rules_table.php:16`.
- Occupancy tier gates on an occupancy threshold — `PricingEngine.php:339-343`.
- Competitor tier averages the nearest/most-recent `CompetitorRate` captures — `PricingEngine.php:426-445`.
- Manual override at the highest priority: `PricingOverride` rows (per-night, e.g. Beds24 import) — `PricingEngine.php:374-383`; explicit per-night calendar rates (admin Calendar → Add Item) — `:390-410`.
- AI-assisted seasonal-rule generator — `app/Services/Pricing/SeasonalPricingAutomationService.php`.

What is **not** in place: live pricing driven by real-time demand signals, occupancy capture, or event feeds — the tiers are configurable rules, not a forecasting engine.

> **Decision needed from client:** include automated dynamic pricing in the current scope (auto-occupancy capture, demand on live booking volume, event/competitor automation) or treat as a later phase. The manual-override capability above is available in the meantime.

---

## 4. Automated guest messages — implemented (email); channel/messaging flow pending agreement

Trigger automation:
- **Booking + payment confirmation**: dispatched on confirmation — `app/Jobs/SendBookingConfirmationJob.php`, triggered from `app/Services/Booking/BookingService.php:301-306`.
- **Pre-arrival** (day before check-in): scheduled daily 09:00 — `routes/console.php:21`, `app/Jobs/SendPreArrivalMessageJob.php`.
- **Check-in** (day of arrival): scheduled daily 08:00 — `routes/console.php:22`, `app/Jobs/SendCheckInNotificationJob.php`.
- **Check-out** (day of departure): scheduled daily 08:30 — `routes/console.php:23`, `app/Jobs/SendCheckoutNotificationJob.php`.

Templates & delivery:
- Five event templates seeded with full content — `database/seeders/CommunicationTemplateSeeder.php`; editable and viewable in admin (Communications) — `app/Http/Controllers/Admin/CommunicationController.php`.
- Delivery is **email only** (SMTP `GuestCommunicationMail`); `sms`/`whatsapp` channels are unsupported and marked failed with an explicit reason — `app/Services/Notification/NotificationService.php:98-132`.
- Idempotent (a delivered email is never re-sent on scheduler re-runs) — `NotificationService.php:41-54`.
- Per-event feature flags, all enabled by default — `SettingsSeeder.php:45-50`, gating in `NotificationService.php:158-172`.

Channel aspects:
- Inbound guest messages sync from Beds24 (`app/Jobs/SyncBeds24MessagesJob`, setting `schedule_beds24_sync_messages_*`); website contact form inbound with optional AI auto-reply — `app/Services/Notification/GuestMessageService.php`.
- **Outbound replies back through Beds24 → Airbnb/Booking.com/Vrbo are not yet wired.**

> **Action needed (per requirement):** confirm what can be achieved through the Beds24 API and the connected channels, then agree the final messaging flow. The direct-booking email flow (confirmation → pre-arrival → check-in → check-out) is live and template-driven.

---

## Decision/action log

| Item | Owner | Action |
|---|---|---|
| ~~48h vs 24h advance notice~~ | Done (7 Sep) | Aligned to 24 hours: code, site copy, live `min_advance_days` = 1 |
| ~~Checkout-day turnaround protection~~ | Done (7 Sep) | Automated in `AvailabilityService::isRoomAvailable` |
| ~~Long-stay discounts in public copy + live rules~~ | Done (7 Sep) | Published on booking-rules page; 4 tiers seeded into production |
| ~~School holidays in the 5% uplift~~ | Done (7 Sep) | `school_holiday_periods` setting + engine support + uplift settings inserted live |
| School-holiday date windows | Host | Confirm/adjust the seeded England windows (Settings → Pricing) |
| Seasonal date windows | Host | Supply client-specific dates as `seasonal` rules or blocks |
| Automated dynamic pricing scope | Host | In current scope or later phase? |
| Beds24/OTA messaging capabilities | Dev + Host | Confirm API/channel options; agree flow; then wire outbound |