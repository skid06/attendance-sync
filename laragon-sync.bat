@echo off
REM Run manual sync using Laragon PHP

echo =======================================
echo ZKTeco Attendance Manual Sync (Laragon)
echo =======================================
echo.

cd /d "%~dp0"

REM Auto-detect PHP path in Laragon
set PHP_PATH=C:\laragon\bin\php\php.exe

REM Check if custom PHP path exists
if exist "C:\laragon\bin\php\php-8.2-Win32\php.exe" (
    set PHP_PATH=C:\laragon\bin\php\php-8.2-Win32\php.exe
)
if exist "C:\laragon\bin\php\php-8.3-nts-Win32\php.exe" (
    set PHP_PATH=C:\laragon\bin\php\php-8.3-nts-Win32\php.exe
)

echo Using PHP: %PHP_PATH%
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
