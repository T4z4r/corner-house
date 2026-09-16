@if (! empty($heading))
{{ $heading }}

@endif
@if (! empty($leadText))
{{ $leadText }}

@endif
@if (! empty($detailRows))
@foreach ($detailRows as $label => $value)
{{ $label }}: {{ $value }}
@endforeach

@endif
@if (! empty($message))
Message: {{ $message }}

@endif
@if (! empty($actionUrl))
{{ $actionLabel ?? 'View' }}:
{{ $actionUrl }}

@endif
@if (! empty($footerNote))
{{ $footerNote }}
@endif

With best wishes,
The {{ \App\Models\Setting::getValue('property_name', 'Corner House') }} Team

Corner House, Main Street, Braunston, Northamptonshire NN7 7ND
{{ \App\Models\Setting::getValue('website_contact_email', config('mail.from.address')) }}