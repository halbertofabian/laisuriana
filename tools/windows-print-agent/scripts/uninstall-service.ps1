param(
    [string]$InstallDir = "C:\Program Files\Laisuriana\LAISURIANAPRINT-SOFTMOR",
    [string]$ServiceName = "LAISURIANAPRINTSOFTMOR"
)

$ErrorActionPreference = "Stop"

$existing = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue
if ($existing) {
    Stop-Service -Name $ServiceName -ErrorAction SilentlyContinue
    & sc.exe delete $ServiceName | Out-Null
    Start-Sleep -Seconds 2
}

$uninstallRegistryKey = "HKLM:\Software\Microsoft\Windows\CurrentVersion\Uninstall\$ServiceName"
if (Test-Path $uninstallRegistryKey) {
    Remove-Item -LiteralPath $uninstallRegistryKey -Force
}

if (Test-Path $InstallDir) {
    Remove-Item -LiteralPath $InstallDir -Recurse -Force
}

Write-Host "Servicio desinstalado:" $ServiceName
