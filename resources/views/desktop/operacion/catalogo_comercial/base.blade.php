@extends('layouts.desktop')

@section('title', 'Catálogos base')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .desktop-catalogo-pane__bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            padding: 8px 12px;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface-alt);
        }
        .desktop-catalogo-pane__meta {
            font-size: .75rem;
            color: var(--text-2);
        }
        .desktop-catalogo-checklist {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .desktop-catalogo-check {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            padding: 0 10px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface-alt);
        }
        .desktop-catalogo-check input {
            margin: 0;
        }
        .desktop-catalogo-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 16px;
        }
        .desktop-catalogo-detail-grid .desktop-field--full {
            grid-column: 1 / -1;
        }
        .desktop-modal__dialog--brand {
            width: min(760px, calc(100vw - 40px));
        }
        .desktop-modal__dialog--catalog {
            width: min(880px, calc(100vw - 40px));
        }
        .desktop-modal__dialog--catalog .desktop-modal__head {
            padding: 22px 24px 16px;
        }
        .desktop-modal__dialog--catalog .desktop-modal__body {
            padding: 18px 24px 20px;
        }
        .desktop-modal__dialog--catalog .desktop-modal__foot {
            padding: 14px 24px;
        }
        .desktop-brand-form-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.8fr) minmax(220px, .82fr);
            gap: 16px 22px;
            align-items: end;
        }
        .desktop-brand-form-grid .desktop-field--full {
            grid-column: 1 / -1;
        }
        .desktop-brand-form-grid .desktop-field {
            margin: 0;
        }
        #mrc_nombre {
            text-transform: uppercase;
        }
        #lna_nombre {
            text-transform: uppercase;
        }
        #ctg_nombre {
            text-transform: uppercase;
        }
        #umd_nombre {
            text-transform: uppercase;
        }
        #dsc_nombre {
            text-transform: uppercase;
        }
        #mtv_nombre {
            text-transform: uppercase;
        }
        #mdl_nombre {
            text-transform: uppercase;
        }
        #cat_nombre.is-uppercase {
            text-transform: uppercase;
        }
        .desktop-modal__eyebrow {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .desktop-modal__desc {
            margin: 0;
            color: var(--text-2);
            font-size: .82rem;
            line-height: 1.5;
            max-width: 64ch;
        }
        .desktop-model-form-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(220px, .9fr);
            gap: 12px 18px;
            align-items: end;
        }
        .desktop-model-stack {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding-top: 8px;
        }
        .desktop-model-picker__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .desktop-model-picker__label {
            font-size: .86rem;
            font-weight: 600;
            color: var(--text);
        }
        .desktop-chip-search {
            position: relative;
        }
        .desktop-chip-search svg {
            position: absolute;
            top: 50%;
            left: 10px;
            width: 15px;
            height: 15px;
            color: var(--text-3);
            transform: translateY(-50%);
            pointer-events: none;
        }
        .desktop-chip-search input {
            width: 100%;
            min-height: 34px;
            padding: 0 10px 0 32px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            outline: 0;
            font: inherit;
            background: var(--surface);
        }
        .desktop-chip-search input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 1px var(--brand);
        }
        .desktop-chip-panel {
            max-height: 208px;
            overflow-y: auto;
            padding: 4px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-lg);
            background: var(--surface-alt);
        }
        .desktop-chip-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-content: flex-start;
        }
        .desktop-chip {
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        .desktop-chip input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .desktop-chip span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 28px;
            padding: 0 9px;
            border: 1px solid var(--stroke);
            border-radius: 999px;
            background: var(--surface);
            color: var(--text-2);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .02em;
            cursor: pointer;
            transition: background .16s ease, border-color .16s ease, color .16s ease, box-shadow .16s ease;
        }
        .desktop-chip span::before {
            content: "";
            width: 0;
            overflow: hidden;
            transition: width .16s ease;
        }
        .desktop-chip:hover span {
            border-color: var(--text-3);
        }
        .desktop-chip input:checked + span {
            background: var(--brand-soft);
            border-color: #b7d6f2;
            color: var(--brand);
            box-shadow: inset 0 0 0 1px rgba(15, 108, 189, .08);
        }
        .desktop-chip input:checked + span::before {
            content: "✓";
            width: 10px;
        }
        .desktop-chip.is-hidden {
            display: none;
        }
        .desktop-chip-empty {
            display: none;
            padding: 12px 10px;
            color: var(--text-2);
            font-size: .8rem;
        }
        .desktop-chip-empty.is-visible {
            display: block;
        }
        .desktop-modal__foot--sticky {
            position: sticky;
            bottom: 0;
            background: var(--surface);
            border-top: 1px solid var(--stroke);
            z-index: 1;
        }
        @media (max-width: 860px) {
            .desktop-catalogo-checklist,
            .desktop-catalogo-detail-grid,
            .desktop-brand-form-grid,
            .desktop-model-form-grid {
                grid-template-columns: 1fr;
            }
            .desktop-model-picker__head {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'base')
        @include('desktop.operacion.catalogo_comercial._subnav')
        @if($permisosUI['crear'])
            <span class="desktop-toolbar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-nuevo-base">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
                Nuevo registro
            </button>
        @endif
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-base">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <select class="desktop-toolbar__select" id="base-estatus">
            <option value="">Todos los estatus</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
        </select>
        <select class="desktop-toolbar__select" id="base-length">
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
            <option value="100" selected>100 por página</option>
        </select>
        <input type="search" id="base-search" class="desktop-toolbar__search" placeholder="Buscar registro">
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-catalogo-pane__bar">
            <div class="desktop-pivot" role="tablist" aria-label="Tipos de catálogo base">
                @foreach($catalogTypes as $catalogType)
                    <button
                        type="button"
                        class="desktop-btn {{ $loop->first ? 'desktop-btn--active' : '' }}"
                        data-catalog-type="{{ $catalogType['key'] }}"
                        @if($loop->first) aria-current="page" @endif
                    >
                        {{ $catalogType['label'] }}
                    </button>
                @endforeach
            </div>
            <div class="desktop-catalogo-pane__meta" id="catalogo-base-meta">Catálogo activo: Marcas</div>
        </div>

        <div class="desktop-list-wrap">
            <table id="desktop-catalogos-base-table" class="desktop-list">
                <thead>
                    <tr id="desktop-catalogos-base-head"></tr>
                </thead>
            </table>
        </div>

        <div class="desktop-list-foot">
            <div id="desktop-catalogos-base-info"></div>
            <div id="desktop-catalogos-base-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <div class="desktop-modal" id="desktop-catalogo-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-catalogo-modal-title">Nuevo registro</div>
                <button type="button" class="desktop-modal__close" data-close-catalogo-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-catalogo-form">
                <div class="desktop-modal__body">
                    <input type="hidden" id="cat_id" name="cat_id">

                    <div class="desktop-form-grid">
                        <div class="desktop-field desktop-field--full">
                            <label>Nombre</label>
                            <input type="text" name="nombre" id="cat_nombre" maxlength="120" required>
                        </div>

                        <div class="desktop-field" id="cat-codigo-wrap" hidden>
                            <label>Código</label>
                            <input type="text" name="codigo" id="cat_codigo" maxlength="20">
                        </div>

                        <div class="desktop-field" id="cat-estatus-wrap">
                            <label>Estatus</label>
                            <select name="estatus" id="cat_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>

                        <div class="desktop-field desktop-field--full" id="cat-categoria-linea-wrap" hidden>
                            <label>Línea</label>
                            <select name="lna_id" id="cat_lna_id">
                                <option value="">Selecciona una línea</option>
                                @foreach($opciones['lineas'] as $linea)
                                    <option value="{{ $linea->lna_id }}">{{ $linea->lna_nombre }}</option>
                                @endforeach
                            </select>
                            <small>El mismo concepto puede existir en distintas líneas.</small>
                        </div>

                        <div class="desktop-field desktop-field--full" id="cat-unidad-extra" hidden>
                            <div class="desktop-form-grid">
                                <div class="desktop-field">
                                    <label>Tipo de cantidad</label>
                                    <select name="tipo_cantidad" id="cat_tipo_cantidad">
                                        <option value="entero">Entero</option>
                                        <option value="decimal">Decimal</option>
                                    </select>
                                    <small>Define si la unidad admite cantidades decimales.</small>
                                </div>
                                <div class="desktop-field" style="justify-content:flex-end;">
                                    <label class="desktop-check">
                                        <input type="checkbox" name="es_predeterminada" id="cat_es_predeterminada" value="1">
                                        <span class="desktop-check__box">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                        </span>
                                        <span class="desktop-check__label">Usar como unidad predeterminada</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-catalogo-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-catalogo">Guardar registro</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-marca-modal" aria-hidden="true">
        <div class="desktop-modal__dialog desktop-modal__dialog--brand">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-marca-modal-title">Nueva marca</div>
                <button type="button" class="desktop-modal__close" data-close-marca-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-marca-form">
                <div class="desktop-modal__body">
                    <input type="hidden" id="mrc_id" name="mrc_id">

                    <div class="desktop-brand-form-grid">
                        <div class="desktop-field">
                            <label>Nombre</label>
                            <input type="text" name="nombre" id="mrc_nombre" maxlength="120" required placeholder="Escribe el nombre de la marca">
                        </div>

                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="estatus" id="mrc_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-marca-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-marca">Guardar marca</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-linea-modal" aria-hidden="true">
        <div class="desktop-modal__dialog desktop-modal__dialog--brand">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-linea-modal-title">Nueva linea</div>
                <button type="button" class="desktop-modal__close" data-close-linea-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-linea-form">
                <div class="desktop-modal__body">
                    <input type="hidden" id="lna_id" name="lna_id">

                    <div class="desktop-brand-form-grid">
                        <div class="desktop-field">
                            <label>Nombre</label>
                            <input type="text" name="nombre" id="lna_nombre" maxlength="120" required placeholder="Escribe el nombre de la linea">
                        </div>

                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="estatus" id="lna_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-linea-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-linea">Guardar linea</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-concepto-modal" aria-hidden="true">
        <div class="desktop-modal__dialog desktop-modal__dialog--brand">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-concepto-modal-title">Nuevo concepto</div>
                <button type="button" class="desktop-modal__close" data-close-concepto-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-concepto-form">
                <div class="desktop-modal__body">
                    <input type="hidden" id="ctg_id" name="ctg_id">

                    <div class="desktop-brand-form-grid">
                        <div class="desktop-field">
                            <label>Nombre</label>
                            <input type="text" name="nombre" id="ctg_nombre" maxlength="120" required placeholder="Escribe el nombre del concepto">
                        </div>

                        <div class="desktop-field">
                            <label>Estado</label>
                            <select name="estatus" id="ctg_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>

                        <div class="desktop-field desktop-field--full">
                            <label>Linea asociada</label>
                            <select name="lna_id" id="ctg_lna_id" required>
                                <option value="">Selecciona una linea</option>
                                @foreach($opciones['lineas'] as $linea)
                                    <option value="{{ $linea->lna_id }}">{{ $linea->lna_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-concepto-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-concepto">Guardar concepto</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-unidad-modal" aria-hidden="true">
        <div class="desktop-modal__dialog desktop-modal__dialog--brand">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-unidad-modal-title">Nueva unidad</div>
                <button type="button" class="desktop-modal__close" data-close-unidad-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-unidad-form">
                <div class="desktop-modal__body">
                    <input type="hidden" id="umd_id" name="umd_id">

                    <div class="desktop-brand-form-grid">
                        <div class="desktop-field">
                            <label>Nombre</label>
                            <input type="text" name="nombre" id="umd_nombre" maxlength="120" required placeholder="Escribe el nombre de la unidad">
                        </div>

                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="estatus" id="umd_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>

                        <div class="desktop-field">
                            <label>Tipo de cantidad</label>
                            <select name="tipo_cantidad" id="umd_tipo_cantidad" required>
                                <option value="entero">Entero</option>
                                <option value="decimal">Decimal</option>
                            </select>
                        </div>

                        <div class="desktop-field" style="justify-content:flex-end;">
                            <label class="desktop-check">
                                <input type="checkbox" name="es_predeterminada" id="umd_es_predeterminada" value="1">
                                <span class="desktop-check__box">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                <span class="desktop-check__label">Unidad predeterminada</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-unidad-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-unidad">Guardar unidad</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-descripcion-modal" aria-hidden="true">
        <div class="desktop-modal__dialog desktop-modal__dialog--brand">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-descripcion-modal-title">Nueva descripción</div>
                <button type="button" class="desktop-modal__close" data-close-descripcion-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-descripcion-form">
                <div class="desktop-modal__body">
                    <input type="hidden" id="dsc_id" name="dsc_id">

                    <div class="desktop-brand-form-grid">
                        <div class="desktop-field">
                            <label>Nombre</label>
                            <input type="text" name="nombre" id="dsc_nombre" maxlength="120" required placeholder="Escribe el nombre de la descripción">
                        </div>

                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="estatus" id="dsc_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-descripcion-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-descripcion">Guardar descripción</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-motivo-modal" aria-hidden="true">
        <div class="desktop-modal__dialog desktop-modal__dialog--brand">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-motivo-modal-title">Nuevo motivo</div>
                <button type="button" class="desktop-modal__close" data-close-motivo-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-motivo-form">
                <div class="desktop-modal__body">
                    <input type="hidden" id="mtv_id" name="mtv_id">

                    <div class="desktop-brand-form-grid">
                        <div class="desktop-field">
                            <label>Nombre</label>
                            <input type="text" name="nombre" id="mtv_nombre" maxlength="120" required placeholder="Escribe el nombre del motivo">
                        </div>

                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="estatus" id="mtv_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-motivo-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-motivo">Guardar motivo</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-modelo-modal" aria-hidden="true">
        <div class="desktop-modal__dialog desktop-modal__dialog--catalog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-modelo-modal-title">Nuevo modelo</div>
                <button type="button" class="desktop-modal__close" data-close-modelo-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-modelo-form">
                <div class="desktop-modal__body">
                    <input type="hidden" id="mdl_id" name="mdl_id">

                    <div class="desktop-model-stack">
                        <div class="desktop-model-form-grid">
                            <div class="desktop-field">
                                <label>Nombre del modelo</label>
                                <input type="text" name="nombre" id="mdl_nombre" maxlength="120" required placeholder="ESCRIBE EL NOMBRE DEL MODELO">
                            </div>
                            <div class="desktop-field">
                                <label>Estatus</label>
                                <select name="estatus" id="mdl_estatus" required>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <div class="desktop-model-picker__head">
                            <label class="desktop-model-picker__label" for="mdl_brand_search">Marcas <span id="mdl-selection-count">(0)</span></label>
                        </div>

                        <div class="desktop-chip-search">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                            <input type="search" id="mdl_brand_search" placeholder="Buscar marca...">
                        </div>

                        <div class="desktop-chip-panel">
                            <div class="desktop-chip-grid" id="mdl-marcas-chip-grid">
                                @foreach($opciones['marcas'] as $marca)
                                    <label class="desktop-chip" data-brand-chip data-search="{{ \Illuminate\Support\Str::upper($marca->mrc_nombre) }}">
                                        <input type="checkbox" name="marca_ids[]" value="{{ $marca->mrc_id }}">
                                        <span>{{ \Illuminate\Support\Str::upper($marca->mrc_nombre) }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <div class="desktop-chip-empty{{ $opciones['marcas']->isEmpty() ? ' is-visible' : '' }}" id="mdl-marcas-empty">
                                {{ $opciones['marcas']->isEmpty() ? 'No hay marcas disponibles.' : 'No se encontraron marcas.' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="desktop-modal__foot desktop-modal__foot--sticky">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-modelo-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-modelo">Guardar modelo</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-detail-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-detail-title">Detalle</div>
                <button type="button" class="desktop-modal__close" data-close-detail-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body">
                <div class="desktop-catalogo-detail-grid" id="desktop-detail-grid"></div>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-detail-modal>Cerrar</button>
            </div>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-confirm-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="width:min(440px, 100%);">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-confirm-title">Confirmar acción</div>
                <button type="button" class="desktop-modal__close" data-close-confirm-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body">
                <p id="desktop-confirm-copy" style="margin:0; color:var(--text-2); line-height:1.55;"></p>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-confirm-modal>Cancelar</button>
                <button type="button" class="desktop-btn desktop-btn--primary" id="desktop-confirm-accept">Continuar</button>
            </div>
        </div>
    </div>

    <div class="desktop-feedback" id="desktop-catalogo-feedback"></div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $table = $('#desktop-catalogos-base-table');
            const $catalogModal = $('#desktop-catalogo-modal');
            const $catalogForm = $('#desktop-catalogo-form');
            const $brandModal = $('#desktop-marca-modal');
            const $brandForm = $('#desktop-marca-form');
            const $lineModal = $('#desktop-linea-modal');
            const $lineForm = $('#desktop-linea-form');
            const $conceptModal = $('#desktop-concepto-modal');
            const $conceptForm = $('#desktop-concepto-form');
            const $unitModal = $('#desktop-unidad-modal');
            const $unitForm = $('#desktop-unidad-form');
            const $descriptionModal = $('#desktop-descripcion-modal');
            const $descriptionForm = $('#desktop-descripcion-form');
            const $reasonModal = $('#desktop-motivo-modal');
            const $reasonForm = $('#desktop-motivo-form');
            const $modelModal = $('#desktop-modelo-modal');
            const $modelForm = $('#desktop-modelo-form');
            const $detailModal = $('#desktop-detail-modal');
            const $confirmModal = $('#desktop-confirm-modal');
            const $feedback = $('#desktop-catalogo-feedback');
            let baseTable = null;
            let currentType = 'marcas';
            let confirmAction = null;

            $.ajaxSetup({
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const permisosUI = @json($permisosUI);
            const catalogConfig = {
                marcas: {
                    label: 'Marca',
                    createLabel: 'Nueva marca',
                    listLabel: 'Marcas',
                    form: { showStatus: true, showCode: false, showLinea: false, showUnidadExtra: false },
                    columns: 'simple'
                },
                modelos: {
                    label: 'Modelo',
                    createLabel: 'Nuevo modelo',
                    listLabel: 'Modelos',
                    columns: 'modelos'
                },
                lineas: {
                    label: 'Línea',
                    createLabel: 'Nueva línea',
                    listLabel: 'Líneas',
                    form: { showStatus: true, showCode: false, showLinea: false, showUnidadExtra: false, uppercaseName: true },
                    columns: 'simple'
                },
                categorias: {
                    label: 'Concepto',
                    createLabel: 'Nuevo concepto',
                    listLabel: 'Conceptos',
                    form: { showStatus: true, showCode: false, showLinea: true, showUnidadExtra: false },
                    columns: 'categorias'
                },
                unidades: {
                    label: 'Unidad',
                    createLabel: 'Nueva unidad',
                    listLabel: 'Unidades',
                    form: { showStatus: true, showCode: false, showLinea: false, showUnidadExtra: true },
                    columns: 'unidades'
                },
                descripciones: {
                    label: 'Descripción',
                    createLabel: 'Nueva descripción',
                    listLabel: 'Descripción',
                    form: { showStatus: true, showCode: false, showLinea: false, showUnidadExtra: false },
                    columns: 'simple'
                },
                conceptos: {
                    label: 'Concepto',
                    createLabel: 'Nuevo concepto',
                    listLabel: 'Conceptos',
                    form: { showStatus: false, showCode: false, showLinea: false, showUnidadExtra: false },
                    columns: 'simple'
                },
                motivos: {
                    label: 'Motivo',
                    createLabel: 'Nuevo motivo',
                    listLabel: 'Motivos',
                    form: { showStatus: true, showCode: false, showLinea: false, showUnidadExtra: false },
                    columns: 'simple'
                }
            };
            const rutas = {
                baseData: function (tipo) { return '{{ url('/desktop/operacion/catalogo-comercial/catalogos') }}/' + tipo + '/data'; },
                baseShow: function (tipo, id) { return '{{ url('/desktop/operacion/catalogo-comercial/catalogos') }}/' + tipo + '/' + id; },
                baseStore: function (tipo) { return '{{ url('/desktop/operacion/catalogo-comercial/catalogos') }}/' + tipo; },
                baseUpdate: function (tipo, id) { return '{{ url('/desktop/operacion/catalogo-comercial/catalogos') }}/' + tipo + '/' + id; },
                baseEstatus: function (tipo, id) { return '{{ url('/desktop/operacion/catalogo-comercial/catalogos') }}/' + tipo + '/' + id + '/estatus'; },
                baseDelete: function (tipo, id) { return '{{ url('/desktop/operacion/catalogo-comercial/catalogos') }}/' + tipo + '/' + id; },
                modelosData: '{{ route('desktop.operacion.catalogo_comercial.modelos.data') }}',
                modeloShow: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/modelos') }}/' + id; },
                modeloStore: '{{ route('desktop.operacion.catalogo_comercial.modelos.store') }}',
                modeloUpdate: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/modelos') }}/' + id; },
                modeloEstatus: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/modelos') }}/' + id + '/estatus'; },
                modeloDelete: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/modelos') }}/' + id; }
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

            function openModal($modal) {
                $modal.addClass('is-open').attr('aria-hidden', 'false');
            }

            function closeModal($modal) {
                $modal.removeClass('is-open').attr('aria-hidden', 'true');
            }

            function renderStatus(value) {
                const active = value === 'activo';
                return '<span class="desktop-status ' + (active ? 'desktop-status--active' : 'desktop-status--inactive') + '">' +
                    (active ? 'Activo' : 'Inactivo') + '</span>';
            }

            function currentCatalogConfig() {
                return catalogConfig[currentType] || catalogConfig.marcas;
            }

            function syncTypeUI() {
                const config = currentCatalogConfig();
                $('[data-catalog-type]').removeClass('desktop-btn--active').removeAttr('aria-current');
                $('[data-catalog-type="' + currentType + '"]').addClass('desktop-btn--active').attr('aria-current', 'page');
                $('#catalogo-base-meta').text('Catálogo activo: ' + (config.listLabel || 'Catálogos'));
                $('#btn-nuevo-base').html(
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>' +
                    (config.createLabel || 'Nuevo registro')
                );
            }

            function syncCatalogFormVisibility() {
                const formConfig = currentCatalogConfig().form || {};

                $('#cat-estatus-wrap').prop('hidden', !formConfig.showStatus);
                $('#cat-codigo-wrap').prop('hidden', !formConfig.showCode);
                $('#cat-unidad-extra').prop('hidden', !formConfig.showUnidadExtra);
                $('#cat-categoria-linea-wrap').prop('hidden', !formConfig.showLinea);
                $('#cat_nombre').toggleClass('is-uppercase', !!formConfig.uppercaseName);
                $('#cat_estatus').prop('required', formConfig.showStatus !== false);
                $('#cat_codigo').prop('required', !!formConfig.showCode);
                $('#cat_lna_id').prop('required', !!formConfig.showLinea);
            }

            function renderActions(row) {
                const isModel = currentType === 'modelos';
                const id = isModel ? row.mdl_id : row.id;
                const name = isModel ? row.mdl_nombre : row.nombre;
                const status = isModel ? row.mdl_estatus : row.estatus;
                const items = [];

                if (permisosUI.editar) {
                    items.push('<button type="button" class="desktop-menu__item btn-edit-record" data-id="' + id + '">' + ICONS.edit + 'Editar</button>');
                }

                items.push('<button type="button" class="desktop-menu__item btn-view-record" data-id="' + id + '">' + ICONS.view + 'Ver detalle</button>');

                if (permisosUI.inactivar) {
                    items.push('<div class="desktop-menu__divider"></div>');
                    items.push('<button type="button" class="desktop-menu__item btn-toggle-record" data-id="' + id + '" data-next="' + (status === 'activo' ? 'inactivo' : 'activo') + '">' + ICONS.toggle + (status === 'activo' ? 'Inactivar' : 'Activar') + '</button>');
                }

                if (permisosUI.eliminar) {
                    items.push('<button type="button" class="desktop-menu__item desktop-menu__item--danger btn-delete-record" data-id="' + id + '" data-name="' + escapeHtml(name) + '">' + ICONS.remove + 'Eliminar</button>');
                }

                return '<div class="desktop-rowmenu">' +
                    '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                    '<div class="desktop-menu">' + items.join('') + '</div>' +
                    '</div>';
            }

            function tableHeaders() {
                const config = currentCatalogConfig();

                if (config.columns === 'modelos') {
                    return ['Modelo', 'Clave', 'Marcas', 'Estatus', 'Acciones'];
                }

                if (config.columns === 'categorias') {
                    return ['Concepto', 'Línea', 'Clave', 'Estatus', 'Acciones'];
                }

                if (config.columns === 'unidades') {
                    return ['Unidad', 'Código', 'Tipo de cantidad', 'Estatus', 'Acciones'];
                }

                return [config.label || 'Registro', 'Clave', 'Estatus', 'Acciones'];
            }

            function tableColumns() {
                const config = currentCatalogConfig();

                if (config.columns === 'modelos') {
                    return [
                        {
                            data: 'mdl_nombre',
                            render: function (value, type, row) {
                                return '<div class="desktop-cell-primary">' +
                                    '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                    '<span><span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                    '<span class="desktop-list__meta">ID ' + escapeHtml(row.mdl_id) + '</span></span></div>';
                            }
                        },
                        {
                            data: 'mdl_clave',
                            render: function (value) {
                                return '<span style="font-weight:600;">' + escapeHtml(value || '-') + '</span>';
                            }
                        },
                        {
                            data: 'marcas',
                            render: function (value, type, row) {
                                const brands = Array.isArray(value) ? value : [];
                                if (!brands.length) return '<span class="desktop-list__meta">Sin marcas asociadas</span>';

                                const pills = brands.slice(0, 2).map(function (brand) {
                                    return '<span class="desktop-pill desktop-pill--brand">' + escapeHtml(brand.nombre) + '</span>';
                                });

                                if (brands.length > 2) {
                                    pills.push('<span class="desktop-pill desktop-pill--more">+' + (brands.length - 2) + '</span>');
                                }

                                return '<div class="desktop-pill-list">' + pills.join('') + '</div>' +
                                    '<span class="desktop-list__meta">' + escapeHtml(row.marcas_texto) + '</span>';
                            }
                        },
                        { data: 'mdl_estatus', render: renderStatus },
                        { data: null, orderable: false, searchable: false, render: renderActions }
                    ];
                }

                if (config.columns === 'categorias') {
                    return [
                        {
                            data: 'nombre',
                            render: function (value, type, row) {
                                return '<div class="desktop-cell-primary">' +
                                    '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                    '<span><span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                    '<span class="desktop-list__meta">ID ' + escapeHtml(row.id) + '</span></span></div>';
                            }
                        },
                        {
                            data: 'linea',
                            render: function (value) {
                                return value
                                    ? '<span class="desktop-list__name">' + escapeHtml(value) + '</span>'
                                    : '<span class="desktop-list__meta">Sin línea</span>';
                            }
                        },
                        {
                            data: 'clave',
                            render: function (value) {
                                return '<span style="font-weight:600;">' + escapeHtml(value || '-') + '</span>';
                            }
                        },
                        { data: 'estatus', render: renderStatus },
                        { data: null, orderable: false, searchable: false, render: renderActions }
                    ];
                }

                if (config.columns === 'unidades') {
                    return [
                        {
                            data: 'nombre',
                            render: function (value, type, row) {
                                return '<div class="desktop-cell-primary">' +
                                    '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                    '<span><span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                    '<span class="desktop-list__meta">' + (row.es_predeterminada ? 'Predeterminada' : 'Unidad activa') + '</span></span></div>';
                            }
                        },
                        {
                            data: 'codigo',
                            render: function (value) {
                                return value
                                    ? '<span class="desktop-list__name">' + escapeHtml(value) + '</span>'
                                    : '<span class="desktop-list__meta">-</span>';
                            }
                        },
                        {
                            data: 'tipo_cantidad',
                            render: function (value) {
                                return '<span class="desktop-list__name">' + (value === 'decimal' ? 'Decimal' : 'Entero') + '</span>';
                            }
                        },
                        { data: 'estatus', render: renderStatus },
                        { data: null, orderable: false, searchable: false, render: renderActions }
                    ];
                }

                return [
                    {
                        data: 'nombre',
                        render: function (value, type, row) {
                            return '<div class="desktop-cell-primary">' +
                                '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                '<span><span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                '<span class="desktop-list__meta">ID ' + escapeHtml(row.id) + '</span></span></div>';
                        }
                    },
                    {
                        data: 'clave',
                        render: function (value) {
                            return value
                                ? '<span class="desktop-list__name">' + escapeHtml(value) + '</span>'
                                : '<span class="desktop-list__meta">-</span>';
                        }
                    },
                    { data: 'estatus', render: renderStatus },
                    { data: null, orderable: false, searchable: false, render: renderActions }
                ];
            }

            function renderCustomFooter() {
                if (!baseTable) return;

                const info = baseTable.page.info();
                const total = info.recordsDisplay;
                const label = currentCatalogConfig().listLabel || 'registros';

                if (!total) {
                    $('#desktop-catalogos-base-info').text('Mostrando 0 ' + label.toLowerCase());
                    $('#desktop-catalogos-base-pagination').empty();
                    return;
                }

                $('#desktop-catalogos-base-info').text(
                    'Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' ' + label.toLowerCase()
                );

                const buttons = [];
                buttons.push({ label: '‹', page: 'previous', disabled: info.page === 0 });
                for (let i = 0; i < info.pages; i += 1) {
                    buttons.push({ label: String(i + 1), page: i, active: i === info.page });
                }
                buttons.push({ label: '›', page: 'next', disabled: info.page >= info.pages - 1 });

                $('#desktop-catalogos-base-pagination').html(buttons.map(function (button) {
                    const classes = [
                        'desktop-pager__btn',
                        button.active ? 'is-active' : '',
                        button.disabled ? 'is-disabled' : ''
                    ].filter(Boolean).join(' ');

                    return '<button type="button" class="' + classes + '" data-page="' + button.page + '"' +
                        (button.disabled ? ' disabled' : '') + '>' + button.label + '</button>';
                }).join(''));
            }

            function destroyTable() {
                if ($.fn.DataTable.isDataTable('#desktop-catalogos-base-table')) {
                    $('#desktop-catalogos-base-table').DataTable().destroy();
                }
                $('#desktop-catalogos-base-head').html('');
                $('#desktop-catalogos-base-table tbody').remove();
                baseTable = null;
            }

            function buildTable() {
                destroyTable();

                $('#desktop-catalogos-base-head').html(tableHeaders().map(function (header, index) {
                    const style = index === 4 ? ' style="width:56px; text-align:right;"' : (index === 3 ? ' style="width:104px;"' : '');
                    return '<th' + style + '>' + header + '</th>';
                }).join(''));

                baseTable = $table.DataTable({
                    ajax: {
                        url: currentType === 'modelos' ? rutas.modelosData : rutas.baseData(currentType),
                        data: function () {
                            return {
                                buscar: $('#base-search').val(),
                                estatus: $('#base-estatus').val()
                            };
                        },
                        dataSrc: 'data'
                    },
                    processing: true,
                    deferRender: true,
                    responsive: false,
                    autoWidth: false,
                    pageLength: Number($('#base-length').val() || 100),
                    lengthChange: false,
                    searching: false,
                    order: [[0, 'asc']],
                    dom: 'rt',
                    language: {
                        processing: 'Cargando...',
                        emptyTable: 'No hay registros disponibles',
                        zeroRecords: 'No se encontraron coincidencias'
                    },
                    columns: tableColumns(),
                    initComplete: renderCustomFooter,
                    drawCallback: renderCustomFooter
                });
            }

            function reloadTable(resetPaging) {
                if (!baseTable) return;
                baseTable.ajax.reload(null, !resetPaging);
            }

            function resetCatalogForm() {
                $catalogForm.get(0).reset();
                $('#cat_id').val('');
                $('#cat_estatus').val('activo');
                $('#cat_tipo_cantidad').val('entero');
                $('#cat_es_predeterminada').prop('checked', false);
                $('#desktop-catalogo-modal-title').text(currentCatalogConfig().createLabel || 'Nuevo registro');
                $('#btn-guardar-catalogo').text('Guardar registro');
                syncCatalogFormVisibility();
            }

            function resetBrandForm() {
                $brandForm.get(0).reset();
                $('#mrc_id').val('');
                $('#mrc_estatus').val('activo');
                $('#mrc_nombre').val('');
                $('#desktop-marca-modal-title').text('Nueva marca');
                $('#btn-guardar-marca').text('Guardar marca');
            }

            function resetLineForm() {
                $lineForm.get(0).reset();
                $('#lna_id').val('');
                $('#lna_estatus').val('activo');
                $('#lna_nombre').val('');
                $('#desktop-linea-modal-title').text('Nueva linea');
                $('#btn-guardar-linea').text('Guardar linea');
            }

            function resetConceptForm() {
                $conceptForm.get(0).reset();
                $('#ctg_id').val('');
                $('#ctg_estatus').val('activo');
                $('#ctg_nombre').val('');
                $('#ctg_lna_id').val('');
                $('#desktop-concepto-modal-title').text('Nuevo concepto');
                $('#btn-guardar-concepto').text('Guardar concepto');
            }

            function resetUnitForm() {
                $unitForm.get(0).reset();
                $('#umd_id').val('');
                $('#umd_estatus').val('activo');
                $('#umd_nombre').val('');
                $('#umd_tipo_cantidad').val('entero');
                $('#umd_es_predeterminada').prop('checked', false);
                $('#desktop-unidad-modal-title').text('Nueva unidad');
                $('#btn-guardar-unidad').text('Guardar unidad');
            }

            function resetDescriptionForm() {
                $descriptionForm.get(0).reset();
                $('#dsc_id').val('');
                $('#dsc_estatus').val('activo');
                $('#dsc_nombre').val('');
                $('#desktop-descripcion-modal-title').text('Nueva descripción');
                $('#btn-guardar-descripcion').text('Guardar descripción');
            }

            function resetReasonForm() {
                $reasonForm.get(0).reset();
                $('#mtv_id').val('');
                $('#mtv_estatus').val('activo');
                $('#mtv_nombre').val('');
                $('#desktop-motivo-modal-title').text('Nuevo motivo');
                $('#btn-guardar-motivo').text('Guardar motivo');
            }

            function resetModelForm() {
                $modelForm.get(0).reset();
                $('#mdl_id').val('');
                $('#mdl_estatus').val('activo');
                $('#mdl_brand_search').val('');
                $('#desktop-modelo-modal-title').text('Nuevo modelo');
                $('#btn-guardar-modelo').text('Guardar modelo');
                syncModelBrandUi();
            }

            function openNewForm() {
                if (currentType === 'marcas') {
                    resetBrandForm();
                    openModal($brandModal);
                    return;
                }

                if (currentType === 'lineas') {
                    resetLineForm();
                    openModal($lineModal);
                    return;
                }

                if (currentType === 'categorias') {
                    resetConceptForm();
                    openModal($conceptModal);
                    return;
                }

                if (currentType === 'unidades') {
                    resetUnitForm();
                    openModal($unitModal);
                    return;
                }

                if (currentType === 'descripciones') {
                    resetDescriptionForm();
                    openModal($descriptionModal);
                    return;
                }

                if (currentType === 'motivos') {
                    resetReasonForm();
                    openModal($reasonModal);
                    return;
                }

                if (currentType === 'modelos') {
                    resetModelForm();
                    openModal($modelModal);
                    return;
                }

                resetCatalogForm();
                openModal($catalogModal);
            }

            function fillCatalogForm(data) {
                resetCatalogForm();
                $('#desktop-catalogo-modal-title').text('Editar ' + (currentCatalogConfig().label || 'registro'));
                $('#btn-guardar-catalogo').text('Guardar cambios');
                $('#cat_id').val(data.id || '');
                $('#cat_nombre').val(data.nombre || '');
                $('#cat_codigo').val(data.codigo || '');
                $('#cat_estatus').val(data.estatus || 'activo');
                $('#cat_lna_id').val(data.lna_id || '');
                $('#cat_tipo_cantidad').val(data.tipo_cantidad || 'entero');
                $('#cat_es_predeterminada').prop('checked', Boolean(data.es_predeterminada));
            }

            function fillBrandForm(data) {
                resetBrandForm();
                $('#desktop-marca-modal-title').text('Editar marca');
                $('#btn-guardar-marca').text('Guardar cambios');
                $('#mrc_id').val(data.id || '');
                $('#mrc_nombre').val(data.nombre || '');
                $('#mrc_estatus').val(data.estatus || 'activo');
            }

            function fillLineForm(data) {
                resetLineForm();
                $('#desktop-linea-modal-title').text('Editar linea');
                $('#btn-guardar-linea').text('Guardar cambios');
                $('#lna_id').val(data.id || '');
                $('#lna_nombre').val(data.nombre || '');
                $('#lna_estatus').val(data.estatus || 'activo');
            }

            function fillConceptForm(data) {
                resetConceptForm();
                $('#desktop-concepto-modal-title').text('Editar concepto');
                $('#btn-guardar-concepto').text('Guardar cambios');
                $('#ctg_id').val(data.id || '');
                $('#ctg_nombre').val(data.nombre || '');
                $('#ctg_estatus').val(data.estatus || 'activo');
                $('#ctg_lna_id').val(data.lna_id || '');
            }

            function fillUnitForm(data) {
                resetUnitForm();
                $('#desktop-unidad-modal-title').text('Editar unidad');
                $('#btn-guardar-unidad').text('Guardar cambios');
                $('#umd_id').val(data.id || '');
                $('#umd_nombre').val(data.nombre || '');
                $('#umd_estatus').val(data.estatus || 'activo');
                $('#umd_tipo_cantidad').val(data.tipo_cantidad || 'entero');
                $('#umd_es_predeterminada').prop('checked', Boolean(data.es_predeterminada));
            }

            function fillDescriptionForm(data) {
                resetDescriptionForm();
                $('#desktop-descripcion-modal-title').text('Editar descripción');
                $('#btn-guardar-descripcion').text('Guardar cambios');
                $('#dsc_id').val(data.id || '');
                $('#dsc_nombre').val(data.nombre || '');
                $('#dsc_estatus').val(data.estatus || 'activo');
            }

            function fillReasonForm(data) {
                resetReasonForm();
                $('#desktop-motivo-modal-title').text('Editar motivo');
                $('#btn-guardar-motivo').text('Guardar cambios');
                $('#mtv_id').val(data.id || '');
                $('#mtv_nombre').val(data.nombre || '');
                $('#mtv_estatus').val(data.estatus || 'activo');
            }

            function fillModelForm(data) {
                resetModelForm();
                $('#desktop-modelo-modal-title').text('Editar modelo');
                $('#btn-guardar-modelo').text('Guardar cambios');
                $('#mdl_id').val(data.mdl_id || '');
                $('#mdl_nombre').val(data.mdl_nombre || '');
                $('#mdl_estatus').val(data.mdl_estatus || 'activo');
                const selected = Array.isArray(data.marca_ids) ? data.marca_ids.map(Number) : [];
                $('#desktop-modelo-form input[name="marca_ids[]"]').each(function () {
                    $(this).prop('checked', selected.includes(Number(this.value)));
                });
                syncModelBrandUi();
            }

            function syncModelBrandUi() {
                const term = String($('#mdl_brand_search').val() || '').trim().toUpperCase();
                let visibles = 0;
                let selected = 0;

                $('#mdl-marcas-chip-grid [data-brand-chip]').each(function () {
                    const $chip = $(this);
                    const match = term === '' || String($chip.data('search') || '').includes(term);
                    const checked = $chip.find('input').is(':checked');

                    $chip.toggleClass('is-hidden', !match);

                    if (match) {
                        visibles += 1;
                    }

                    if (checked) {
                        selected += 1;
                    }
                });

                $('#mdl-selection-count').text('(' + selected + ')');
                $('#mdl-marcas-empty')
                    .text(term !== '' ? 'No se encontraron marcas.' : 'No hay marcas disponibles.')
                    .toggleClass('is-visible', visibles === 0);
            }

            function renderDetail(title, fields) {
                $('#desktop-detail-title').text(title);
                $('#desktop-detail-grid').html(fields.map(function (field) {
                    return '<div class="desktop-field ' + (field.full ? 'desktop-field--full' : '') + '">' +
                        '<label>' + escapeHtml(field.label) + '</label>' +
                        '<input type="text" readonly value="' + escapeHtml(field.value) + '">' +
                        '</div>';
                }).join(''));
                openModal($detailModal);
            }

            function openConfirm(message, callback) {
                confirmAction = callback;
                $('#desktop-confirm-copy').text(message);
                openModal($confirmModal);
            }

            function loadRecord(id, mode) {
                const request = currentType === 'modelos'
                    ? $.getJSON(rutas.modeloShow(id))
                    : $.getJSON(rutas.baseShow(currentType, id));

                request.done(function (response) {
                    const data = response.data || {};

                    if (mode === 'edit') {
                        if (currentType === 'marcas') {
                            fillBrandForm(data);
                            openModal($brandModal);
                            return;
                        }

                        if (currentType === 'lineas') {
                            fillLineForm(data);
                            openModal($lineModal);
                            return;
                        }

                        if (currentType === 'categorias') {
                            fillConceptForm(data);
                            openModal($conceptModal);
                            return;
                        }

                        if (currentType === 'unidades') {
                            fillUnitForm(data);
                            openModal($unitModal);
                            return;
                        }

                        if (currentType === 'descripciones') {
                            fillDescriptionForm(data);
                            openModal($descriptionModal);
                            return;
                        }

                        if (currentType === 'motivos') {
                            fillReasonForm(data);
                            openModal($reasonModal);
                            return;
                        }

                        if (currentType === 'modelos') {
                            fillModelForm(data);
                            openModal($modelModal);
                        } else {
                            fillCatalogForm(data);
                            openModal($catalogModal);
                        }
                        return;
                    }

                    if (currentType === 'modelos') {
                        renderDetail('Detalle de modelo', [
                            { label: 'Modelo', value: data.mdl_nombre || '', full: true },
                            { label: 'Clave', value: data.mdl_clave || '-' },
                            { label: 'Estatus', value: data.mdl_estatus === 'activo' ? 'Activo' : 'Inactivo' },
                            { label: 'Marcas asociadas', value: data.marcas_texto || '-', full: true }
                        ]);
                        return;
                    }

                    const fields = [
                        { label: currentCatalogConfig().label || 'Registro', value: data.nombre || '', full: true },
                        { label: 'Clave', value: data.clave || '-' },
                        { label: 'Estatus', value: data.estatus === 'activo' ? 'Activo' : 'Inactivo' }
                    ];

                    if (currentType === 'categorias') {
                        fields.splice(1, 0, { label: 'Línea', value: data.linea || '-' });
                    }

                    if (currentType === 'unidades') {
                        fields.splice(1, 0, { label: 'Código', value: data.codigo || '-' });
                        fields.push({ label: 'Tipo de cantidad', value: data.tipo_cantidad === 'decimal' ? 'Decimal' : 'Entero' });
                        fields.push({ label: 'Predeterminada', value: data.es_predeterminada ? 'Sí' : 'No' });
                    }

                    renderDetail('Detalle de ' + (currentCatalogConfig().label || 'registro'), fields);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            }

            function toggleRecord(id, nextStatus) {
                const request = currentType === 'modelos'
                    ? $.ajax({ url: rutas.modeloEstatus(id), method: 'PATCH', data: { mdl_estatus: nextStatus } })
                    : $.ajax({ url: rutas.baseEstatus(currentType, id), method: 'PATCH', data: { estatus: nextStatus } });

                request.done(function (response) {
                    showFeedback('success', response.message || 'Estatus actualizado correctamente.');
                    reloadTable(false);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            }

            function deleteRecord(id, name) {
                openConfirm('Se eliminará "' + name + '". Esta acción no se puede deshacer.', function () {
                    const request = currentType === 'modelos'
                        ? $.ajax({ url: rutas.modeloDelete(id), method: 'DELETE' })
                        : $.ajax({ url: rutas.baseDelete(currentType, id), method: 'DELETE' });

                    request.done(function (response) {
                        showFeedback('success', response.message || 'Registro eliminado correctamente.');
                        reloadTable(true);
                    }).fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
                });
            }

            buildTable();
            syncTypeUI();

            $('[data-catalog-type]').on('click', function () {
                const next = String($(this).data('catalogType') || '');
                if (!next || next === currentType) return;
                currentType = next;
                syncTypeUI();
                buildTable();
            });

            $('#btn-nuevo-base').on('click', openNewForm);
            $('#btn-recargar-base').on('click', function () { reloadTable(true); });
            $('#base-estatus').on('change', function () { reloadTable(true); });
            $('#base-search').on('input', function () { reloadTable(true); });
            $('#base-length').on('change', function () {
                if (!baseTable) return;
                baseTable.page.len(Number(this.value)).draw();
            });
            $('#desktop-catalogos-base-pagination').on('click', '.desktop-pager__btn', function () {
                if ($(this).is(':disabled') || !baseTable) return;
                baseTable.page($(this).data('page')).draw('page');
            });

            $catalogForm.on('submit', function (event) {
                event.preventDefault();
                const recordId = $('#cat_id').val();
                const request = $.ajax({
                    url: recordId ? rutas.baseUpdate(currentType, recordId) : rutas.baseStore(currentType),
                    method: recordId ? 'PUT' : 'POST',
                    data: (function () {
                        const formData = $catalogForm.serializeArray();
                        const hasStatus = formData.some(function (item) {
                            return item.name === 'estatus';
                        });

                        if (!hasStatus) {
                            formData.push({
                                name: 'estatus',
                                value: $('#cat_estatus').val() || 'activo'
                            });
                        }

                        return $.param(formData);
                    })()
                });

                request.done(function (response) {
                    closeModal($catalogModal);
                    showFeedback('success', response.message || 'Registro guardado correctamente.');
                    reloadTable(true);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });

            $brandForm.on('submit', function (event) {
                event.preventDefault();
                const recordId = $('#mrc_id').val();
                const request = $.ajax({
                    url: recordId ? rutas.baseUpdate('marcas', recordId) : rutas.baseStore('marcas'),
                    method: recordId ? 'PUT' : 'POST',
                    data: $brandForm.serialize()
                });

                request.done(function (response) {
                    closeModal($brandModal);
                    showFeedback('success', response.message || 'Marca guardada correctamente.');
                    reloadTable(true);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });

            $('#mrc_nombre').on('input', function () {
                this.value = String(this.value || '').toUpperCase();
            });
            $('#lna_nombre').on('input', function () {
                this.value = String(this.value || '').toUpperCase();
            });
            $('#ctg_nombre').on('input', function () {
                this.value = String(this.value || '').toUpperCase();
            });
            $('#umd_nombre').on('input', function () {
                this.value = String(this.value || '').toUpperCase();
            });
            $('#dsc_nombre').on('input', function () {
                this.value = String(this.value || '').toUpperCase();
            });
            $('#mtv_nombre').on('input', function () {
                this.value = String(this.value || '').toUpperCase();
            });

            $('#mdl_nombre').on('input', function () {
                this.value = String(this.value || '').toUpperCase();
            });
            $('#cat_nombre').on('input', function () {
                const formConfig = currentCatalogConfig().form || {};
                if (!formConfig.uppercaseName) {
                    return;
                }

                this.value = String(this.value || '').toUpperCase();
            });
            $('#mdl_brand_search').on('input', syncModelBrandUi);
            $('#desktop-modelo-form').on('change', 'input[name="marca_ids[]"]', syncModelBrandUi);

            $modelForm.on('submit', function (event) {
                event.preventDefault();
                const recordId = $('#mdl_id').val();
                const request = $.ajax({
                    url: recordId ? rutas.modeloUpdate(recordId) : rutas.modeloStore,
                    method: recordId ? 'PUT' : 'POST',
                    data: $modelForm.serialize()
                });

                request.done(function (response) {
                    closeModal($modelModal);
                    showFeedback('success', response.message || 'Modelo guardado correctamente.');
                    reloadTable(true);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });

            $lineForm.on('submit', function (event) {
                event.preventDefault();
                const recordId = $('#lna_id').val();
                const request = $.ajax({
                    url: recordId ? rutas.baseUpdate('lineas', recordId) : rutas.baseStore('lineas'),
                    method: recordId ? 'PUT' : 'POST',
                    data: $lineForm.serialize()
                });

                request.done(function (response) {
                    closeModal($lineModal);
                    showFeedback('success', response.message || 'Linea guardada correctamente.');
                    reloadTable(true);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });

            $conceptForm.on('submit', function (event) {
                event.preventDefault();
                const recordId = $('#ctg_id').val();
                const request = $.ajax({
                    url: recordId ? rutas.baseUpdate('categorias', recordId) : rutas.baseStore('categorias'),
                    method: recordId ? 'PUT' : 'POST',
                    data: $conceptForm.serialize()
                });

                request.done(function (response) {
                    closeModal($conceptModal);
                    showFeedback('success', response.message || 'Concepto guardado correctamente.');
                    reloadTable(true);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });

            $unitForm.on('submit', function (event) {
                event.preventDefault();
                const recordId = $('#umd_id').val();
                const request = $.ajax({
                    url: recordId ? rutas.baseUpdate('unidades', recordId) : rutas.baseStore('unidades'),
                    method: recordId ? 'PUT' : 'POST',
                    data: $unitForm.serialize()
                });

                request.done(function (response) {
                    closeModal($unitModal);
                    showFeedback('success', response.message || 'Unidad guardada correctamente.');
                    reloadTable(true);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });

            $descriptionForm.on('submit', function (event) {
                event.preventDefault();
                const recordId = $('#dsc_id').val();
                const request = $.ajax({
                    url: recordId ? rutas.baseUpdate('descripciones', recordId) : rutas.baseStore('descripciones'),
                    method: recordId ? 'PUT' : 'POST',
                    data: $descriptionForm.serialize()
                });

                request.done(function (response) {
                    closeModal($descriptionModal);
                    showFeedback('success', response.message || 'Descripción guardada correctamente.');
                    reloadTable(true);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });

            $reasonForm.on('submit', function (event) {
                event.preventDefault();
                const recordId = $('#mtv_id').val();
                const request = $.ajax({
                    url: recordId ? rutas.baseUpdate('motivos', recordId) : rutas.baseStore('motivos'),
                    method: recordId ? 'PUT' : 'POST',
                    data: $reasonForm.serialize()
                });

                request.done(function (response) {
                    closeModal($reasonModal);
                    showFeedback('success', response.message || 'Motivo guardado correctamente.');
                    reloadTable(true);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });

            $(document).on('click', '.btn-edit-record', function () {
                loadRecord($(this).data('id'), 'edit');
            });

            $(document).on('click', '.btn-view-record', function () {
                loadRecord($(this).data('id'), 'view');
            });

            $(document).on('click', '.btn-toggle-record', function () {
                toggleRecord($(this).data('id'), $(this).data('next'));
            });

            $(document).on('click', '.btn-delete-record', function () {
                deleteRecord($(this).data('id'), $(this).data('name'));
            });

            $('[data-close-catalogo-modal]').on('click', function () { closeModal($catalogModal); });
            $('[data-close-marca-modal]').on('click', function () { closeModal($brandModal); });
            $('[data-close-linea-modal]').on('click', function () { closeModal($lineModal); });
            $('[data-close-concepto-modal]').on('click', function () { closeModal($conceptModal); });
            $('[data-close-unidad-modal]').on('click', function () { closeModal($unitModal); });
            $('[data-close-descripcion-modal]').on('click', function () { closeModal($descriptionModal); });
            $('[data-close-motivo-modal]').on('click', function () { closeModal($reasonModal); });
            $('[data-close-modelo-modal]').on('click', function () { closeModal($modelModal); });
            $('[data-close-detail-modal]').on('click', function () { closeModal($detailModal); });
            $('[data-close-confirm-modal]').on('click', function () { closeModal($confirmModal); });
            $('#desktop-confirm-accept').on('click', function () {
                const action = confirmAction;
                confirmAction = null;
                closeModal($confirmModal);
                if (typeof action === 'function') action();
            });

            $catalogModal.on('click', function (event) { if (event.target === this) closeModal($catalogModal); });
            $brandModal.on('click', function (event) { if (event.target === this) closeModal($brandModal); });
            $lineModal.on('click', function (event) { if (event.target === this) closeModal($lineModal); });
            $conceptModal.on('click', function (event) { if (event.target === this) closeModal($conceptModal); });
            $unitModal.on('click', function (event) { if (event.target === this) closeModal($unitModal); });
            $descriptionModal.on('click', function (event) { if (event.target === this) closeModal($descriptionModal); });
            $reasonModal.on('click', function (event) { if (event.target === this) closeModal($reasonModal); });
            $modelModal.on('click', function (event) { if (event.target === this) closeModal($modelModal); });
            $detailModal.on('click', function (event) { if (event.target === this) closeModal($detailModal); });
            $confirmModal.on('click', function (event) { if (event.target === this) closeModal($confirmModal); });
        })();
    </script>
@endpush
