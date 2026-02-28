# Backend Setup Instructions

## The Problem
The error "Network Error" means the frontend cannot reach the backend at `http://localhost/donation_backend`.

## Solution

### OPTION 1: Move Backend to XAMPP htdocs (Recommended)

1. **Copy the backend folder to XAMPP's htdocs directory:**
   ```powershell
   # Default XAMPP location on Windows
   Copy-Item -Path "F:\donation_app\donation_backend" -Destination "C:\xampp\htdocs\" -Recurse -Force
   ```

2. **Verify XAMPP is running:**
   - Open XAMPP Control Panel
   - Start Apache (must show green "Running" status)
   - Start MySQL (must show green "Running" status)

3. **Test the backend:**
   Open browser and go to:
   ```
   http://localhost/donation_backend/test_connection.php
   ```
   
   You should see:
   ```json
   {"success":true,"message":"Backend is reachable!","timestamp":"...","php_version":"..."}
   ```

4. **If it works, test the database:**
   ```
   http://localhost/donation_backend/test_db.php
   ```

### OPTION 2: Use Different Port (if XAMPP uses port 8080)

If XAMPP Apache runs on port 8080 instead of 80:

1. **Update frontend API URL:**
   Create `F:\donation_app\frontend\.env`:
   ```
   VITE_API_BASE_URL=http://localhost:8080/donation_backend
   ```

2. **Restart the frontend:**
   ```powershell
   # Stop the current dev server (Ctrl+C)
   cd F:\donation_app\frontend
   npm run dev
   ```

3. **Test backend at:**
   ```
   http://localhost:8080/donation_backend/test_connection.php
   ```

### OPTION 3: Create Virtual Host (Advanced)

If you want to keep the backend at `F:\donation_app\donation_backend`:

1. **Edit XAMPP httpd.conf:**
   `C:\xampp\apache\conf\httpd.conf`
   
   Add:
   ```apache
   Alias /donation_backend "F:/donation_app/donation_backend"
   <Directory "F:/donation_app/donation_backend">
       Options Indexes FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>
   ```

2. **Restart Apache in XAMPP**

3. **Test:**
   ```
   http://localhost/donation_backend/test_connection.php
   ```

## Quick Diagnostics

### Step 1: Check Apache Port
Open browser:
```
http://localhost/
```
- If it shows XAMPP dashboard → Apache is on port 80 ✓
- If nothing loads, try: `http://localhost:8080/`
- If that works → Apache is on port 8080, use OPTION 2

### Step 2: Check Backend Files Location
```powershell
# Check if files are in htdocs
Test-Path "C:\xampp\htdocs\donation_backend\test_connection.php"
```
- If True → Files are in correct location
- If False → Use OPTION 1 to copy files

### Step 3: Check Permissions
```powershell
# Make sure uploads directory exists with write permissions
New-Item -ItemType Directory -Path "C:\xampp\htdocs\donation_backend\uploads\items" -Force
```

## Automatic Setup Script

Run this PowerShell script to set everything up:

```powershell
# Set paths
$sourcePath = "F:\donation_app\donation_backend"
$destPath = "C:\xampp\htdocs\donation_backend"

# Copy backend to htdocs
Write-Host "Copying backend to XAMPP htdocs..." -ForegroundColor Yellow
Copy-Item -Path $sourcePath -Destination $destPath -Recurse -Force

# Create uploads directory
Write-Host "Creating uploads directory..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path "$destPath\uploads\items" -Force | Out-Null

Write-Host "`nSetup complete!" -ForegroundColor Green
Write-Host "`nTest the backend:"
Write-Host "http://localhost/donation_backend/test_connection.php" -ForegroundColor Cyan
Write-Host "`nIf Apache runs on port 8080, use:"
Write-Host "http://localhost:8080/donation_backend/test_connection.php" -ForegroundColor Cyan
```

## What URL Should You Use?

After setup, test these URLs in order:

1. `http://localhost/donation_backend/test_connection.php`
2. `http://localhost:8080/donation_backend/test_connection.php`  
3. `http://localhost:80/donation_backend/test_connection.php`

Whichever one shows the JSON response is your correct backend URL!

Then update frontend if needed:
- If using port 8080, create `.env` file (see OPTION 2)
- If using port 80, no changes needed (default)
