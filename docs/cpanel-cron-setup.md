# cPanel Cron Job Setup Guide

This guide explains how to set up the required cron job for Corner House's automated Beds24 synchronisation.

## Why a Cron Job is Needed

Corner House uses Laravel's task scheduler to automatically:
- **Sync bookings** from Beds24 (reservations, availability, calendar blocks)
- **Sync messages** from Beds24 (guest communications via Airbnb, Booking.com, etc.)
- **Push rates** to Beds24 (pricing updates from the platform)

These tasks run on configurable schedules managed within the admin panel. However, the scheduler itself requires a single system-level cron job to trigger it every minute.

## Step 1: Log in to cPanel

1. Navigate to your cPanel URL (typically `https://yourdomain.com:2083`)
2. Enter your cPanel username and password
3. Click **Log in**

## Step 2: Open Cron Jobs

1. In the cPanel dashboard, find the **Advanced** section
2. Click on **Cron Jobs**

## Step 3: Set Email Notifications (Optional)

cPanel sends cron job output to an email address by default.

1. In the **Cron Email** section, enter your email address
2. Click **Update Email**

> **Tip:** You can disable email notifications for individual cron jobs by adding `> /dev/null 2>&1` to the command (see Step 4).

## Step 4: Add the Cron Job

In the **Add New Cron Job** section:

### Frequency Settings

Select **Common Settings** → **Once Per Minute** (or manually set):

| Field | Value |
|-------|-------|
| Minute | `*` |
| Hour | `*` |
| Day | `*` |
| Month | `*` |
| Weekday | `*` |

### Command

Enter the following command, replacing `/path/to/corner-house` with your actual project path:

```bash
cd /path/to/corner-house && php artisan schedule:run >> /dev/null 2>&1
```

### Finding Your Project Path

To find your project path:

1. In cPanel, open **File Manager**
2. Navigate to your Corner House installation directory
3. Look at the path shown in the address bar
4. Alternatively, run `pwd` via SSH if available

Common paths:
- `/home/username/corner-house`
- `/home/username/public_html/corner-house`
- `/var/www/html/corner-house`

### Example

If your project is at `/home/john/corner-house`:

```bash
cd /home/john/corner-house && php artisan schedule:run >> /dev/null 2>&1
```

## Step 5: Verify the Cron Job

1. After adding, the cron job appears in **Current Cron Jobs**
2. Wait 1-2 minutes for it to run
3. Check the Corner House admin panel → **Schedule Settings** to confirm jobs are running

## Configuring Schedule Frequencies

Once the cron job is active, configure how often each task runs:

1. Log in to Corner House admin panel
2. Navigate to **System** → **Schedule Settings** (or **Settings** → **Schedule Settings**)
3. Configure each sync task:

| Task | Description | Recommended Setting |
|------|-------------|---------------------|
| **Booking Sync** | Pulls reservations from Beds24 | Every 5 minutes |
| **Message Sync** | Fetches guest messages | Every 5 minutes |
| **Rate Push** | Sends pricing to Beds24 | Hourly |

### Available Frequencies

- **Every 5 minutes** — Most responsive, highest API usage
- **Every 15 minutes** — Good balance for most properties
- **Every 30 minutes** — Lower API usage, slight delay
- **Every hour** — Suitable for rate push (pricing changes less frequently)
- **Twice daily** — Runs at 06:00 and 18:00
- **Once daily** — Runs at 06:00

### Disabling a Task

Toggle the **Enabled** switch off for any task you don't want to sync automatically. Manual syncs are always available from the admin panel.

## Troubleshooting

### Cron Job Not Running

1. **Verify the command** — Ensure the path is correct and `php` is accessible
2. **Check PHP path** — Some servers require the full PHP path:
   ```bash
   cd /path/to/corner-house && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
   ```
3. **Find PHP path** — Run `which php` via SSH, or check cPanel → **Select PHP Version**

### Schedule Settings Not Taking Effect

1. The schedule is evaluated when `schedule:run` executes
2. After changing settings, the next cron execution picks up the changes
3. No restart is required

### Checking if Jobs Ran

1. Go to **System** → **Audit Logs** in the admin panel
2. Look for `settings.updated` entries related to schedule changes
3. Check **Channels** → **Beds24 integrations** for `last_synced_at` timestamps

### Viewing Laravel Scheduled Tasks

Via SSH, run:
```bash
php artisan schedule:list
```

This shows all registered scheduled tasks and their next run times.

## Server Requirements

| Requirement | Minimum |
|-------------|---------|
| PHP | 8.3+ |
| Cron access | Required |
| Memory | 256MB+ recommended |
| max_execution_time | 60+ seconds |

## Security Notes

- The cron command runs as your cPanel user
- No sensitive data is exposed in the command
- The `>> /dev/null 2>&1` suppresses output (safe to use)
- Laravel's scheduler has built-in overlap prevention

## Advanced: Multiple Cron Entries

For high-traffic setups, you can run the scheduler more frequently:

```bash
* * * * * cd /path/to/corner-house && php artisan schedule:run >> /dev/null 2>&1
* * * * * sleep 30 && cd /path/to/corner-house && php artisan schedule:run >> /dev/null 2>&1
```

This runs the scheduler every 30 seconds instead of every minute.

## Support

If you encounter issues:
1. Check cPanel error logs
2. Verify PHP version meets requirements
3. Ensure the `.env` file has correct Beds24 credentials
4. Contact your hosting provider if cron access is restricted
