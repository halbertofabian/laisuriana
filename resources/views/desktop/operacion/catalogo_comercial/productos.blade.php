@extends('layouts.desktop')

@section('title', 'Productos')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .desktop-product-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 280px;
            gap: 16px;
        }
        .desktop-product-sidecard {
            padding: 14px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-lg);
            background: var(--surface-alt);
        }
        .desktop-product-sidecard h3 {
            margin: 0 0 8px;
            font-size: .86rem;
            font-weight: 600;
        }
        .desktop-product-sidecard p {
            margin: 0 0 12px;
            color: var(--text-2);
            font-size: .75rem;
            line-height: 1.5;
        }
        .desktop-product-preview {
            width: 100%;
            aspect-ratio: 1;
            border: 1px dashed var(--stroke-strong);
            border-radius: var(--r-lg);
            background: var(--surface);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: var(--text-3);
            font-size: .74rem;
            text-align: center;
            padding: 10px;
        }
        .desktop-product-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }
        .desktop-product-preview.has-image img {
            display: block;
        }
        .desktop-product-preview.has-image span {
            display: none;
        }
        .desktop-product-methods {
            display: grid;
            gap: 8px;
            margin-top: 12px;
        }
        .desktop-product-method {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface);
        }
        .desktop-product-method input {
            margin-top: 2px;
        }
        .desktop-product-method strong {
            display: block;
            font-size: .78rem;
        }
        .desktop-product-method span {
            display: block;
            margin-top: 2px;
            color: var(--text-2);
            font-size: .72rem;
            line-height: 1.4;
        }
        .desktop-product-block {
            display: none;
            margin-top: 12px;
        }
        .desktop-product-block.is-active {
            display: block;
        }
        .desktop-product-checkgrid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .desktop-product-check {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            min-height: 42px;
            padding: 8px 10px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface-alt);
        }
        .desktop-product-check small {
            display: block;
            margin-top: 2px;
            color: var(--text-3);
            font-size: .7rem;
        }
        .desktop-product-type {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .desktop-product-type__option {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface-alt);
        }
        .desktop-product-type__option strong {
            display: block;
            font-size: .8rem;
        }
        .desktop-product-type__option span {
            display: block;
            margin-top: 3px;
            color: var(--text-2);
            font-size: .72rem;
            line-height: 1.45;
        }
        .desktop-product-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }
        .desktop-product-summary__card {
            padding: 10px 12px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface-alt);
        }
        .desktop-product-summary__card small {
            display: block;
            color: var(--text-2);
            font-size: .7rem;
        }
        .desktop-product-summary__card strong {
            display: block;
            margin-top: 3px;
            font-size: .9rem;
        }
        .desktop-product-variable {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--divider);
        }
        .desktop-product-variable[hidden] {
            display: none;
        }
        .desktop-product-corridas {
            margin-top: 12px;
            display: grid;
            gap: 8px;
        }
        .desktop-product-corrida {
            padding: 12px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface-alt);
        }
        .desktop-product-corrida__title {
            margin: 0 0 10px;
            font-size: .8rem;
            font-weight: 600;
        }
        .desktop-product-corrida__grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }
        .desktop-product-pill-list {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        @media (max-width: 1100px) {
            .desktop-product-layout {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 860px) {
            .desktop-product-checkgrid,
            .desktop-product-type,
            .desktop-product-summary,
            .desktop-product-corrida__grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'productos')
        @include('desktop.operacion.catalogo_comercial._subnav')
        @if($permisosUI['crear'])
            <span class="desktop-toolbar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-nuevo-producto">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
                Nuevo producto
            </button>
        @endif
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-productos">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <select class="desktop-toolbar__select" id="productos-estatus">
            <option value="">Todos los estatus</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
        </select>
        <select class="desktop-toolbar__select" id="productos-length">
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
            <option value="100" selected>100 por página</option>
        </select>
        <input type="search" id="productos-search" class="desktop-toolbar__search" placeholder="Buscar producto, código, marca o proveedor">
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-list-wrap">
            <table id="desktop-productos-table" class="desktop-list">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Detalles</th>
                        <th>Línea</th>
                        <th>Corridas</th>
                        <th style="width:104px;">Estado</th>
                        <th style="width:56px; text-align:right;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="desktop-list-foot">
            <div id="desktop-productos-info"></div>
            <div id="desktop-productos-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <div class="desktop-modal" id="desktop-producto-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="width:min(1200px, 100%);">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-producto-modal-title">Nuevo producto</div>
                <button type="button" class="desktop-modal__close" data-close-producto-modal aria-label="Cerrar">&times;</button>
            </div>
            <form id="desktop-producto-form" enctype="multipart/form-data">
                <div class="desktop-modal__body">
                    <input type="hidden" id="prd_id" name="prd_id">
                    <input type="hidden" id="prd_imagen_reset" name="prd_imagen_reset" value="0">
                    <div class="desktop-product-layout">
                        <div>
                            <div class="desktop-form-grid">
                                <div class="desktop-field desktop-field--full">
                                    <label>Nombre del producto</label>
                                    <input type="text" name="prd_nombre" id="prd_nombre" maxlength="180" required>
                                </div>
                                <div class="desktop-field">
                                    <label>Código interno</label>
                                    <input type="text" name="prd_codigo" id="prd_codigo" maxlength="40" readonly placeholder="Se crea automáticamente al guardar">
                                </div>
                                <div class="desktop-field">
                                    <label>Código de barras</label>
                                    <input type="text" name="prd_codigo_barras" id="prd_codigo_barras" maxlength="80">
                                </div>
                                <div class="desktop-field">
                                    <label>Clave fiscal (SAT)</label>
                                    <input type="text" name="prd_clave_sat" id="prd_clave_sat" maxlength="20">
                                </div>
                                <div class="desktop-field">
                                    <label>Marca</label>
                                    <select name="prd_mrc_id" id="prd_mrc_id" required>
                                        <option value="">Selecciona una marca</option>
                                        @foreach($opciones['marcas'] as $item)
                                            <option value="{{ $item->mrc_id }}">{{ $item->mrc_nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="desktop-field">
                                    <label>Modelo</label>
                                    <select name="prd_mdl_id" id="prd_mdl_id">
                                        <option value="">Sin modelo</option>
                                    </select>
                                </div>
                                <div class="desktop-field">
                                    <label>Proveedor principal</label>
                                    <select name="prd_prv_id" id="prd_prv_id">
                                        <option value="">Sin proveedor asignado</option>
                                        @foreach($opciones['proveedores'] as $item)
                                            <option value="{{ $item->prv_id }}">{{ $item->prv_nombre_empresa }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="desktop-field">
                                    <label>Línea</label>
                                    <select name="prd_lna_id" id="prd_lna_id" required>
                                        <option value="">Selecciona una línea</option>
                                        @foreach($opciones['lineas'] as $item)
                                            <option value="{{ $item->lna_id }}">{{ $item->lna_nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="desktop-field">
                                    <label>Concepto</label>
                                    <select name="prd_ctg_id" id="prd_ctg_id">
                                        <option value="">Selecciona un concepto</option>
                                        @foreach($opciones['categorias'] as $item)
                                            <option value="{{ $item->ctg_id }}">{{ $item->ctg_nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="desktop-field">
                                    <label>Descripción</label>
                                    <select name="prd_dsc_id" id="prd_dsc_id">
                                        <option value="">Selecciona una descripción</option>
                                        @foreach($opciones['descripciones'] as $item)
                                            <option value="{{ $item->dsc_id }}">{{ $item->dsc_nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="desktop-field">
                                    <label>Unidad de venta</label>
                                    <select name="prd_umd_id" id="prd_umd_id" required>
                                        <option value="">Selecciona una unidad</option>
                                        @foreach($opciones['unidades'] as $item)
                                            <option value="{{ $item->umd_id }}">{{ $item->umd_nombre }} ({{ $item->umd_codigo }}){{ $item->umd_es_predeterminada ? ' ★' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="desktop-field desktop-field--full">
                                    <label>Almacenes permitidos</label>
                                    <div class="desktop-product-checkgrid" id="prd-almacenes-checklist">
                                        @foreach($opciones['almacenes'] as $item)
                                            <label class="desktop-product-check">
                                                <input type="checkbox" name="almacen_ids[]" value="{{ $item->alm_id }}">
                                                <span>
                                                    <strong>{{ $item->alm_nombre }}</strong>
                                                    <small>{{ $item->sucursal?->scl_nombre }}</small>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="desktop-field desktop-field--full">
                                    <label>Tipo de producto</label>
                                    <div class="desktop-product-type">
                                        <label class="desktop-product-type__option">
                                            <input type="radio" name="prd_tipo" value="simple" id="prd_tipo_simple">
                                            <span>
                                                <strong>Producto simple</strong>
                                                <span>Un solo producto sin combinaciones.</span>
                                            </span>
                                        </label>
                                        <label class="desktop-product-type__option">
                                            <input type="radio" name="prd_tipo" value="variable" id="prd_tipo_variable">
                                            <span>
                                                <strong>Producto variable</strong>
                                                <span>Genera SKU a partir de atributos y valores.</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="desktop-field desktop-field--full">
                                    <div class="desktop-product-summary" id="producto-resumen-general"></div>
                                </div>
                                <div class="desktop-field">
                                    <label>Precio de venta</label>
                                    <input type="number" name="prd_precio_base" id="prd_precio_base" min="0" step="0.01" value="0.00" required>
                                </div>
                                <div class="desktop-field">
                                    <label>Costo</label>
                                    <input type="number" name="prd_costo" id="prd_costo" min="0" step="0.01" value="0.00">
                                </div>
                                <div class="desktop-field">
                                    <label>Estado</label>
                                    <select name="prd_estatus" id="prd_estatus" required>
                                        <option value="activo">Activo</option>
                                        <option value="inactivo">Inactivo</option>
                                    </select>
                                </div>
                                <div class="desktop-field">
                                    <label>Stock mínimo base</label>
                                    <input type="number" name="prd_stock_minimo" id="prd_stock_minimo" min="0" step="1" value="0" required>
                                </div>
                                <div class="desktop-field">
                                    <label>Stock máximo base</label>
                                    <input type="number" name="prd_stock_maximo" id="prd_stock_maximo" min="0" step="1" value="0" required>
                                </div>
                                <div class="desktop-field desktop-field--full">
                                    <label>Descripción</label>
                                    <textarea name="prd_descripcion" id="prd_descripcion" rows="3" maxlength="2000"></textarea>
                                </div>
                            </div>

                            <div class="desktop-product-variable" id="producto-variable-shell" hidden>
                                <div class="desktop-field desktop-field--full">
                                    <label>Atributos para corridas</label>
                                    <div class="desktop-product-checkgrid" id="producto-atributos-checklist">
                                        @foreach($opciones['atributos'] as $atributo)
                                            <label class="desktop-product-check">
                                                <input type="checkbox" name="atributo_ids[]" value="{{ $atributo->atr_id }}">
                                                <span>
                                                    <strong>{{ $atributo->atr_nombre }}</strong>
                                                    <small>Atributo activo</small>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div id="producto-valores-config"></div>
                                <div class="desktop-field" style="margin-top:12px;">
                                    <label>Atributo objetivo de corridas</label>
                                    <select id="prd_corrida_atr_objetivo"></select>
                                    <small>Las corridas y reglas base se construirán a partir de los valores de este atributo.</small>
                                </div>
                                <div class="desktop-product-corridas" id="producto-corridas-config"></div>
                                <div id="producto-hidden-corridas"></div>
                            </div>
                        </div>

                        <aside class="desktop-product-sidecard">
                            <h3>Imagen principal</h3>
                            <p>Sube un archivo o usa un enlace público. La imagen es opcional.</p>
                            <div class="desktop-product-preview" id="producto-imagen-preview-wrap">
                                <span>Aquí verás la foto principal del producto.</span>
                                <img id="producto-imagen-preview" alt="Vista previa del producto">
                            </div>

                            <div class="desktop-product-methods">
                                <label class="desktop-product-method">
                                    <input type="radio" name="prd_imagen_metodo" value="archivo">
                                    <span>
                                        <strong>Subir archivo</strong>
                                        <span>Imagen guardada en esta computadora.</span>
                                    </span>
                                </label>
                                <label class="desktop-product-method">
                                    <input type="radio" name="prd_imagen_metodo" value="url">
                                    <span>
                                        <strong>Usar URL</strong>
                                        <span>Enlace público de imagen externa.</span>
                                    </span>
                                </label>
                            </div>

                            <div class="desktop-product-block" data-image-method="archivo">
                                <div class="desktop-field">
                                    <label>Archivo de imagen</label>
                                    <input type="file" name="prd_imagen_archivo" id="prd_imagen_archivo" accept="image/*">
                                    <small>JPG, PNG o WEBP de hasta 5 MB.</small>
                                </div>
                            </div>

                            <div class="desktop-product-block" data-image-method="url">
                                <div class="desktop-field">
                                    <label>URL externa</label>
                                    <input type="url" name="prd_imagen_url" id="prd_imagen_url" maxlength="500" placeholder="https://...">
                                </div>
                            </div>

                            <div style="display:grid; gap:8px; margin-top:12px;">
                                <button type="button" class="desktop-btn desktop-btn--default" id="btn-quitar-imagen-producto">Quitar foto</button>
                            </div>
                        </aside>
                    </div>
                </div>
                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-producto-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-producto">Guardar producto</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-producto-detail-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title">Detalle de producto</div>
                <button type="button" class="desktop-modal__close" data-close-producto-detail-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body">
                <div class="desktop-form-grid" id="desktop-producto-detail-grid"></div>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-producto-detail-modal>Cerrar</button>
            </div>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-producto-confirm-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="width:min(440px, 100%);">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title">Confirmar acción</div>
                <button type="button" class="desktop-modal__close" data-close-producto-confirm-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body">
                <p id="desktop-producto-confirm-copy" style="margin:0; color:var(--text-2); line-height:1.55;"></p>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-producto-confirm-modal>Cancelar</button>
                <button type="button" class="desktop-btn desktop-btn--primary" id="desktop-producto-confirm-accept">Continuar</button>
            </div>
        </div>
    </div>

    <div class="desktop-feedback" id="desktop-productos-feedback"></div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $table = $('#desktop-productos-table');
            const $modal = $('#desktop-producto-modal');
            const $detailModal = $('#desktop-producto-detail-modal');
            const $confirmModal = $('#desktop-producto-confirm-modal');
            const $form = $('#desktop-producto-form');
            const $feedback = $('#desktop-productos-feedback');
            const valoresCatalogo = @json($valoresCatalogo);
            const modelosCatalogo = @json($modelosCatalogo);
            let productosTable = null;
            let confirmAction = null;
            let corridasState = {};

            $.ajaxSetup({
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const permisosUI = @json($permisosUI);
            const rutas = {
                data: '{{ route('desktop.operacion.catalogo_comercial.productos.data') }}',
                create: '{{ route('desktop.operacion.catalogo_comercial.productos.create') }}',
                edit: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/productos') }}/' + id + '/editar'; },
                show: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/productos') }}/' + id; },
                store: '{{ route('desktop.operacion.catalogo_comercial.productos.store') }}',
                update: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/productos') }}/' + id; },
                estatus: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/productos') }}/' + id + '/estatus'; },
                destroy: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/productos') }}/' + id; }
            };
            const ICONS = {
                edit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>',
                view: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3"/></svg>',
                toggle: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.2-8.5"/><path d="m9 11 3 3L22 4"/></svg>',
                remove: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>',
                dots: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>'
            };

            function escapeHtml(value) {
                return String(value || '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            function initials(value) {
                return String(value || '?').trim().split(/\s+/).slice(0, 2).map(function (part) {
                    return part.charAt(0);
                }).join('').toUpperCase() || '?';
            }

            function parseError(xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const firstGroup = Object.values(xhr.responseJSON.errors)[0];
                    if (Array.isArray(firstGroup) && firstGroup[0]) return firstGroup[0];
                }
                return xhr.responseJSON?.message || 'No fue posible completar la operación.';
            }

            function showFeedback(type, message) {
                $feedback.removeClass('is-error is-success is-visible')
                    .addClass(type === 'error' ? 'is-error' : 'is-success')
                    .text(message)
                    .addClass('is-visible');

                window.clearTimeout(showFeedback._timer);
                showFeedback._timer = window.setTimeout(function () {
                    $feedback.removeClass('is-visible');
                }, 3600);
            }

            function openModal($target) {
                $target.addClass('is-open').attr('aria-hidden', 'false');
            }

            function closeModal($target) {
                $target.removeClass('is-open').attr('aria-hidden', 'true');
            }

            function renderStatus(value) {
                const active = value === 'activo';
                return '<span class="desktop-status ' + (active ? 'desktop-status--active' : 'desktop-status--inactive') + '">' + (active ? 'Activo' : 'Inactivo') + '</span>';
            }

            function renderActions(row) {
                const items = [];

                if (permisosUI.editar) {
                    items.push('<button type="button" class="desktop-menu__item btn-editar-producto" data-id="' + row.prd_id + '">' + ICONS.edit + 'Editar</button>');
                }

                items.push('<button type="button" class="desktop-menu__item btn-ver-producto" data-id="' + row.prd_id + '">' + ICONS.view + 'Ver detalle</button>');

                if (permisosUI.inactivar) {
                    items.push('<div class="desktop-menu__divider"></div>');
                    items.push('<button type="button" class="desktop-menu__item btn-toggle-producto" data-id="' + row.prd_id + '" data-estatus="' + (row.prd_estatus === 'activo' ? 'inactivo' : 'activo') + '">' + ICONS.toggle + (row.prd_estatus === 'activo' ? 'Inactivar' : 'Activar') + '</button>');
                }

                if (permisosUI.eliminar) {
                    items.push('<button type="button" class="desktop-menu__item desktop-menu__item--danger btn-eliminar-producto" data-id="' + row.prd_id + '" data-name="' + escapeHtml(row.prd_nombre) + '">' + ICONS.remove + 'Eliminar</button>');
                }

                return '<div class="desktop-rowmenu">' +
                    '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                    '<div class="desktop-menu">' + items.join('') + '</div>' +
                    '</div>';
            }

            function renderFooter() {
                if (!productosTable) return;
                const info = productosTable.page.info();
                const total = info.recordsDisplay;

                if (!total) {
                    $('#desktop-productos-info').text('Mostrando 0 productos');
                    $('#desktop-productos-pagination').empty();
                    return;
                }

                $('#desktop-productos-info').text('Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' productos');

                const buttons = [];
                buttons.push({ label: '‹', page: 'previous', disabled: info.page === 0 });
                for (let i = 0; i < info.pages; i += 1) {
                    buttons.push({ label: String(i + 1), page: i, active: i === info.page });
                }
                buttons.push({ label: '›', page: 'next', disabled: info.page >= info.pages - 1 });

                $('#desktop-productos-pagination').html(buttons.map(function (button) {
                    const classes = ['desktop-pager__btn', button.active ? 'is-active' : '', button.disabled ? 'is-disabled' : ''].filter(Boolean).join(' ');
                    return '<button type="button" class="' + classes + '" data-page="' + button.page + '"' + (button.disabled ? ' disabled' : '') + '>' + button.label + '</button>';
                }).join(''));
            }

            function initTable() {
                productosTable = $table.DataTable({
                    ajax: {
                        url: rutas.data,
                        data: function () {
                            return {
                                buscar: $('#productos-search').val(),
                                estatus: $('#productos-estatus').val()
                            };
                        },
                        dataSrc: 'data'
                    },
                    processing: true,
                    deferRender: true,
                    responsive: false,
                    autoWidth: false,
                    pageLength: 100,
                    lengthChange: false,
                    searching: false,
                    order: [[1, 'asc']],
                    dom: 'rt',
                    language: {
                        processing: 'Cargando...',
                        emptyTable: 'No hay productos registrados',
                        zeroRecords: 'No se encontraron productos'
                    },
                    columns: [
                        {
                            data: 'prd_nombre',
                            render: function (value, type, row) {
                                const detalleProducto = [
                                    row.marca || 'S/M',
                                    row.modelo || 'S/Mo',
                                    row.categoria || 'S/C',
                                    row.descripcion_catalogo || 'S/D',
                                    row.prd_codigo || 'S/CI'
                                ].join(' - ');

                                return '<div class="desktop-cell-primary">' +
                                    '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                    '<span><span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                    '<span class="desktop-list__meta">' + escapeHtml(detalleProducto) + '</span></span></div>';
                            }
                        },
                        {
                            data: null,
                            render: function (row) {
                                const costoMinimo = row.costo_minimo_sku !== null && row.costo_minimo_sku !== undefined
                                    ? Number(row.costo_minimo_sku)
                                    : Number(row.prd_costo || 0);
                                const costoMaximo = row.costo_maximo_sku !== null && row.costo_maximo_sku !== undefined
                                    ? Number(row.costo_maximo_sku)
                                    : Number(row.prd_costo || 0);
                                const costoTexto = costoMinimo === costoMaximo
                                    ? '$' + escapeHtml(costoMinimo.toFixed(2))
                                    : '$' + escapeHtml(costoMinimo.toFixed(2)) + ' a $' + escapeHtml(costoMaximo.toFixed(2));

                                return '<span class="desktop-list__name">$' + escapeHtml(Number(row.prd_precio_base || 0).toFixed(2)) + '</span>' +
                                    '<span class="desktop-list__meta">Costo ' + costoTexto + ' · ' + escapeHtml(row.unidad || 'Sin unidad') + '</span>';
                            }
                        },
                        {
                            data: null,
                            render: function (row) {
                                return '<span class="desktop-list__name">' + escapeHtml(row.linea || 'Sin linea') + '</span>';
                            }
                        },
                        {
                            data: null,
                            render: function (row) {
                                const labels = Array.isArray(row.atributos) ? row.atributos : [];
                                const pills = labels.slice(0, 2).map(function (label) {
                                    return '<span class="desktop-pill desktop-pill--brand">' + escapeHtml(label) + '</span>';
                                });
                                if (labels.length > 2) pills.push('<span class="desktop-pill desktop-pill--more">+' + (labels.length - 2) + '</span>');
                                return '<div class="desktop-pill-list">' + (pills.join('') || '<span class="desktop-pill desktop-pill--neutral">' + (row.prd_tipo === 'variable' ? 'Variable' : 'Simple') + '</span>') + '</div>' +
                                    '<span class="desktop-list__meta">' + escapeHtml(row.skus_activos || 0) + ' activos · ' + escapeHtml(row.skus_total || 0) + ' SKU</span>';
                            }
                        },
                        { data: 'prd_estatus', render: renderStatus },
                        { data: null, orderable: false, searchable: false, render: renderActions }
                    ],
                    initComplete: renderFooter,
                    drawCallback: renderFooter
                });
            }

            function reloadTable(resetPaging) {
                if (!productosTable) return;
                productosTable.ajax.reload(null, !resetPaging);
            }

            function updateModelOptions(selectedMarcaId, selectedModeloId) {
                const marcaId = Number(selectedMarcaId || 0);
                const modelos = modelosCatalogo.filter(function (modelo) {
                    return Array.isArray(modelo.marca_ids) && modelo.marca_ids.map(Number).includes(marcaId);
                });

                const html = ['<option value="">Sin modelo</option>'];
                modelos.forEach(function (modelo) {
                    html.push('<option value="' + modelo.mdl_id + '">' + escapeHtml(modelo.mdl_nombre) + '</option>');
                });
                $('#prd_mdl_id').html(html.join('')).val(selectedModeloId ? String(selectedModeloId) : '');
            }

            function setImagePreview(url) {
                const wrap = document.getElementById('producto-imagen-preview-wrap');
                const img = document.getElementById('producto-imagen-preview');
                if (!wrap || !img) return;

                if (url) {
                    wrap.classList.add('has-image');
                    img.src = url;
                } else {
                    wrap.classList.remove('has-image');
                    img.removeAttribute('src');
                }
            }

            function syncImageMethodUI() {
                const method = $form.find('input[name="prd_imagen_metodo"]:checked').val() || '';
                $('.desktop-product-block').removeClass('is-active');
                if (method) $('.desktop-product-block[data-image-method="' + method + '"]').addClass('is-active');
            }

            function getSelectedAttributeIds() {
                return $('#producto-atributos-checklist input[name="atributo_ids[]"]:checked').map(function () {
                    return Number(this.value);
                }).get();
            }

            function normalizeCorridasState() {
                const selectedIds = getSelectedAttributeIds();
                Object.keys(corridasState).forEach(function (key) {
                    if (!selectedIds.includes(Number(key))) delete corridasState[key];
                });
            }

            function renderValoresConfig() {
                const selectedIds = getSelectedAttributeIds();
                const container = $('#producto-valores-config');
                const target = $('#prd_corrida_atr_objetivo');

                if (!selectedIds.length) {
                    container.html('<div class="desktop-field"><small>Selecciona al menos un atributo para configurar valores.</small></div>');
                    target.html('');
                    renderCorridasConfig();
                    renderProductSummary();
                    return;
                }

                const attrChecks = selectedIds.map(function (atributoId) {
                    const label = $('#producto-atributos-checklist input[value="' + atributoId + '"]').closest('label').find('strong').text();
                    const valores = valoresCatalogo.filter(function (valor) { return Number(valor.vat_atr_id) === Number(atributoId); });
                    const htmlValores = valores.length ? valores.map(function (valor) {
                        const checked = $('#producto-valores-config input[data-valor-id="' + valor.vat_id + '"]').is(':checked');
                        return '<label class="desktop-product-check">' +
                            '<input type="checkbox" name="atributo_valores[' + atributoId + '][]" value="' + valor.vat_id + '" data-valor-id="' + valor.vat_id + '"' + (checked ? ' checked' : '') + '>' +
                            '<span><strong>' + escapeHtml(valor.vat_valor) + '</strong><small>Valor activo</small></span></label>';
                    }).join('') : '<small>No hay valores activos disponibles.</small>';

                    return '<div class="desktop-field desktop-field--full" data-attr-wrap="' + atributoId + '">' +
                        '<label>Valores de ' + escapeHtml(label) + '</label>' +
                        '<div class="desktop-product-checkgrid">' + htmlValores + '</div></div>';
                }).join('');

                container.html(attrChecks);

                const currentTarget = Number(target.val() || selectedIds[0] || 0);
                target.html(selectedIds.map(function (atributoId) {
                    const label = $('#producto-atributos-checklist input[value="' + atributoId + '"]').closest('label').find('strong').text();
                    return '<option value="' + atributoId + '"' + (Number(atributoId) === currentTarget ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
                }).join(''));

                renderCorridasConfig();
                renderProductSummary();
            }

            function selectedValuesByAttr() {
                const map = {};
                $('#producto-valores-config input[type="checkbox"]:checked').each(function () {
                    const match = String(this.name).match(/^atributo_valores\[(\d+)\]\[\]$/);
                    if (!match) return;
                    const attrId = Number(match[1]);
                    map[attrId] = map[attrId] || [];
                    map[attrId].push(Number(this.value));
                });
                return map;
            }

            function renderCorridasConfig() {
                normalizeCorridasState();
                const targetAttrId = Number($('#prd_corrida_atr_objetivo').val() || 0);
                const valuesMap = selectedValuesByAttr();
                const selectedValues = valuesMap[targetAttrId] || [];
                const rows = [];

                selectedValues.forEach(function (valorId, index) {
                    const valueInfo = valoresCatalogo.find(function (item) { return Number(item.vat_id) === Number(valorId); });
                    const current = corridasState[valorId] || {
                        nombre: valueInfo?.vat_valor || ('Corrida ' + (index + 1)),
                        precio_base: $('#prd_precio_base').val() || '0.00',
                        costo_base: $('#prd_costo').val() || '0.00',
                        stock_minimo: $('#prd_stock_minimo').val() || '0',
                        stock_maximo: $('#prd_stock_maximo').val() || '0'
                    };
                    corridasState[valorId] = current;

                    rows.push(
                        '<div class="desktop-product-corrida" data-corrida-valor="' + valorId + '">' +
                            '<div class="desktop-product-corrida__title">' + escapeHtml(valueInfo?.vat_valor || 'Corrida') + '</div>' +
                            '<div class="desktop-product-corrida__grid">' +
                                '<div class="desktop-field"><label>Nombre</label><input type="text" data-corrida-field="nombre" value="' + escapeHtml(current.nombre) + '"></div>' +
                                '<div class="desktop-field"><label>Precio base</label><input type="number" min="0" step="0.01" data-corrida-field="precio_base" value="' + escapeHtml(current.precio_base) + '"></div>' +
                                '<div class="desktop-field"><label>Costo base</label><input type="number" min="0" step="0.01" data-corrida-field="costo_base" value="' + escapeHtml(current.costo_base) + '"></div>' +
                                '<div class="desktop-field"><label>Stock mínimo</label><input type="number" min="0" step="1" data-corrida-field="stock_minimo" value="' + escapeHtml(current.stock_minimo) + '"></div>' +
                                '<div class="desktop-field"><label>Stock máximo</label><input type="number" min="0" step="1" data-corrida-field="stock_maximo" value="' + escapeHtml(current.stock_maximo) + '"></div>' +
                            '</div>' +
                        '</div>'
                    );
                });

                $('#producto-corridas-config').html(rows.join('') || '<small>No hay corridas configuradas todavía.</small>');
                buildHiddenCorridas();
                renderProductSummary();
            }

            function buildHiddenCorridas() {
                const targetAttrId = Number($('#prd_corrida_atr_objetivo').val() || 0);
                const valuesMap = selectedValuesByAttr();
                const selectedValues = valuesMap[targetAttrId] || [];
                const hidden = [];

                selectedValues.forEach(function (valorId, index) {
                    const state = corridasState[valorId];
                    if (!state) return;

                    hidden.push('<input type="hidden" name="corridas[' + index + '][crc_nombre]" value="' + escapeHtml(state.nombre) + '">');
                    hidden.push('<input type="hidden" name="corridas[' + index + '][crc_atr_id]" value="' + targetAttrId + '">');
                    hidden.push('<input type="hidden" name="corridas[' + index + '][crc_precio_base]" value="' + escapeHtml(state.precio_base) + '">');
                    hidden.push('<input type="hidden" name="corridas[' + index + '][crc_costo_base]" value="' + escapeHtml(state.costo_base) + '">');
                    hidden.push('<input type="hidden" name="corridas[' + index + '][crc_stock_minimo]" value="' + escapeHtml(state.stock_minimo) + '">');
                    hidden.push('<input type="hidden" name="corridas[' + index + '][crc_stock_maximo]" value="' + escapeHtml(state.stock_maximo) + '">');
                    hidden.push('<input type="hidden" name="corridas[' + index + '][crc_valor_ids][]" value="' + valorId + '">');
                });

                $('#producto-hidden-corridas').html(hidden.join(''));
            }

            function renderProductSummary() {
                const type = $('input[name="prd_tipo"]:checked').val() || 'simple';
                const attrCount = getSelectedAttributeIds().length;
                const valuesCount = Object.values(selectedValuesByAttr()).reduce(function (sum, list) { return sum + list.length; }, 0);
                const skuCount = type === 'variable'
                    ? Object.values(selectedValuesByAttr()).reduce(function (total, list) {
                        if (!list.length) return 0;
                        return total * list.length;
                    }, 1)
                    : 1;

                $('#producto-resumen-general').html([
                    ['Tipo actual', type === 'variable' ? 'Variable' : 'Simple'],
                    ['Atributos', String(type === 'variable' ? attrCount : 0)],
                    ['Valores', String(type === 'variable' ? valuesCount : 0)],
                    ['SKU a generar', String(type === 'variable' ? (skuCount || 0) : 1)]
                ].map(function (item) {
                    return '<div class="desktop-product-summary__card"><small>' + item[0] + '</small><strong>' + item[1] + '</strong></div>';
                }).join(''));
            }

            function syncProductTypeUI() {
                const isVariable = $('input[name="prd_tipo"]:checked').val() === 'variable';
                $('#producto-variable-shell').prop('hidden', !isVariable);
                if (!isVariable) {
                    corridasState = {};
                    $('#producto-hidden-corridas').empty();
                } else {
                    renderValoresConfig();
                }
                renderProductSummary();
            }

            function resetForm() {
                $form.get(0).reset();
                $('#prd_id').val('');
                $('#prd_estatus').val('activo');
                $('#prd_precio_base').val('0.00');
                $('#prd_costo').val('0.00');
                $('#prd_stock_minimo').val('0');
                $('#prd_stock_maximo').val('0');
                $('#prd_imagen_reset').val('0');
                $('input[name="prd_tipo"][value="simple"]').prop('checked', true);
                $('#desktop-producto-modal-title').text('Nuevo producto');
                $('#btn-guardar-producto').text('Guardar producto');
                $('#producto-atributos-checklist input[type="checkbox"]').prop('checked', false);
                $('#prd-almacenes-checklist input[type="checkbox"]').prop('checked', false);
                corridasState = {};
                updateModelOptions('', '');
                setImagePreview(null);
                syncImageMethodUI();
                syncProductTypeUI();
            }

            function fillForm(data) {
                resetForm();
                $('#desktop-producto-modal-title').text('Editar producto');
                $('#btn-guardar-producto').text('Guardar cambios');
                $('#prd_id').val(data.prd_id || '');
                $('#prd_codigo').val(data.prd_codigo || '');
                $('#prd_codigo_barras').val(data.prd_codigo_barras || '');
                $('#prd_clave_sat').val(data.prd_clave_sat || '');
                $('#prd_nombre').val(data.prd_nombre || '');
                $('#prd_descripcion').val(data.prd_descripcion || '');
                $('#prd_precio_base').val(data.prd_precio_base ?? '0.00');
                $('#prd_costo').val(data.prd_costo ?? '0.00');
                $('#prd_stock_minimo').val(data.prd_stock_minimo ?? '0');
                $('#prd_stock_maximo').val(data.prd_stock_maximo ?? '0');
                $('#prd_mrc_id').val(String(data.prd_mrc_id || ''));
                updateModelOptions(data.prd_mrc_id, data.prd_mdl_id);
                $('#prd_prv_id').val(String(data.prd_prv_id || ''));
                $('#prd_lna_id').val(String(data.prd_lna_id || ''));
                $('#prd_ctg_id').val(String(data.prd_ctg_id || ''));
                $('#prd_dsc_id').val(String(data.prd_dsc_id || ''));
                $('#prd_umd_id').val(String(data.prd_umd_id || ''));
                $('#prd_estatus').val(data.prd_estatus || 'activo');
                $('input[name="prd_tipo"][value="' + (data.prd_tipo || 'simple') + '"]').prop('checked', true);
                $('#prd-almacenes-checklist input[type="checkbox"]').each(function () {
                    $(this).prop('checked', (data.almacen_ids || []).includes(Number(this.value)));
                });
                $('#producto-atributos-checklist input[type="checkbox"]').each(function () {
                    $(this).prop('checked', (data.atributo_ids || []).includes(Number(this.value)));
                });
                corridasState = {};
                (data.corridas || []).forEach(function (corrida) {
                    (corrida.prc_valor_ids || []).forEach(function (valorId) {
                        corridasState[valorId] = {
                            nombre: corrida.prc_nombre || '',
                            precio_base: corrida.prc_precio_base ?? $('#prd_precio_base').val(),
                            costo_base: corrida.prc_costo_base ?? $('#prd_costo').val(),
                            stock_minimo: corrida.prc_stock_minimo ?? $('#prd_stock_minimo').val(),
                            stock_maximo: corrida.prc_stock_maximo ?? $('#prd_stock_maximo').val()
                        };
                    });
                });
                syncProductTypeUI();

                const valuesMap = data.atributo_valores || {};
                Object.keys(valuesMap).forEach(function (attrId) {
                    (valuesMap[attrId] || []).forEach(function (valorId) {
                        $('#producto-valores-config input[name="atributo_valores[' + attrId + '][]"][value="' + valorId + '"]').prop('checked', true);
                    });
                });

                const corridaTarget = (data.corridas && data.corridas[0] && data.corridas[0].prc_atr_id) ? data.corridas[0].prc_atr_id : '';
                if (corridaTarget) $('#prd_corrida_atr_objetivo').val(String(corridaTarget));
                renderCorridasConfig();

                if (data.prd_imagen_tipo === 'url') {
                    $('input[name="prd_imagen_metodo"][value="url"]').prop('checked', true);
                    $('#prd_imagen_url').val(data.prd_imagen_url || '');
                }
                setImagePreview(data.prd_imagen_preview_url || null);
                syncImageMethodUI();
            }

            function renderDetail(data) {
                const atributos = Array.isArray(data.atributo_ids) ? data.atributo_ids.length : 0;
                const fields = [
                    ['Producto', data.prd_nombre || '', true],
                    ['Código', data.prd_codigo || '-'],
                    ['Código de barras', data.prd_codigo_barras || '-'],
                    ['Clave SAT', data.prd_clave_sat || '-'],
                    ['Precio base', '$' + Number(data.prd_precio_base || 0).toFixed(2)],
                    ['Costo', '$' + Number(data.prd_costo || 0).toFixed(2)],
                    ['Tipo', data.prd_tipo === 'variable' ? 'Variable' : 'Simple'],
                    ['Atributos', String(atributos)],
                    ['Stock mínimo', String(data.prd_stock_minimo || 0)],
                    ['Stock máximo', String(data.prd_stock_maximo || 0)],
                    ['Estado', data.prd_estatus === 'activo' ? 'Activo' : 'Inactivo'],
                    ['Descripción catálogo', data.descripcion_catalogo || '-'],
                    ['Almacenes', (data.almacen_ids || []).join(', ') || '-', true],
                    ['Descripción', data.prd_descripcion || '-', true]
                ];

                $('#desktop-producto-detail-grid').html(fields.map(function (field) {
                    return '<div class="desktop-field ' + (field[2] ? 'desktop-field--full' : '') + '">' +
                        '<label>' + escapeHtml(field[0]) + '</label>' +
                        '<input type="text" readonly value="' + escapeHtml(field[1]) + '">' +
                        '</div>';
                }).join(''));
                openModal($detailModal);
            }

            function openConfirm(message, callback) {
                confirmAction = callback;
                $('#desktop-producto-confirm-copy').text(message);
                openModal($confirmModal);
            }

            function loadProducto(id, mode) {
                $.getJSON(rutas.show(id))
                    .done(function (response) {
                        const data = response.data || {};
                        if (mode === 'edit') {
                            fillForm(data);
                            openModal($modal);
                            return;
                        }
                        renderDetail(data);
                    })
                    .fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
            }

            function toggleProducto(id, estatus) {
                $.ajax({
                    url: rutas.estatus(id),
                    method: 'PATCH',
                    data: { prd_estatus: estatus }
                }).done(function (response) {
                    showFeedback('success', response.message || 'Estatus actualizado correctamente.');
                    reloadTable(false);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            }

            function deleteProducto(id, name) {
                openConfirm('Se eliminará "' + name + '". Esta acción no se puede deshacer.', function () {
                    $.ajax({
                        url: rutas.destroy(id),
                        method: 'DELETE'
                    }).done(function (response) {
                        showFeedback('success', response.message || 'Producto eliminado correctamente.');
                        reloadTable(true);
                    }).fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
                });
            }

            initTable();
            resetForm();

            const params = new URLSearchParams(window.location.search);
            if (params.get('guardado') === '1') {
                showFeedback('success', params.get('mensaje') || 'Producto guardado correctamente.');
                params.delete('guardado');
                params.delete('mensaje');
                const nextQuery = params.toString();
                const nextUrl = window.location.pathname + (nextQuery ? '?' + nextQuery : '');
                window.history.replaceState({}, '', nextUrl);
            }

            $('#btn-nuevo-producto').on('click', function () {
                window.location.href = rutas.create;
            });
            $('#btn-recargar-productos').on('click', function () { reloadTable(true); });
            $('#productos-estatus').on('change', function () { reloadTable(true); });
            $('#productos-search').on('input', function () { reloadTable(true); });
            $('#productos-length').on('change', function () {
                if (!productosTable) return;
                productosTable.page.len(Number(this.value)).draw();
            });
            $('#desktop-productos-pagination').on('click', '.desktop-pager__btn', function () {
                if ($(this).is(':disabled') || !productosTable) return;
                productosTable.page($(this).data('page')).draw('page');
            });

            $('#prd_mrc_id').on('change', function () {
                updateModelOptions(this.value, '');
            });
            $('input[name="prd_tipo"]').on('change', syncProductTypeUI);
            $('#producto-atributos-checklist').on('change', 'input[type="checkbox"]', renderValoresConfig);
            $('#producto-valores-config').on('change', 'input[type="checkbox"]', renderCorridasConfig);
            $('#prd_corrida_atr_objetivo').on('change', renderCorridasConfig);
            $('#producto-corridas-config').on('input', 'input[data-corrida-field]', function () {
                const valorId = Number($(this).closest('[data-corrida-valor]').data('corridaValor'));
                const field = String($(this).data('corridaField'));
                corridasState[valorId] = corridasState[valorId] || {};
                corridasState[valorId][field] = this.value;
                buildHiddenCorridas();
            });
            $('#prd_precio_base, #prd_costo, #prd_stock_minimo, #prd_stock_maximo').on('input', function () {
                renderCorridasConfig();
            });

            $('input[name="prd_imagen_metodo"]').on('change', syncImageMethodUI);
            $('#prd_imagen_archivo').on('change', function (event) {
                const file = event.target.files?.[0];
                if (!file) return;
                $('#prd_imagen_reset').val('0');
                setImagePreview(URL.createObjectURL(file));
            });
            $('#prd_imagen_url').on('input', function () {
                $('#prd_imagen_reset').val('0');
                if ($('input[name="prd_imagen_metodo"][value="url"]').is(':checked')) {
                    setImagePreview(this.value.trim() || null);
                }
            });
            $('#btn-quitar-imagen-producto').on('click', function () {
                $('#prd_imagen_archivo').val('');
                $('#prd_imagen_url').val('');
                $('input[name="prd_imagen_metodo"]').prop('checked', false);
                $('#prd_imagen_reset').val('1');
                setImagePreview(null);
                syncImageMethodUI();
            });

            $form.on('submit', function (event) {
                event.preventDefault();
                buildHiddenCorridas();

                const id = $('#prd_id').val();
                const formData = new FormData($form.get(0));
                if (id) formData.append('_method', 'PUT');

                $.ajax({
                    url: id ? rutas.update(id) : rutas.store,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false
                }).done(function (response) {
                    closeModal($modal);
                    showFeedback('success', response.message || 'Producto guardado correctamente.');
                    reloadTable(true);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });

            $(document).on('click', '.btn-editar-producto', function () { window.location.href = rutas.edit($(this).data('id')); });
            $(document).on('click', '.btn-ver-producto', function () { loadProducto($(this).data('id'), 'view'); });
            $(document).on('click', '.btn-toggle-producto', function () { toggleProducto($(this).data('id'), $(this).data('estatus')); });
            $(document).on('click', '.btn-eliminar-producto', function () { deleteProducto($(this).data('id'), $(this).data('name')); });

            $('[data-close-producto-modal]').on('click', function () { closeModal($modal); });
            $('[data-close-producto-detail-modal]').on('click', function () { closeModal($detailModal); });
            $('[data-close-producto-confirm-modal]').on('click', function () { closeModal($confirmModal); });
            $('#desktop-producto-confirm-accept').on('click', function () {
                const action = confirmAction;
                confirmAction = null;
                closeModal($confirmModal);
                if (typeof action === 'function') action();
            });

            $modal.on('click', function (event) { if (event.target === this) closeModal($modal); });
            $detailModal.on('click', function (event) { if (event.target === this) closeModal($detailModal); });
            $confirmModal.on('click', function (event) { if (event.target === this) closeModal($confirmModal); });
        })();
    </script>
@endpush
