<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cargar imagen de producto</title>
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/css/core.css') }}">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Public Sans", sans-serif;
            background: linear-gradient(180deg, #f7f8fc 0%, #eef2ff 100%);
            color: #253858;
        }
        .mobile-upload-shell {
            max-width: 34rem;
            margin: 0 auto;
            padding: 1.25rem;
        }
        .mobile-upload-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 18px 45px rgba(37, 56, 88, 0.12);
            padding: 1.25rem;
        }
        .mobile-upload-card h1 {
            margin: 0 0 0.5rem;
            font-size: 1.45rem;
        }
        .mobile-upload-card p {
            margin: 0 0 1rem;
            color: #677788;
        }
        .preview {
            border: 1px dashed #cfd8ea;
            border-radius: 0.9rem;
            min-height: 14rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f8faff;
            margin-bottom: 1rem;
        }
        .preview img {
            display: block;
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            border: 0;
            border-radius: 0.85rem;
            padding: 0.9rem 1rem;
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            background: #7367f0;
        }
        .input {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #d9e2f0;
            border-radius: 0.85rem;
            padding: 0.9rem 1rem;
            margin-bottom: 1rem;
            font-size: 1rem;
            background: #fff;
        }
        .alert {
            border-radius: 0.85rem;
            padding: 0.8rem 0.95rem;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        .alert-success {
            background: #e8faf0;
            color: #18794e;
        }
        .alert-danger {
            background: #fff0f0;
            color: #b42318;
        }
        .hint {
            font-size: 0.92rem;
            color: #677788;
            margin-top: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="mobile-upload-shell">
        <div class="mobile-upload-card">
            <h1>Imagen general del producto</h1>
            <p>Sube la foto desde tu celular. Puedes elegir una imagen de galería o tomarla en el momento.</p>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" enctype="multipart/form-data" action="{{ route('operacion.catalogo_comercial.productos.imagen_movil.store', ['token' => $token]) }}">
                @csrf
                <div class="preview" id="previewBox">
                    <span id="previewPlaceholder">La vista previa aparecerá aquí.</span>
                    <img id="previewImage" alt="Vista previa" style="display:none;">
                </div>
                <input class="input" type="file" name="imagen" accept="image/*" capture="environment" id="imagenInput" required>
                <button class="btn" type="submit">Subir imagen</button>
            </form>

            <div class="hint">Cuando termine la carga, regresa a la computadora y guarda el producto.</div>
        </div>
    </div>

    <script>
        document.getElementById('imagenInput').addEventListener('change', function (event) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;

            const image = document.getElementById('previewImage');
            const placeholder = document.getElementById('previewPlaceholder');
            image.src = URL.createObjectURL(file);
            image.style.display = 'block';
            placeholder.style.display = 'none';
        });
    </script>
</body>
</html>
