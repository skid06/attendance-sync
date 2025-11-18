@echo off
REM Stop all ZKTeco Docker containers

echo =======================================
echo Stopping ZKTeco Docker Containers
echo =======================================
echo.

cd /d "%~dp0"

echo Stopping containers...
docker-compose down

if %ERRORLEVEL% EQU 0 (
    echo.
    echo =======================================
    echo All containers stopped successfully!
    echo =======================================
) else (
    echo =======================================
    echo Failed to stop containers!
    echo =======================================
)

echo.
pause
