# Beds24 API Credentials

This guide explains how to obtain the Beds24 API credentials used by this app.

## What You Need

- A Beds24 account with permission to create API invite codes
- An invite code from Beds24
- A refresh token for long-lived access

## Account Details To Look For

On the Beds24 account page, the key values are:

- `Account Number (Owner id)`: the Beds24 account owner ID
- `Your Association Code`: the code you may need when linking or verifying the account

For your account, those values are:

- `Account Number (Owner id)`: `175307`
- `Your Association Code`: `SB175307`

The other account details are usually not needed for the API setup:

- Administrator email: `baltazarchristian49@gmail.com`
- Country: `United Kingdom`
- Account balance: `1.00 EUR`
- Current time: `29th Aug 2026, 11:58`

## Get an Invite Code in Beds24

1. Sign in to Beds24.
2. Open `Settings` > `Marketplace` > `API`.
3. Create an API invite code for the account or property you want to connect.
4. Copy the invite code.

Invite codes are meant to be exchanged once and then replaced with a refresh token.

## Exchange the Invite Code

The app can exchange the invite code for:

- a short-lived access token
- a long-lived refresh token

Use the Beds24 setup screen in the admin area and submit the invite code there.

## What Gets Saved in the App

When the invite code is exchanged, the app stores:

- `refresh_token`
- `access_token`
- `access_token_expires_at`

The refresh token is what keeps the integration working after the access token expires.

If Beds24 shows values like `Owner id` or `Association Code`, keep them handy for support or troubleshooting, but do not place them in `.env` unless the app explicitly asks for them.

## Optional Environment Variables

If you prefer to configure credentials manually, these are the relevant environment variables:

```env
BEDS24_API_URL=https://beds24.com/api/v2
BEDS24_REFRESH_TOKEN=
BEDS24_WEBHOOK_SECRET=
```

## Verify the Connection

After setup, open the Beds24 integrations page and inspect the token details.

You should see:

- token validity
- scopes
- expiry information
- request diagnostics

## Common Issues

- `Beds24 refresh token is not configured`: the invite code was not exchanged yet, or no refresh token was saved.
- `Invalid token`: the token may have expired, been revoked, or been issued with insufficient scopes.
- Missing data in listings: confirm the Beds24 token has the right scopes for bookings, properties, inventory, or accounts.

## Useful Links

- https://wiki.beds24.com/index.php/API_V2.0
- https://wiki.beds24.com/index.php/OTAs%3A_How_to_connect_to_Beds24_using_API_V2
- https://beds24.com/api/v2/#/
