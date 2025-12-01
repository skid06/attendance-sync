# Attendance Sync Scheduling Guide

## Automated Sync Every 10 Minutes

The application is configured to run `attendance:sync` automatically every 10 minutes.

## Setup Instructions

### For Linux/Unix/Mac (using cron)

1. Open the crontab editor:
   ```bash
   crontab -e
   ```

2. Add this single line (runs Laravel's scheduler every minute):
   ```
   * * * * * cd /path/to/attendance-sync && php artisan schedule:run >> /dev/null 2>&1
   ```

3. Replace `/path/to/attendance-sync` with your actual project path

4. Save and exit

**That's it!** Laravel's scheduler will handle running the sync every 10 minutes.

### For Windows (using Task Scheduler)

1. Open **Task Scheduler** (search for it in Start menu)

2. Click **Create Basic Task**

3. Name: `Laravel Scheduler`

4. Trigger: **Daily**, Start at `12:00 AM`, Recur every `1 day`

5. Action: **Start a program**
   - Program: `C:\path\to\php.exe`
   - Arguments: `artisan schedule:run`
   - Start in: `C:\path\to\attendance-sync`

6. After creating, edit the task:
   - Go to **Triggers** tab → Edit
   - Check **Repeat task every: 1 minute**
   - Duration: **Indefinitely**

7. Click OK to save

### For Laragon (Windows)

1. Create a batch file `run-scheduler.bat` in your project root:
   ```batch
   @echo off
   cd /d %~dp0
   php artisan schedule:run
   ```

2. Use Task Scheduler as above, but point to this batch file

## Recommended Sync Configuration

Update your `.env` file with these recommended settings:

```env
# Attendance Device Configuration
ATTENDANCE_DRIVER=dahua
ATTENDANCE_DEBUG=true

# Dahua Device Configuration
DAHUA_DB_CONNECTION=local_attendance
DAHUA_DB_TABLE=attendance_records
DAHUA_FETCH_MINUTES=15              # Fetch last 15 minutes (with 5 min buffer)

# Remote API Configuration
ATTENDANCE_API_URL=https://your-server.com/index.php
ATTENDANCE_API_KEY=your-api-key-here
ATTENDANCE_API_TIMEOUT=30

# Sync Settings
ATTENDANCE_SYNC_BATCH_SIZE=100      # Records per batch
ATTENDANCE_AUTO_CLEAR=false         # Don't auto-clear for Dahua (DB managed externally)
ATTENDANCE_RETRY_FAILED=true        # Retry failed syncs
ATTENDANCE_MAX_RETRIES=3            # Max retry attempts

# Timezone
APP_TIMEZONE=Australia/Brisbane
```

## Why These Settings?

### Sync Interval: Every 10 Minutes
- **Balance**: Frequent enough to keep data current, not so frequent to overload systems
- **Overlap Protection**: `withoutOverlapping()` prevents multiple syncs running simultaneously
- **Background**: Runs in background to not block other operations

### Fetch Minutes: 15 Minutes
- **Buffer**: 5-minute buffer ensures no records are missed between syncs
- **Safety**: If one sync fails, the next one will catch the missed records
- **Efficiency**: Not too large to avoid re-processing old records

### Batch Size: 100 Records
- **Network**: Good balance for API requests
- **Memory**: Won't overload PHP memory
- **Speed**: Fast enough for most scenarios
- **Adjust**: Increase to 200-500 if you have high traffic

### Auto-Clear: FALSE for Dahua
- **Database-based**: Dahua reads from a database, not a device buffer
- **External Management**: Let Dahua manage its own data retention
- **Safety**: Prevents accidental data loss

### Retry: TRUE
- **Reliability**: Handles temporary network issues
- **Max Retries**: 3 attempts prevents infinite loops

## Monitoring

### Check if scheduler is running:
```bash
# View recent cron logs
tail -f storage/logs/attendance-sync-cron.log
```

### Check sync status:
```bash
# View Laravel logs
tail -f storage/logs/laravel.log
```

### Test the scheduler manually:
```bash
# Run scheduler once (simulates what cron does)
php artisan schedule:run

# Run sync command directly
php artisan attendance:sync
```

### View scheduled tasks:
```bash
# List all scheduled tasks
php artisan schedule:list
```

## Troubleshooting

### Scheduler not running
- **Check cron**: `crontab -l` to verify cron entry exists
- **Check permissions**: Ensure PHP can write to `storage/logs/`
- **Check path**: Verify project path in cron is correct

### No records syncing
- **Check logs**: Look for errors in `storage/logs/laravel.log`
- **Increase fetch time**: Try `DAHUA_FETCH_MINUTES=60` temporarily
- **Test manually**: Run `php artisan attendance:sync` to see immediate results

### Records syncing multiple times
- **Adjust fetch time**: Reduce `DAHUA_FETCH_MINUTES` to 12
- **Check scheduler**: Only one cron entry should exist
- **Check overlapping**: Ensure `withoutOverlapping()` is in schedule

## Alternative Sync Intervals

If you need different intervals, edit `routes/console.php`:

```php
// Every 5 minutes
->everyFiveMinutes()

// Every 15 minutes
->everyFifteenMinutes()

// Every 30 minutes
->everyThirtyMinutes()

// Every hour
->hourly()

// Custom (every X minutes)
->cron('*/10 * * * *')  // Every 10 minutes
```

## Performance Optimization

For high-volume environments (1000+ records per sync):

```env
ATTENDANCE_SYNC_BATCH_SIZE=500      # Larger batches
DAHUA_FETCH_MINUTES=12              # Tighter window
ATTENDANCE_API_TIMEOUT=60           # Longer timeout
```

For low-volume environments (< 50 records per sync):

```env
ATTENDANCE_SYNC_BATCH_SIZE=50       # Smaller batches
DAHUA_FETCH_MINUTES=20              # Wider safety margin
ATTENDANCE_API_TIMEOUT=15           # Shorter timeout
```

## Security Notes

1. **Logs contain sensitive data** - Ensure `storage/logs/` is not web-accessible
2. **API key in .env** - Never commit `.env` to version control
3. **File permissions**: `chmod 644 .env` to protect configuration
4. **Log rotation**: Set up logrotate to prevent disk space issues

Example logrotate config (`/etc/logrotate.d/laravel-attendance`):
```
/path/to/attendance-sync/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
}
```
