@echo off
REM Create Windows Task Scheduler task for automatic sync (Laragon)

echo =======================================
echo Create Scheduled Task (Laragon)
echo =======================================
echo.

cd /d "%~dp0.."

set SCRIPT_PATH=%~dp0laragon-sync-scheduled.bat

echo This will create a Windows Task Scheduler task to run
echo the sync automatically every hour.
echo.
echo Script path: %SCRIPT_PATH%
echo.

set /p confirm="Do you want to continue? (Y/N): "

if /i not "%confirm%"=="Y" (
    echo Cancelled.
    pause
    exit /b 0
)

echo.
echo Creating scheduled task...
echo.

schtasks /create /tn "Attendance Laragon Sync" /tr "%SCRIPT_PATH%" /sc hourly /st 09:00 /f

if %ERRORLEVEL% EQU 0 (
    echo.
    echo =======================================
    echo Task created successfully!
    echo =======================================
    echo.
    echo Task name: Attendance Laragon Sync
    echo Schedule: Every hour starting at 9:00 AM
    echo.
    echo To view the task:
    echo   schtasks /query /tn "Attendance Laragon Sync"
    echo.
    echo To run the task manually:
    echo   schtasks /run /tn "Attendance Laragon Sync"
    echo.
    echo To delete the task:
    echo   schtasks /delete /tn "Attendance Laragon Sync"
) else (
    echo.
    echo =======================================
    echo Failed to create task!
    echo =======================================
    echo.
    echo Make sure you run this script as Administrator.
)

echo.
pause
