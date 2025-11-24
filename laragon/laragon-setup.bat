@echo off
REM Initial setup script for Laragon deployment

echo =======================================
echo Attendance Sync Setup (Laragon)
echo =======================================
echo.

cd /d "%~dp0.."

REM Auto-detect paths
set PHP_PATH=C:\laragon\bin\php\php.exe
set COMPOSER_PATH=C:\laragon\bin\composer\composer.bat

if exist "C:\laragon\bin\php\php-8.2-Win32\php.exe" (
    set PHP_PATH=C:\laragon\bin\php\php-8.2-Win32\php.exe
)
if exist "C:\laragon\bin\php\php-8.3-nts-Win32\php.exe" (
    set PHP_PATH=C:\laragon\bin\php\php-8.3-nts-Win32\php.exe
)

if not exist "%COMPOSER_PATH%" (
    set COMPOSER_PATH=composer
)

echo Using PHP: %PHP_PATH%
echo Using Composer: %COMPOSER_PATH%
echo.

REM Step 1: Create .env file
echo Step 1: Creating .env file...
if not exist ".env" (
    copy .env.example .env
    echo .env file created from .env.example
    echo.
    echo IMPORTANT: Please edit .env file with your configuration!
    echo Press any key to edit .env now...
    pause >nul
    notepad .env
) else (
    echo .env file already exists.
)
echo.

REM Step 2: Install dependencies
echo Step 2: Installing dependencies...
call "%COMPOSER_PATH%" install --no-dev --optimize-autoloader
if %ERRORLEVEL% NEQ 0 (
    echo Failed to install dependencies!
    pause
    exit /b 1
)
echo.

REM Step 3: Generate app key
echo Step 3: Generating application key...
"%PHP_PATH%" artisan key:generate
echo.

REM Step 4: Create database directories
echo Step 4: Setting up database...
if not exist "database" mkdir database
if not exist "database\database.sqlite" (
    type nul > database\database.sqlite
    echo SQLite database created.
)
echo.

REM Step 5: Create storage directories
echo Step 5: Creating storage directories...
if not exist "storage\logs" mkdir storage\logs
if not exist "storage\framework\cache\data" mkdir storage\framework\cache\data
if not exist "storage\framework\sessions" mkdir storage\framework\sessions
if not exist "storage\framework\views" mkdir storage\framework\views
if not exist "bootstrap\cache" mkdir bootstrap\cache
echo Storage directories created.
echo.

REM Step 6: Run migrations
echo Step 6: Running migrations...
"%PHP_PATH%" artisan migrate --force
echo.

REM Step 7: Test connection
echo Step 7: Testing connection...
echo.
"%PHP_PATH%" artisan attendance:sync --test

echo.
if %ERRORLEVEL% EQU 0 (
    echo =======================================
    echo Setup completed successfully!
    echo =======================================
    echo.
    echo Next steps:
    echo 1. Run: laragon-sync.bat to test manual sync
    echo 2. Set up Windows Task Scheduler for automatic sync
    echo 3. Use laragon-logs.bat to view logs
) else (
    echo =======================================
    echo Setup completed with warnings!
    echo =======================================
    echo.
    echo Connection test failed. Please check:
    echo 1. Your .env configuration
    echo 2. Network connectivity to Attendance device
    echo 3. Remote API endpoint and credentials
)

echo.
pause
