<section class="{{ request()->routeIs('cancellation') ? 'cancellation-policy' : 'page' }}" id="page-refunds" data-page="refunds">
    <div class="section">
        <div class="wrap legal">
            <h1 style="font-size:clamp(2.2rem,4.5vw,3.4rem)">Cancellation and refund policy</h1>
            <p class="lede">The cancellation fees below apply to direct bookings. Platform bookings follow the policy shown when you book.</p>
            <p class="small">Last updated: <span class="rev-date"></span></p>

            <h2>If you booked through a platform</h2>
            <p>Bookings made through Airbnb, Booking.com or Vrbo are cancelled and refunded under that platform&rsquo;s policy, through that platform. Please cancel in your account there rather than contacting us, so that the refund is processed correctly. The policy shown at the time you booked is the one that applies.</p>

            <h2>If you booked directly with us</h2>
            @php
                $noticeDays = (int) \App\Models\Setting::getValue('cancellation_notice_days', config('cancellation.cancellation_notice_days.value'));
                $feePercent = (int) \App\Models\Setting::getValue('cancellation_fee_percent', config('cancellation.cancellation_fee_percent.value'));
                $lateHours = (int) \App\Models\Setting::getValue('cancellation_late_hours', config('cancellation.cancellation_late_hours.value'));
                $lateFeePercent = (int) \App\Models\Setting::getValue('cancellation_late_fee_percent', config('cancellation.cancellation_late_fee_percent.value'));
            @endphp
            <table class="refund-table">
                <thead><tr><th>When you cancel</th><th>What you get back</th></tr></thead>
                <tbody>
                    <tr><td>Within 24 hours of booking, where the booking was made at least 7 days before check-in</td><td>Everything you have paid, including the booking fee</td></tr>
                    <tr><td>More than {{ $noticeDays }} days ({{ $noticeDays * 24 }} hours) before check-in</td><td>Everything you have paid, less the non-refundable 30% booking fee</td></tr>
                    <tr><td>Within {{ $noticeDays }} days before check-in, but more than {{ $lateHours }} hours before check-in</td><td>{{ $feePercent }}% cancellation fee on the accommodation cost</td></tr>
                    <tr><td>Within {{ $lateHours }} hours before check-in, or after check-in</td><td>{{ $lateFeePercent }}% cancellation fee on the accommodation cost</td></tr>
                </tbody>
            </table>
            <p class="small">Times are measured against the 3:00pm check-in time at the property, UK local time.</p>
            <p>The late cancellation fee takes priority within the final {{ $lateHours }} hours. Cancellation fees are not added together. Any refund is limited to the amount you have paid, less the applicable fee.</p>

            <h2>The security deposit</h2>
            <p>The security deposit is returned in full on cancellation, whenever you cancel. An uncaptured card hold is released; a deposit that has already been charged is refunded. It is separate from the accommodation cost and is never treated as part of a cancellation charge. Your bank controls when released funds become available again.</p>

            <h2>If we cancel</h2>
            <p>If we cancel your booking for any reason, or the property becomes unavailable through damage, essential repair or an event outside our reasonable control, you receive a full refund of everything you have paid, including the booking fee. We will tell you as soon as we know, and will help you find somewhere else where we can.</p>

            <h2>Changing your dates</h2>
            <p>A date change is treated as a cancellation and a new booking. In practice, if we are able to re-let your original dates we will do our best to move you at no cost, but we cannot promise this in advance. Ask us as early as you can, as the chances are much better with more notice.</p>

            <h2>Travel insurance</h2>
            <p>We do not refund outside this policy, including for illness, transport problems, weather or a change of plan. Travel insurance covering cancellation is inexpensive relative to a whole-house booking, and we recommend you arrange it at the point you book.</p>

            <h2>How refunds are paid</h2>
            <p>Refunds are made by the same method used to pay, within 10 working days of the cancellation being confirmed in writing.</p>

            <div class="cta-row"><a class="btn btn-outline" href="{{ route('home') }}#terms">Terms and conditions</a><a class="btn btn-primary" href="{{ route('home') }}#book">Back to booking</a></div>
        </div>
    </div>
</section>
