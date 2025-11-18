@echo off
REM Test ZKTeco connection using Laragon PHP

echo =======================================
echo Testing ZKTeco Connections (Laragon)
echo =======================================
echo.

cd /d "%~dp0"

REM Load saved configuration
if exist "laragon-config.bat" (
    call laragon-config.bat
    echo Using saved config: %PHP_PATH%
) else (
    echo ERROR: Configuration not found!
    echo Please run laragon-quick-setup.bat first.
    pause
    exit /b 1
)

echo.

echo Testing connection to ZKTeco device and remote API...
echo.

"%PHP_PATH%" artisan attendance:sync --test

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
