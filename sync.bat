@echo off
REM ZKTeco Attendance Sync - Quick Sync Script
REM Run this manually or via Windows Task Scheduler

cd /d "%~dp0"

echo ================================================
echo ZKTeco Attendance Sync
echo Started: %date% %time%
echo ================================================

php artisan attendance:sync --clear

echo ================================================
echo Completed: %date% %time%
echo ================================================
