# Attendance Sync Application

A Laravel console application that retrieves attendance data from biometric devices and sends it to a remote server via API. Built with a contract-based architecture to support multiple device types.

## Deployment Options

Choose the deployment method that best suits your needs:

- **Laragon (Recommended for Windows):** See [DEPLOYMENT_LARAGON.md](DEPLOYMENT_LARAGON.md) for Windows deployment with Laragon
  - Modern, fast, and lightweight
  - Easy setup and management
  - Built-in PHP 8.x and Composer
  - Perfect for Windows environments

- **Docker:** See [DEPLOYMENT_DOCKER.md](DEPLOYMENT_DOCKER.md) for Windows 11 + Docker Desktop deployment
  - Isolated containerized environment
  - Production-ready and portable
  - Cross-platform compatibility

- **XAMPP:** See [DEPLOYMENT_WINDOWS.md](DEPLOYMENT_WINDOWS.md) for Windows 11 + XAMPP deployment
  - Traditional PHP environment
  - More manual configuration required

## Features

- **Contract-based architecture** - Easily add support for new device types
- Connect to attendance devices via TCP/IP
- Retrieve attendance records (check-in/check-out data)
- Send data to remote server in batches
- Clear device records after successful sync
- Connection testing mode
- Detailed logging and error handling
- Configurable batch sizes and retry logic

## Supported Devices

- **ZKTeco** - Biometric attendance devices (default)
- **Null** - Testing driver (no actual device connection)

## Requirements

- PHP 8.1 or higher
- Composer
- Network access to attendance device
- Device must be configured for TCP/IP communication

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

## Configuration

### 1. Configure Attendance Device

Update the `.env` file with your device details:

```env
# Attendance Device Configuration
# Supported drivers: zkteco, null (for testing)
ATTENDANCE_DRIVER=zkteco
ATTENDANCE_DEVICE_IP=192.168.1.201    # Your device IP address
ATTENDANCE_DEVICE_PORT=4370           # Default ZKTeco port
```

### 2. Configure Remote API

Set up your remote server API endpoint:

```env
# Remote API Configuration
ATTENDANCE_API_URL=https://api.example.com/api/v1
ATTENDANCE_API_KEY=your-api-key-here
ATTENDANCE_API_TIMEOUT=30
```

### 3. Sync Settings (Optional)

```env
# Sync Settings
ATTENDANCE_SYNC_BATCH_SIZE=100       # Records per batch
ATTENDANCE_AUTO_CLEAR=false          # Auto-clear after sync
ATTENDANCE_RETRY_FAILED=true
ATTENDANCE_MAX_RETRIES=3
```

## Usage

### Basic Sync Command

Sync attendance data from device to remote server:

```bash
php artisan attendance:sync
```

### Test Connections

Test connectivity to both the device and remote API:

```bash
php artisan attendance:sync --test
```

### Sync with Auto-Clear

Sync data and clear records from device after successful sync:

```bash
php artisan attendance:sync --clear
```

### Custom Batch Size

Specify a custom batch size for sending records:

```bash
php artisan attendance:sync --batch-size=50
```

### Combined Options

```bash
php artisan attendance:sync --clear --batch-size=200
```

## Command Options

| Option | Description |
|--------|-------------|
| `--test` | Test connection without syncing data |
| `--clear` | Clear attendance records from device after successful sync |
| `--batch-size=N` | Number of records to send per batch (default: 100) |

## Architecture

This application uses Laravel's contract (interface) pattern for flexibility:

```
app/
├── Contracts/
│   └── AttendanceDeviceInterface.php    # Device contract
├── Console/Commands/
│   └── SyncAttendanceData.php           # Main console command
├── Services/
│   ├── AttendanceSyncService.php        # Remote API integration
│   └── Devices/
│       ├── ZKTecoDevice.php             # ZKTeco implementation
│       └── NullDevice.php               # Testing implementation
├── Providers/
│   └── AttendanceServiceProvider.php    # Service binding
config/
└── attendance.php                        # Configuration file
```

### Adding a New Device Driver

1. Create a new class implementing `AttendanceDeviceInterface`:

```php
namespace App\Services\Devices;

use App\Contracts\AttendanceDeviceInterface;

class HikvisionDevice implements AttendanceDeviceInterface
{
    public function connect(): bool { /* ... */ }
    public function disconnect(): bool { /* ... */ }
    public function getAttendance(): array { /* ... */ }
    public function clearAttendance(): bool { /* ... */ }
    public function testConnection(): bool { /* ... */ }
    public function getDeviceInfo(): array { /* ... */ }
}
```

2. Add configuration in `config/attendance.php`:

```php
'devices' => [
    'hikvision' => [
        'driver' => 'hikvision',
        'ip' => env('ATTENDANCE_DEVICE_IP'),
        'port' => env('ATTENDANCE_DEVICE_PORT', 8000),
    ],
],
```

3. Register in `AttendanceServiceProvider`:

```php
'hikvision' => new HikvisionDevice($config['ip'], $config['port']),
```

## API Endpoint Requirements

Your remote server should have the following endpoints:

### 1. Health Check (Optional)

```
GET /health
Authorization: Bearer {API_KEY}
```

Response:
```json
{
    "status": "ok"
}
```

### 2. Sync Attendance Records

```
POST /attendance
Authorization: Bearer {API_KEY}
Content-Type: application/json
```

Request body:
```json
{
    "records": [
        {
            "user_id": "12345",
            "timestamp": "2025-11-06 09:30:00",
            "verify_type": "Fingerprint",
            "status": "Check In",
            "raw_timestamp": 1730880600
        }
    ],
    "device_info": {
        "type": "zkteco",
        "ip": "192.168.1.201",
        "synced_at": "2025-11-06T09:35:00Z"
    }
}
```

Response:
```json
{
    "success": true,
    "message": "Records saved successfully",
    "records_received": 100
}
```

## Data Structure

### Attendance Record Fields

Each attendance record contains:

- `user_id`: Employee/user ID from the device
- `timestamp`: Human-readable timestamp (Y-m-d H:i:s)
- `verify_type`: Authentication method (Fingerprint, Card, Face, Password, etc.)
- `status`: Attendance type (Check In, Check Out, Break Out, Break In, etc.)
- `raw_timestamp`: Unix timestamp

## Troubleshooting

### Cannot Connect to Device

1. Verify the device IP address is correct
2. Ensure the device is on the same network or accessible
3. Check if the device port is open on your firewall
4. Verify the device has TCP/IP communication enabled

Test connection:
```bash
php artisan attendance:sync --test
```

### API Connection Failed

1. Verify the API URL is correct
2. Check if the API key is valid
3. Ensure your server has internet access
4. Check API server logs for errors

## Logs

Application logs are stored in `storage/logs/laravel.log`

View recent logs:
```bash
tail -f storage/logs/laravel.log
```

Enable debug logging in `.env`:
```env
ATTENDANCE_DEBUG=true
```

## Security Considerations

1. Keep your API key secure in the `.env` file
2. Never commit `.env` to version control
3. Use HTTPS for remote API communication
4. Implement proper authentication on your remote API
5. Consider encrypting sensitive data in transit

## License

This application is provided as-is for attendance management purposes.
