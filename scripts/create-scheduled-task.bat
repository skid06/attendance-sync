@echo off
REM Create Windows Scheduled Task for Automatic Sync
REM This script must be run as Administrator

echo ================================================
echo Create Scheduled Task for Attendance Sync
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

set SCRIPT_DIR=%~dp0
set PHP_PATH=C:\xampp\php\php.exe
set SYNC_SCRIPT=%SCRIPT_DIR%sync.bat

echo This will create a scheduled task that runs every hour.
echo.
echo Configuration:
echo - Task Name: Attendance Sync Sync
echo - Schedule: Every 1 hour
echo - Script: %SYNC_SCRIPT%
echo.

set /p CONFIRM="Continue? (Y/N): "
if /i not "%CONFIRM%"=="Y" (
    echo Cancelled.
    pause
    exit /b 0
)

echo.
echo Creating scheduled task...

schtasks /create /tn "Attendance Sync Sync" /tr "\"%SYNC_SCRIPT%\"" /sc hourly /st 09:00 /ru SYSTEM /f

if %errorLevel% equ 0 (
    echo.
    echo SUCCESS! Scheduled task created.
    echo.
    echo The task will run every hour starting at 09:00.
    echo.
    echo To modify the schedule:
    echo 1. Open Task Scheduler (Win + R, type: taskschd.msc)
    echo 2. Find "Attendance Sync Sync" in the task list
    echo 3. Right-click and select Properties
    echo.
    echo To test the task now, run:
    echo schtasks /run /tn "Attendance Sync Sync"
) else (
    echo.
    echo ERROR: Failed to create scheduled task.
)

echo.
pause
