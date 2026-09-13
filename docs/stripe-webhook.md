# Stripe Webhook — Configuration & Handover

> **@Axel** — please send me the webhook you want me to add (the event name(s) / payload
> structure / callback URL), and I will wire it into the application and document it here.

This document describes the Stripe webhook integration currently implemented in the Corner House
application (Laravel), what is already handled, and what is needed from the Stripe account side.

## Current implementation (what already exists)

| Item | Value |
| --- | --- |
| Webhook endpoint | `POST /webhooks/stripe` |
| Route name | `webhooks.stripe` |
| Authentication | Signature verification only (no session/login) |
| CSRF | Exempt for all `webhooks/*` routes |
| Signature header | `Stripe-Signature` |
| Signing secret | Setting `stripe_webhook_secret` (falls back to `STRIPE_WEBHOOK_SECRET`) |
| Stripe API keys | Settings `stripe_key` / `stripe_secret` (fall back to `STRIPE_KEY` / `STRIPE_SECRET`) |
| Handled event | `checkout.session.completed` |
| Behaviour | Marks the matching local `Payment` as `paid`, records the PaymentIntent, confirms the reservation (idempotent — duplicate webhooks do not double-confirm) |

### How it works

1. Stripe sends a signed `POST` to `/webhooks/stripe` with a `Stripe-Signature` header.
2. The payload is verified with the signing secret (`Stripe\Webhook::constructEvent`); an invalid
   or unsigned payload returns `400`.
3. Only `checkout.session.completed` is processed (`app/Services/Payment/PaymentService.php`).
   The checkout session is matched to a local `Payment` by `provider_session_id`.
4. If the session is paid/complete, the payment is marked paid and the reservation confirmed.

Key files:

- `app/Http/Controllers/Webhooks/StripeWebhookController.php`
- `app/Services/Payment/PaymentService.php`
- `app/Services/Payment/StripePaymentGateway.php`
- `config/services.php` (`stripe` block)
- `routes/webhooks.php`
- `bootstrap/app.php` (webhooks route group + CSRF exemption)
- Tests: `tests/Feature/PaymentWebhookTest.php`

## Registering the webhook in Stripe

Dashboard → **Developers → Webhooks → Add endpoint**:

- **Endpoint URL:** `https://cornerhousebraunston.uk/webhooks/stripe`
- **Events:** select `checkout.session.completed` at minimum.
- **Version / signing secret:** copy the `whsec_...` signing secret into the `stripe_webhook_secret`
  setting (Admin → Settings → Stripe).

Local development (Stripe CLI):

```bash
stripe listen --forward-to http://localhost:8000/webhooks/stripe
```

The CLI prints `whsec_...` secret + a success message when the endpoint responds. Replay an event
with:

```bash
stripe trigger checkout.session.completed
```

Manual verification:

```bash
curl -X POST https://cornerhousebraunston.uk/webhooks/stripe \
  -H "Content-Type: application/json" \
  -H "Stripe-Signature: <sig>" \
  --data @event-payload.json
```

## Known limitations

- Only `checkout.session.completed` is handled today; refunds are triggered manually from the admin
  (Payments → Refund), not from Stripe's `charge.refunded` / `refund.*` events.
- No handling of `checkout.session.async_payment_*`, `payment_intent.*`, or `charge.*` events.

## To do / requested from Axel

- [ ] Provide the additional webhook event(s) you want added (event names + expected payload).
- [ ] Confirm the signing secret to store in `stripe_webhook_secret`.
- [ ] Confirm whether the webhook endpoint should live on the live domain only, or a staging URL too.