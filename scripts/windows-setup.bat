@echo off
REM Attendance Sync Sync - Windows Setup Script
REM This script helps automate the initial setup on Windows

echo ================================================
echo Attendance Sync Sync - Windows Setup
echo ================================================
echo.

REM Check if running as Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: This script must be run as Administrator!
    echo Right-click this file and select "Run as administrator"
    pause
    exit /b 1
)

echo [1/6] Checking PHP installation...
php --version >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: PHP not found in PATH!
    echo Please ensure XAMPP is installed and PHP is in your PATH.
    pause
    exit /b 1
)
echo OK - PHP is installed
echo.

echo [2/6] Checking Composer installation...
composer --version >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: Composer not found!
    echo Please install Composer from https://getcomposer.org/
    pause
    exit /b 1
)
echo OK - Composer is installed
echo.

echo [3/6] Installing dependencies...
call composer install
if %errorLevel% neq 0 (
    echo ERROR: Failed to install dependencies
    pause
    exit /b 1
)
echo OK - Dependencies installed
echo.

echo [4/6] Setting up environment file...
if not exist .env (
    copy .env.example .env
    echo Created .env file
    echo IMPORTANT: Edit .env file with your device IP and API settings!
) else (
    echo .env file already exists
)
echo.

echo [5/6] Generating application key...
php artisan key:generate
echo.

echo [6/6] Setting up database...
if not exist database\database.sqlite (
    type nul > database\database.sqlite
    echo Created database file
)
php artisan migrate --force
echo.

echo ================================================
echo Setup Complete!
echo ================================================
echo.
echo NEXT STEPS:
echo 1. Edit .env file and configure:
echo    - ATTENDANCE_DEVICE_IP (your device IP address)
echo    - ATTENDANCE_API_URL (your server URL)
echo    - ATTENDANCE_API_KEY (your API key)
echo.
echo 2. Test the connection:
echo    php artisan attendance:sync --test
echo.
echo 3. Run manual sync:
echo    php artisan attendance:sync
echo.
echo For full deployment guide, see DEPLOYMENT_WINDOWS.md
echo.
pause
