@echo off
REM Start scheduled Docker container (runs sync every hour)

echo =======================================
echo Starting Scheduled ZKTeco Sync Container
echo =======================================
echo.

cd /d "%~dp0"

echo Starting container...
echo This will run sync every hour automatically.
echo.

docker compose --profile scheduled up -d

if %ERRORLEVEL% EQU 0 (
    echo.
    echo =======================================
    echo Container started successfully!
    echo =======================================
    echo.
    echo The sync will run every hour automatically.
    echo.
    echo To view logs: docker-logs.bat
    echo To stop: docker-stop.bat
) else (
    echo.
    echo =======================================
    echo Failed to start container!
    echo =======================================
)

echo.
pause
