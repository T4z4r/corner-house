# Stripe — Direct Booking Payments Configuration

This guide covers the Stripe configuration required to take payments for **direct bookings** on the
Corner House website, and the webhooks the application needs.

## How the app uses Stripe

- Guests book on the website (`/book`), enter their details and are redirected to a
  **Stripe-hosted Checkout page** (redirect mode — not Payment Elements or inline intents).
- The app creates a Stripe **Checkout Session** (`mode: payment`) with a single line item for the
  full amount (stay + optional damage deposit + add-ons), charged in `GBP`.
- On return, `/book/confirmation?session_id=cs_...` verifies the session and confirms the booking.
- Stripe sends a **webhook** (`checkout.session.completed`) to confirm the payment idempotently.
- Refunds are issued manually from the admin panel (Payments → Refund); they are **not**
  webhook-driven.

Relevant code:

| Concern | Location |
| --- | --- |
| Stripe gateway | `app/Services/Payment/StripePaymentGateway.php` |
| Payment orchestration | `app/Services/Payment/PaymentService.php` |
| Webhook controller | `app/Http/Controllers/Webhooks/StripeWebhookController.php` |
| Booking/payment flow | `app/Http/Controllers/Website/BookingController.php` |
| Config | `config/services.php` (`stripe` block) |
| Webhook route | `routes/webhooks.php` → `POST /webhooks/stripe` |
| Tests | `tests/Feature/PaymentWebhookTest.php`, `tests/Feature/PublicBookingTest.php` |

---

## 1. Create or sign in to your Stripe account

