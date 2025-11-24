# PowerShell script to build Docker image for Attendance Sync Sync

Write-Host "=======================================" -ForegroundColor Cyan
Write-Host "Building Attendance Sync Docker Image" -ForegroundColor Cyan
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host ""

# Change to script directory
Set-Location "$PSScriptRoot/.."

Write-Host "Building image..." -ForegroundColor Yellow
docker compose build

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "=======================================" -ForegroundColor Green
    Write-Host "Build completed successfully!" -ForegroundColor Green
    Write-Host "=======================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "1. Run: .\docker-test.ps1 to test connections" -ForegroundColor White
    Write-Host "2. Run: .\docker-sync.ps1 to sync manually" -ForegroundColor White
    Write-Host "3. Run: .\docker-start-scheduled.ps1 for automatic hourly sync" -ForegroundColor White
} else {
    Write-Host ""
    Write-Host "=======================================" -ForegroundColor Red
    Write-Host "Build failed! Check the errors above." -ForegroundColor Red
    Write-Host "=======================================" -ForegroundColor Red
}

Write-Host ""
Write-Host "Press any key to continue..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
