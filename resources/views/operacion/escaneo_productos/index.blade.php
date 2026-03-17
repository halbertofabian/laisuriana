@extends('layouts.app')

@section('title', 'Escaneo de Productos')

@section('content')
<x-section-header
    eyebrow="Operación"
    icon="tabler-scan"
    title="Escaneo de Productos"
    subtitle="Consulta rápida por lector o cámara para ver información esencial del producto."
/>

<div class="card mb-4">
    <div class="card-body">
        <form id="form-escaneo" class="row g-3 align-items-end">
            <div class="col-lg-7">
                <label class="form-label">Código / SKU / Código de barras</label>
                <input type="text" class="form-control" id="escaneo-input" placeholder="Ej. SKU-POLO-CH-AZM o código de barras" required>
            </div>
            <div class="col-lg-5 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary" id="btn-buscar-escaneo">
                    <i class="icon-base ti tabler-search"></i> Buscar
                </button>
                <button type="button" class="btn btn-outline-primary" id="btn-iniciar-camara">
                    <i class="icon-base ti tabler-camera"></i> Iniciar cámara
                </button>
                <button type="button" class="btn btn-outline-secondary d-none" id="btn-detener-camara">
                    <i class="icon-base ti tabler-camera-off"></i> Detener
                </button>
            </div>
        </form>
        <div class="mt-3 d-none" id="camara-wrap">
            <video id="camara-video" class="w-100 rounded border" style="max-height:320px; object-fit:cover;" playsinline muted></video>
            <div class="form-text">Si la cámara detecta un código, se llenará automáticamente y se ejecutará la búsqueda.</div>
        </div>
    </div>
</div>

<div class="card d-none" id="resultado-wrap">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <h5 class="mb-1" id="resultado-producto-nombre">-</h5>
                <div class="text-body-secondary" id="resultado-producto-clasificacion">-</div>
                <div class="small text-body-secondary mt-1" id="resultado-producto-descripcion">-</div>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="small text-body-secondary">Precio SKU</div>
                <div class="h5 mb-2" id="resultado-precio">$0.00</div>
                <div class="small text-body-secondary">Costo producto</div>
                <div class="fw-semibold" id="resultado-costo">$0.00</div>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-3"><small class="text-body-secondary d-block">Código producto</small><strong id="resultado-prd-codigo">-</strong></div>
            <div class="col-md-3"><small class="text-body-secondary d-block">Barcode producto</small><strong id="resultado-prd-barcode">-</strong></div>
            <div class="col-md-3"><small class="text-body-secondary d-block">Código SKU</small><strong id="resultado-psk-codigo">-</strong></div>
            <div class="col-md-3"><small class="text-body-secondary d-block">Barcode SKU</small><strong id="resultado-psk-barcode">-</strong></div>
        </div>
    </div>
</div>
@endsection

@push('page-scripts')
<script>
(() => {
    const rutas = {
        buscar: '{{ route('operacion.escaneo_productos.buscar') }}',
    };

    const input = document.getElementById('escaneo-input');
    const camaraWrap = document.getElementById('camara-wrap');
    const camaraVideo = document.getElementById('camara-video');
    const btnIniciar = document.getElementById('btn-iniciar-camara');
    const btnDetener = document.getElementById('btn-detener-camara');

    let stream = null;
    let detector = null;
    let scanInterval = null;

    function parseError(xhr) {
        if (xhr.responseJSON?.message) return xhr.responseJSON.message;
        if (xhr.responseJSON?.errors) {
            return Object.values(xhr.responseJSON.errors).flat().join('\n');
        }
        return 'No fue posible consultar el producto.';
    }

    function renderResultado(data) {
        const producto = data.producto || {};
        const clasificacion = [producto.marca, producto.linea, producto.categoria].filter(Boolean).join(' / ') || 'Sin clasificación';

        document.getElementById('resultado-producto-nombre').textContent = producto.prd_nombre || data.psk_nombre || 'Producto';
        document.getElementById('resultado-producto-clasificacion').textContent = clasificacion;
        document.getElementById('resultado-producto-descripcion').textContent = producto.prd_descripcion || '-';
        document.getElementById('resultado-precio').textContent = '$' + Number(data.psk_precio || 0).toFixed(2);
        document.getElementById('resultado-costo').textContent = '$' + Number(producto.prd_costo || 0).toFixed(2);

        document.getElementById('resultado-prd-codigo').textContent = producto.prd_codigo || '-';
        document.getElementById('resultado-prd-barcode').textContent = producto.prd_codigo_barras || '-';
        document.getElementById('resultado-psk-codigo').textContent = data.psk_codigo || '-';
        document.getElementById('resultado-psk-barcode').textContent = data.psk_codigo_barras || '-';
        document.getElementById('resultado-wrap').classList.remove('d-none');
    }

    function buscar(codigo) {
        const valor = String(codigo || '').trim();
        if (!valor) {
            return;
        }

        AppUI.showLoader();
        $.getJSON(rutas.buscar, { q: valor })
            .done(function (response) {
                renderResultado(response.data || {});
            })
            .fail(function (xhr) {
                document.getElementById('resultado-wrap').classList.add('d-none');
                AppUI.showMessage('Sin coincidencias', parseError(xhr), 'warning');
            })
            .always(function () {
                AppUI.hideLoader();
            });
    }

    async function iniciarCamara() {
        if (!('mediaDevices' in navigator) || !navigator.mediaDevices.getUserMedia) {
            AppUI.showMessage('Cámara no disponible', 'Tu navegador no soporta acceso a cámara para escaneo.', 'warning');
            return;
        }

        if (!('BarcodeDetector' in window)) {
            AppUI.showMessage('Escaneo por cámara no disponible', 'Este navegador no soporta BarcodeDetector. Puedes usar lector físico o captura manual.', 'warning');
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            detector = new window.BarcodeDetector({ formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'upc_a', 'upc_e'] });

            camaraVideo.srcObject = stream;
            camaraWrap.classList.remove('d-none');
            btnDetener.classList.remove('d-none');
            btnIniciar.classList.add('d-none');

            scanInterval = setInterval(async function () {
                if (!detector || !camaraVideo || camaraVideo.readyState < 2) {
                    return;
                }

                try {
                    const barcodes = await detector.detect(camaraVideo);
                    if (!barcodes.length) {
                        return;
                    }

                    const valor = barcodes[0].rawValue;
                    if (!valor) {
                        return;
                    }

                    input.value = valor;
                    detenerCamara();
                    buscar(valor);
                } catch (_error) {
                    // Ignorar lecturas intermitentes de cámara.
                }
            }, 650);
        } catch (_error) {
            AppUI.showMessage('No se pudo iniciar cámara', 'Verifica permisos del navegador para usar cámara.', 'error');
        }
    }

    function detenerCamara() {
        if (scanInterval) {
            clearInterval(scanInterval);
            scanInterval = null;
        }

        if (stream) {
            stream.getTracks().forEach((track) => track.stop());
            stream = null;
        }

        detector = null;
        camaraVideo.srcObject = null;
        camaraWrap.classList.add('d-none');
        btnDetener.classList.add('d-none');
        btnIniciar.classList.remove('d-none');
    }

    document.getElementById('form-escaneo').addEventListener('submit', function (event) {
        event.preventDefault();
        buscar(input.value);
    });

    btnIniciar.addEventListener('click', iniciarCamara);
    btnDetener.addEventListener('click', detenerCamara);

    window.addEventListener('beforeunload', detenerCamara);
})();
</script>
@endpush
