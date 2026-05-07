$mysqlDir = "C:\xampp\mysql"
$dataDir = "$mysqlDir\data"
$backupSource = "$mysqlDir\backup"
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupDest = "$mysqlDir\data_backup_$timestamp"

Write-Host "Starting MySQL repair process..."

if (-not (Test-Path $dataDir)) {
    Write-Error "Data directory not found at $dataDir"
    exit
}

# 1. Backup existing data
Write-Host "Backing up current data to $backupDest..."
Rename-Item -Path $dataDir -NewName $backupDest

# 2. Create fresh data dir and copy from backup
Write-Host "Initializing fresh data directory..."
New-Item -ItemType Directory -Path $dataDir
Copy-Item -Path "$backupSource\*" -Destination $dataDir -Recurse

# 3. Identify user databases
$excludeList = @("mysql", "performance_schema", "phpmyadmin", "test")
$userDbs = Get-ChildItem -Path $backupDest -Directory | Where-Object { $excludeList -notcontains $_.Name }

foreach ($db in $userDbs) {
    Write-Host "Restoring database: $($db.Name)..."
    Copy-Item -Path $db.FullName -Destination $dataDir -Recurse
}

# 4. Restore ibdata1 (contains the actual data for InnoDB tables)
Write-Host "Restoring ibdata1 file..."
Copy-Item -Path "$backupDest\ibdata1" -Destination $dataDir -Force

Write-Host "Repair complete. Please try starting MySQL from XAMPP Control Panel now."
