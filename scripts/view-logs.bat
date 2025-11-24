@echo off
REM View Application Logs
REM Opens the log file in Notepad

cd /d "%~dp0.."

if exist storage\logs\laravel.log (
    notepad storage\logs\laravel.log
) else (
    echo No log file found at storage\logs\laravel.log
    pause
)
