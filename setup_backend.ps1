# Donation App Backend Setup Script
# Run this in PowerShell to automatically set up the backend

Write-Host "=== Donation App Backend Setup ===" -ForegroundColor Cyan
Write-Host ""

# Configuration
$sourcePath = "F:\donation_app\donation_backend"
$destPath = "C:\xampp\htdocs\donation_backend"
$xamppPath = "C:\xampp"

# Check if XAMPP exists
if (!(Test-Path $xamppPath)) {
    Write-Host "ERROR: XAMPP not found at $xamppPath" -ForegroundColor Red
    Write-Host "Please install XAMPP or update the xamppPath variable in this script." -ForegroundColor Yellow
    exit 1
}

# Check if source exists
if (!(Test-Path $sourcePath)) {
    Write-Host "ERROR: Backend source not found at $sourcePath" -ForegroundColor Red
    exit 1
}

# Step 1: Copy backend to htdocs
Write-Host "[1/4] Copying backend files to XAMPP htdocs..." -ForegroundColor Yellow
try {
    if (Test-Path $destPath) {
        Write-Host "      Removing old files..." -ForegroundColor Gray
        Remove-Item -Path $destPath -Recurse -Force
    }
    Copy-Item -Path $sourcePath -Destination $destPath -Recurse -Force
    Write-Host "      ✓ Backend files copied successfully" -ForegroundColor Green
} catch {
    Write-Host "      ✗ Failed to copy files: $_" -ForegroundColor Red
    exit 1
}

# Step 2: Create uploads directory
Write-Host "[2/4] Creating uploads directory..." -ForegroundColor Yellow
try {
    New-Item -ItemType Directory -Path "$destPath\uploads\items" -Force | Out-Null
    Write-Host "      ✓ Uploads directory created" -ForegroundColor Green
} catch {
    Write-Host "      ✗ Failed to create uploads directory: $_" -ForegroundColor Red
}

# Step 3: Check Apache port
Write-Host "[3/4] Detecting Apache port..." -ForegroundColor Yellow
$apachePort = $null
$configFile = "$xamppPath\apache\conf\httpd.conf"

if (Test-Path $configFile) {
    $content = Get-Content $configFile
    foreach ($line in $content) {
        if ($line -match "^Listen (\d+)") {
            $apachePort = $matches[1]
            break
        }
    }
}

if ($apachePort) {
    Write-Host "      ✓ Apache configured to use port: $apachePort" -ForegroundColor Green
} else {
    Write-Host "      ! Could not detect Apache port, assuming 80" -ForegroundColor Yellow
    $apachePort = "80"
}

# Step 4: Test backend connectivity
Write-Host "[4/4] Testing backend connection..." -ForegroundColor Yellow
$testUrl = if ($apachePort -eq "80") { 
    "http://localhost/donation_backend/test_connection.php" 
} else { 
    "http://localhost:$apachePort/donation_backend/test_connection.php" 
}

try {
    $response = Invoke-WebRequest -Uri $testUrl -TimeoutSec 5 -ErrorAction Stop
    $result = $response.Content | ConvertFrom-Json
    if ($result.success) {
        Write-Host "      ✓ Backend is reachable!" -ForegroundColor Green
        Write-Host "      PHP Version: $($result.php_version)" -ForegroundColor Gray
    } else {
        Write-Host "      ✗ Backend responded but with error" -ForegroundColor Red
    }
} catch {
    Write-Host "      ✗ Backend is NOT reachable" -ForegroundColor Red
    Write-Host "      Error: $_" -ForegroundColor Gray
    Write-Host ""
    Write-Host "      Please make sure:" -ForegroundColor Yellow
    Write-Host "      1. XAMPP Control Panel is open" -ForegroundColor White
    Write-Host "      2. Apache is started (green Running status)" -ForegroundColor White
    Write-Host "      3. MySQL is started (green Running status)" -ForegroundColor White
}

# Summary
Write-Host ""
Write-Host "=== Setup Summary ===" -ForegroundColor Cyan
Write-Host "Backend Location: $destPath" -ForegroundColor White
Write-Host "Backend URL: $testUrl" -ForegroundColor White
Write-Host ""

# Create .env file if non-standard port
if ($apachePort -ne "80") {
    $envPath = "F:\donation_app\frontend\.env"
    $envContent = "VITE_API_BASE_URL=http://localhost:$apachePort/donation_backend"
    
    Write-Host "Creating frontend .env file for port $apachePort..." -ForegroundColor Yellow
    Set-Content -Path $envPath -Value $envContent
    Write-Host "✓ Created $envPath" -ForegroundColor Green
    Write-Host ""
    Write-Host "IMPORTANT: Restart your frontend dev server!" -ForegroundColor Red
    Write-Host "  cd frontend" -ForegroundColor White
    Write-Host "  npm run dev" -ForegroundColor White
}

Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Open browser and test: $testUrl" -ForegroundColor White
Write-Host "2. Import database: http://localhost/phpmyadmin" -ForegroundColor White
Write-Host "   - Import file: F:\donation_app\database\donation_app.sql" -ForegroundColor White
Write-Host "3. Test database: ${testUrl -replace 'test_connection', 'test_db'}" -ForegroundColor White
Write-Host ""
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
