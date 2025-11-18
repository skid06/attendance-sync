@echo off
REM View status of ZKTeco Docker containers

echo =======================================
echo ZKTeco Docker Container Status
echo =======================================
echo.

cd /d "%~dp0"

echo Running containers:
echo =======================================
docker ps --filter "name=zkteco"

echo.
echo =======================================
echo All ZKTeco containers (including stopped):
echo =======================================
docker ps -a --filter "name=zkteco"

echo.
echo =======================================
echo Docker images:
echo =======================================
docker images | findstr "zkteco"

echo.
echo =======================================
echo Disk usage:
echo =======================================
docker system df

echo.
pause
