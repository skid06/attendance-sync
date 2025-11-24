# Deploying Attendance Sync on Windows 11 with Docker

This guide will help you deploy the Laravel attendance sync application on Windows 11 using Docker Desktop.

## Why Docker?

Docker provides several advantages over traditional XAMPP deployment:
- **Isolated Environment**: No conflicts with existing PHP installations
- **Consistent Deployment**: Works the same on any system with Docker
- **Easy Updates**: Simply rebuild the image to update
- **Portable**: Can easily move to production servers
- **No Manual Configuration**: PHP extensions and dependencies are pre-configured

## Prerequisites

### 1. Install Docker Desktop for Windows

1. Download Docker Desktop from [https://www.docker.com/products/docker-desktop/](https://www.docker.com/products/docker-desktop/)
2. Run the installer `Docker Desktop Installer.exe`
3. During installation:
   - Enable **WSL 2** (Windows Subsystem for Linux 2) when prompted
   - Enable **Hyper-V** if asked
4. Restart your computer if prompted
5. Launch Docker Desktop
6. Verify installation:
   ```cmd
   docker --version
   docker-compose --version
   ```

### 2. Configure Docker Desktop

1. Open Docker Desktop
2. Go to **Settings** (gear icon)
3. **General**:
   - ✓ Use the WSL 2 based engine
   - ✓ Start Docker Desktop when you log in (optional)
4. **Resources** → **WSL Integration**:
   - Enable integration with your default WSL distro
5. Click **Apply & Restart**

## Setup Steps

### Step 1: Prepare Your Project

1. **Navigate to your project directory:**
   ```cmd
   cd C:\path\to\attendance-sync
   ```

2. **Create or verify .env file:**
   ```cmd
   copy .env.example .env
   ```

3. **Edit .env file** with your configuration:
   ```env
   APP_NAME="Attendance Sync"
   APP_ENV=production
   APP_DEBUG=false

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

   # Debug Logging
   ATTENDANCE_DEBUG=false
   ```

4. **Create required directories:**
   ```cmd
   mkdir database storage\logs
   type nul > database\database.sqlite
   ```

### Step 2: Build the Docker Image

Build the Docker image (this may take a few minutes the first time):

```cmd
docker-compose build
```

This will:
- Download the PHP 8.2 base image
- Install PHP extensions (sockets, zip, pdo_sqlite)
- Install Composer dependencies
- Set up the application environment

### Step 3: Test the Connection

Before running the sync, test the connection to your device and API:

```cmd
docker-compose --profile test run --rm attendance-test
```

Or manually:

```cmd
docker-compose run --rm attendance-sync php artisan attendance:sync --test
```

You should see:
```
✅ Attendance device connection successful
✅ Remote API connection successful
```

### Step 4: Run Manual Sync

Run a one-time sync manually:

```cmd
docker-compose run --rm attendance-sync php artisan attendance:sync
```

With auto-clear:

```cmd
docker-compose run --rm attendance-sync php artisan attendance:sync --clear
```

Custom batch size:

```cmd
docker-compose run --rm attendance-sync php artisan attendance:sync --batch-size=50
```

## Automated Sync Options

You have several options for running the sync automatically:

### Option 1: Using Windows Task Scheduler (Recommended)

This approach uses Windows Task Scheduler to trigger Docker containers on a schedule.

1. **Use the provided batch script** `docker/docker-sync.bat`

2. **Create a scheduled task:**
   ```cmd
   schtasks /create /tn "Attendance Docker Sync" /tr "C:\path\to\attendance-sync\docker\docker-sync.bat" /sc hourly /st 09:00
   ```

3. **Or use Task Scheduler GUI:**
   - Press `Win + R`, type `taskschd.msc`
   - Create Basic Task
   - Name: `Attendance Docker Sync`
   - Trigger: Daily, repeat every 1 hour
   - Action: Start a program
   - Program: `C:\path\to\attendance-sync\docker\docker-sync.bat`

### Option 2: Long-Running Scheduled Container

Start a container that runs the sync every hour automatically:

```cmd
docker-compose --profile scheduled up -d
```

This will:
- Run in the background continuously
- Execute sync every hour (3600 seconds)
- Automatically restart if it fails
- Auto-start when Docker Desktop starts

To view logs:
```cmd
docker-compose logs -f attendance-sync-scheduled
```

To stop:
```cmd
docker-compose --profile scheduled down
```

### Option 3: Using Docker Compose as a Service

Run the default sync service in one-shot mode with restart policies:

```cmd
docker-compose up -d
```

This runs the sync once and stops. Combine with Windows Task Scheduler for periodic execution.

## Managing the Docker Containers

### View Running Containers

```cmd
docker ps
```

### View All Containers (including stopped)

```cmd
docker ps -a
```

### View Logs

```cmd
# View logs from scheduled service
docker-compose logs -f attendance-sync-scheduled

# View logs from last run
docker-compose logs attendance-sync

# View last 100 lines
docker-compose logs --tail=100 attendance-sync
```

### Stop Running Containers

```cmd
docker-compose down
```

### Restart Containers

```cmd
docker-compose restart
```

### Remove Containers and Images

```cmd
# Stop and remove containers
docker-compose down

# Remove images as well
docker-compose down --rmi all

# Remove everything including volumes (BE CAREFUL - this deletes your database!)
docker-compose down --rmi all --volumes
```

## Batch Scripts for Easy Management

We've provided several batch scripts in the `docker/` folder for easy management on Windows:

### `docker/docker-build.bat` - Build the Docker image
```cmd
docker\docker-build.bat
```

### `docker/docker-test.bat` - Test connections
```cmd
docker\docker-test.bat
```

### `docker/docker-sync.bat` - Run manual sync
```cmd
docker\docker-sync.bat
```

### `docker/docker-logs.bat` - View logs
```cmd
docker\docker-logs.bat
```

### `docker/docker-start-scheduled.bat` - Start scheduled sync
```cmd
docker\docker-start-scheduled.bat
```

### `docker/docker-stop.bat` - Stop all containers
```cmd
docker\docker-stop.bat
```

## Network Configuration

### Accessing Device on Local Network

The Docker container uses `network_mode: host` which allows it to access devices on your local network directly. This means:

- The container can reach your device at `192.168.1.201`
- No port mapping is needed
- The container uses your Windows host's network interface

### Firewall Configuration

If you have issues connecting to the device:

1. **Windows Defender Firewall:**
   - Allow Docker Desktop through firewall
   - Ensure outbound connections on port 4370 are allowed

2. **Test connectivity from Windows:**
   ```cmd
   ping 192.168.1.201
   telnet 192.168.1.201 4370
   ```

3. **If Docker can't access the device:**
   - Try restarting Docker Desktop
   - Check if WSL 2 can access the network: `wsl ping 192.168.1.201`

## Troubleshooting

### Issue 1: Docker Desktop won't start

**Solution:**
1. Ensure Hyper-V and WSL 2 are enabled:
   ```cmd
   wsl --list --verbose
   wsl --set-default-version 2
   ```
2. Restart Windows
3. Check Docker Desktop logs: Settings → Troubleshoot → View logs

### Issue 2: "docker-compose: command not found"

**Solution:**
Docker Compose is bundled with Docker Desktop. Use:
```cmd
docker compose version
```

Or use the standalone version:
```cmd
docker-compose version
```

### Issue 3: Cannot connect to attendance device

**Solution:**
1. Verify device IP in .env file
2. Test from Windows first: `ping 192.168.1.201`
3. Ensure Docker Desktop is using host network
4. Check WSL 2 network: `wsl ping 192.168.1.201`
5. Try restarting Docker Desktop

### Issue 4: Permission errors on database/logs

**Solution:**
```cmd
# Create directories and files with correct permissions
mkdir database storage\logs
type nul > database\database.sqlite
icacls storage /grant Everyone:(OI)(CI)F /T
icacls database /grant Everyone:(OI)(CI)F /T
```

### Issue 5: Build fails or takes too long

**Solution:**
1. Check your internet connection
2. Clear Docker build cache:
   ```cmd
   docker builder prune
   docker-compose build --no-cache
   ```
3. Restart Docker Desktop

### Issue 6: Container exits immediately

**Solution:**
1. Check logs:
   ```cmd
   docker-compose logs
   ```
2. Verify .env file exists and is configured
3. Check database file exists: `database\database.sqlite`
4. Run in foreground to see errors:
   ```cmd
   docker-compose run --rm attendance-sync php artisan attendance:sync
   ```

### Issue 7: Health check failing

**Solution:**
The health check runs `php artisan attendance:sync --test`. If failing:
1. Check .env configuration
2. Verify device is accessible
3. Verify API endpoint is reachable
4. Disable health check temporarily by commenting it out in docker-compose.yml

## Viewing Application Logs

### Application logs (Laravel logs)

```cmd
# View from host
type storage\logs\laravel.log

# View from container
docker-compose exec attendance-sync-scheduled cat storage/logs/laravel.log
```

### Docker container logs

```cmd
# Follow logs in real-time
docker-compose logs -f

# Last 100 lines
docker-compose logs --tail=100

# Logs from specific service
docker-compose logs attendance-sync-scheduled
```

## Production Deployment Checklist

Before deploying to production:

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Configure correct `ATTENDANCE_DEVICE_IP`
- [ ] Configure correct `ATTENDANCE_API_URL` and `ATTENDANCE_API_KEY`
- [ ] Test connection: `docker-compose --profile test run --rm attendance-test`
- [ ] Test manual sync: `docker-compose run --rm attendance-sync php artisan attendance:sync`
- [ ] Set up automated sync (Task Scheduler or scheduled container)
- [ ] Configure log rotation
- [ ] Test scheduled sync runs successfully
- [ ] Document your deployment configuration
- [ ] Set up monitoring/alerting (optional)
- [ ] Create backup script for database

## Updating the Application

When you need to update the application code or dependencies:

1. **Pull latest changes (if using Git):**
   ```cmd
   git pull
   ```

2. **Rebuild the Docker image:**
   ```cmd
   docker-compose build --no-cache
   ```

3. **Restart containers:**
   ```cmd
   docker-compose down
   docker-compose --profile scheduled up -d
   ```

## Backup and Restore

### Backup

Create a backup of your database and logs:

```cmd
# Stop containers first
docker-compose down

# Create backup directory
mkdir backups\%date:~-4,4%%date:~-10,2%%date:~-7,2%

# Copy database and logs
copy database\database.sqlite backups\%date:~-4,4%%date:~-10,2%%date:~-7,2%\
copy storage\logs\*.log backups\%date:~-4,4%%date:~-10,2%%date:~-7,2%\
copy .env backups\%date:~-4,4%%date:~-10,2%%date:~-7,2%\
```

### Restore

```cmd
# Stop containers
docker-compose down

# Restore database
copy backups\20241118\database.sqlite database\

# Restart
docker-compose --profile scheduled up -d
```

## Useful Commands Reference

```cmd
# Build image
docker-compose build

# Test connection
docker-compose --profile test run --rm attendance-test

# Manual sync
docker-compose run --rm attendance-sync php artisan attendance:sync

# Start scheduled sync (every hour)
docker-compose --profile scheduled up -d

# Stop all containers
docker-compose down

# View logs (real-time)
docker-compose logs -f

# View logs (last 100 lines)
docker-compose logs --tail=100

# Execute command in running container
docker-compose exec attendance-sync-scheduled php artisan attendance:sync --test

# Restart containers
docker-compose restart

# Rebuild without cache
docker-compose build --no-cache

# Remove everything (including volumes - CAREFUL!)
docker-compose down --rmi all --volumes

# View container status
docker ps

# View Docker images
docker images

# Clean up unused Docker resources
docker system prune -a
```

## Support

For Docker-specific issues:

1. Check Docker Desktop logs: Settings → Troubleshoot
2. Check container logs: `docker-compose logs`
3. Check application logs: `type storage\logs\laravel.log`
4. Verify .env configuration
5. Test network connectivity to attendance device

For application issues, refer to the main README.md.

---

**Need help?** Check the [main README](README.md) or [XAMPP deployment guide](DEPLOYMENT_WINDOWS.md) for additional information.
