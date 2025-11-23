<?php

namespace App\Console\Commands;

use App\Contracts\AttendanceDeviceInterface;
use App\Services\AttendanceSyncService;
use Illuminate\Console\Command;
use Exception;

class SyncAttendanceData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:sync
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
        private AttendanceDeviceInterface $device,
        private AttendanceSyncService $syncService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deviceInfo = $this->device->getDeviceInfo();
        $driverName = ucfirst($deviceInfo['type'] ?? 'Unknown');

        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info("║       Attendance Data Sync ({$driverName})                      ");
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        try {
            // Test connection mode
            if ($this->option('test')) {
                return $this->testConnections();
            }

            // Connect to device
            $this->info('Connecting to attendance device...');
            if (!$this->device->connect()) {
                $this->error('Failed to connect to attendance device');
                return Command::FAILURE;
            }
            $this->info('Connected successfully');
            $this->newLine();

            // Get attendance records
            $this->info('Fetching attendance records from device...');
            $records = $this->device->getAttendance();

            if (empty($records)) {
                $this->warn('No attendance records found on device');
                $this->device->disconnect();
                return Command::SUCCESS;
            }

            $this->info("Retrieved " . count($records) . " attendance records");
            $this->newLine();

            // Display sample records
            $this->displaySampleRecords($records);

            // Confirm before sending
            if (!$this->confirm('Do you want to send these records to the remote server?', true)) {
                $this->warn('Sync cancelled by user');
                $this->device->disconnect();
                return Command::SUCCESS;
            }

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
                    if ($this->device->clearAttendance()) {
                        $this->info('Attendance records cleared successfully');
                    } else {
                        $this->error('Failed to clear attendance records');
                    }
                }
            }

            // Disconnect from device
            $this->device->disconnect();

            $this->newLine();
            $this->info('Sync process completed');

            return $result['success'] ? Command::SUCCESS : Command::FAILURE;

        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());

            if (config('attendance.debug')) {
                $this->error($e->getTraceAsString());
            }

            $this->device->disconnect();

            return Command::FAILURE;
        }
    }

    /**
     * Test connections to device and remote API
     */
    private function testConnections(): int
    {
        $this->info('Testing connections...');
        $this->newLine();

        // Test device connection
        $this->info('Testing attendance device connection...');
        if ($this->device->testConnection()) {
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
}
