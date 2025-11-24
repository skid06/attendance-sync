@echo off
REM Attendance Connection Test
REM Use this to quickly test if the device is accessible

cd /d "%~dp0.."

echo ================================================
echo Attendance Connection Test
echo ================================================
echo.

php artisan attendance:sync --test

echo.
pause