1. Go to [dashboard.stripe.com](https://dashboard.stripe.com) and create an account (or sign in).
2. Switch between **Test mode** and **Live mode** with the toggle at the top right. Start in
   **Test mode** until everything works end-to-end.
3. Before taking real money, complete the account **verification** (business details, bank account /
   payout details). Stripe will not accept live payments until the account is activated.

---

## 2. Get your API keys

Dashboard → **Developers → API keys**:

| Key | Where to find it | Format |
| --- | --- | --- |
| **Publishable key** | “Publishable key” | `pk_test_...` / `pk_live_...` |
| **Secret key** | “Secret key” | `sk_test_...` / `sk_live_...` |

> Keys are shown per mode — a `pk_test_`/`sk_test_` pair only works in test mode, `pk_live_`/`sk_live_`
> only in live mode.

---

## 3. Add the keys to the app

The app reads the keys from the **database settings first**, falling back to `.env`. There are two
ways to configure them; the settings panel is easiest because it takes precedence and requires no
deploy:

### Option A — Admin settings panel (recommended)

1. Log in to the admin panel and go to **Settings → Stripe** (`/admin/settings/stripe`).
2. Set:
   - **Stripe publishable key** → `stripe_key`
   - **Stripe secret key** → `stripe_secret`
   - **Stripe webhook signing secret** → `stripe_webhook_secret`
3. Save. No deploy needed.

### Option B — Environment variables

`.env`:

```
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

Then run `php artisan config:clear`.

> **Important:** a DB setting, if set, overrides `.env`. Make sure the two do not disagree. If
> `stripe_secret` is blank in both places, the app silently falls back to a fake gateway and **no
> real Stripe charge is made**.
>
> `stripe_test_mode` exists in the settings panel as a UI indicator only; the actual mode is decided
> by whether the configured keys are `sk_test_` or `sk_live_`.

---

## 4. Create the webhook endpoint in Stripe

Dashboard → **Developers → Webhooks → Add endpoint**.

1. **Endpoint URL**
   ```
   https://cornerhousebraunston.uk/webhooks/stripe
   ```
   (Route name `webhooks.stripe`, CSRF-exempt, accepts `Stripe-Signature` header only.)

2. **Events to send** — select exactly what the app understands. The minimum (and only currently
   handled) event is:

   | Event | Purpose |
   | --- | --- |
   | `checkout.session.completed` | Marks the local payment `paid` and confirms the reservation (idempotent). |

   You may additionally subscribe to `checkout.session.async_payment_succeeded` /
   `checkout.session.async_payment_failed` for reporting, but the app does not process them yet.

3. **Signing secret** — after saving, Stripe shows a **Signing secret** (`whsec_...`). Copy it into
   the app’s `stripe_webhook_secret` setting (or `STRIPE_WEBHOOK_SECRET`). This secret is used to
   verify every request; a wrong/missing secret causes the app to return `400 Invalid payload`.

4. The endpoint responds `200 ok` when accepted, `400` when the signature does not verify.

---

## 5. Local development (Stripe CLI)

Install the [Stripe CLI](https://stripe.com/docs/stripe-cli) if not already present, then:

```bash
stripe login
stripe listen --forward-to http://localhost:8000/webhooks/stripe
```

The CLI prints a temporary `whsec_...` that you use as `stripe_webhook_secret` locally, and shows a
success message when the endpoint responds.

Replay a test event:

```bash
stripe trigger checkout.session.completed
```

---

## 6. Verify the flow end-to-end (test mode)

1. Make sure the app is using **test** keys (`sk_test_...`) and the matching test signing secret.
2. Go to the website and complete a booking: `/book` → select dates/room → fill in guest details →
   Continue to payment.
3. You are redirected to a **checkout.stripe.com** page. Pay with the standard test card:
   ```
   4242 4242 4242 4242   any future expiry    any CVC   any 3-digit postcode
   ```
4. After paying you are redirected to `/book/confirmation?session_id=cs_...`: the reservation should
   show as **paid/confirmed**.
5. Check admin **Payments** (`/admin/payments`): status `paid`, with the `cs_...` session id and
   `pi_...` payment intent recorded.
6. Check the webhook worked: Dashboard → **Developers → Webhooks** → your endpoint → **Events** for
   `checkout.session.completed` with delivery status 200.

> The webhook and the browser redirect both confirm the payment. `PaymentService::markPaid()` locks
> the row, so duplicate webhooks or a concurrent browser redirect cannot double-confirm.

---

## 7. Go-live checklist

1. Complete the Stripe account **activation/verification** (required for live payments).
2. Switch to **live mode** in the Stripe dashboard and copy the **live keys**
   (`pk_live_...` / `sk_live_...`).
3. Update the app: `stripe_key`, `stripe_secret` in Settings → Stripe (or `.env`).
4. Create/update the webhook endpoint for **live mode** at
   `https://cornerhousebraunston.uk/webhooks/stripe`, subscribe to `checkout.session.completed`,
   and store the **live** `whsec_...` in `stripe_webhook_secret`.
   Test-mode and live-mode endpoints/secrets are completely separate.
5. Make a small live test payment, check the confirmation page, admin Payments, and the webhook
   delivery log.
6. Verify a **refund** works: admin → Payments → Refund (uses the recorded `pi_...` intent).

---

## 8. Troubleshooting

| Symptom | Likely cause | Fix |
| --- | --- | --- |
| Guest never lands on a Stripe checkout page | `stripe_secret` blank → app fell back to the fake gateway | Set `stripe_secret` (Settings → Stripe) |
| Webhook returns `400 Invalid payload` | Wrong `stripe_webhook_secret`, or signature missing | Copy `whsec_...` from the webhook endpoint into the app |
| Payment is `paid` on the confirmation page but webhook logs “unknown session” | Webhook event reached before the `Payment` row existed, or session id differs | Check Stripe delivery log; the browser redirect still confirms the booking |
| Booking stays on `hold` | Webhook not delivered or signing secret wrong | Dashboard → Webhooks → endpoint → Events, check delivery attempts/retries |
| Residential `90210`/`000` charge fails in test mode | Deprecated test card number | Use `4242 4242 4242 4242` |

---

## 9. Configuration summary

| Item | Value |
| --- | --- |
| Checkout mode | Hosted Checkout Session, `mode: payment`, single line item, currency `GBP` |
| Success URL | `/book/confirmation?session_id={CHECKOUT_SESSION_ID}` |
| Cancel URL | `/book?check_in=...&check_out=...&guests=...` |
| Webhook URL | `https://cornerhousebraunston.uk/webhooks/stripe` |
| Webhook verification | `Stripe-Signature` header + `whsec_...` signing secret |
| Handled event | `checkout.session.completed` |
| Refunds | Manual from admin panel (Stripe `re_...` refund) — not webhook-driven |
| Payment provider | `stripe/stripe-php` (`^21.3`), interface `PaymentGatewayInterface` with `FakePaymentGateway` fallback |