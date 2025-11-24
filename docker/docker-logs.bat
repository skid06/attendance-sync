@echo off
REM View Docker container logs

echo =======================================
echo ZKTeco Docker Container Logs
echo =======================================
echo.

cd /d "%~dp0.."

echo Select log view option:
echo.
echo 1. View live logs (follow)
echo 2. View last 100 lines
echo 3. View last 50 lines
echo 4. View all logs
echo 5. View application logs (Laravel)
echo.

set /p choice="Enter your choice (1-5): "

if "%choice%"=="1" (
    echo.
    echo Following logs... Press Ctrl+C to stop.
    echo.
    docker compose logs -f zkteco-sync-scheduled
) else if "%choice%"=="2" (
    echo.
    docker compose logs --tail=100 zkteco-sync-scheduled
    echo.
    pause
) else if "%choice%"=="3" (
    echo.
    docker compose logs --tail=50 zkteco-sync-scheduled
    echo.
    pause
) else if "%choice%"=="4" (
    echo.
    docker compose logs zkteco-sync-scheduled
    echo.
    pause
) else if "%choice%"=="5" (
    echo.
    echo Last 50 lines from Laravel logs:
    echo =======================================
    powershell -command "Get-Content storage\logs\laravel.log -Tail 50"
    echo.
    pause
) else (
    echo Invalid choice!
    pause
)
