<style>
/* ── Modal shell ─────────────────────────────────────────── */
#modal-cliente .modal-dialog { max-width: min(1020px, 96vw); }
#modal-cliente .modal-content {
    border-radius: 20px;
    border: none;
    overflow: hidden;
    box-shadow: 0 24px 64px rgba(10,37,64,.18), 0 4px 16px rgba(10,37,64,.08);
}

/* ── Header ──────────────────────────────────────────────── */
.cli-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.1rem 1.4rem 1rem;
    background: #fff;
    border-bottom: 1px solid #eef0f6;
}
.cli-head__left { display: flex; align-items: center; gap: .9rem; }
.cli-head__avatar {
    width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
    background: linear-gradient(135deg, #635bff 0%, #8b5cf6 100%);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.2rem;
    box-shadow: 0 4px 12px rgba(99,91,255,.35);
}
.cli-head__title { margin: 0; font-size: 1.05rem; font-weight: 800; color: #0a2540; letter-spacing: -.01em; }
.cli-head__sub   { margin: 0; font-size: .82rem; color: #7a8ba5; }
.cli-head__close {
    width: 36px; height: 36px; border: 0; border-radius: 10px;
    background: #f1f4f9; color: #64748b; font-size: 1.3rem; line-height: 1;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .15s;
}
.cli-head__close:hover { background: #e2e8f0; color: #1e293b; }

/* ── Tabs ────────────────────────────────────────────────── */
.cli-tabs {
    display: flex; align-items: center; gap: 0;
    padding: 0 1.4rem;
    background: #fff;
    border-bottom: 2px solid #eef0f6;
}
.cli-tab {
    display: inline-flex; align-items: center; gap: .4rem;
    border: 0; background: transparent; cursor: pointer;
    color: #94a3b8; font-weight: 700; font-size: .82rem; letter-spacing: .02em;
    padding: .75rem .95rem .7rem;
    border-bottom: 2px solid transparent; margin-bottom: -2px;
    transition: color .18s, border-color .18s;
    white-space: nowrap;
}
.cli-tab i { font-size: .95rem; }
.cli-tab:hover { color: #475569; }
.cli-tab.active { color: #635bff; border-bottom-color: #635bff; }
.cli-tab__badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 18px; height: 18px; border-radius: 50%; font-size: .68rem;
    font-weight: 800; background: #635bff; color: #fff;
}

/* ── Body ────────────────────────────────────────────────── */
.cli-body {
    background: #f8fafc;
    padding: 1.1rem 1.4rem;
    max-height: 60vh; overflow-y: auto;
}

/* ── Preview card ────────────────────────────────────────── */
.cli-preview {
    display: flex; align-items: center; gap: .8rem;
    background: linear-gradient(135deg, #635bff 0%, #8b5cf6 100%);
    border-radius: 14px; padding: .85rem 1.1rem;
    margin-bottom: 1rem;
    box-shadow: 0 4px 16px rgba(99,91,255,.22);
}
.cli-preview__initials {
    width: 46px; height: 46px; border-radius: 12px; flex-shrink: 0;
    background: rgba(255,255,255,.2); border: 2px solid rgba(255,255,255,.35);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; font-weight: 800; color: #fff; letter-spacing: -.02em;
}
.cli-preview__label { font-size: .73rem; color: rgba(255,255,255,.7); text-transform: uppercase; letter-spacing: .07em; font-weight: 700; }
.cli-preview__name  { font-size: 1.05rem; color: #fff; font-weight: 800; }
.cli-preview__pill  {
    margin-left: auto;
    background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.35);
    color: #fff; border-radius: 30px; padding: .18rem .75rem;
    font-size: .75rem; font-weight: 700;
}

/* ── Sections ────────────────────────────────────────────── */
.cli-section { display: none; }
.cli-section.active { display: block; }

.cli-card {
    background: #fff; border-radius: 14px; padding: 1rem 1.1rem;
    border: 1px solid #eef0f6;
    box-shadow: 0 1px 4px rgba(10,37,64,.04);
    margin-bottom: .85rem;
}
.cli-card__header {
    display: flex; align-items: center; gap: .55rem;
    margin-bottom: .85rem; padding-bottom: .65rem;
    border-bottom: 1px solid #f0f3f9;
}
.cli-card__icon {
    width: 30px; height: 30px; border-radius: 8px;
    background: linear-gradient(135deg, #635bff15, #8b5cf615);
    display: flex; align-items: center; justify-content: center;
    color: #635bff; font-size: .95rem;
}
.cli-card__title {
    font-size: .78rem; font-weight: 800; letter-spacing: .06em;
    color: #64748b; text-transform: uppercase; margin: 0;
}

/* ── Grid explícito (consistente módulo/POS) ─────────────── */
.cli-body .row.g-2,
.cli-body .row.g-3 {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    column-gap: 1rem;
    row-gap: .7rem;
    margin: 0;
}
.cli-body .col-md-4,
.cli-body .col-md-6,
.cli-body .col-md-8,
.cli-body .col-md-12 {
    padding: 0;
    margin: 0;
    width: 100% !important;
    max-width: none;
    min-width: 0;
    display: block;
}
.cli-body .col-md-4  { grid-column: span 4; }
.cli-body .col-md-6  { grid-column: span 6; }
.cli-body .col-md-8  { grid-column: span 8; }
.cli-body .col-md-12 { grid-column: span 12; }
@media (max-width: 980px) {
    .cli-body .row.g-2,
    .cli-body .row.g-3 {
        grid-template-columns: 1fr;
    }
    .cli-body .col-md-4,
    .cli-body .col-md-6,
    .cli-body .col-md-8,
    .cli-body .col-md-12 {
        grid-column: 1 / -1;
    }
}

/* ── Form fields ─────────────────────────────────────────── */
.cli-field { margin-bottom: 0; }
.cli-field .form-label {
    font-size: .8rem; font-weight: 700; color: #475569;
    margin-bottom: .35rem; display: block;
}
.cli-field .form-label span.req { color: #ef4444; margin-left: 2px; }

.cli-input-wrap { position: relative; }
.cli-input-wrap { width: 100%; min-width: 0; }
.cli-input-wrap .cli-icon {
    position: absolute; left: .7rem; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: .95rem; pointer-events: none; z-index: 1;
}
.cli-input-wrap textarea ~ .cli-icon { top: .75rem; transform: none; }

.cli-input-wrap .form-control,
.cli-input-wrap .form-select {
    padding-left: 2.15rem !important;
}

.cli-body .form-control,
.cli-body .form-select {
    width: 100% !important;
    min-width: 0;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    height: 42px;
    font-size: .88rem;
    color: #1e293b;
    background: #fff;
    box-shadow: 0 1px 3px rgba(10,37,64,.04);
    transition: border-color .18s, box-shadow .18s;
}
.cli-body .form-control:focus,
.cli-body .form-select:focus {
    border-color: #635bff;
    box-shadow: 0 0 0 3px rgba(99,91,255,.12);
    outline: none;
}
.cli-body textarea.form-control { height: auto; min-height: 72px; }

/* Status select colored */
#cli_estatus option[value="activo"]   { color: #16a34a; }
#cli_estatus option[value="inactivo"] { color: #94a3b8; }

.cli-status-badge {
    display: inline-flex; align-items: center; gap: .35rem;
    font-size: .8rem; font-weight: 700;
}
.cli-status-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #22c55e; display: inline-block;
}

/* ── Footer ──────────────────────────────────────────────── */
.cli-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: .85rem 1.4rem;
    background: #fff; border-top: 1px solid #eef0f6;
    gap: .5rem;
}
.cli-footer__hint { font-size: .78rem; color: #94a3b8; }
.cli-footer__hint span { color: #ef4444; }
.cli-footer__actions { display: flex; gap: .5rem; }

.cli-btn-cancel {
    height: 40px; padding: 0 1.1rem; border-radius: 10px;
    border: 1.5px solid #e2e8f0; background: #fff; color: #64748b;
    font-weight: 700; font-size: .88rem; cursor: pointer;
    transition: background .15s, border-color .15s;
}
.cli-btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }

.cli-btn-save {
    height: 40px; padding: 0 1.5rem; border-radius: 10px;
    background: linear-gradient(135deg, #635bff 0%, #8b5cf6 100%);
    border: none; color: #fff; font-weight: 800; font-size: .88rem;
    cursor: pointer; min-width: 160px;
    box-shadow: 0 4px 12px rgba(99,91,255,.35);
    display: inline-flex; align-items: center; gap: .4rem;
    transition: opacity .18s, box-shadow .18s;
}
.cli-btn-save:hover { opacity: .92; box-shadow: 0 6px 18px rgba(99,91,255,.4); }

/* ── Embedded overrides ──────────────────────────────────── */
.cliente-embedded.cli-form-wrap {
    width: min(1280px, 96vw); background: #fff;
    border-radius: 18px; border: 1px solid #e2e8f0; overflow: hidden;
}
</style>

@php($embedded = $embedded ?? false)

@if(!$embedded)
<div class="modal fade" id="modal-cliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
@endif

<form id="form-cliente"
      class="{{ $embedded ? 'cliente-embedded cli-form-wrap' : '' }}"
      @if($embedded) style="display:block;" @endif>

    {{-- ── HEADER ──────────────────────────────────────────── --}}
    <div class="cli-head">
        <div class="cli-head__left">
            <div class="cli-head__avatar"><i class="ti tabler-user-star"></i></div>
            <div>
                <h5 class="cli-head__title" id="modal-title">Nuevo cliente</h5>
                <p class="cli-head__sub">Completa los datos del cliente</p>
            </div>
        </div>
        <button type="button" class="cli-head__close"
            @if($embedded) @click="cerrarModalClientes()" @else data-bs-dismiss="modal" @endif
            aria-label="Cerrar">
            <i class="ti tabler-x" style="font-size:1rem;"></i>
        </button>
    </div>

    {{-- ── TABS ─────────────────────────────────────────────── --}}
    <div class="cli-tabs" id="cli-tabs">
        <button type="button" class="cli-tab active" data-tab="tab-persona">
            <i class="ti tabler-user"></i> Persona
        </button>
        <button type="button" class="cli-tab" data-tab="tab-contacto">
            <i class="ti tabler-phone"></i> Contacto
        </button>
        <button type="button" class="cli-tab" data-tab="tab-documentos">
            <i class="ti tabler-id-badge-2"></i> Documentos
        </button>
        <button type="button" class="cli-tab" data-tab="tab-direccion">
            <i class="ti tabler-map-pin"></i> Dirección
        </button>
    </div>

    {{-- ── BODY ────────────────────────────────────────────── --}}
    <div class="cli-body">
        <input type="hidden" id="cli_id" name="cli_id">

        {{-- Preview card --}}
        <div class="cli-preview">
            <div class="cli-preview__initials" id="cli-preview-initials">?</div>
            <div>
                <div class="cli-preview__label">Cliente</div>
                <div class="cli-preview__name" id="cliente-preview-nombre">Vista previa del nombre</div>
            </div>
            <div class="cli-preview__pill" id="cli-preview-estatus">Activo</div>
        </div>

        {{-- ── TAB 1: PERSONA ──────────────────────────────── --}}
        <div class="cli-section active" id="tab-persona">
            <div class="cli-card">
                <div class="cli-card__header">
                    <div class="cli-card__icon"><i class="ti tabler-user-circle"></i></div>
                    <h6 class="cli-card__title">Datos personales</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 cli-field">
                        <label class="form-label">Nombre <span class="req">*</span></label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-user cli-icon"></i>
                            <input class="form-control" name="cli_nombre" id="cli_nombre" maxlength="120" required placeholder="Nombre(s)">
                        </div>
                    </div>
                    <div class="col-md-6 cli-field">
                        <label class="form-label">Apellido paterno</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-user cli-icon"></i>
                            <input class="form-control" name="cli_apellido_paterno" id="cli_apellido_paterno" maxlength="120" placeholder="Apellido paterno">
                        </div>
                    </div>
                    <div class="col-md-6 cli-field">
                        <label class="form-label">Apellido materno</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-user cli-icon"></i>
                            <input class="form-control" name="cli_apellido_materno" id="cli_apellido_materno" maxlength="120" placeholder="Apellido materno">
                        </div>
                    </div>
                    <div class="col-md-6 cli-field">
                        <label class="form-label">Empresa / Razón social</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-building cli-icon"></i>
                            <input class="form-control" name="cli_razon_social" id="cli_razon_social" maxlength="180" placeholder="Opcional">
                        </div>
                    </div>
                    <div class="col-md-6 cli-field">
                        <label class="form-label">Fecha de nacimiento</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-calendar cli-icon"></i>
                            <input type="date" class="form-control" name="cli_fecha_nacimiento" id="cli_fecha_nacimiento">
                        </div>
                    </div>
                    <div class="col-md-6 cli-field">
                        <label class="form-label">Estatus <span class="req">*</span></label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-toggle-right cli-icon"></i>
                            <select class="form-select" name="cli_estatus" id="cli_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── TAB 2: CONTACTO ─────────────────────────────── --}}
        <div class="cli-section" id="tab-contacto">
            <div class="cli-card">
                <div class="cli-card__header">
                    <div class="cli-card__icon"><i class="ti tabler-address-book"></i></div>
                    <h6 class="cli-card__title">Información de contacto</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 cli-field">
                        <label class="form-label">Teléfono</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-phone cli-icon"></i>
                            <input class="form-control" name="cli_telefono" id="cli_telefono" maxlength="25" placeholder="10 dígitos">
                        </div>
                    </div>
                    <div class="col-md-6 cli-field">
                        <label class="form-label">WhatsApp</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-brand-whatsapp cli-icon"></i>
                            <input class="form-control" name="cli_whatsapp" id="cli_whatsapp" maxlength="25" placeholder="10 dígitos">
                        </div>
                    </div>
                    <div class="col-md-12 cli-field">
                        <label class="form-label">Correo electrónico</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-mail cli-icon"></i>
                            <input type="email" class="form-control" name="cli_email" id="cli_email" maxlength="140" placeholder="ejemplo@correo.com">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── TAB 3: DOCUMENTOS ───────────────────────────── --}}
        <div class="cli-section" id="tab-documentos">
            <div class="cli-card">
                <div class="cli-card__header">
                    <div class="cli-card__icon"><i class="ti tabler-file-certificate"></i></div>
                    <h6 class="cli-card__title">Documentos de identificación</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 cli-field">
                        <label class="form-label">Tipo de identificación</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-id-badge-2 cli-icon"></i>
                            <select class="form-select" id="doc_tipo" name="doc_tipo">
                                <option value="">Selecciona tipo</option>
                                <option value="rfc">RFC</option>
                                <option value="curp">CURP</option>
                                <option value="ine">INE</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 cli-field">
                        <label class="form-label">Valor del documento</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-hash cli-icon"></i>
                            <input class="form-control text-uppercase" id="doc_valor" name="doc_valor" maxlength="30" placeholder="Captura el valor">
                        </div>
                    </div>
                </div>
                <input type="hidden" name="cli_rfc"  id="cli_rfc">
                <input type="hidden" name="cli_curp" id="cli_curp">
                <input type="hidden" name="cli_ine"  id="cli_ine">
            </div>
        </div>

        {{-- ── TAB 4: DIRECCIÓN ────────────────────────────── --}}
        <div class="cli-section" id="tab-direccion">
            <div class="cli-card">
                <div class="cli-card__header">
                    <div class="cli-card__icon"><i class="ti tabler-map-pin"></i></div>
                    <h6 class="cli-card__title">Dirección de entrega / facturación</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-4 cli-field">
                        <label class="form-label">Código postal</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-mailbox cli-icon"></i>
                            <input class="form-control" name="cli_cp" id="cli_cp" maxlength="10" placeholder="C.P.">
                        </div>
                    </div>
                    <div class="col-md-8 cli-field">
                        <label class="form-label">Colonia / Asentamiento</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-map cli-icon"></i>
                            <select class="form-select" name="cli_colonia" id="cli_colonia">
                                <option value="">Selecciona</option>
                            </select>
                            <input type="hidden" name="cli_tipo_asentamiento" id="cli_tipo_asentamiento">
                        </div>
                    </div>
                    <div class="col-md-6 cli-field">
                        <label class="form-label">Municipio / Delegación</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-building-community cli-icon"></i>
                            <input class="form-control" name="cli_municipio" id="cli_municipio" maxlength="120" placeholder="Municipio">
                        </div>
                    </div>
                    <div class="col-md-6 cli-field">
                        <label class="form-label">Estado</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-map-2 cli-icon"></i>
                            <input class="form-control" name="cli_estado" id="cli_estado" maxlength="120" placeholder="Estado">
                        </div>
                    </div>
                    <div class="col-md-6 cli-field">
                        <label class="form-label">Ciudad</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-building-skyscraper cli-icon"></i>
                            <input class="form-control" name="cli_ciudad" id="cli_ciudad" maxlength="120" placeholder="Ciudad">
                        </div>
                    </div>
                    <div class="col-md-6 cli-field">
                        <label class="form-label">Calle</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-road cli-icon"></i>
                            <input class="form-control" name="cli_calle" id="cli_calle" maxlength="180" placeholder="Calle">
                        </div>
                    </div>
                    <div class="col-md-6 cli-field">
                        <label class="form-label">Núm. exterior</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-home cli-icon"></i>
                            <input class="form-control" name="cli_num_ext" id="cli_num_ext" maxlength="30" placeholder="Ext.">
                        </div>
                    </div>
                    <div class="col-md-6 cli-field">
                        <label class="form-label">Núm. interior</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-door cli-icon"></i>
                            <input class="form-control" name="cli_num_int" id="cli_num_int" maxlength="30" placeholder="Int. (opcional)">
                        </div>
                    </div>
                    <div class="col-md-12 cli-field">
                        <label class="form-label">Referencias</label>
                        <div class="cli-input-wrap">
                            <i class="ti tabler-notes cli-icon" style="top:.75rem;transform:none;"></i>
                            <textarea class="form-control" rows="2" name="cli_referencias" id="cli_referencias" placeholder="Entre calles, señas particulares..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /cli-body --}}

    {{-- ── FOOTER ──────────────────────────────────────────── --}}
    <div class="cli-footer">
        <span class="cli-footer__hint"><span>*</span> Campos obligatorios</span>
        <div class="cli-footer__actions">
            <button type="button" class="cli-btn-cancel"
                @if($embedded) @click="cerrarModalClientes()" @else data-bs-dismiss="modal" @endif>
                Cancelar
            </button>
            <button type="submit" class="cli-btn-save">
                <i class="ti tabler-device-floppy"></i>
                Guardar cliente
            </button>
        </div>
    </div>

</form>

@if(!$embedded)
        </div>
    </div>
</div>
@endif

<script>
(function () {
    /* ── Tab switching ───────────────────────────────────── */
    document.querySelectorAll('#cli-tabs .cli-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#cli-tabs .cli-tab').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.cli-section').forEach(function (s) { s.classList.remove('active'); });
            btn.classList.add('active');
            var target = btn.getAttribute('data-tab');
            var section = document.getElementById(target);
            if (section) section.classList.add('active');
        });
    });

    /* ── Live preview update ────────────────────────────── */
    function updatePreview() {
        var nombre   = (document.getElementById('cli_nombre')?.value || '').trim();
        var paterno  = (document.getElementById('cli_apellido_paterno')?.value || '').trim();
        var materno  = (document.getElementById('cli_apellido_materno')?.value || '').trim();
        var estatus  = (document.getElementById('cli_estatus')?.value || 'activo');

        var full = [nombre, paterno, materno].filter(Boolean).join(' ') || 'Vista previa del nombre';
        var initials = [nombre, paterno].filter(Boolean).map(function (p) { return p[0].toUpperCase(); }).join('') || '?';

        var previewNombre   = document.getElementById('cliente-preview-nombre');
        var previewInitials = document.getElementById('cli-preview-initials');
        var previewEstatus  = document.getElementById('cli-preview-estatus');

        if (previewNombre)   previewNombre.textContent   = full;
        if (previewInitials) previewInitials.textContent = initials;
        if (previewEstatus)  previewEstatus.textContent  = estatus.charAt(0).toUpperCase() + estatus.slice(1);
    }

    ['cli_nombre','cli_apellido_paterno','cli_apellido_materno','cli_estatus'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', updatePreview);
    });

    /* ── Reset tabs on modal open ───────────────────────── */
    var modalEl = document.getElementById('modal-cliente');
    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function () {
            document.querySelectorAll('#cli-tabs .cli-tab').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.cli-section').forEach(function (s) { s.classList.remove('active'); });
            var firstTab = document.querySelector('#cli-tabs .cli-tab');
            var firstSec = document.getElementById('tab-persona');
            if (firstTab) firstTab.classList.add('active');
            if (firstSec) firstSec.classList.add('active');
        });
    }
})();
</script>
