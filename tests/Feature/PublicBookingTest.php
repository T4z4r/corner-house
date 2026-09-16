<?php

namespace Tests\Feature;

use App\Mail\NewBookingRequestMail;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use App\Services\Booking\BookingService;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'min_price_weekday'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekday', 'cast' => 'decimal:2']);
        Setting::updateOrCreate(['key' => 'min_price_weekend'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekend', 'cast' => 'decimal:2']);
        Setting::updateOrCreate(['key' => 'cleaning_fee'], ['value' => '0', 'group' => 'booking', 'label' => 'Cleaning', 'cast' => 'decimal:2']);
        Setting::updateOrCreate(['key' => 'damage_deposit'], ['value' => '0', 'group' => 'booking', 'label' => 'Deposit', 'cast' => 'decimal:2']);
        Setting::updateOrCreate(['key' => 'direct_booking_discount'], ['value' => '0', 'group' => 'booking', 'label' => 'Discount', 'cast' => 'decimal:2']);
    }

    public function test_search_lists_the_whole_house_with_server_price(): void
    {
        $property = Property::factory()->create(['name' => 'Corner House', 'capacity' => 12]);
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'name' => 'Lion Bedroom',
            'base_rate' => 100,
            'status' => 'active',
            'capacity' => 2,
        ]);
        $checkIn = now()->addDays(10)->toDateString();
        $checkOut = now()->addDays(12)->toDateString();

        $this->get(route('booking.search', [
            'property_id' => $property->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 2,
        ]))
            ->assertOk()
            ->assertSee('Corner House')
            ->assertSee('Whole house')
            ->assertSee('200.00')
            ->assertDontSee($room->name);
    }

    public function test_search_hides_the_whole_house_when_one_bedroom_is_unavailable(): void
    {
        $property = Property::factory()->create(['capacity' => 12]);
        $firstRoom = Room::factory()->create(['property_id' => $property->id, 'status' => 'active']);
        $secondRoom = Room::factory()->create(['property_id' => $property->id, 'status' => 'active']);
        $checkIn = now()->addDays(10)->toDateString();
        $checkOut = now()->addDays(12)->toDateString();

        app(BookingService::class)->create([
            'room_id' => $secondRoom->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 12,
            'status' => 'confirmed',
        ]);

        $this->get(route('booking.search', [
            'property_id' => $property->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 12,
        ]))
            ->assertOk()
            ->assertSee('No rooms available for those dates.')
            ->assertDontSee($firstRoom->name);
    }

    public function test_details_page_rejects_unavailable_room(): void
    {
        $room = Room::factory()->create(['base_rate' => 100, 'status' => 'active']);
        $checkIn = now()->addDays(10)->toDateString();
        $checkOut = now()->addDays(12)->toDateString();

        app(BookingService::class)->create([
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 1,
            'status' => 'confirmed',
        ]);

        $this->get(route('booking.details', [
            'room' => $room,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 1,
        ]))->assertRedirect(route('booking.search'));
    }

    public function test_prices_defaults_to_the_primary_room_when_no_room_is_supplied(): void
    {
        $property = Property::factory()->create(['status' => 'active']);
        $primary = Room::factory()->create(['property_id' => $property->id, 'name' => 'Lion Suite', 'status' => 'active', 'base_rate' => 100, 'is_primary' => true]);
        Room::factory()->create(['property_id' => $property->id, 'name' => 'Elephant Room', 'status' => 'active', 'base_rate' => 200]);

        $start = now()->addDays(10)->toDateString();
        $end = now()->addDays(12)->toDateString();

        $this->getJson(route('booking.prices').'?start='.$start.'&end='.$end)
            ->assertOk()
            ->assertJsonPath('room_id', $primary->id)
            ->assertJsonPath('base_amount', 200);

    }

    public function test_booking_request_requires_an_explicit_room_selection(): void
    {
        $room = Room::factory()->create(['base_rate' => 80, 'status' => 'active']);

        $this->post(route('booking.request'), [
            'check_in' => now()->addDays(14)->toDateString(),
            'check_out' => now()->addDays(16)->toDateString(),
            'guests_count' => 2,
            'guest_first_name' => 'Alex',
            'guest_last_name' => 'Guest',
            'guest_email' => 'alex@example.com',
        ])
            ->assertSessionHasErrors('room_id');

        $this->assertDatabaseCount('reservations', 0);
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_booking_request_accepts_the_room_id_from_the_website_widget_payload(): void
    {
        $room = Room::factory()->create(['base_rate' => 80, 'status' => 'active']);
        $checkIn = now()->addDays(14)->toDateString();
        $checkOut = now()->addDays(16)->toDateString();

        $this->post(route('booking.request'), [
            'roomId' => $room->id,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'name' => 'Alex Guest',
            'email' => 'alex@example.com',
            'phone' => '+44 7700 900123',
            'guests' => 2,
            'agree' => true,
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseCount('reservations', 0);
        $this->assertDatabaseHas('enquiries', [
            'type' => 'booking',
            'room_id' => $room->id,
            'name' => 'Alex Guest',
            'email' => 'alex@example.com',
            'terms_accepted' => true,
        ]);

        $enquiry = Enquiry::query()->first();
        $this->assertNotNull($enquiry->booking_hold_id);
        $this->assertDatabaseHas('booking_holds', [
            'id' => $enquiry->booking_hold_id,
            'room_id' => $room->id,
            'status' => 'active',
        ]);
    }

    public function test_booking_request_creates_a_48_hour_hold_from_the_widget_payload(): void
    {
        $room = Room::factory()->create(['base_rate' => 80, 'status' => 'active']);
        $checkIn = now()->addDays(20)->toDateString();
        $checkOut = now()->addDays(22)->toDateString();

        $response = $this->postJson(route('booking.request'), [
            'roomId' => $room->id,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'guests' => 2,
            'drinks' => true,
            'agree' => true,
        ])
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $enquiry = Enquiry::query()->first();
        $this->assertNotNull($enquiry);
        $response->assertJsonPath('redirect_url', route('booking.requested', ['enquiry' => $enquiry->id]));
        $this->get($response->json('redirect_url'))
            ->assertOk()
            ->assertSee('Your booking request has been received')
            ->assertSee('Request reference')
            ->assertSee('#'.$enquiry->id);
        $hold = $enquiry->bookingHold;

        $this->assertNotNull($hold);
        $this->assertTrue($hold->expires_at->gt(now()->addHours(47)));
        $this->assertTrue($hold->expires_at->lt(now()->addHours(49)));
    }

    public function test_booking_request_emails_the_booking_team_a_styled_enquiry_without_payment_instruction(): void
    {
        Mail::fake();

        $recipient = 'bookings@cornerhouse.test';
        Setting::updateOrCreate(['key' => 'booking_notify_email'], ['value' => $recipient, 'group' => 'booking']);

        $room = Room::factory()->create(['base_rate' => 80, 'status' => 'active', 'name' => 'Lion Bedroom']);
        $checkIn = now()->addDays(14)->toDateString();
        $checkOut = now()->addDays(16)->toDateString();

        $this->postJson(route('booking.request'), [
            'roomId' => $room->id,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+44 7700 900123',
            'guests' => 2,
            'message' => 'Celebrating a birthday.',
            'agree' => true,
        ])->assertOk();

        $enquiry = Enquiry::query()->first();
        $this->assertNotNull($enquiry);

        Mail::assertSent(NewBookingRequestMail::class, function (NewBookingRequestMail $mail) use ($enquiry, $recipient): bool {
            if (! $mail->hasTo($recipient) || $mail->enquiry->id !== $enquiry->id || $mail->room->id !== $enquiry->room_id) {
                return false;
            }

            $mail->assertSeeInHtml('New direct booking request');
            $mail->assertSeeInHtml('Jane Doe');
            $mail->assertSeeInHtml('Celebrating a birthday.');
            $mail->assertDontSeeInHtml('email the guest a payment link');
            $mail->assertDontSeeInHtml('signed rental agreement');

            return true;
        });
    }

    public function test_details_page_total_includes_damage_deposit(): void
    {
        $room = Room::factory()->create(['base_rate' => 100, 'status' => 'active', 'capacity' => 2]);
        Setting::updateOrCreate(['key' => 'damage_deposit'], ['value' => '950']);
        $checkIn = now()->addDays(10)->toDateString();
        $checkOut = now()->addDays(12)->toDateString();

        $this->get(route('booking.details', [
            'room' => $room,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 1,
        ]))
            ->assertOk()
            ->assertSee('Damage deposit')
            ->assertSee('🇰🇪 Kenya +254')
            ->assertSee('🇮🇳 India +91')
            ->assertSee('🇧🇷 Brazil +55')
            ->assertSee('data-country="gb" selected', false)
            ->assertSee('£1,150.00');
    }

    public function test_check_in_on_the_checkout_day_is_rejected(): void
    {
        $room = Room::factory()->create(['base_rate' => 100, 'status' => 'active']);
        $checkIn = now()->addDays(10)->toDateString();
        $checkOut = now()->addDays(12)->toDateString();

        app(BookingService::class)->create([
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 1,
            'status' => 'confirmed',
        ]);

        // A new stay cannot begin on the day the current guest departs.
        $this->get(route('booking.details', [
            'room' => $room,
            'check_in' => $checkOut,
            'check_out' => now()->addDays(14)->toDateString(),
            'guests' => 1,
        ]))
            ->assertRedirect(route('booking.search'))
            ->assertSessionHasErrors(['error' => 'Check-in is blocked on the day the current guest departs (no same-day turnaround).']);
    }

    public function test_check_in_the_day_after_checkout_is_allowed(): void
    {
        $room = Room::factory()->create(['base_rate' => 100, 'status' => 'active']);
        $checkIn = now()->addDays(10)->toDateString();
        $checkOut = now()->addDays(12)->toDateString();

        app(BookingService::class)->create([
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 1,
            'status' => 'confirmed',
        ]);

        $this->get(route('booking.details', [
            'room' => $room,
            'check_in' => now()->addDays(13)->toDateString(),
            'check_out' => now()->addDays(15)->toDateString(),
            'guests' => 1,
        ]))->assertOk();
    }

    public function test_hold_is_refused_when_check_in_falls_on_the_checkout_day(): void
    {
        $room = Room::factory()->create(['base_rate' => 80, 'status' => 'active']);
        $checkIn = now()->addDays(10)->toDateString();
        $checkOut = now()->addDays(12)->toDateString();

        app(BookingService::class)->create([
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 1,
            'status' => 'confirmed',
        ]);

        $this->post(route('booking.request'), [
            'room_id' => $room->id,
            'check_in' => $checkOut,
            'check_out' => now()->addDays(14)->toDateString(),
            'guests_count' => 1,
            'guest_first_name' => 'Alex',
            'guest_last_name' => 'Guest',
            'guest_email' => 'alex@example.com',
        ])
            ->assertSessionHasErrors(['error' => 'Room unavailable: Check-in is blocked on the day the current guest departs (no same-day turnaround).']);

        $this->assertSame(1, Reservation::query()->count());
        $this->assertDatabaseMissing('booking_holds', ['room_id' => $room->id]);
    }

    public function test_guest_can_pay_the_refundable_deposit_then_confirm_from_session(): void
    {
        $room = Room::factory()->create(['base_rate' => 80, 'status' => 'active']);
        Setting::updateOrCreate(['key' => 'damage_deposit'], ['value' => '950']);
        $checkIn = now()->addDays(14)->toDateString();
        $checkOut = now()->addDays(16)->toDateString();

        $result = app(BookingService::class)->create([
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 2,
            'damage_deposit' => 950,
            'status' => 'hold',
            'source' => 'direct',
        ]);

        $reservation = $result['reservation'];

        $this->get(route('booking.checkout', [$reservation->getRouteKey(), 'payment_option' => 'deposit']))
            ->assertOk()
            ->assertSee('Complete Your Payment')
            ->assertSee('Refundable Security Deposit');

        $payment = Payment::query()->where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame(950.0, (float) $payment->amount);
        $this->assertNotNull($payment->provider_session_id);

        $this->get(route('booking.confirmation', ['session_id' => $payment->provider_session_id]))
            ->assertOk()
            ->assertSee('Booking confirmed');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'confirmed',
            'payment_status' => 'partial',
        ]);
        $this->assertSame(950.0, (float) $reservation->fresh()->paid_amount);
        $this->assertSame(1, Reservation::query()->count());
    }

    public function test_checkout_defaults_to_full_payment_and_confirms_from_stripe_checkout(): void
    {
        Setting::updateOrCreate(['key' => 'damage_deposit'], ['value' => '950']);
        $reservation = Reservation::factory()->create([
            'status' => 'hold',
            'payment_status' => 'unpaid',
            'total_amount' => 1500,
            'paid_amount' => 0,
        ]);

        $this->get(route('booking.checkout', [$reservation, 'amount' => 1]))
            ->assertOk()
            ->assertViewHas('paymentOption', 'full')
            ->assertViewHas('paymentAmount', 1500.0)
            ->assertViewHas('balanceDue', 0.0)
            ->assertSee('type="radio" name="payment_option" value="full" checked', false)
            ->assertDontSee('type="radio" name="payment_option" value="deposit" checked', false)
            ->assertSee('Pay in full')
            ->assertSee('Confirm Full Payment')
            ->assertSee('No booking balance will remain after payment.')
            ->assertDontSee('Balance due before arrival');

        $payment = $reservation->payments()->sole();
        $gateway = app(PaymentGatewayInterface::class);
        $this->assertSame(1500.0, (float) $payment->amount);
        $this->assertSame(1500.0, $gateway->sessions[$payment->provider_session_id]['amount']);
        $this->assertSame(1500.0, $gateway->intents[$payment->provider_payment_id]['amount']);
        $this->assertStringContainsString('payment_option=full', $gateway->sessions[$payment->provider_session_id]['cancel_url']);

        $this->get(route('booking.confirmation', ['session_id' => $payment->provider_session_id]))
            ->assertOk()
            ->assertSee('Booking confirmed');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'payment_status' => 'paid',
            'paid_amount' => 1500,
        ]);
    }

    public function test_switching_payment_options_uses_the_correct_amount_for_both_stripe_methods(): void
    {
        Setting::updateOrCreate(['key' => 'damage_deposit'], ['value' => '950']);
        $reservation = Reservation::factory()->create([
            'status' => 'hold',
            'payment_status' => 'unpaid',
            'total_amount' => 1500,
            'paid_amount' => 0,
        ]);

        $this->get(route('booking.checkout', [$reservation, 'payment_option' => 'deposit']))
            ->assertOk()
            ->assertViewHas('paymentOption', 'deposit')
            ->assertSee('type="radio" name="payment_option" value="deposit" checked', false)
            ->assertDontSee('type="radio" name="payment_option" value="full" checked', false)
            ->assertViewHas('paymentAmount', 950.0);
        $depositPayment = $reservation->payments()->sole();

        $this->get(route('booking.checkout', [$reservation, 'payment_option' => 'full']))
            ->assertOk()
            ->assertViewHas('paymentOption', 'full')
            ->assertViewHas('paymentAmount', 1500.0);
        $fullPayment = $reservation->payments()->where('amount', 1500)->sole();
        $gateway = app(PaymentGatewayInterface::class);
        $this->assertSame(1500.0, $gateway->sessions[$fullPayment->provider_session_id]['amount']);
        $this->assertSame(1500.0, $gateway->intents[$fullPayment->provider_payment_id]['amount']);

        $this->get(route('booking.checkout', [$reservation, 'payment_option' => 'deposit']))
            ->assertOk()
            ->assertViewHas('paymentAmount', 950.0)
            ->assertViewHas('balanceDue', 550.0)
            ->assertViewHas('checkoutUrl', $depositPayment->metadata['checkout_url'])
            ->assertViewHas('paymentIntentSecret', $depositPayment->metadata['client_secret']);

        $this->get(route('booking.checkout', [$reservation, 'payment_option' => 'full']))
            ->assertOk()
            ->assertViewHas('checkoutUrl', $fullPayment->metadata['checkout_url'])
            ->assertViewHas('paymentIntentSecret', $fullPayment->metadata['client_secret']);
        $this->assertCount(2, $reservation->payments()->get());

        $this->postJson(route('booking.checkout.confirm', $reservation), [
            'payment_intent_id' => $fullPayment->provider_payment_id,
        ])->assertOk()->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'payment_status' => 'paid',
            'paid_amount' => 1500,
        ]);
    }

    public function test_checkout_rejects_an_invalid_payment_option_without_creating_a_payment(): void
    {
        $reservation = Reservation::factory()->create(['payment_status' => 'unpaid']);

        $this->getJson(route('booking.checkout', [$reservation, 'payment_option' => 'custom']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payment_option');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_a_partial_booking_can_pay_its_balance_by_card_without_losing_the_deposit(): void
    {
        $reservation = Reservation::factory()->create(['total_amount' => 1500, 'paid_amount' => 950, 'payment_status' => 'partial']);
        Payment::factory()->create(['reservation_id' => $reservation->id, 'amount' => 950, 'status' => 'paid']);
        $link = app(PaymentLinkService::class)->createForReservation($reservation);

        $this->get(route('booking.pay-link', $link->token))
            ->assertRedirect(route('booking.checkout', $reservation));
        $this->get(route('booking.checkout', $reservation))
            ->assertOk()
            ->assertViewHas('paymentAmount', 550.0)
            ->assertViewHas('balanceDue', 0.0)
            ->assertSee('Pay your remaining balance')
            ->assertDontSee('Choose how much to pay');

        $payment = $reservation->payments()->where('status', 'pending')->sole();
        $gateway = app(PaymentGatewayInterface::class);
        $this->assertSame(550.0, $gateway->intents[$payment->provider_payment_id]['amount']);
        $this->assertSame(550.0, $gateway->sessions[$payment->provider_session_id]['amount']);
        $this->assertSame('Corner House booking balance', $gateway->sessions[$payment->provider_session_id]['line_items'][0]['name']);

        $this->postJson(route('booking.checkout.confirm', $reservation), ['payment_intent_id' => $payment->provider_payment_id])
            ->assertOk();
        $this->postJson(route('booking.checkout.confirm', $reservation), ['payment_intent_id' => $payment->provider_payment_id])
            ->assertOk();

        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'payment_status' => 'paid', 'paid_amount' => 1500]);
        $this->get(route('booking.checkout', $reservation))->assertRedirect();
        $this->assertCount(2, $reservation->payments()->get());
    }

    public function test_a_partial_booking_can_pay_its_balance_through_hosted_checkout(): void
    {
        $reservation = Reservation::factory()->create(['total_amount' => 1500, 'paid_amount' => 950, 'payment_status' => 'partial']);

        $this->get(route('booking.checkout', [$reservation, 'payment_option' => 'full']))
            ->assertOk()
            ->assertViewHas('paymentAmount', 550.0);
        $payment = $reservation->payments()->sole();
        $this->get(route('booking.confirmation', ['session_id' => $payment->provider_session_id]))
            ->assertOk();
        $this->get(route('booking.confirmation', ['session_id' => $payment->provider_session_id]))
            ->assertOk();

        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'payment_status' => 'paid', 'paid_amount' => 1500]);
    }

    public function test_confirmation_page_renders_premium_summary_for_a_paid_booking(): void
    {
        $reservation = Reservation::factory()->create();

        $this->withSession(['booking.reservation_id' => $reservation->id])
            ->get(route('booking.confirmation'))
            ->assertOk()
            ->assertSee('Booking confirmed')
            ->assertSee($reservation->reference)
            ->assertSee('Your stay')
            ->assertDontSee('&amp;mdash;', false)
            ->assertSee('Payment summary')
            ->assertSee('Total')
            ->assertSee($reservation->guest->full_name)
            ->assertSee('Confirmed');
    }

    public function test_confirmation_page_renders_pending_state_for_an_unpaid_booking(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'hold',
            'payment_status' => 'unpaid',
            'base_amount' => 250,
            'total_amount' => 250,
            'paid_amount' => 0,
        ]);

        $this->withSession(['booking.reservation_id' => $reservation->id])
            ->get(route('booking.confirmation'))
            ->assertOk()
            ->assertDontSee('Booking confirmed')
            ->assertSee('Reservation received')
            ->assertSee('is still pending');
    }

    public function test_confirmation_page_renders_empty_state_without_a_reservation(): void
    {
        $this->get(route('booking.confirmation'))
            ->assertOk()
            ->assertSee('Waiting for confirmation')
            ->assertSee('could not find that booking');
    }

    public function test_stripe_checkout_receives_customer_email_and_deposit_line_item(): void
    {
        $room = Room::factory()->create(['name' => 'The Garden Suite', 'base_rate' => 100, 'status' => 'active']);
        Setting::updateOrCreate(['key' => 'damage_deposit'], ['value' => '950']);
        $checkIn = now()->addDays(25)->toDateString();
        $checkOut = now()->addDays(27)->toDateString();

        $result = app(BookingService::class)->create([
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 2,
            'guest_first_name' => 'Jane',
            'guest_last_name' => 'Doe',
            'guest_email' => 'jane@example.com',
            'damage_deposit' => 950,
            'status' => 'hold',
            'source' => 'direct',
        ]);

        $reservation = $result['reservation'];

        $this->get(route('booking.checkout', [$reservation->getRouteKey(), 'payment_option' => 'deposit']))
            ->assertOk();

        $payment = Payment::query()->where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->provider_session_id);

        $gateway = app(PaymentGatewayInterface::class);
        $session = $gateway->sessions[$payment->provider_session_id];

        $this->assertSame('jane@example.com', $session['customer_email']);
        $this->assertSame(950.0, (float) $session['amount']);
        $this->assertSame('Corner House deposit (refundable)', $session['line_items'][0]['name']);
    }

    public function test_guest_can_view_stripe_checkout_page_and_confirm_the_deposit(): void
    {
        $room = Room::factory()->create(['name' => 'The Garden Suite', 'base_rate' => 100, 'status' => 'active']);
        Setting::updateOrCreate(['key' => 'damage_deposit'], ['value' => '950']);
        $checkIn = now()->addDays(30)->toDateString();
        $checkOut = now()->addDays(32)->toDateString();

        $result = app(BookingService::class)->create([
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 2,
            'guest_first_name' => 'Sarah',
            'guest_last_name' => 'Connor',
            'guest_email' => 'sarah@example.com',
            'damage_deposit' => 950,
            'status' => 'hold',
            'source' => 'direct',
        ]);

        $reservation = $result['reservation'];

        $this->get(route('booking.checkout', [$reservation->getRouteKey(), 'payment_option' => 'deposit']))
            ->assertOk()
            ->assertSee('Complete Your Payment')
            ->assertSee('The Garden Suite')
            ->assertSee('payment-element');

        $payment = Payment::query()->where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($payment->provider_payment_id);

        $this->postJson(route('booking.checkout.confirm', $reservation->getRouteKey()), [
            'payment_intent_id' => $payment->provider_payment_id,
        ])->assertOk()->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'confirmed',
            'payment_status' => 'partial',
        ]);
    }

    public function test_checkout_hosted_option_shows_the_balance_due_when_paying_a_partial_deposit(): void
    {
        $room = Room::factory()->create(['name' => 'The Garden Suite', 'base_rate' => 800, 'status' => 'active']);
        Setting::updateOrCreate(['key' => 'damage_deposit'], ['value' => '950']);
        $checkIn = now()->addDays(14)->toDateString();
        $checkOut = now()->addDays(16)->toDateString();

        $reservation = app(BookingService::class)->create([
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 2,
            'guest_first_name' => 'Sarah',
            'guest_last_name' => 'Connor',
            'guest_email' => 'sarah@example.com',
            'damage_deposit' => 950,
            'status' => 'hold',
            'source' => 'direct',
        ])['reservation'];

        $this->get(route('booking.checkout', [$reservation->getRouteKey(), 'payment_option' => 'deposit']))
            ->assertOk()
            ->assertSee('Stripe Instant Checkout')
            ->assertSee('&mdash; the balance of &pound;', false)
            ->assertSee('is due before arrival.', false)
            ->assertDontSee('&amp;mdash;');
    }

    public function test_payment_link_redirects_to_checkout_and_expires_after_the_configured_hours(): void
    {
        $room = Room::factory()->create(['base_rate' => 80, 'status' => 'active']);
        $checkIn = now()->addDays(14)->toDateString();
        $checkOut = now()->addDays(16)->toDateString();

        $reservation = app(BookingService::class)->create([
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 2,
            'status' => 'hold',
            'source' => 'direct',
        ])['reservation'];

        $link = app(PaymentLinkService::class)->createForReservation($reservation, now()->addHours(24));

        $this->get(route('booking.pay-link', $link->token))
            ->assertRedirect(route('booking.checkout', $reservation->getRouteKey()));

        $link->update(['expires_at' => now()->subMinute()]);

        $this->get(route('booking.pay-link', $link->token))
            ->assertOk()
            ->assertSee('This payment link has expired');
    }

    public function test_api_calculates_price_and_creates_hold(): void
    {
        $room = Room::factory()->create(['base_rate' => 50, 'status' => 'active']);
        $checkIn = now()->addDays(20)->toDateString();
        $checkOut = now()->addDays(22)->toDateString();

        $this->postJson('/api/v1/booking/calculate-price', [
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 1,
        ])->assertOk()->assertJsonPath('total', 100);

        $this->postJson('/api/v1/booking/hold', [
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'session_id' => 'sess-1',
        ])->assertOk()->assertJsonStructure(['hold_token', 'expires_at']);

        $this->assertDatabaseHas('booking_holds', ['room_id' => $room->id, 'status' => 'active']);
    }

    public function test_minimum_stay_rule_blocks_short_booking(): void
    {
        $room = Room::factory()->create(['base_rate' => 100, 'min_stay' => 3, 'status' => 'active']);

        $this->post(route('booking.request'), [
            'room_id' => $room->id,
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(11)->toDateString(),
            'guests_count' => 1,
            'guest_first_name' => 'Alex',
            'guest_last_name' => 'Guest',
            'guest_email' => 'alex@example.com',
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('reservations', 0);
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_maximum_stay_rule_blocks_long_booking(): void
    {
        $room = Room::factory()->create(['base_rate' => 100, 'max_stay' => 2, 'status' => 'active']);
        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'Short stay season',
            'rule_type' => 'seasonal',
            'start_date' => now()->addDays(8),
            'end_date' => now()->addDays(20),
            'max_stay' => 2,
            'adjustment_type' => 'amount',
            'adjustment_value' => 0,
            'priority' => 5,
        ]);

        $this->post(route('booking.request'), [
            'room_id' => $room->id,
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(13)->toDateString(),
            'guests_count' => 1,
            'guest_first_name' => 'Alex',
            'guest_last_name' => 'Guest',
            'guest_email' => 'alex@example.com',
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('reservations', 0);
        $this->assertDatabaseCount('enquiries', 0);
    }
}
