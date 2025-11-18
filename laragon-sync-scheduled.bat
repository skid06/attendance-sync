@echo off
REM Scheduled sync script for Windows Task Scheduler (Laragon)
REM This script runs silently and logs output

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

REM Create logs directory if it doesn't exist
if not exist "storage\logs" mkdir storage\logs

REM Run sync and append to log file
echo [%date% %time%] Starting sync... >> storage\logs\sync.log
"%PHP_PATH%" artisan attendance:sync --clear >> storage\logs\sync.log 2>&1
echo [%date% %time%] Sync completed with exit code: %ERRORLEVEL% >> storage\logs\sync.log
echo. >> storage\logs\sync.log
