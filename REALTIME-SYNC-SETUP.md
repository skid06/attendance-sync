# Real-Time Attendance Sync Setup Guide

## Overview

The real-time sync monitors your Dahua attendance database continuously and syncs new records to the remote server within **30 seconds** of when they appear in the database.

## How It Works

1. **Polls database every 30 seconds** for new records
2. **Tracks last sync timestamp** in `storage/app/last-sync-timestamp.txt`
3. **Syncs immediately** when new records are found
4. **Continues running** until stopped manually or by system shutdown
5. **Logs everything** to `storage/logs/laravel.log`

## Windows Task Scheduler Setup

### Step 1: Create a Batch File

Create a file called `start-realtime-sync.bat` in your project root:

```batch
@echo off
cd /d %~dp0
echo Starting Real-Time Attendance Sync...
echo Press Ctrl+C to stop
php artisan attendance:sync-realtime
```

Save this file in: `C:\path\to\attendance-sync\start-realtime-sync.bat`

### Step 2: Configure Windows Task Scheduler

1. **Open Task Scheduler**
   - Press `Win + R`
   - Type `taskschd.msc`
   - Press Enter

2. **Create a New Task** (not Basic Task)
   - Click "Create Task..." in the right panel
   - Name: `Attendance Real-Time Sync`
   - Description: `Continuously syncs attendance data from Dahua device to remote server`

3. **General Tab Settings**
   - ✅ Run whether user is logged on or not
   - ✅ Run with highest privileges
   - Configure for: `Windows 10` (or your version)

4. **Triggers Tab**
   - Click "New..."
   - Begin the task: `At startup`
   - ✅ Enabled
   - Click OK

5. **Actions Tab**
   - Click "New..."
   - Action: `Start a program`
   - Program/script: `C:\path\to\php.exe`
     - For Laragon: `C:\laragon\bin\php\php-8.x\php.exe`
     - For XAMPP: `C:\xampp\php\php.exe`
   - Add arguments: `artisan attendance:sync-realtime`
   - Start in: `C:\path\to\attendance-sync`
   - Click OK

6. **Conditions Tab**
   - ❌ Uncheck "Start the task only if the computer is on AC power"
   - ❌ Uncheck "Stop if the computer switches to battery power"

7. **Settings Tab**
   - ❌ Uncheck "Stop the task if it runs longer than:"
   - If the task is already running: `Do not start a new instance`
   - ✅ Allow task to be run on demand

8. **Click OK** to save the task
   - You may be prompted for your Windows password

### Step 3: Start the Task

**Option 1: Reboot** (task will auto-start at boot)

**Option 2: Start manually**
- Right-click the task in Task Scheduler
- Click "Run"

### Step 4: Verify It's Running

**Check Task Scheduler:**
- Task Status should show "Running"
- Last Run Result should show "The operation completed successfully (0x0)"

**Check Logs:**
```bash
tail -f storage/logs/laravel.log
```

You should see:
```
[2025-12-01 10:00:00] local.INFO: Real-time sync started
[2025-12-01 10:00:00] local.COMMENT: Checking for new records... (Loop #1)
[2025-12-01 10:00:30] local.COMMENT: Checking for new records... (Loop #2)
```

## Alternative Setup Methods

### Method 1: Using Batch File (Simple)

1. Double-click `start-realtime-sync.bat`
2. Keep the window open
3. To stop: Press `Ctrl+C` in the window

**Pros:** Easy to test
**Cons:** Stops when you close the window or log out

### Method 2: Using NSSM (Windows Service)

For production environments, use NSSM to run as a Windows Service:

1. Download NSSM from https://nssm.cc/download
2. Extract `nssm.exe` to a folder
3. Run as Administrator:
   ```
   nssm install AttendanceSync
   ```
4. Configure:
   - Path: `C:\path\to\php.exe`
   - Startup directory: `C:\path\to\attendance-sync`
   - Arguments: `artisan attendance:sync-realtime`
5. Click "Install service"
6. Start the service:
   ```
   nssm start AttendanceSync
   ```

