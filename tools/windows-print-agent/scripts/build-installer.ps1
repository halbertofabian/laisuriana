param(
    [string]$Runtime = "win-x64",
    [string]$OutputRoot = ""
)

$ErrorActionPreference = "Stop"

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$agentProjectDir = Split-Path -Parent $scriptDir
$repoRoot = Split-Path -Parent (Split-Path -Parent $agentProjectDir)
$installerProjectDir = Join-Path $repoRoot "tools\windows-print-agent-installer"
$agentProjectPath = Join-Path $agentProjectDir "Laisuriana.PrintAgent.csproj"
$installerProjectPath = Join-Path $installerProjectDir "Laisuriana.PrintAgent.Setup.csproj"
$payloadDir = Join-Path $installerProjectDir "Payload"

if ([string]::IsNullOrWhiteSpace($OutputRoot)) {
    $OutputRoot = Join-Path $repoRoot "public\downloads\LAISURIANAPRINT-SOFTMOR"
}

$stagingDir = Join-Path $OutputRoot "build-staging"
$agentPublishDir = Join-Path $stagingDir "agent-publish"
$installerPublishDir = Join-Path $OutputRoot "setup-publish"
$setupExePath = Join-Path $OutputRoot "LAISURIANAPRINT-SOFTMOR-Setup.exe"

New-Item -ItemType Directory -Force -Path $OutputRoot | Out-Null
New-Item -ItemType Directory -Force -Path $payloadDir | Out-Null
New-Item -ItemType Directory -Force -Path $stagingDir | Out-Null

Get-ChildItem -LiteralPath $payloadDir -Force | Where-Object { $_.Name -ne '.gitkeep' } | Remove-Item -Recurse -Force
if (Test-Path $agentPublishDir) {
    Remove-Item -LiteralPath $agentPublishDir -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $agentPublishDir | Out-Null

dotnet publish $agentProjectPath -c Release -r $Runtime --self-contained true /p:PublishSingleFile=true /p:IncludeNativeLibrariesForSelfExtract=true -o $agentPublishDir

Copy-Item -LiteralPath (Join-Path $agentPublishDir "Laisuriana.PrintAgent.exe") -Destination (Join-Path $payloadDir "Laisuriana.PrintAgent.exe") -Force
Copy-Item -LiteralPath (Join-Path $agentProjectDir "scripts\install-service.ps1") -Destination (Join-Path $payloadDir "install-service.ps1") -Force
Copy-Item -LiteralPath (Join-Path $agentProjectDir "scripts\uninstall-service.ps1") -Destination (Join-Path $payloadDir "uninstall-service.ps1") -Force
Copy-Item -LiteralPath (Join-Path $agentProjectDir "appsettings.json") -Destination (Join-Path $payloadDir "appsettings.json") -Force
Copy-Item -LiteralPath (Join-Path $agentProjectDir "appsettings.template.json") -Destination (Join-Path $payloadDir "appsettings.template.json") -Force

if (Test-Path $installerPublishDir) {
    Remove-Item -LiteralPath $installerPublishDir -Recurse -Force
}

dotnet publish $installerProjectPath -c Release -r $Runtime --self-contained true /p:PublishSingleFile=true /p:IncludeNativeLibrariesForSelfExtract=true -o $installerPublishDir

Copy-Item -LiteralPath (Join-Path $installerPublishDir "Laisuriana.PrintAgent.Setup.exe") -Destination $setupExePath -Force

Write-Host "Instalador generado:" $setupExePath
