param(
    [string]$InstallDir = "C:\Program Files\Laisuriana\LAISURIANAPRINT-SOFTMOR",
    [string]$ServiceName = "LAISURIANAPRINTSOFTMOR",
    [string]$DisplayName = "LAISURIANAPRINT-SOFTMOR",
    [string]$SourceDir = ""
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($SourceDir)) {
    $SourceDir = Split-Path -Parent $MyInvocation.MyCommand.Path
}

if (-not (Test-Path $SourceDir)) {
    throw "No se encontro la carpeta origen del agente: $SourceDir"
}

New-Item -ItemType Directory -Force -Path $InstallDir | Out-Null

$sourceExePath = Join-Path $SourceDir "Laisuriana.PrintAgent.exe"
if (-not (Test-Path $sourceExePath)) {
    throw "No se encontro el ejecutable del agente en $sourceExePath"
}

Copy-Item -LiteralPath $sourceExePath -Destination (Join-Path $InstallDir "Laisuriana.PrintAgent.exe") -Force

$sourceTemplatePath = Join-Path $SourceDir "appsettings.template.json"
if (Test-Path $sourceTemplatePath) {
    Copy-Item -LiteralPath $sourceTemplatePath -Destination (Join-Path $InstallDir "appsettings.template.json") -Force
}

$sourceConfigPath = Join-Path $SourceDir "appsettings.json"
$targetConfigPath = Join-Path $InstallDir "appsettings.json"
if ((Test-Path $sourceConfigPath) -and -not (Test-Path $targetConfigPath)) {
    Copy-Item -LiteralPath $sourceConfigPath -Destination $targetConfigPath -Force
}

$sourceUninstallPath = Join-Path $SourceDir "uninstall-service.ps1"
if (Test-Path $sourceUninstallPath) {
    Copy-Item -LiteralPath $sourceUninstallPath -Destination (Join-Path $InstallDir "uninstall-service.ps1") -Force
}

$exePath = Join-Path $InstallDir "Laisuriana.PrintAgent.exe"
if (-not (Test-Path $exePath)) {
    throw "No se encontro el ejecutable del agente en $exePath"
}

$existing = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue
if ($existing) {
    Stop-Service -Name $ServiceName -ErrorAction SilentlyContinue
    & sc.exe delete $ServiceName | Out-Null
    Start-Sleep -Seconds 2
}

& sc.exe create $ServiceName binPath= "`"$exePath`"" start= auto DisplayName= "`"$DisplayName`"" | Out-Null
& sc.exe description $ServiceName "Agente local de impresion automatica de Laisuriana" | Out-Null
& sc.exe failure $ServiceName reset= 86400 actions= restart/60000/restart/60000/restart/60000 | Out-Null
Start-Service -Name $ServiceName

$uninstallCommand = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -File "' + (Join-Path $InstallDir "uninstall-service.ps1") + '" -InstallDir "' + $InstallDir + '" -ServiceName "' + $ServiceName + '"'
$uninstallRegistryKey = "HKLM:\Software\Microsoft\Windows\CurrentVersion\Uninstall\$ServiceName"
New-Item -Path $uninstallRegistryKey -Force | Out-Null
Set-ItemProperty -Path $uninstallRegistryKey -Name "DisplayName" -Value $DisplayName
Set-ItemProperty -Path $uninstallRegistryKey -Name "Publisher" -Value "Laisuriana"
Set-ItemProperty -Path $uninstallRegistryKey -Name "DisplayVersion" -Value "1.0.0"
Set-ItemProperty -Path $uninstallRegistryKey -Name "InstallLocation" -Value $InstallDir
Set-ItemProperty -Path $uninstallRegistryKey -Name "DisplayIcon" -Value $exePath
Set-ItemProperty -Path $uninstallRegistryKey -Name "UninstallString" -Value $uninstallCommand
Set-ItemProperty -Path $uninstallRegistryKey -Name "NoModify" -Value 1 -Type DWord
Set-ItemProperty -Path $uninstallRegistryKey -Name "NoRepair" -Value 1 -Type DWord

Write-Host "Servicio instalado y arrancado:" $ServiceName
