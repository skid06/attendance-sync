# Diagnose Docker installation and try building with verbose output

Write-Host "=======================================" -ForegroundColor Cyan
Write-Host "Docker Diagnostics" -ForegroundColor Cyan
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host ""

Set-Location "$PSScriptRoot/.."

# Check Docker is running
Write-Host "1. Checking Docker installation..." -ForegroundColor Yellow
docker --version
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Docker is not installed or not running!" -ForegroundColor Red
    exit 1
}
Write-Host "   OK" -ForegroundColor Green
Write-Host ""

# Check Docker daemon
Write-Host "2. Checking Docker daemon..." -ForegroundColor Yellow
docker info | Select-String "Server Version"
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Cannot connect to Docker daemon!" -ForegroundColor Red
    Write-Host "Make sure Docker Desktop is running." -ForegroundColor Yellow
    exit 1
}
Write-Host "   OK" -ForegroundColor Green
Write-Host ""

# Test pulling a simple image
Write-Host "3. Testing Docker pull (network test)..." -ForegroundColor Yellow
docker pull hello-world:latest
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Cannot pull images from Docker Hub!" -ForegroundColor Red
    Write-Host "This might be a network/proxy issue." -ForegroundColor Yellow
} else {
    Write-Host "   OK" -ForegroundColor Green
}
Write-Host ""

# Check if Dockerfile exists
Write-Host "4. Checking Dockerfile..." -ForegroundColor Yellow
if (Test-Path "Dockerfile") {
    Write-Host "   Dockerfile found" -ForegroundColor Green
} else {
    Write-Host "ERROR: Dockerfile not found!" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Try building with maximum verbosity
Write-Host "5. Attempting build with verbose output..." -ForegroundColor Yellow
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host ""

docker build --progress=plain --no-cache -t attendance-sync-sync:latest . 2>&1 | Tee-Object -FilePath "build.log"

Write-Host ""
Write-Host "=======================================" -ForegroundColor Cyan
if ($LASTEXITCODE -eq 0) {
    Write-Host "Build successful!" -ForegroundColor Green
} else {
    Write-Host "Build failed!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Build log saved to: build.log" -ForegroundColor Yellow
    Write-Host "Please check the log file for details." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Press any key to continue..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
