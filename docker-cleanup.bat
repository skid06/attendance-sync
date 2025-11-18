@echo off
REM Clean up Docker resources

echo =======================================
echo Docker Cleanup Utility
echo =======================================
echo.
echo WARNING: This will remove unused Docker resources.
echo Your database and logs will NOT be affected.
echo.

cd /d "%~dp0"

echo Select cleanup option:
echo.
echo 1. Remove stopped containers only
echo 2. Remove unused images
echo 3. Full cleanup (containers, images, cache)
echo 4. Cancel
echo.

set /p choice="Enter your choice (1-4): "

if "%choice%"=="1" (
    echo.
    echo Removing stopped containers...
    docker container prune -f
    echo Done!
) else if "%choice%"=="2" (
    echo.
    echo Removing unused images...
    docker image prune -a -f
    echo Done!
) else if "%choice%"=="3" (
    echo.
    echo Performing full cleanup...
    docker-compose down
    docker system prune -a -f
    echo Done!
    echo.
    echo Note: You'll need to rebuild the image before using again.
    echo Run: docker-build.bat
) else if "%choice%"=="4" (
    echo Cancelled.
) else (
    echo Invalid choice!
)

echo.
pause
