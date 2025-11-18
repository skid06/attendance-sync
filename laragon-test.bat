@echo off
REM Test ZKTeco connection using Laragon PHP

echo =======================================
echo Testing ZKTeco Connections (Laragon)
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

echo Testing connection to ZKTeco device and remote API...
echo.

"%PHP_PATH%" artisan attendance:sync --test

echo.
if %ERRORLEVEL% EQU 0 (
    echo =======================================
    echo Connection test completed successfully!
    echo =======================================
) else (
    echo =======================================
    echo Connection test failed!
    echo Check your .env configuration.
    echo =======================================
)

echo.
pause