**Pros:** Runs as a proper Windows service, auto-restarts on failure
**Cons:** Requires NSSM installation

## Configuration

### Adjust Poll Interval

Default is 30 seconds. To change:

**Edit the Task Scheduler action arguments:**
```
artisan attendance:sync-realtime --interval=15
```

Options:
- `--interval=15` - Check every 15 seconds (faster)
- `--interval=60` - Check every 60 seconds (slower, less resource usage)

### Adjust Batch Size

Default is 100 records per batch. To change:

```
artisan attendance:sync-realtime --batch-size=200
```

## Monitoring

### View Logs in Real-Time

**PowerShell:**
```powershell
Get-Content storage\logs\laravel.log -Wait -Tail 50
```

**Command Prompt:**
```batch
powershell Get-Content storage\logs\laravel.log -Wait -Tail 50
```

### Check Last Sync Timestamp

```batch
type storage\app\last-sync-timestamp.txt
```

This shows the Unix timestamp of the last successful sync.

### View Task History

1. Open Task Scheduler
2. Find your task
3. Click the "History" tab (enable if disabled)

## Troubleshooting

### Task Shows "Running" but Nothing Happens

**Check logs:**
```bash
tail storage/logs/laravel.log
```

**Common issues:**
- Database connection failed
- API connection failed
- Wrong PHP path in Task Scheduler

### Task Stops After a While

**Check Task Scheduler Settings:**
- Uncheck "Stop the task if it runs longer than..."
- Set "If the task fails, restart every: 1 minute"
- Attempt restart up to: 3 times

### High CPU Usage

**Increase poll interval:**
```
--interval=60
```

This reduces database queries.

### Records Not Syncing

**Check database has new records:**
```sql
SELECT COUNT(*) FROM attendance_records
WHERE AttendanceDateTime > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 HOUR)) * 1000;
```

**Check last sync timestamp:**
```bash
# Convert timestamp to readable date
php -r "echo date('Y-m-d H:i:s', file_get_contents('storage/app/last-sync-timestamp.txt'));"
```

**Reset last sync to force re-sync:**
```bash
del storage\app\last-sync-timestamp.txt
```

Then restart the task.

## Stopping the Sync

### Method 1: Task Scheduler
1. Open Task Scheduler
2. Right-click the task
3. Click "End"

### Method 2: Task Manager
1. Open Task Manager (`Ctrl+Shift+Esc`)
2. Find `php.exe` running `artisan attendance:sync-realtime`
3. Right-click → End Task

### Method 3: Command Line
```batch
taskkill /F /IM php.exe /FI "WINDOWTITLE eq attendance:sync-realtime*"
```

## Performance Tips

### For High-Volume Environments (500+ records per hour)

```env
# .env settings
ATTENDANCE_SYNC_BATCH_SIZE=200
```

```batch
# Task Scheduler arguments
artisan attendance:sync-realtime --interval=15 --batch-size=200
```

### For Low-Volume Environments (< 50 records per hour)

```env
# .env settings
ATTENDANCE_SYNC_BATCH_SIZE=50
```

```batch
# Task Scheduler arguments
artisan attendance:sync-realtime --interval=60 --batch-size=50
```

## Backup Strategy

Even with real-time sync, keep manual sync available for emergencies:

```bash
# Manual sync (fetches last 60 minutes)
php artisan attendance:sync
```

Update `.env`:
```env
DAHUA_FETCH_MINUTES=60
```

This lets you manually sync if the real-time process fails.

## Security Notes

1. **File Permissions:** Ensure `storage/app/` is writable by the PHP process
2. **Log Rotation:** Set up log rotation to prevent disk space issues
3. **API Key:** Keep your API key secure in `.env`
4. **Network:** Use HTTPS for the remote API

## Support

For issues or questions:
1. Check `storage/logs/laravel.log`
2. Verify database connection
3. Test remote API connection: `php artisan attendance:sync --test`
4. Check Windows Event Viewer for system errors
