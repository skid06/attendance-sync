<?php

namespace App\Services\Devices;

use App\Contracts\AttendanceDeviceInterface;

/**
 * Null device for testing purposes
 */
class NullDevice implements AttendanceDeviceInterface
{
    public function connect(): bool
    {
        return true;
    }

    public function disconnect(): bool
    {
        return true;
    }

    public function testConnection(): bool
    {
        return true;
    }

    public function getAttendance(): array
    {
        return [];
    }

    public function clearAttendance(): bool
    {
        return true;
    }

    public function getDeviceInfo(): array
    {
        return [
            'type' => 'null',
            'description' => 'Null device for testing',
        ];
    }
}
