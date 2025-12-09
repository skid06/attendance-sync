<?php

namespace App\Console\Commands;

use App\Contracts\AttendanceDeviceInterface;
use App\Services\AttendanceSyncService;
use App\Services\Devices\DahuaDevice;
use App\Services\Devices\HikVisionDevice;
use App\Services\Devices\NullDevice;
use App\Services\Devices\ZKTecoDevice;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Exception;

class SyncAttendanceData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:sync
                            {--driver= : Specify device driver (dahua, hikvision, zkteco)}
                            {--clear : Clear attendance records from device after sync}
                            {--batch-size=100 : Number of records to send per batch}
                            {--test : Test connection without syncing data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync attendance data from device to remote server';

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

        $deviceInfo = $device->getDeviceInfo();
        $driverDisplayName = ucfirst($deviceInfo['type'] ?? 'Unknown');

        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info("║       Attendance Data Sync ({$driverDisplayName})                      ");
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        try {
            // Test connection mode
            if ($this->option('test')) {
                return $this->testConnections($device);
            }

            // Connect to device
            $this->info('Connecting to attendance device...');
            if (!$device->connect()) {
                $this->error('Failed to connect to attendance device');
                return Command::FAILURE;
            }
            $this->info('Connected successfully');
            $this->newLine();

            // Get attendance records
            $this->info('Fetching attendance records from device...');
            $records = $device->getAttendance();

            if (empty($records)) {
                $this->warn('No attendance records found on device');
                $device->disconnect();
                return Command::SUCCESS;
            }

            $this->info("Retrieved " . count($records) . " attendance records");
            $this->newLine();

            // Display sample records
            $this->displaySampleRecords($records);

            // Send to remote server
            $this->info('Sending attendance records to remote server...');
            $batchSize = (int) $this->option('batch-size');

            $result = $this->syncService->sendAttendanceRecordsInBatches(
                $records,
                $deviceInfo,
                $batchSize
            );

            $this->newLine();
            $this->displaySyncResults($result);

            // Clear device records if requested and sync was successful
            if ($this->option('clear') && $result['success']) {
                if ($this->confirm('Clear attendance records from device?', true)) {
                    $this->info('Clearing attendance records from device...');
                    if ($device->clearAttendance()) {
                        $this->info('Attendance records cleared successfully');
                    } else {
                        $this->error('Failed to clear attendance records');
                    }
                }
            }

            // Disconnect from device
            $device->disconnect();

            $this->newLine();
            $this->info('Sync process completed');

            return $result['success'] ? Command::SUCCESS : Command::FAILURE;

        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());

            if (config('attendance.debug')) {
                $this->error($e->getTraceAsString());
            }

            if (isset($device)) {
                $device->disconnect();
            }

            return Command::FAILURE;
        }
    }

    /**
     * Test connections to device and remote API
     */
    private function testConnections(AttendanceDeviceInterface $device): int
    {
        $this->info('Testing connections...');
        $this->newLine();

        // Test device connection
        $this->info('Testing attendance device connection...');
        if ($device->testConnection()) {
            $this->info('Attendance device connection successful');
        } else {
            $this->error('Attendance device connection failed');
        }

        $this->newLine();

        // Test remote API connection
        $this->info('Testing remote API connection...');
        if ($this->syncService->testConnection()) {
            $this->info('Remote API connection successful');
        } else {
            $this->error('Remote API connection failed');
        }

        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Display sample attendance records
     */
    private function displaySampleRecords(array $records): void
    {
        $this->info('Sample records (showing first 5):');

        $headers = ['User ID', 'Timestamp', 'Verify Type', 'Status'];
        $sampleData = array_slice($records, 0, 5);

        $rows = array_map(function ($record) {
            return [
                $record['user_id'],
                $record['timestamp'],
                $record['verify_type'],
                $record['status'],
            ];
        }, $sampleData);

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * Display sync results
     */
    private function displaySyncResults(array $result): void
    {
        if ($result['success']) {
            $this->info('Sync completed successfully!');
        } else {
            $this->error('Sync completed with errors');
        }

        $this->newLine();
        $this->info("Sync Summary:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Records', $result['total_records'] ?? 0],
                ['Successfully Sent', $result['sent']],
                ['Failed', $result['failed']],
                ['Batches', $result['batches'] ?? 1],
            ]
        );
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
