# Deploying ZKTeco Attendance Sync on Windows with Laragon

This guide will help you deploy the Laravel ZKTeco attendance application on Windows using Laragon - a modern, lightweight, and portable PHP development environment.

## Why Laragon?

- **Fast & Lightweight**: Uses less resources than XAMPP
- **Modern**: Comes with latest PHP versions
- **Portable**: Isolated environment, doesn't affect system
- **Easy**: Auto-configuration and virtual hosts
- **Complete**: Includes PHP, Composer, Node.js, and more

## Prerequisites

### 1. Download and Install Laragon

1. Download Laragon from [https://laragon.org/download/](https://laragon.org/download/)
2. Choose **Laragon Full** (includes PHP 8.x, Composer, Node.js)
3. Run the installer `laragon-wamp.exe`
4. Install to default location `C:\laragon` (recommended)
5. Launch Laragon

### 2. Verify Installation

After installing Laragon:

1. Start Laragon (click "Start All")
2. Click **Menu** → **PHP** → Check PHP version (should be 8.2 or higher)
3. Click **Menu** → **Tools** → **Quick app** to verify it's working

## Setup Steps

### Step 1: Enable PHP Sockets Extension

1. In Laragon, click **Menu** → **PHP** → **php.ini**
2. Find the line `;extension=sockets` (use Ctrl+F to search)
3. Remove the semicolon (`;`) to enable it:
   ```ini
   extension=sockets
   ```
4. Save the file
5. In Laragon, click **Menu** → **PHP** → **Reload** (or restart Laragon)

### Step 2: Add Your Project to Laragon

**Option A: Move/Copy Project to Laragon Directory**

1. Copy your project folder to `C:\laragon\www\`
   ```cmd
   xcopy C:\Users\m.valencia\Documents\zkteco-attendance C:\laragon\www\zkteco-attendance /E /I
   ```

2. The project will be at: `C:\laragon\www\zkteco-attendance`

**Option B: Create a Symlink (Advanced)**

```cmd
mklink /D C:\laragon\www\zkteco-attendance C:\Users\m.valencia\Documents\zkteco-attendance
```

### Step 3: Install Composer Dependencies

1. In Laragon, right-click on the window → **Terminal** (or press Ctrl+Alt+T)
2. Navigate to your project:
   ```bash
   cd zkteco-attendance
   ```
3. Install dependencies:
   ```bash
   composer install
   ```

### Step 4: Configure Environment

1. Create `.env` file from example:
   ```bash
   copy .env.example .env
   ```

2. Edit `.env` file (Laragon has built-in editor):
   - Right-click project folder in Laragon → **Open** → **.env**
   - Or use: `notepad .env`

3. Configure your settings:
   ```env
   APP_NAME="ZKTeco Attendance"
   APP_ENV=production
   APP_DEBUG=false

   # Database (SQLite)
   DB_CONNECTION=sqlite

   # ZKTeco Device Configuration
   ZKTECO_DEVICE_IP=192.168.1.201
   ZKTECO_DEVICE_PORT=4370

   # Remote API Configuration
   REMOTE_API_URL=https://your-server.com/api/v1
   REMOTE_API_KEY=your-actual-api-key-here
   REMOTE_API_TIMEOUT=30

   # Sync Settings
   SYNC_BATCH_SIZE=100
   AUTO_CLEAR_DEVICE=false
   RETRY_FAILED_RECORDS=true
   MAX_RETRIES=3

   # Debug Logging
   ZKTECO_DEBUG_LOGGING=false
   ```

4. Generate application key:
   ```bash
   php artisan key:generate
   ```

### Step 5: Setup Database

1. Create SQLite database:
   ```bash
   type nul > database\database.sqlite
   ```

2. Run migrations (if any):
   ```bash
   php artisan migrate
   ```

### Step 6: Test the Connection

1. In Laragon Terminal, test the connection:
   ```bash
   php artisan attendance:sync --test
   ```

2. You should see:
   ```
   ✅ ZKTeco device connection successful
   ✅ Remote API connection successful
   ```

### Step 7: Run Manual Sync

Test the sync manually:

```bash
php artisan attendance:sync
```

With auto-clear:

```bash
php artisan attendance:sync --clear
```

## Setting Up Automatic Sync

### Method 1: Windows Task Scheduler (Recommended)

1. **Create a sync batch file** in your project root `C:\laragon\www\zkteco-attendance\laragon-sync.bat`:

   ```batch
   @echo off
   cd /d C:\laragon\www\zkteco-attendance
   C:\laragon\bin\php\php-8.2-Win32\php.exe artisan attendance:sync --clear >> storage\logs\sync.log 2>&1
   ```

2. **Create scheduled task via Task Scheduler GUI:**

   - Press `Win + R`, type `taskschd.msc`, press Enter
   - Click **Create Basic Task**
   - Name: `ZKTeco Laragon Sync`
   - Trigger: Daily, repeat every 1 hour
   - Action: Start a program
   - Program: `C:\laragon\www\zkteco-attendance\laragon-sync.bat`
   - Click Finish

3. **Or create via command line:**

   ```cmd
   schtasks /create /tn "ZKTeco Laragon Sync" /tr "C:\laragon\www\zkteco-attendance\laragon-sync.bat" /sc hourly /st 09:00
   ```

### Method 2: Using Laragon's Task Scheduler Feature

Laragon can run Laravel scheduler automatically:

1. **Update `app/Console/Kernel.php`** to add the schedule:

   ```php
   protected function schedule(Schedule $schedule)
   {
       // Sync every hour
       $schedule->command('attendance:sync --clear')
                ->hourly()
                ->withoutOverlapping();
   }
   ```

2. **Create a batch file** `laragon-scheduler.bat`:

   ```batch
   @echo off
   cd /d C:\laragon\www\zkteco-attendance
   C:\laragon\bin\php\php-8.2-Win32\php.exe artisan schedule:run
   ```

3. **Set up Windows Task Scheduler to run every minute:**

   ```cmd
   schtasks /create /tn "ZKTeco Laravel Scheduler" /tr "C:\laragon\www\zkteco-attendance\laragon-scheduler.bat" /sc minute /st 09:00
   ```

   This runs Laravel's scheduler every minute, which then executes your hourly task.

## Batch Scripts for Easy Management

Create these batch files in your project root for easy management:

### `laragon-test.bat` - Test Connection

```batch
@echo off
cd /d C:\laragon\www\zkteco-attendance
C:\laragon\bin\php\php-8.2-Win32\php.exe artisan attendance:sync --test
pause
```

### `laragon-sync.bat` - Manual Sync

```batch
@echo off
cd /d C:\laragon\www\zkteco-attendance
C:\laragon\bin\php\php-8.2-Win32\php.exe artisan attendance:sync --clear
pause
```

### `laragon-logs.bat` - View Logs

```batch
@echo off
cd /d C:\laragon\www\zkteco-attendance
type storage\logs\laravel.log
pause
```

### `laragon-install.bat` - Install/Update Dependencies

```batch
@echo off
cd /d C:\laragon\www\zkteco-attendance
C:\laragon\bin\composer\composer.bat install --no-dev --optimize-autoloader
pause
```

## Network Configuration

### Accessing ZKTeco Device on Local Network

1. **Test network connectivity:**
   ```cmd
   ping 192.168.1.201
   ```

2. **Test port access:**
   ```cmd
   telnet 192.168.1.201 4370
   ```

3. **Windows Firewall:**
   - Ensure outbound connections on port 4370 are allowed
   - Laragon should automatically configure this

## Troubleshooting

### Issue 1: "sockets extension not found"

**Solution:**
1. Open Laragon
2. Menu → PHP → php.ini
3. Find `;extension=sockets` and remove `;`
4. Save and reload: Menu → PHP → Reload

### Issue 2: Cannot find PHP executable

**Solution:**
Verify PHP path in your batch files. Check your actual PHP version:
- Look in `C:\laragon\bin\php\`
- You might have `php-8.2-Win32`, `php-8.3-nts-Win32`, etc.
- Update batch files with correct path

### Issue 3: Composer not found

**Solution:**
1. In Laragon Terminal, run:
   ```bash
   composer --version
   ```
2. If not found, in Laragon click Menu → Tools → PATH → Add Composer

### Issue 4: Cannot connect to device

**Solution:**
1. Verify device IP in `.env`
2. Test from Command Prompt: `ping 192.168.1.201`
3. Check Windows Firewall settings
4. Ensure device and computer are on same network

### Issue 5: Permission errors

**Solution:**
1. Right-click `storage` and `bootstrap/cache` folders
2. Properties → Security → Edit
3. Add Full Control for your user account
4. Apply to all files and folders

### Issue 6: View application logs

```cmd
# View entire log
type C:\laragon\www\zkteco-attendance\storage\logs\laravel.log

# View last 50 lines
powershell Get-Content C:\laragon\www\zkteco-attendance\storage\logs\laravel.log -Tail 50
```

## Using Laragon Features

### Quick Terminal Access

- Right-click Laragon window → Terminal
- Or press `Ctrl + Alt + T`
- Automatically opens in your project directory

### Quick Edit Files

- Right-click project in Laragon → Open → Select file
- Uses your default editor

### Start/Stop Services

- Click "Start All" to start PHP and services
- Click "Stop All" to stop everything
- Or use individual Start/Stop buttons

## Production Deployment Checklist

Before going live:

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Set correct `ZKTECO_DEVICE_IP`
- [ ] Set correct `REMOTE_API_URL` and `REMOTE_API_KEY`
- [ ] Test device connection: `php artisan attendance:sync --test`
- [ ] Test manual sync: `php artisan attendance:sync`
- [ ] Configure Windows Task Scheduler for automatic sync
- [ ] Test scheduled task runs successfully
- [ ] Set up log rotation/monitoring
- [ ] Create backup schedule
- [ ] Document your configuration

## Updating the Application

When you need to update:

1. **Pull latest changes (if using Git):**
   ```bash
   git pull
   ```

2. **Update dependencies:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Clear caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Run migrations (if any):**
   ```bash
   php artisan migrate --force
   ```

## Backup and Restore

### Backup

```batch
@echo off
set BACKUP_DIR=C:\ZKTeco-Backups
set DATE=%date:~-4,4%%date:~-10,2%%date:~-7,2%
mkdir %BACKUP_DIR%\%DATE% 2>nul

copy C:\laragon\www\zkteco-attendance\database\database.sqlite %BACKUP_DIR%\%DATE%\
copy C:\laragon\www\zkteco-attendance\.env %BACKUP_DIR%\%DATE%\
xcopy C:\laragon\www\zkteco-attendance\storage\logs %BACKUP_DIR%\%DATE%\logs\ /E /I

echo Backup completed: %BACKUP_DIR%\%DATE%
pause
```

### Restore

```cmd
copy C:\ZKTeco-Backups\20241118\database.sqlite C:\laragon\www\zkteco-attendance\database\
copy C:\ZKTeco-Backups\20241118\.env C:\laragon\www\zkteco-attendance\
```

## Performance Tips

1. **Enable OPcache** (if not already):
   - Laragon → Menu → PHP → php.ini
   - Uncomment: `zend_extension=opcache`
   - Set: `opcache.enable=1`

2. **Optimize Composer autoloader:**
   ```bash
   composer dump-autoload --optimize --no-dev
   ```

3. **Cache Laravel configuration:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   ```

## Security Best Practices

1. **Keep `.env` secure**
   - Never commit to Git
   - Backup separately

2. **Regular updates**
   - Keep Laragon updated
   - Update PHP and Composer regularly
   - Update project dependencies: `composer update`

3. **Use HTTPS for API**
   - Always use `https://` in `REMOTE_API_URL`

4. **Limit access**
   - Don't expose Laragon to the internet
   - Use firewall rules

## Advantages of Laragon

| Feature | XAMPP | Laragon | Docker |
|---------|-------|---------|--------|
| **Installation** | Complex | Simple | Complex on Windows |
| **Resource Usage** | Heavy | Light | Heavy |
| **PHP Versions** | Single | Multiple, switchable | Configurable |
| **Startup Time** | Slow | Fast | Slow |
| **Portable** | No | Yes | No |
| **Easy Updates** | Manual | Built-in | Rebuild image |
| **Windows Integration** | Good | Excellent | Limited |

## Common Commands Reference

```bash
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

# Clear caches
php artisan config:clear
php artisan cache:clear

# Check PHP version
php -v

# Check installed extensions
php -m

# Composer install
composer install

# Composer update
composer update
```

## Support

For Laragon-specific issues:

1. Check Laragon logs: `C:\laragon\logs`
2. Check application logs: `storage\logs\laravel.log`
3. Verify `.env` configuration
4. Test network connectivity to ZKTeco device

For application issues, refer to the main [README.md](README.md).

## Next Steps

After successful deployment:

1. Set up automated sync (Task Scheduler)
2. Configure log rotation
3. Create backup automation
4. Test failover scenarios
5. Document your specific configuration
6. Monitor sync operations

---

**Need help?** Check the [main README](README.md) for additional information.
