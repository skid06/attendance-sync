<?php

namespace App\Contracts;

interface AttendanceDeviceInterface
{
    /**
     * Connect to the attendance device
     */
    public function connect(): bool;

    /**
     * Disconnect from the attendance device
     */
    public function disconnect(): bool;

    /**
     * Get attendance records from the device
     *
     * @return array Array of attendance records with standardized format:
     *               [
     *                   'user_id' => string,
     *                   'timestamp' => string (Y-m-d H:i:s),
     *                   'verify_type' => string,
     *                   'status' => string,
     *                   'raw_timestamp' => int (unix timestamp),
     *               ]
     */
    public function getAttendance(): array;

    /**
     * Clear attendance records from the device
     */
    public function clearAttendance(): bool;

    /**
     * Test connection to the device
     */
    public function testConnection(): bool;

    /**
     * Get device information
     *
     * @return array Device info such as name, serial, firmware version, etc.
     */
    public function getDeviceInfo(): array;
}
