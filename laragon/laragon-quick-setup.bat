@echo off
REM Quick setup for Laragon - Manual PHP path entry

echo =======================================
echo Attendance Quick Setup (Laragon)
echo =======================================
echo.

cd /d "%~dp0.."

echo Detecting PHP installations...
echo.
dir C:\laragon\bin\php /b
echo.

echo Please enter your PHP folder name from the list above
echo Example: php-8.2-Win32 or php-8.3-nts-Win32
echo.
set /p PHP_FOLDER="Enter PHP folder name: "

set PHP_PATH=C:\laragon\bin\php\%PHP_FOLDER%\php.exe

if not exist "%PHP_PATH%" (
    echo.
    echo ERROR: PHP not found at: %PHP_PATH%
    echo Please check the folder name and try again.
    pause
    exit /b 1
)

echo.
echo Using PHP: %PHP_PATH%
echo.

REM Verify PHP works
"%PHP_PATH%" -v
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: PHP is not working!
    pause
    exit /b 1
)

echo.
echo =======================================
echo Starting Setup...
echo =======================================
echo.

REM Create .env
if not exist ".env" (
    echo Creating .env file...
    copy .env.example .env
    echo.
    set /p edit="Edit .env now? (Y/N): "
    if /i "%edit%"=="Y" notepad .env
)

REM Install Composer dependencies
echo.
echo Installing dependencies...
"%PHP_PATH%" C:\laragon\bin\composer\composer.phar install --no-dev --optimize-autoloader

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ERROR: Composer install failed!
    pause
    exit /b 1
)

REM Generate key
echo.
echo Generating application key...
"%PHP_PATH%" artisan key:generate

REM Create database
echo.
echo Creating database...
if not exist "database" mkdir database
if not exist "database\database.sqlite" type nul > database\database.sqlite

REM Create directories
echo Creating storage directories...
if not exist "storage\logs" mkdir storage\logs
if not exist "storage\framework\cache\data" mkdir storage\framework\cache\data
if not exist "storage\framework\sessions" mkdir storage\framework\sessions
if not exist "storage\framework\views" mkdir storage\framework\views
if not exist "bootstrap\cache" mkdir bootstrap\cache

REM Run migrations
echo.
echo Running migrations...
"%PHP_PATH%" artisan migrate --force

REM Save config
echo.
echo Saving configuration...
echo SET PHP_PATH=%PHP_PATH% > laragon-config.bat
echo SET COMPOSER_PATH=C:\laragon\bin\composer\composer.phar >> laragon-config.bat

REM Test connection
echo.
echo =======================================
echo Testing connection...
echo =======================================
echo.
"%PHP_PATH%" artisan attendance:sync --test

echo.
if %ERRORLEVEL% EQU 0 (
    echo =======================================
    echo Setup completed successfully!
    echo =======================================
) else (
    echo =======================================
    echo Setup complete but connection test failed
    echo =======================================
    echo Please check your .env configuration
)

echo.
echo Configuration saved to laragon-config.bat
echo You can now use: laragon-test.bat and laragon-sync.bat
echo.
pause
