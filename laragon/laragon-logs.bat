@echo off
REM View application logs (Laragon)

echo =======================================
echo Attendance Application Logs
echo =======================================
echo.

cd /d "%~dp0.."

echo Select log view option:
echo.
echo 1. View Laravel logs (last 50 lines)
echo 2. View Laravel logs (last 100 lines)
echo 3. View Laravel logs (all)
echo 4. View sync logs (last 50 lines)
echo 5. Open logs folder
echo 6. Clear Laravel logs
echo.

set /p choice="Enter your choice (1-6): "

if "%choice%"=="1" (
    echo.
    echo Last 50 lines from Laravel logs:
    echo =======================================
    powershell -command "Get-Content storage\logs\laravel.log -Tail 50 -ErrorAction SilentlyContinue"
    echo.
    pause
) else if "%choice%"=="2" (
    echo.
    echo Last 100 lines from Laravel logs:
    echo =======================================
    powershell -command "Get-Content storage\logs\laravel.log -Tail 100 -ErrorAction SilentlyContinue"
    echo.
    pause
) else if "%choice%"=="3" (
    echo.
    echo All Laravel logs:
    echo =======================================
    type storage\logs\laravel.log
    echo.
    pause
) else if "%choice%"=="4" (
    echo.
    echo Last 50 lines from sync logs:
    echo =======================================
    powershell -command "Get-Content storage\logs\sync.log -Tail 50 -ErrorAction SilentlyContinue"
    echo.
    pause
) else if "%choice%"=="5" (
    echo.
    echo Opening logs folder...
    explorer storage\logs
) else if "%choice%"=="6" (
    echo.
    set /p confirm="Are you sure you want to clear Laravel logs? (Y/N): "
    if /i "%confirm%"=="Y" (
        echo. > storage\logs\laravel.log
        echo Laravel logs cleared.
    ) else (
        echo Cancelled.
    )
    echo.
    pause
) else (
    echo Invalid choice!
    pause
)
