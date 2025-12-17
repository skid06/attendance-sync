<?php

namespace App\Services\Devices;

use App\Contracts\AttendanceDeviceInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HikVisionDevice implements AttendanceDeviceInterface
{
    private string $connection;
    private string $table;
    private int $fetchMinutes;
    private int $duplicateThreshold;
    private bool $connected = false;

    public function __construct(array $config)
    {
        $this->connection = $config['connection'] ?? 'local_attendance';
        $this->table = $config['table'] ?? 'attendance_records';
        $this->fetchMinutes = $config['fetch_minutes'] ?? 10;
        $this->duplicateThreshold = $config['duplicate_threshold'] ?? 1; // minutes
    }

    /**
     * Test database connection
     */
    public function connect(): bool
    {
        try {
            DB::connection($this->connection)->getPdo();
            $this->connected = true;
            Log::info("Connected to HikVision local database: {$this->connection}");
            return true;
        } catch (Exception $e) {
            Log::error("Failed to connect to HikVision database: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Disconnect (no-op for database connections)
     */
    public function disconnect(): bool
    {
        $this->connected = false;
        Log::info("Disconnected from HikVision database");
        return true;
    }

    /**
     * Get attendance records from local database (last N minutes based on authDateTime)
     */
    public function getAttendance(): array
    {
        try {
            // authDateTime is a DateTime string, not milliseconds
            $threshold = now()->subMinutes($this->fetchMinutes)->format('Y-m-d H:i:s');

            Log::info("Querying HikVision database", [
                'threshold' => $threshold,
                'fetch_minutes' => $this->fetchMinutes,
            ]);

            $rawRecords = DB::connection($this->connection)
                ->table($this->table)
                ->where('authDateTime', '>=', $threshold)
                ->orderBy('authDateTime', 'desc')
                ->get();

            // Only log when records are found
            if ($rawRecords->count() > 0) {
                Log::info("Retrieved {count} records from HikVision database (last {minutes} minutes)", [
                    'count' => $rawRecords->count(),
                    'minutes' => $this->fetchMinutes,
                    'threshold' => $epochThreshold,
                ]);
            }

            return $this->transformAttendanceData($rawRecords->toArray());

        } catch (Exception $e) {
            Log::error("Error getting attendance from HikVision database: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get attendance records since a specific timestamp (for real-time sync)
     */
    public function getAttendanceSince(int $unixTimestamp): array
    {
        try {
            // Convert Unix timestamp to DateTime string for comparison
            $threshold = date('Y-m-d H:i:s', $unixTimestamp);

            $rawRecords = DB::connection($this->connection)
                ->table($this->table)
                ->where('authDateTime', '>', $threshold)
                ->orderBy('authDateTime', 'asc')
                ->get();

            // Only log when records are found
            if ($rawRecords->count() > 0) {
                Log::info("Retrieved {count} new records from HikVision database", [
                    'count' => $rawRecords->count(),
                    'since' => date('Y-m-d H:i:s', $unixTimestamp),
                ]);
            }

            return $this->transformAttendanceData($rawRecords->toArray());

        } catch (Exception $e) {
            Log::error("Error getting attendance since timestamp from HikVision database: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clear attendance (not applicable for HikVision - data managed externally)
     */
    public function clearAttendance(): bool
    {
        Log::warning("Clear attendance not supported for HikVision device (database managed externally)");
        return false;
    }

    /**
     * Test connection to database
     */
    public function testConnection(): bool
    {
        return $this->connect();
    }

    /**
     * Get device information
     */
    public function getDeviceInfo(): array
    {
        try {
            $latestRecord = DB::connection($this->connection)
                ->table($this->table)
                ->orderBy('authDateTime', 'desc')
                ->first();

            return [
                'type' => 'hikvision',
                'connection' => $this->connection,
                'table' => $this->table,
                'fetch_minutes' => $this->fetchMinutes,
                'connected' => $this->connected,
                'latest_time' => $latestRecord->authDateTime ?? null,  // authDateTime is already in DateTime format
            ];
        } catch (Exception $e) {
            Log::error("Error getting HikVision device info: " . $e->getMessage());
            return [
                'type' => 'hikvision',
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Transform database records to standardized attendance format
     */
    private function transformAttendanceData(array $rawData): array
    {
        $transformed = [];

        foreach ($rawData as $record) {
            // Convert stdClass to array if needed
            $record = (array) $record;

            $transformed[] = [
                // Standardized fields
                'employee_id' => $record['employee_id'] ?? $record['employee_id'] ?? 'Unknown',
                'timestamp' => isset($record['authDateTime']) && !empty($record['authDateTime'])
                    ? $record['authDateTime']  // authDateTime is already in DateTime format
                    : ($record['AttendanceTime'] ?? date('Y-m-d H:i:s')),
                'verify_type' => $this->getVerifyTypeName($record['AttendanceMethod'] ?? $record['verify_type'] ?? 0),
                'status' => $this->getStatusName($record['AttendanceState'] ?? $record['status'] ?? 0),
                'raw_timestamp' => isset($record['authDateTime']) && !empty($record['authDateTime'])
                    ? strtotime($record['authDateTime'])  // Convert DateTime string to Unix timestamp
                    : strtotime($record['AttendanceTime'] ?? 'now'),

                // Additional HikVision-specific fields
                'person_id' => $record['PersonID'] ?? null,
                'person_name' => $record['PersonName'] ?? null,
                'person_card_no' => $record['PerSonCardNo'] ?? null,
                'attendance_datetime' => $record['authDateTime'] ?? null,  // Use authDateTime field
                'attendance_state' => $record['AttendanceState'] ?? null,
                'attendance_method' => $record['AttendanceMethod'] ?? null,
                'device_ip_address' => $record['DeviceIPAddress'] ?? null,
                'device_name' => $record['DeviceName'] ?? null,
                'snapshots_path' => $record['SnapshotsPath'] ?? null,
                'handler' => $record['Handler'] ?? null,
                'attendance_utc_time' => $record['AttendanceUtcTime'] ?? null,
                'remarks' => $record['Remarks'] ?? null,
            ];
        }

        // Remove duplicates before returning
        return $this->removeDuplicates($transformed);
    }

    /**
     * Remove duplicate attendance records
     * Removes:
     * 1. Same person + same status within threshold minutes
     * 2. Same person + rapid status change within threshold minutes (e.g., Clock Out → Clock In)
     */
    private function removeDuplicates(array $records): array
    {
        if (empty($records)) {
            return $records;
        }

        $thresholdSeconds = $this->duplicateThreshold * 60;
        $filtered = [];
        $removed = 0;

        // Group by person to check duplicates per person
        $byPerson = [];
        foreach ($records as $record) {
            $personId = $record['person_id'] ?? $record['user_id'] ?? 'unknown';
            if (!isset($byPerson[$personId])) {
                $byPerson[$personId] = [];
            }
            $byPerson[$personId][] = $record;
        }

        // Process each person's records
        foreach ($byPerson as $personRecords) {
            // Sort by timestamp
            usort($personRecords, function($a, $b) {
                return $a['raw_timestamp'] - $b['raw_timestamp'];
            });

            // Track last kept record (any status)
            $lastKeptTimestamp = null;
            $lastKeptStatus = null;

            foreach ($personRecords as $record) {
                $status = $record['status'] ?? 'Unknown';
                $timestamp = $record['raw_timestamp'];

                $isDuplicate = false;

                if ($lastKeptTimestamp !== null) {
                    $timeDiff = abs($timestamp - $lastKeptTimestamp);

                    if ($timeDiff <= $thresholdSeconds) {
                        // Check if same status (duplicate scan)
                        if ($status === $lastKeptStatus) {
                            $isDuplicate = true;
                            $removed++;
                        }
                        // Check if rapid status change (Clock In → Clock Out or vice versa)
                        else {
                            $isDuplicate = true;
                            $removed++;
                        }
                    }
                }

                if (!$isDuplicate) {
                    $filtered[] = $record;
                    $lastKeptTimestamp = $timestamp;
                    $lastKeptStatus = $status;
                }
            }
        }

        // Log if duplicates were removed
        if ($removed > 0) {
            Log::info("Removed {$removed} duplicate attendance records", [
                'original_count' => count($records),
                'after_dedup' => count($filtered),
                'removed' => $removed,
                'threshold_minutes' => $this->duplicateThreshold,
            ]);
        }

        return $filtered;
    }

    /**
     * Get verify type name
     */
    private function getVerifyTypeName($type): string
    {
        $types = [
            0 => 'Password',
            1 => 'Fingerprint',
            2 => 'Card',
            3 => 'Face',
            4 => 'Fingerprint and Password',
            5 => 'Card and Password',
            6 => 'Face and Password',
        ];

        return $types[$type] ?? 'Unknown';
    }

    /**
     * Get status name
     */
    private function getStatusName($status): string
    {
        $statuses = [
            0 => 'Check In',
            1 => 'Check Out',
            2 => 'Break Out',
            3 => 'Break In',
            4 => 'Overtime In',
            5 => 'Overtime Out',
        ];

        return $statuses[$status] ?? 'Unknown';
    }
}
