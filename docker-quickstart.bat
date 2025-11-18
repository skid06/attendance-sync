@echo off
REM Quick start setup for ZKTeco Docker deployment

echo =======================================
echo ZKTeco Attendance Docker Quick Start
echo =======================================
echo.

cd /d "%~dp0"

REM Check if Docker is installed
docker --version >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Docker is not installed or not in PATH!
    echo.
    echo Please install Docker Desktop from:
    echo https://www.docker.com/products/docker-desktop/
    echo.
    pause
    exit /b 1
)

echo Docker is installed.
echo.

REM Check if .env exists
if not exist ".env" (
    echo Creating .env file from .env.example...
    copy .env.example .env
    echo.
    echo IMPORTANT: Please edit .env file with your configuration:
    echo - ZKTECO_DEVICE_IP: Your ZKTeco device IP address
    echo - REMOTE_API_URL: Your remote API endpoint
    echo - REMOTE_API_KEY: Your API key
    echo.
    pause
    notepad .env
)

REM Create required directories
echo Creating required directories...
if not exist "database" mkdir database
if not exist "storage\logs" mkdir storage\logs

REM Create SQLite database if it doesn't exist
if not exist "database\database.sqlite" (
    echo Creating SQLite database...
    type nul > database\database.sqlite
)

echo.
echo =======================================
echo Step 1: Building Docker Image
echo =======================================
echo.

docker compose build

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo Build failed! Please check the errors above.
    pause
    exit /b 1
)

echo.
echo =======================================
echo Step 2: Testing Connections
echo =======================================
echo.

docker compose run --rm zkteco-sync php artisan attendance:sync --test

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo Connection test failed!
    echo Please check your .env configuration.
    echo.
    pause
    exit /b 1
)

echo.
echo =======================================
echo Setup Complete!
echo =======================================
echo.
echo What would you like to do next?
echo.
echo 1. Run manual sync now
echo 2. Start scheduled sync (every hour)
echo 3. Exit
echo.

set /p choice="Enter your choice (1-3): "

if "%choice%"=="1" (
    echo.
    echo Running manual sync...
    docker compose run --rm zkteco-sync php artisan attendance:sync --clear
) else if "%choice%"=="2" (
    echo.
    echo Starting scheduled sync container...
    docker compose --profile scheduled up -d
    echo.
    echo Scheduled sync is now running!
    echo It will sync every hour automatically.
    echo.
    echo Use docker-logs.bat to view logs.
    echo Use docker-stop.bat to stop the service.
) else (
    echo.
    echo Setup complete! You can now use the other batch scripts:
    echo - docker-sync.bat: Run manual sync
    echo - docker-start-scheduled.bat: Start automatic hourly sync
    echo - docker-logs.bat: View logs
    echo - docker-stop.bat: Stop containers
)

echo.
pause
