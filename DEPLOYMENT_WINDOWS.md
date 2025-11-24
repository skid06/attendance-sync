# Deploying Attendance Sync on Windows 11 with XAMPP

This guide will help you deploy the Laravel attendance sync application on Windows 11 using XAMPP.

## Prerequisites

### 1. Install XAMPP

1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Choose version with **PHP 8.2 or higher**
3. Install to `C:\xampp` (default location)
4. During installation, select:
   - Apache
   - PHP
   - MySQL (optional, we use SQLite)

### 2. Install Composer

1. Download Composer from [https://getcomposer.org/download/](https://getcomposer.org/download/)
2. Run the installer `Composer-Setup.exe`
3. When asked for PHP location, browse to `C:\xampp\php\php.exe`
4. Complete the installation
5. Verify installation:
   ```cmd
   composer --version
   ```

## Setup Steps

### Step 1: Enable PHP Sockets Extension

1. Open `C:\xampp\php\php.ini` in a text editor (Notepad++)
2. Find the line `;extension=sockets` (use Ctrl+F to search)
3. Remove the semicolon (`;`) to uncomment it:
   ```ini
   extension=sockets
   ```
4. Save the file
5. Restart Apache from XAMPP Control Panel

### Step 2: Copy Project Files

1. Copy your project to `C:\xampp\htdocs\attendance-sync`
2. Or clone from git if you have it in a repository:
   ```cmd
   cd C:\xampp\htdocs
   git clone <your-repo-url> attendance-sync
   ```

### Step 3: Install Dependencies

1. Open Command Prompt as Administrator
2. Navigate to project directory:
   ```cmd
   cd C:\xampp\htdocs\attendance-sync
   ```
3. Install PHP dependencies:
   ```cmd
   composer install
   ```

### Step 4: Configure Environment

1. Copy `.env.example` to `.env`:
   ```cmd
   copy .env.example .env
   ```

2. Open `.env` in a text editor and configure:

   ```env
   APP_NAME="Attendance Sync"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=http://localhost

   # Database (SQLite - no changes needed)
   DB_CONNECTION=sqlite

   # Attendance Device Configuration
   ATTENDANCE_DRIVER=zkteco
   ATTENDANCE_DEVICE_IP=192.168.1.201
   ATTENDANCE_DEVICE_PORT=4370

   # Remote API Configuration
   ATTENDANCE_API_URL=https://your-server.com/api/v1
   ATTENDANCE_API_KEY=your-actual-api-key-here
   ATTENDANCE_API_TIMEOUT=30

   # Sync Settings
   ATTENDANCE_SYNC_BATCH_SIZE=100
   ATTENDANCE_AUTO_CLEAR=false
   ATTENDANCE_RETRY_FAILED=true
   ATTENDANCE_MAX_RETRIES=3

   # Debug Logging (set to true for troubleshooting)
   ATTENDANCE_DEBUG=false
   ```

3. Generate application key:
   ```cmd
   php artisan key:generate
   ```

### Step 5: Setup Database

1. Create SQLite database file:
   ```cmd
   type nul > database\database.sqlite
   ```

2. Run migrations (if any):
   ```cmd
   php artisan migrate
   ```

### Step 6: Test the Connection

1. Test device connection:
   ```cmd
   php artisan attendance:sync --test
   ```

2. If successful, you should see:
   ```
   ✅ Attendance device connection successful
   ✅ Remote API connection successful
   ```

### Step 7: Manual Sync Test

Run a manual sync to test the full workflow:

```cmd
php artisan attendance:sync
```

## Setting Up Automatic Sync (Windows Task Scheduler)

To run the sync automatically on a schedule, use Windows Task Scheduler:

### Method 1: Using Task Scheduler GUI

1. Press `Win + R`, type `taskschd.msc`, press Enter
2. Click **"Create Basic Task"** in the right panel
3. Configure as follows:

   **General Tab:**
   - Name: `Attendance Sync`
   - Description: `Sync attendance data from device`
   - Run whether user is logged on or not: ☑
   - Run with highest privileges: ☑

   **Triggers Tab:**
   - Click **New**
   - Begin the task: `On a schedule`
   - Daily, Every: `1` days
   - Repeat task every: `1 hour` (or your preferred interval)
   - For a duration of: `Indefinitely`
   - Click **OK**

   **Actions Tab:**
   - Click **New**
   - Action: `Start a program`
   - Program/script: `C:\xampp\php\php.exe`
   - Add arguments: `artisan attendance:sync --clear`
   - Start in: `C:\xampp\htdocs\attendance-sync`
   - Click **OK**

   **Conditions Tab:**
   - Uncheck: `Start the task only if the computer is on AC power`
   - Check: `Wake the computer to run this task` (optional)

   **Settings Tab:**
   - Check: `Allow task to be run on demand`
   - Check: `Run task as soon as possible after a scheduled start is missed`
   - If the task fails, restart every: `5 minutes`
   - Attempt to restart up to: `3 times`

4. Click **OK** to save

### Method 2: Using Command Line

Use the provided batch scripts in the `scripts/` folder:

```cmd
scripts\create-scheduled-task.bat
```

Or manually create a scheduled task:

```cmd
schtasks /create /tn "Attendance Sync" /tr "C:\xampp\htdocs\attendance-sync\scripts\sync.bat" /sc hourly /st 09:00
```

To view the task:
```cmd
schtasks /query /tn "Attendance Sync"
```

To run manually:
```cmd
schtasks /run /tn "Attendance Sync"
```

To delete the task:
```cmd
schtasks /delete /tn "Attendance Sync"
```

## Batch Scripts for Easy Management

We've provided batch scripts in the `scripts/` folder:

### `scripts/sync.bat` - Run Sync
```cmd
scripts\sync.bat
```

### `scripts/test-connection.bat` - Test Connection
```cmd
scripts\test-connection.bat
```

### `scripts/view-logs.bat` - View Logs
```cmd
scripts\view-logs.bat
```

### `scripts/windows-setup.bat` - Initial Setup
```cmd
scripts\windows-setup.bat
```

### `scripts/create-scheduled-task.bat` - Create Scheduled Task
```cmd
scripts\create-scheduled-task.bat
```

## Network Configuration

### Ensure Network Access to Device

1. **Test Network Connectivity:**
   ```cmd
   ping 192.168.1.201
   ```

2. **Test Port Access:**
   ```cmd
   telnet 192.168.1.201 4370
   ```

   If telnet is not installed:
   - Go to **Control Panel** → **Programs** → **Turn Windows features on or off**
   - Check **Telnet Client**
   - Click OK and wait for installation

3. **Windows Firewall:**
   - Open **Windows Defender Firewall**
   - Click **Advanced settings**
   - Click **Outbound Rules** → **New Rule**
   - Rule Type: **Port**
   - Protocol: **UDP**, Specific remote ports: **4370**
   - Action: **Allow the connection**
   - Profile: Check all (Domain, Private, Public)
   - Name: `Attendance Device Access`
   - Click Finish

## Troubleshooting

### Issue 1: "Class 'socket_create' not found"

**Solution:**
1. Verify sockets extension is enabled in `php.ini`
2. Check the correct `php.ini` file:
   ```cmd
   php --ini
   ```
3. Restart Apache from XAMPP Control Panel

### Issue 2: Cannot connect to device

**Solution:**
1. Verify device IP address is correct
2. Ensure device is on the same network or accessible
3. Check Windows Firewall settings
4. Verify device port (default: 4370)
5. Check device settings - TCP/IP communication must be enabled

### Issue 3: Composer install fails

**Solution:**
1. Run as Administrator
2. Disable antivirus temporarily
3. Clear Composer cache:
   ```cmd
   composer clear-cache
   composer install
   ```

### Issue 4: Permission denied errors

**Solution:**
1. Right-click `storage` and `bootstrap/cache` folders
2. Properties → Security → Edit
3. Add **Full Control** for **Users** and **IUSR**
4. Check "Replace all child object permissions"
5. Click Apply

### Issue 5: View logs

Check application logs:
```cmd
type storage\logs\laravel.log
```

Or open with Notepad:
```cmd
notepad storage\logs\laravel.log
```

### Issue 6: "The stream or file could not be opened"

**Solution:**
Run these commands:
```cmd
mkdir storage\logs
mkdir storage\framework\cache
mkdir storage\framework\sessions
mkdir storage\framework\views
echo. > storage\logs\laravel.log
```

## Backup

Create a backup batch file:

```batch
@echo off
set BACKUP_DIR=C:\Attendance-Backups
set DATE=%date:~-4,4%%date:~-10,2%%date:~-7,2%
set TIME=%time:~0,2%%time:~3,2%
set BACKUP_FILE=%BACKUP_DIR%\backup_%DATE%_%TIME%.zip

mkdir %BACKUP_DIR% 2>nul

echo Creating backup...
powershell Compress-Archive -Path "storage\logs\*", "database\database.sqlite", ".env" -DestinationPath "%BACKUP_FILE%"
echo Backup created: %BACKUP_FILE%
pause
```

## Production Checklist

Before going live:

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Set correct `ATTENDANCE_DEVICE_IP`
- [ ] Set correct `ATTENDANCE_API_URL` and `ATTENDANCE_API_KEY`
- [ ] Test device connection: `php artisan attendance:sync --test`
- [ ] Test manual sync: `php artisan attendance:sync`
- [ ] Configure Windows Task Scheduler
- [ ] Test scheduled task manually
- [ ] Configure Windows Firewall rules
- [ ] Set up log rotation/monitoring
- [ ] Create backup schedule
- [ ] Document your specific configuration

## Support

For issues:
1. Check logs: `storage\logs\laravel.log`
2. Run connection test: `php artisan attendance:sync --test`
3. Verify `.env` configuration
4. Check network connectivity to device

## Useful Commands Reference

```cmd
# Test connection
php artisan attendance:sync --test

# Manual sync
php artisan attendance:sync

# Sync with auto-clear
php artisan attendance:sync --clear

# Sync with custom batch size
php artisan attendance:sync --batch-size=50

# View logs
type storage\logs\laravel.log

# Clear config cache
php artisan config:clear

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Security Notes

1. Keep `.env` file secure - it contains sensitive API keys
2. Never commit `.env` to version control
3. Use HTTPS for remote API endpoints
4. Consider using VPN for device access if on different networks
5. Regularly update dependencies: `composer update`
6. Enable Windows Firewall
7. Keep XAMPP and PHP updated
