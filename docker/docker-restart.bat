@echo off
REM Restart ZKTeco Docker containers

echo =======================================
echo Restarting ZKTeco Docker Containers
echo =======================================
echo.

cd /d "%~dp0.."

echo Stopping containers...
docker compose down

echo.
echo Starting containers...
docker compose --profile scheduled up -d

if %ERRORLEVEL% EQU 0 (
    echo.
    echo =======================================
    echo Containers restarted successfully!
    echo =======================================
) else (
    echo =======================================
    echo Failed to restart containers!
    echo =======================================
)

echo.
pause
