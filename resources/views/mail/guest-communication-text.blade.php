{{ $emailBody }}

With best wishes,
The {{ \App\Models\Setting::getValue('property_name', 'Corner House') }} Team

Corner House, Main Street, Braunston, Northamptonshire NN7 7ND
{{ \App\Models\Setting::getValue('website_contact_email', config('mail.from.address')) }}