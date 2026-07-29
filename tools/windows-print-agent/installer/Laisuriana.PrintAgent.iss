#define MyAppName "Laisuriana Print Agent"
#define MyAppPublisher "Laisuriana"
#define MyAppExeName "Laisuriana.PrintAgent.exe"
#define MyAppVersion "1.0.0"

[Setup]
AppId={{4B7D80E1-1F52-4B92-9A62-9BE7A2DA91A1}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={autopf}\Laisuriana\PrintAgent
DisableProgramGroupPage=yes
PrivilegesRequired=admin
OutputDir=.
OutputBaseFilename=Laisuriana-PrintAgent-Setup
Compression=lzma
SolidCompression=yes
WizardStyle=modern
UninstallDisplayIcon={app}\{#MyAppExeName}

[Files]
Source: "..\bin\Release\net8.0-windows\win-x64\publish\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Run]
Filename: "sc.exe"; Parameters: "create LaisurianaPrintAgent binPath= ""{app}\{#MyAppExeName}"" start= auto DisplayName= ""Laisuriana Print Agent"""; Flags: runhidden
Filename: "sc.exe"; Parameters: "description LaisurianaPrintAgent ""Agente local de impresion automatica de Laisuriana"""; Flags: runhidden
Filename: "sc.exe"; Parameters: "start LaisurianaPrintAgent"; Flags: runhidden

[UninstallRun]
Filename: "sc.exe"; Parameters: "stop LaisurianaPrintAgent"; Flags: runhidden
Filename: "sc.exe"; Parameters: "delete LaisurianaPrintAgent"; Flags: runhidden
