@echo off
REM Run manual sync using Laragon PHP

echo =======================================
echo ZKTeco Attendance Manual Sync (Laragon)
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

echo Starting sync...
echo.

"%PHP_PATH%" artisan attendance:sync --clear

echo.
if %ERRORLEVEL% EQU 0 (
    echo =======================================
    echo Sync completed successfully!
    echo =======================================
) else (
    echo =======================================
    echo Sync failed! Check logs for details.
    echo =======================================
)

echo.
pause
