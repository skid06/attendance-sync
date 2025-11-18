@echo off
REM Build Docker image for ZKTeco Attendance Sync

echo =======================================
echo Building ZKTeco Attendance Docker Image
echo =======================================
echo.

cd /d "%~dp0"

echo Building image...
docker compose build

if %ERRORLEVEL% EQU 0 (
    echo.
    echo =======================================
    echo Build completed successfully!
    echo =======================================
    echo.
    echo Next steps:
    echo 1. Run: docker-test.bat to test connections
    echo 2. Run: docker-sync.bat to sync manually
    echo 3. Run: docker-start-scheduled.bat for automatic hourly sync
) else (
    echo.
    echo =======================================
    echo Build failed! Check the errors above.
    echo =======================================
)

echo.
pause
