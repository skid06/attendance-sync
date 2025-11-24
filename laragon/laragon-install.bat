@echo off
REM Install/Update dependencies using Laragon Composer

echo =======================================
echo Installing Dependencies (Laragon)
echo =======================================
echo.

cd /d "%~dp0.."

REM Auto-detect Composer path
set COMPOSER_PATH=C:\laragon\bin\composer\composer.bat

if not exist "%COMPOSER_PATH%" (
    set COMPOSER_PATH=composer
)

echo Using Composer: %COMPOSER_PATH%
echo.

echo Installing Composer dependencies...
echo This may take a few minutes...
echo.

call "%COMPOSER_PATH%" install --no-dev --optimize-autoloader

echo.
if %ERRORLEVEL% EQU 0 (
    echo =======================================
    echo Dependencies installed successfully!
    echo =======================================
) else (
    echo =======================================
    echo Installation failed!
    echo =======================================
)

echo.
pause
