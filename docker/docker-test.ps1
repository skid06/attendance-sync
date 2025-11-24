# PowerShell script to test ZKTeco connections

Write-Host "=======================================" -ForegroundColor Cyan
Write-Host "Testing ZKTeco Connections" -ForegroundColor Cyan
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host ""

# Change to script directory
Set-Location "$PSScriptRoot/.."

Write-Host "Testing connection to ZKTeco device and remote API..." -ForegroundColor Yellow
Write-Host ""

docker compose run --rm zkteco-sync php artisan attendance:sync --test

Write-Host ""
if ($LASTEXITCODE -eq 0) {
    Write-Host "=======================================" -ForegroundColor Green
    Write-Host "Connection test completed successfully!" -ForegroundColor Green
    Write-Host "=======================================" -ForegroundColor Green
} else {
    Write-Host "=======================================" -ForegroundColor Red
    Write-Host "Connection test failed!" -ForegroundColor Red
    Write-Host "Check your .env configuration." -ForegroundColor Red
    Write-Host "=======================================" -ForegroundColor Red
}

Write-Host ""
Write-Host "Press any key to continue..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
