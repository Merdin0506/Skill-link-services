param(
    [ValidateSet('daily', 'weekly', 'monthly')]
    [string]$Period = 'daily',

    [switch]$Notify
)

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

$notifyFlag = if ($Notify) { '--notify' } else { '' }
php spark security:report-generate --period $Period $notifyFlag
