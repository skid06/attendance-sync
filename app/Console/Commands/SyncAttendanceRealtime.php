<?php

namespace App\Console\Commands;

use App\Contracts\AttendanceDeviceInterface;
use App\Services\AttendanceSyncService;
use App\Services\Devices\DahuaDevice;
use App\Services\Devices\HikVisionDevice;
use App\Services\Devices\NullDevice;
use App\Services\Devices\ZKTecoDevice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Exception;

class SyncAttendanceRealtime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:sync-realtime
                            {--driver= : Specify device driver (dahua, hikvision, zkteco)}
                            {--interval=30 : Poll interval in seconds}
                            {--batch-size=100 : Number of records to send per batch}
                            {--test : Test connections and exit (does not start continuous sync)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Continuously sync attendance data in real-time (polls database every 30 seconds)';

    private bool $shouldStop = false;

    public function __construct(
        private AttendanceSyncService $syncService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Resolve device based on --driver option or use default
        $driverName = $this->option('driver') ?: config('attendance.default');
        $device = $this->createDevice($driverName);

        // Device-specific last sync file
        $lastSyncFile = storage_path("app/last-sync-timestamp-{$driverName}.txt");

        $interval = (int) $this->option('interval');
        $batchSize = (int) $this->option('batch-size');

        $deviceInfo = $device->getDeviceInfo();
        $driverDisplayName = ucfirst($deviceInfo['type'] ?? 'Unknown');

        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info("║   Real-Time Attendance Sync ({$driverDisplayName})                   ");
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Setup signal handlers for graceful shutdown (Windows compatible)
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'shutdown']);
            pcntl_signal(SIGINT, [$this, 'shutdown']);
        }

        $this->info("🔄 Starting real-time sync...");
        $this->info("⏱️  Poll interval: {$interval} seconds");
        $this->info("📦 Batch size: {$batchSize}");
        $this->newLine();

        try {
            // Test connection first
            $this->info("Testing connection to device...");
            if (!$device->connect()) {
                $this->error("❌ Failed to connect to device");
                return 1;
            }
            $this->info("✅ Connected successfully");
            $this->newLine();

            // Test API connection
            $this->info("Testing connection to remote API...");
            if (!$this->syncService->testConnection()) {
                $this->warn("⚠️  Warning: Could not connect to remote API");
                if ($this->option('test')) {
                    $device->disconnect();
                    return 1;
                }
                $this->warn("    Will continue and retry when syncing...");
            } else {
                $this->info("✅ API connection successful");
            }
            $this->newLine();

            // If test mode, exit here
            if ($this->option('test')) {
                $this->info("🎉 All connection tests passed!");
                $this->info("📊 Device Info:");
                foreach ($deviceInfo as $key => $value) {
                    $this->line("   {$key}: " . (is_array($value) ? json_encode($value) : $value));
                }
                $this->newLine();
                $this->info("✅ Ready for real-time sync. Run without --test to start continuous monitoring.");
                $device->disconnect();
                return 0;
            }

            // Initialize last sync timestamp
            $lastSync = $this->getLastSyncTimestamp($lastSyncFile);
            $this->info("📝 Last sync: " . ($lastSync ? date('Y-m-d H:i:s', $lastSync) : 'Never'));
            $this->info("🚀 Real-time sync is now active. Press Ctrl+C to stop.");
            $this->newLine();

            $loopCount = 0;
            $lastSuccessfulSync = $lastSync ?? time();

            // Main polling loop
            while (!$this->shouldStop) {
                $loopCount++;

                try {
                    // Automatic recovery mechanisms:

                    // 1. Check if last sync is stale (no successful sync in last 2 hours)
                    $hoursSinceLastSuccess = (time() - $lastSuccessfulSync) / 3600;
                    if ($hoursSinceLastSuccess >= 2) {
                        $this->warn("⚠️  No sync in " . round($hoursSinceLastSuccess, 1) . " hours. Resetting to fetch last hour...");
                        // Auto-recovery triggered - no log to reduce noise
                        $lastSync = time() - 3600; // Reset to 1 hour ago
                        $this->saveLastSyncTimestamp($lastSync, $lastSyncFile);
                    }

                    // 2. Periodic full check (every 120 loops = ~1 hour)
                    // This catches any edge cases where incremental sync might miss records
                    if ($loopCount % 120 == 0) {
                        $this->comment("🔄 Periodic full check (every hour)...");
                        // Periodic check - no log to reduce noise
                        $lastSync = time() - 3600; // Check last hour
                    }

                    // Get new records since last sync
                    $records = $this->getNewRecords($lastSync, $device);

                    if (empty($records)) {
                        // Silently continue - no output for empty checks
                    } else {
                        $count = count($records);
                        $this->newLine();
                        $this->info("[" . date('Y-m-d H:i:s') . "] ✨ Found {$count} new record(s)! Syncing...");

                        // Sync the records
                        $result = $this->syncService->sendAttendanceRecordsInBatches(
                            $records,
                            $deviceInfo,
                            $batchSize
                        );

                        if ($result['success']) {
                            $this->info("   ✅ Successfully synced {$result['sent']} record(s)");

                            // Update last sync timestamp to the max timestamp of records we just sent
                            // This prevents re-fetching records with the same timestamp
                            $maxTimestamp = max(array_column($records, 'raw_timestamp'));
                            $lastSync = $maxTimestamp;
                            $lastSuccessfulSync = time(); // Track when we last successfully synced (for stale detection)
                            $this->saveLastSyncTimestamp($lastSync, $lastSyncFile);
                        } else {
                            $this->error("   ❌ Sync failed: {$result['message']}");
                            Log::error("Real-time sync failed", $result);
                        }
                    }

                } catch (Exception $e) {
                    $this->error("   ❌ Error during sync: " . $e->getMessage());
                    Log::error("Real-time sync error: " . $e->getMessage(), [
                        'exception' => $e,
                        'loop' => $loopCount,
                    ]);
                }

                // Sleep for the specified interval
                if (!$this->shouldStop) {
                    sleep($interval);
                }

                // Process signals if available
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }

                // Free up memory
                gc_collect_cycles();
            }

            $this->newLine();
            $this->info("🛑 Real-time sync stopped gracefully");
            $device->disconnect();

            return 0;

        } catch (Exception $e) {
            $this->error("❌ Fatal error: " . $e->getMessage());
            Log::error("Real-time sync fatal error", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Get new records since last sync
     */
    private function getNewRecords(?int $lastSync, AttendanceDeviceInterface $device): array
    {
        if ($lastSync === null) {
            // First run - get records from last hour
            Log::info("First real-time sync run - fetching last hour");
            return $device->getAttendance();
        }

        // Get records since last sync timestamp
        // Silently fetch - only log if records are found (logged in device class)

        // Use getAttendanceSince if available (Dahua device supports this)
        if (method_exists($device, 'getAttendanceSince')) {
            return $device->getAttendanceSince($lastSync);
        }

        // Fallback to regular getAttendance for other devices
        return $device->getAttendance();
    }

    /**
     * Get last sync timestamp from file
     */
    private function getLastSyncTimestamp(string $lastSyncFile): ?int
    {
        if (!file_exists($lastSyncFile)) {
            return null;
        }

        $timestamp = file_get_contents($lastSyncFile);
        return $timestamp ? (int) $timestamp : null;
    }

    /**
     * Save last sync timestamp to file
     */
    private function saveLastSyncTimestamp(int $timestamp, string $lastSyncFile): void
    {
        $dir = dirname($lastSyncFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($lastSyncFile, $timestamp);
    }

    /**
     * Shutdown handler for graceful stop
     */
    public function shutdown(): void
    {
        $this->shouldStop = true;
        $this->newLine();
        $this->warn("⚠️  Shutdown signal received, stopping gracefully...");
    }

    /**
     * Create an attendance device instance based on driver name
     */
    private function createDevice(string $driver): AttendanceDeviceInterface
    {
        $config = config("attendance.devices.{$driver}");

        if (empty($config)) {
            throw new InvalidArgumentException("Attendance device driver [{$driver}] is not configured.");
        }

        return match ($driver) {
            'zkteco' => new ZKTecoDevice(
                $config['ip'],
                $config['port'] ?? 4370
            ),
            'dahua' => new DahuaDevice($config),
            'hikvision' => new HikVisionDevice($config),
            'null' => new NullDevice(),
            default => throw new InvalidArgumentException("Unsupported attendance device driver: {$driver}"),
        };
    }
}
