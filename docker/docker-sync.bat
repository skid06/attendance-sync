@echo off
REM Run manual sync using Docker

echo =======================================
echo Attendance Sync Manual Sync
echo =======================================
echo.

cd /d "%~dp0.."

echo Starting sync...
echo.

docker compose run --rm zkteco-sync php artisan attendance:sync --clear

echo.
if %ERRORLEVEL% EQU 0 (
    echo =======================================
    echo Sync completed successfully!
    echo =======================================
) else (
    echo =======================================
    echo Sync failed! Check logs for details.
    echo =======================================
)

echo.
pause
