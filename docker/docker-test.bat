@echo off
REM Test ZKTeco device and API connection using Docker

echo =======================================
echo Testing ZKTeco Connections
echo =======================================
echo.

cd /d "%~dp0.."

echo Testing connection to ZKTeco device and remote API...
echo.

docker compose run --rm zkteco-sync php artisan attendance:sync --test

echo.
if %ERRORLEVEL% EQU 0 (
    echo =======================================
    echo Connection test completed successfully!
    echo =======================================
) else (
    echo =======================================
    echo Connection test failed!
    echo Check your .env configuration.
    echo =======================================
)

echo.
pause
