{{ $emailBody }}

With best wishes,
The {{ \App\Models\Setting::getValue('property_name', 'Corner House') }} Team

Corner house, old road, Braunston, Daventry, NN11 7JB
{{ \App\Models\Setting::getValue('website_contact_email', config('mail.from.address')) }}