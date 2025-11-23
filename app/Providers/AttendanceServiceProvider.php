<?php

namespace App\Providers;

use App\Contracts\AttendanceDeviceInterface;
use App\Services\AttendanceSyncService;
use App\Services\Devices\NullDevice;
use App\Services\Devices\ZKTecoDevice;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AttendanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AttendanceDeviceInterface::class, function ($app) {
            return $this->createDevice(config('attendance.default'));
        });

        $this->app->singleton(AttendanceSyncService::class, function ($app) {
            return new AttendanceSyncService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Create an attendance device instance based on driver name
     */
    protected function createDevice(string $driver): AttendanceDeviceInterface
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
            'null' => new NullDevice(),
            default => throw new InvalidArgumentException("Unsupported attendance device driver: {$driver}"),
        };
    }
}
