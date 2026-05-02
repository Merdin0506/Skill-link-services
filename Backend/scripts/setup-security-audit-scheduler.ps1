param(
    [ValidateSet('daily', 'weekly', 'monthly')]
    [string]$Period = 'daily',

    [string]$Time = '01:00',

    [string]$TaskName = 'SkillLink-Security-Audit-Report',

    [switch]$Notify
)

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$phpExe = 'C:\xampp\php\php.exe'
$sparkPath = Join-Path $projectRoot 'spark'

if (-not (Test-Path $phpExe)) {
    throw "PHP executable not found at $phpExe"
}

if (-not (Test-Path $sparkPath)) {
    throw "CodeIgniter spark file not found at $sparkPath"
}

$notifyArg = if ($Notify) { '--notify' } else { '' }
$taskCmd = "\"$phpExe\" \"$sparkPath\" security:report-generate --period $Period $notifyArg"

schtasks /Create /F /SC DAILY /TN "$TaskName" /TR "$taskCmd" /ST $Time /RU SYSTEM | Out-Null

Write-Host "Scheduled task created/updated successfully." -ForegroundColor Green
Write-Host "Task Name : $TaskName" -ForegroundColor Yellow
Write-Host "Schedule  : DAILY at $Time" -ForegroundColor Yellow
Write-Host "Command   : $taskCmd" -ForegroundColor Yellow
Write-Host ''
Write-Host 'To verify task:' -ForegroundColor Cyan
Write-Host "  schtasks /Query /TN \"$TaskName\" /V /FO LIST" -ForegroundColor White
Write-Host 'To remove task:' -ForegroundColor Cyan
Write-Host "  schtasks /Delete /TN \"$TaskName\" /F" -ForegroundColor White
