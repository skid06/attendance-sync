# Build using simplified Dockerfile (fallback option)

Write-Host "=======================================" -ForegroundColor Cyan
Write-Host "Building with Simplified Dockerfile" -ForegroundColor Cyan
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host ""

Set-Location "$PSScriptRoot/.."

if (-not (Test-Path "Dockerfile.simple")) {
    Write-Host "ERROR: Dockerfile.simple not found!" -ForegroundColor Red
    exit 1
}

Write-Host "Building with Dockerfile.simple..." -ForegroundColor Yellow
Write-Host ""

docker build --progress=plain -f Dockerfile.simple -t attendance-sync-sync:latest . 2>&1 | Tee-Object -FilePath "build-simple.log"

Write-Host ""
if ($LASTEXITCODE -eq 0) {
    Write-Host "=======================================" -ForegroundColor Green
    Write-Host "Build successful!" -ForegroundColor Green
    Write-Host "=======================================" -ForegroundColor Green
} else {
    Write-Host "=======================================" -ForegroundColor Red
    Write-Host "Build failed!" -ForegroundColor Red
    Write-Host "=======================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Build log saved to: build-simple.log" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Press any key to continue..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
