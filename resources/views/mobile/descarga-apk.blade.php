@php
    $apkUrl = asset('downloads/lasuriana-app-release.apk');
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' . urlencode($apkUrl);
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Descarga App Movil</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 24px; background: #f5f7fb; color: #1f2937; }
        .card { max-width: 520px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 8px 24px rgba(0,0,0,.08); text-align: center; }
        h1 { margin-top: 0; font-size: 24px; }
        p { color: #4b5563; }
        img { width: 280px; height: 280px; border-radius: 8px; border: 1px solid #e5e7eb; }
        .btn { display: inline-block; margin-top: 16px; background: #0f62fe; color: #fff; text-decoration: none; padding: 12px 18px; border-radius: 8px; font-weight: 700; }
        .url { margin-top: 14px; font-size: 12px; word-break: break-all; color: #6b7280; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Descargar App Android (APK)</h1>
        <p>Escanea este QR con tu celular para abrir el enlace de descarga.</p>
        <img src="{{ $qrUrl }}" alt="QR de descarga APK">
        <div>
            <a class="btn" href="{{ $apkUrl }}">Descargar APK</a>
        </div>
        <div class="url">{{ $apkUrl }}</div>
    </div>
</body>
</html>
