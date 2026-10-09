/* Facturación usa los controles y mensajes del layout desktop compartido. */
window.Facturacion = (() => {
    const esc = value => String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#39;');
    const money = value => '$' + Number(value || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const round = value => Math.round((Number(value) + Number.EPSILON) * 100) / 100;
    const signed = value => (value > 0 ? '+' : value < 0 ? '−' : '') + money(Math.abs(value));
    const qty = value => Number(value).toLocaleString('es-MX', { maximumFractionDigits: 2 });
    const ICON_PDF = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/></svg>';
    const ICON_DESCARGA = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>';
    const ICON_X = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';

    async function api(url, method = 'GET', data, signal) {
        const response = await fetch(url, {
            method, signal, headers: {
                Accept: 'application/json', 'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }, ...(data === undefined ? {} : { body: JSON.stringify(data) })
        });
        let result;
        try { result = await response.json(); } catch (_) { throw new Error('La sesión expiró o el servidor no respondió. Recarga la página.'); }
        if (!response.ok) {
            const error = new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No fue posible completar la operación.');
            error.errors = result.errors || {};
            error.status = response.status;
            throw error;
        }
        return result;
    }

    const SITUACION = {
        requiere_ajuste: ['Requiere ajuste', 'fac-pill--warning'],
        lista: ['Lista para facturar', 'desktop-pill--brand'],
        ajustada: ['Ajuste guardado', 'desktop-pill--brand'],
        simulada: ['Factura simulada', 'fac-pill--success'],
        revision: ['No facturable', 'desktop-pill--neutral'],
    };

    function listado(config) {
        const tbody = document.querySelector('#facturas-tabla tbody');
        const info = document.getElementById('facturas-info');
        const pager = document.getElementById('facturas-paginacion');
        const form = document.getElementById('facturas-filtros');
        const pivot = document.getElementById('facturas-situaciones');
        let rows = [], page = 1, controller, emitting = false, timer;

        // Los filtros viven en la URL: al volver de un ticket se conserva la misma vista.
        const inicial = new URLSearchParams(window.location.search);
        ['buscar', 'estado', 'desde', 'hasta'].forEach(name => { if (form.elements[name]) form.elements[name].value = inicial.get(name) || ''; });
        marcarPivot(form.elements.estado.value);

        function marcarPivot(estado) {
            pivot.querySelectorAll('[data-situacion]').forEach(button => {
                const activo = button.dataset.situacion === estado;
                button.classList.toggle('desktop-btn--active', activo);
                button.setAttribute('aria-selected', activo ? 'true' : 'false');
            });
        }
        function acciones(row) {
            const ver = (texto, primario) => `<a class="desktop-btn desktop-btn--${primario ? 'primary' : 'default'}" href="${config.base}/${row.id}">${texto}</a>`;
            const facturar = habilitado => config.emitir ? `<button type="button" class="desktop-btn desktop-btn--${habilitado ? 'primary' : 'default'}" data-emitir="${row.id}" ${habilitado ? '' : 'disabled aria-disabled="true"'}>Facturar</button>` : '';
            switch (row.situacion) {
                case 'simulada': return `<a class="desktop-btn desktop-btn--default" href="${config.base}/${row.id}/pdf" target="_blank" rel="noopener" aria-label="Ver PDF (se abre en otra pestaña)">${ICON_PDF}Ver PDF</a>`
                    + `<a class="desktop-btn desktop-btn--default" href="${config.base}/${row.id}/pdf?descargar=1" download title="Descargar PDF ${esc(row.folio_factura)}" aria-label="Descargar PDF ${esc(row.folio_factura)}">${ICON_DESCARGA}Descargar PDF</a>`
                    + ver('Ver factura');
                case 'revision': return facturar(false) + ver('Ver ticket');
                case 'requiere_ajuste': return (config.ajustar ? ver('Ajustar', true) : ver('Ver ticket')) + facturar(false);
                case 'ajustada': return facturar(true) + ver(config.ajustar ? 'Ajustar' : 'Ver ticket');
                default: return facturar(true) + ver('Ver ticket', !config.emitir);
            }
        }
        function nota(row) {
            switch (row.situacion) {
                case 'simulada': return `<span class="fac-note fac-note--success">Folio ${esc(row.folio_factura)}</span>` + (row.motivo ? `<span class="fac-note fac-note--warning">Después de facturar: ${esc(row.motivo)}</span>` : '');
                case 'revision': return `<span class="fac-note fac-note--danger">${esc(row.motivo)}</span>`;
                case 'requiere_ajuste': return row.sin_almacen
                    ? '<span class="fac-note fac-note--warning">Falta habilitar un almacén para facturar (Administración → Almacenes).</span>'
                    : '<span class="fac-note fac-note--warning">Venta de dos almacenes: guarda un ajuste para poder facturar.</span>';
                case 'ajustada': return '<span class="fac-note">Ajuste guardado; ya puede facturarse.</span>';
                default: return '<span class="fac-note">Un solo almacén: se factura directo.</span>';
            }
        }
        function totalFacturar(row) {
            if (row.total_ajustado !== null) {
                const diferencia = Number(row.diferencia || 0);
                return `<strong>${money(row.total_ajustado)}</strong>` + (diferencia ? `<span class="fac-note fac-note--warning">${signed(diferencia)} vs. original</span>` : '<span class="fac-note">Igual al original</span>');
            }
            if (row.situacion === 'lista') return `<strong>${money(row.total)}</strong>`;
            return `<span class="desktop-list__meta">${row.situacion === 'requiere_ajuste' ? 'Pendiente de ajuste' : '—'}</span>`;
        }
        function fila(row) {
            const [etiqueta, tono] = SITUACION[row.situacion] || SITUACION.lista;
            return `<tr>
                <td><span class="desktop-list__name">${esc(row.folio)}</span><span class="desktop-list__meta">${esc(row.fecha || '—')}</span></td>
                <td>${esc(row.cliente)}</td>
                <td><div class="desktop-pill-list">${(row.almacenes || []).map(nombre => `<span class="desktop-pill desktop-pill--neutral">${esc(nombre)}</span>`).join('')}</div></td>
                <td class="fac-num">${money(row.total)}</td>
                <td class="fac-num">${totalFacturar(row)}</td>
                <td><span class="desktop-pill ${tono}">${etiqueta}</span>${nota(row)}</td>
                <td><div class="fac-actions">${acciones(row)}</div></td>
            </tr>`;
        }
        async function cargar(target = 1) {
            controller?.abort(); controller = new AbortController();
            const params = new URLSearchParams();
            new FormData(form).forEach((value, key) => { if (String(value).trim() !== '') params.set(key, String(value).trim()); });
            if (target > 1) params.set('page', target);
            window.history.replaceState(null, '', `${window.location.pathname}${params.size ? '?' + params : ''}`);
            info.textContent = 'Consultando ventas...';
            if (!rows.length) tbody.innerHTML = '<tr><td colspan="7" class="desktop-list__empty">Cargando ventas...</td></tr>';
            try {
                const data = await api(`${config.base}/data?${params}`, 'GET', undefined, controller.signal);
                page = data.current_page; rows = data.data;
                Object.entries(data.resumen || {}).forEach(([clave, total]) => {
                    const badge = pivot.querySelector(`[data-count="${clave}"]`); if (badge) badge.textContent = total;
                });
                tbody.innerHTML = rows.length ? rows.map(fila).join('')
                    : '<tr><td colspan="7" class="desktop-list__empty">No hay ventas con estos filtros.</td></tr>';
                info.textContent = data.total ? `Mostrando ${data.from} a ${data.to} de ${data.total} ventas` : 'Sin resultados';
                pager.innerHTML = data.last_page > 1 ? `<button class="desktop-pager__btn" data-page="${page - 1}" ${page <= 1 ? 'disabled' : ''} aria-label="Página anterior">‹</button><span>${page} / ${data.last_page}</span><button class="desktop-pager__btn" data-page="${page + 1}" ${page >= data.last_page ? 'disabled' : ''} aria-label="Página siguiente">›</button>` : '';
            } catch (error) {
                if (error.name === 'AbortError') return;
                rows = [];
                info.textContent = error.message;
                tbody.innerHTML = `<tr><td colspan="7" class="desktop-list__empty">${esc(error.message)}<br>Usa Filtrar para reintentar.</td></tr>`;
            }
        }
        form.addEventListener('submit', event => { event.preventDefault(); clearTimeout(timer); cargar(); });
        form.elements.buscar.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(cargar, 350); });
        ['desde', 'hasta'].forEach(name => form.elements[name].addEventListener('change', () => cargar()));
        pivot.addEventListener('click', event => {
            const button = event.target.closest('[data-situacion]'); if (!button) return;
            form.elements.estado.value = button.dataset.situacion; marcarPivot(button.dataset.situacion); cargar();
        });
        pager.addEventListener('click', event => { const button = event.target.closest('[data-page]'); if (button && !button.disabled) cargar(Number(button.dataset.page)); });
        tbody.addEventListener('click', async event => {
            const button = event.target.closest('[data-emitir]');
            if (!button || button.disabled || emitting) return;
            const row = rows.find(item => item.id === Number(button.dataset.emitir));
            emitting = true;
            try {
                const total = row.total_ajustado ?? row.total;
                const detalle = row.total_ajustado !== null && Number(row.diferencia) ? ` Diferencia con el original: ${signed(Number(row.diferencia))}.` : '';
                if (!await DesktopUI.confirm({ title: 'Generar factura simulada', okText: 'Facturar', message: `Venta ${row.folio} por ${money(total)}.${detalle} Es una simulación sin valor fiscal y no mueve inventario.` })) return;
                button.disabled = true;
                const response = await api(`${config.base}/${row.id}/emitir`, 'POST', { version: row.version });
                DesktopUI.toast(`Factura simulada ${response.data.fac_folio} generada para ${row.folio}.`, 'success');
                await cargar(page);
            } catch (error) { DesktopUI.toast(error.message, 'error'); button.disabled = false; }
            finally { emitting = false; }
        });
        cargar(Number(inicial.get('page')) || 1);
    }

    function ajuste(config) {
        const $ = id => document.getElementById(id);
        const form = $('ajuste-form'), tbody = $('ajuste-partidas'), warehouse = $('ajuste-almacen');
        const search = $('ajuste-buscar'), list = $('ajuste-resultados'), productInfo = $('ajuste-productos-info');
        const save = $('ajuste-guardar'), emit = $('ajuste-facturar'), emitHint = $('ajuste-facturar-motivo');
        const estado = $('ajuste-estado'), errorBox = $('ajuste-error'), undoBox = $('ajuste-deshacer');
        const draftBox = $('ajuste-borrador'), conflictBox = $('ajuste-conflicto'), notesInput = $('ajuste-notas');
        const draftKey = `facturacion-borrador-${config.venta}`;
        const normalizar = item => ({ ...item, cantidad: item.cantidad, precio: item.precio, importe: round(Number(item.cantidad) * Number(item.precio)) });

        let items = config.partidas.map(normalizar);
        let notas = config.notas || '';
        let warehouseId = config.almacen || '';
        let version = Number(config.version), guardado = config.guardado;
        let busy = false, leaving = false, errors = {}, results = [], active = -1;
        let lastQuery = null, mostrarCatalogo = false, searchController, searchPromise = null, timer, draftTimer, undoTimer, removed = null, nuevo = null;

        const snapshot = (almacen = warehouseId, lista = items, texto = notas) => JSON.stringify({
            a: String(almacen || ''), n: String(texto || '').trim(),
            i: lista.map(item => [Number(item.psk_id), Number(item.cantidad), Number(item.precio)]),
        });
        let savedSnapshot = snapshot();
        const isDirty = () => config.editable && snapshot() !== savedSnapshot;
        const total = () => config.mixta ? round(items.reduce((sum, item) => sum + (Number.isFinite(item.importe) ? item.importe : 0), 0)) : config.original;

        // ---------- Estado visible ----------
        function setPill(texto, tono) {
            estado.textContent = texto;
            estado.className = `desktop-pill fac-pill--lg ${tono === 'brand' ? 'desktop-pill--brand' : tono === 'neutral' ? 'desktop-pill--neutral' : 'fac-pill--' + tono}`;
        }
        function actualizar() {
            const dirty = isDirty();
            const suma = total(), diferencia = round(suma - config.original);
            const sinPartidas = config.mixta && !items.length;
            $('ajuste-total').textContent = money(suma);
            $('ajuste-total-resumen').textContent = sinPartidas ? '—' : money(suma);
            $('ajuste-num-partidas').textContent = config.mixta ? `(${items.length} ${items.length === 1 ? 'partida' : 'partidas'})` : '';
            const caja = $('ajuste-diferencia-caja');
            caja.classList.toggle('is-warning', !sinPartidas && diferencia !== 0);
            caja.classList.toggle('is-success', !sinPartidas && diferencia === 0);
            $('ajuste-diferencia').textContent = sinPartidas ? '—' : signed(diferencia);
            $('ajuste-diferencia-texto').textContent = sinPartidas ? 'Diferencia' : diferencia === 0 ? 'Diferencia · igual al original'
                : `Diferencia · ${diferencia > 0 ? 'mayor' : 'menor'} que el original`;

            if (config.emitida) setPill('Factura simulada', 'success');
            else if (dirty) setPill('Cambios sin guardar', 'warning');
            else if (config.mixta && guardado) setPill('Ajuste guardado', 'brand');
            else if (config.mixta) setPill('Sin ajuste', 'neutral');
            else setPill('Lista para facturar', 'brand');

            if (save) {
                save.disabled = busy || !dirty;
                save.title = dirty ? 'Guardar ajuste (Ctrl+S)' : 'No hay cambios por guardar';
            }
            if (emit) {
                const motivo = config.bloqueo ? 'Venta no facturable.'
                    : dirty ? 'Guarda los cambios para facturar.'
                    : config.mixta && !guardado ? 'Guarda un ajuste para habilitar Facturar.' : '';
                emit.disabled = busy || Boolean(motivo);
                if (emitHint) emitHint.textContent = motivo;
            }
            programarBorrador();
        }

        // ---------- Partidas ----------
        function existenciaTexto(item) {
            if (item.disponible === false) return '<span class="fac-note fac-note--danger">Ya no está disponible en este almacén. Sustitúyelo.</span>';
            if (item.existencia === undefined || item.existencia === null) return '';
            const valor = Number(item.existencia);
            return `<span class="fac-note ${valor <= 0 ? 'fac-note--warning' : ''}">Existencia: ${qty(valor)}${valor <= 0 ? ' (solo informativo)' : ''}</span>`;
        }
        function campo(item, index, field) {
            const key = `${index}.${field}`, decimal = field === 'precio' || item.decimal;
            const minimo = field === 'precio' ? '0' : (item.decimal ? '0.01' : '1');
            return `<div class="desktop-field"><input type="number" inputmode="decimal" min="${minimo}" max="999999" step="${decimal ? '0.01' : '1'}"
                data-index="${index}" data-field="${field}" value="${esc(item[field])}" aria-label="${field === 'precio' ? 'Precio' : 'Cantidad'} de ${esc(item.nombre)}"
                ${errors[key] ? `aria-invalid="true" aria-describedby="err-${index}-${field}"` : ''}>
                ${errors[key] ? `<small class="fac-note--danger" id="err-${index}-${field}">${esc(errors[key])}</small>` : ''}</div>`;
        }
        function render() {
            const columnas = config.editable ? 5 : 4;
            tbody.innerHTML = items.length ? items.map((item, index) => {
                const conError = Object.keys(errors).some(key => key.startsWith(index + '.'));
                return `<tr data-row="${index}" class="${conError ? 'is-error' : ''} ${nuevo === item.psk_id ? 'is-nueva' : ''}">
                    <td><span class="desktop-list__name">${esc(item.nombre)}</span><span class="desktop-list__meta">${esc(item.codigo)}</span>${config.editable ? existenciaTexto(item) : ''}
                        ${errors[`${index}.psk_id`] ? `<span class="fac-note fac-note--danger">${esc(errors[`${index}.psk_id`])}</span>` : ''}</td>
                    <td class="fac-num">${config.editable ? campo(item, index, 'cantidad') : qty(item.cantidad)}</td>
                    <td class="fac-num">${config.editable ? campo(item, index, 'precio') : money(item.precio)}</td>
                    <td class="fac-num" data-importe="${index}"><strong>${money(item.importe)}</strong></td>
                    ${config.editable ? `<td><button type="button" class="desktop-btn desktop-btn--ghost fac-quitar" data-quitar="${index}" title="Quitar partida" aria-label="Quitar ${esc(item.nombre)}">${ICON_X}</button></td>` : ''}
                </tr>`;
            }).join('') : `<tr><td colspan="${columnas}" class="desktop-list__empty">${!config.editable ? 'Sin partidas.' : warehouseId
                ? 'Busca productos arriba o usa “Copiar al ajustado” para partir del ticket original.'
                : 'Selecciona el almacén para facturar y agrega productos.'}</td></tr>`;
            nuevo = null;
            actualizar();
        }
        function limpiarErrores() {
            errors = {}; errorBox.textContent = '';
            if ($('ajuste-almacen-error')) $('ajuste-almacen-error').textContent = '';
            if ($('ajuste-notas-error')) $('ajuste-notas-error').textContent = '';
        }
        function agregar(sku, cantidad = 1, precio = sku.precio, silencioso = false) {
            const existing = items.find(item => item.psk_id === sku.psk_id);
            if (existing) {
                existing.cantidad = round(Number(existing.cantidad || 0) + Number(cantidad));
                existing.importe = round(existing.cantidad * Number(existing.precio));
                existing.existencia = sku.existencia; existing.disponible = true;
            } else {
                items.push(normalizar({ psk_id: sku.psk_id, codigo: sku.codigo, nombre: sku.nombre, decimal: sku.decimal, cantidad, precio, existencia: sku.existencia, disponible: true }));
            }
            Object.keys(errors).forEach(key => { if (key.endsWith('.psk_id')) delete errors[key]; });
            nuevo = sku.psk_id; ocultarDeshacer();
            if (!silencioso && productInfo) productInfo.textContent = `Agregado: ${sku.nombre}${existing ? ` (cantidad ${qty(existing.cantidad)})` : ''}.`;
            render();
        }
        function quitar(index) {
            const [item] = items.splice(index, 1);
            removed = { item, index };
            errors = {};
            render();
            undoBox.querySelector('[data-texto]').textContent = `Se quitó ${item.nombre}.`;
            undoBox.hidden = false;
            clearTimeout(undoTimer); undoTimer = setTimeout(ocultarDeshacer, 10000);
            const siguiente = tbody.querySelector(`[data-quitar="${Math.min(index, items.length - 1)}"]`);
            (siguiente || search)?.focus();
        }
        function ocultarDeshacer() { undoBox.hidden = true; removed = null; }

        // ---------- Validación junto al campo ----------
        function validar() {
            limpiarErrores();
            let valido = true;
            if (!warehouseId) { $('ajuste-almacen-error').textContent = 'Selecciona el almacén para facturar.'; valido = false; }
            if (!items.length) { errorBox.textContent = 'Agrega al menos un producto al ticket ajustado.'; valido = false; }
            const decimales = value => /^\d+(\.\d{1,2})?$/.test(String(value).trim());
            items.forEach((item, index) => {
                const cantidad = Number(item.cantidad), precio = Number(item.precio);
                if (String(item.cantidad).trim() === '' || !Number.isFinite(cantidad) || cantidad <= 0) errors[`${index}.cantidad`] = 'Debe ser mayor a cero.';
                else if (cantidad > 999999) errors[`${index}.cantidad`] = 'Cantidad demasiado alta.';
                else if (!item.decimal && !Number.isInteger(cantidad)) errors[`${index}.cantidad`] = 'Usa una cantidad entera.';
                else if (!decimales(item.cantidad)) errors[`${index}.cantidad`] = 'Máximo dos decimales.';
                if (String(item.precio).trim() === '' || !Number.isFinite(precio) || precio < 0) errors[`${index}.precio`] = 'Captura un precio válido.';
                else if (precio > 999999) errors[`${index}.precio`] = 'Precio demasiado alto.';
                else if (!decimales(item.precio)) errors[`${index}.precio`] = 'Máximo dos decimales.';
                if (item.disponible === false) errors[`${index}.psk_id`] = 'Sustituye este producto: ya no está disponible.';
            });
            if (Object.keys(errors).length) valido = false;
            if (!valido) { render(); enfocarError(); }
            return valido;
        }
        function enfocarError() {
            const campoError = tbody.querySelector('[aria-invalid="true"]');
            if (campoError) { campoError.focus(); campoError.select?.(); return; }
            if ($('ajuste-almacen-error')?.textContent) { warehouse.focus(); return; }
            if (tbody.querySelector('tr.is-error')) { tbody.querySelector('tr.is-error').scrollIntoView({ block: 'center' }); return; }
            if (errorBox.textContent) search?.focus();
        }
        function erroresServidor(error) {
            const lista = error.errors || {};
            limpiarErrores();
            if (lista.version) { conflictBox.hidden = false; conflictBox.scrollIntoView({ block: 'nearest' }); guardarBorrador(); return; }
            let general = '';
            Object.entries(lista).forEach(([key, mensajes]) => {
                const mensaje = [].concat(mensajes)[0];
                const partida = key.match(/^items\.(\d+)\.(\w+)$/);
                if (partida) errors[`${partida[1]}.${partida[2]}`] = mensaje;
                else if (key === 'almacen_id' && $('ajuste-almacen-error')) $('ajuste-almacen-error').textContent = mensaje;
                else if (key === 'notas' && $('ajuste-notas-error')) $('ajuste-notas-error').textContent = mensaje;
                else if (!general) general = mensaje;
            });
            if (!Object.keys(lista).length) general = error.message;
            errorBox.textContent = general;
            render(); enfocarError();
            DesktopUI.toast(general || 'Revisa los campos marcados.', 'error');
        }

        // ---------- Buscador de productos ----------
        function abrirLista(abrir) {
            if (!list) return;
            if (!abrir) mostrarCatalogo = false;
            list.hidden = !abrir;
            search.setAttribute('aria-expanded', abrir ? 'true' : 'false');
            if (!abrir) search.removeAttribute('aria-activedescendant');
        }
        function pintarResultados() {
            if (!list) return;
            list.innerHTML = results.length ? results.map((sku, index) => {
                const enTicket = items.find(item => item.psk_id === sku.psk_id);
                return `<div class="fac-resultado ${index === active ? 'is-active' : ''}" role="option" id="fac-opt-${index}" data-opcion="${index}" aria-selected="${index === active}">
                    <div><span class="desktop-list__name">${esc(sku.nombre)}</span><span class="desktop-list__meta">${esc(sku.codigo)}${sku.codigo_barras ? ' · ' + esc(sku.codigo_barras) : ''}${enTicket ? ` · En el ticket: ${qty(enTicket.cantidad)}` : ''}</span></div>
                    <span class="fac-resultado__precio">${money(sku.precio)}</span>
                    <span class="fac-resultado__exist ${sku.existencia <= 0 ? 'is-low' : ''}">Exist. ${qty(sku.existencia)}</span>
                </div>`;
            }).join('') : `<div class="fac-resultados__vacio">Sin coincidencias en este almacén${search.value.trim() ? ' para “' + esc(search.value.trim()) + '”' : ''}.</div>`;
            if (active >= 0) {
                search.setAttribute('aria-activedescendant', `fac-opt-${active}`);
                list.querySelector(`#fac-opt-${active}`)?.scrollIntoView({ block: 'nearest' });
            } else search.removeAttribute('aria-activedescendant');
        }
        function buscarProductos(silencioso = false) {
            clearTimeout(timer);
            searchController?.abort(); searchController = new AbortController();
            if (!warehouseId || !search) return Promise.resolve();
            const consulta = search.value.replaceAll("'", '-').trim();
            const params = new URLSearchParams({ almacen_id: warehouseId, buscar: consulta });
            if (!silencioso) productInfo.textContent = 'Buscando...';
            const signal = searchController.signal;
            searchPromise = api(`${config.base}/productos?${params}`, 'GET', undefined, signal).then(result => {
                results = result.data; lastQuery = consulta;
                const exacto = results.findIndex(sku => sku.exacto);
                active = exacto >= 0 ? exacto : (results.length ? 0 : -1);
                if (!silencioso) productInfo.textContent = results.length
                    ? `${results.length === 40 ? 'Primeros 40' : results.length} resultado(s). ↑ ↓ para elegir, Enter para agregar.`
                    : 'Sin coincidencias. Prueba con otro nombre o código.';
                pintarResultados();
                // Con el buscador vacío la lista solo se abre a petición (↓), para no tapar las partidas.
                if (document.activeElement === search && (consulta || mostrarCatalogo)) abrirLista(true);
            }).catch(error => {
                if (error.name === 'AbortError') return;
                results = []; productInfo.textContent = error.message; abrirLista(false);
            }).finally(() => { if (!signal.aborted) searchPromise = null; });
            return searchPromise;
        }
        async function elegir() {
            const consulta = search.value.replaceAll("'", '-').trim();
            if (searchPromise || consulta !== lastQuery) await buscarProductos();
            if (!results.length) return;
            const exacto = results.find(sku => sku.exacto);
            // Un código escaneado exacto o un único resultado se agregan sin pasos extra.
            const sku = (active >= 0 && !list.hidden) ? results[active] : (exacto || (results.length === 1 ? results[0] : null));
            if (sku) {
                agregar(sku);
                search.value = ''; lastQuery = null; abrirLista(false); search.focus();
                buscarProductos(true);
            } else {
                active = 0; pintarResultados(); abrirLista(true);
                productInfo.textContent = 'Hay varias coincidencias: elige con ↑ ↓ y Enter.';
            }
        }
        search?.addEventListener('input', () => {
            clearTimeout(timer); searchController?.abort(); searchPromise = null;
            timer = setTimeout(buscarProductos, 220);
        });
        search?.addEventListener('focus', () => { if (search.value.trim() && results.length) { pintarResultados(); abrirLista(true); } });
        search?.addEventListener('blur', () => setTimeout(() => { if (document.activeElement !== search) abrirLista(false); }, 120));
        search?.addEventListener('keydown', event => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (!results.length) return;
                if (list.hidden) { mostrarCatalogo = true; abrirLista(true); }
                else active = event.key === 'ArrowDown' ? Math.min(active + 1, results.length - 1) : Math.max(active - 1, 0);
                pintarResultados();
            } else if (event.key === 'Enter') {
                event.preventDefault(); elegir();
            } else if (event.key === 'Escape') {
                if (!list.hidden) { event.preventDefault(); abrirLista(false); }
                else if (search.value) { event.preventDefault(); search.value = ''; buscarProductos(); }
            }
        });
        list?.addEventListener('mousedown', event => event.preventDefault());
        list?.addEventListener('click', event => {
            const option = event.target.closest('[data-opcion]'); if (!option || busy) return;
            active = Number(option.dataset.opcion); agregar(results[active]);
            search.value = ''; lastQuery = null; abrirLista(false); search.focus(); buscarProductos(true);
        });

        // ---------- Almacén y copia desde el original ----------
        function marcarAlmacenOriginal() {
            document.querySelectorAll('[data-almacen-pill]').forEach(pill => {
                const habilitado = warehouseId && pill.dataset.almacenPill === String(warehouseId);
                pill.classList.toggle('desktop-pill--brand', Boolean(habilitado));
                pill.classList.toggle('desktop-pill--neutral', !habilitado);
                pill.title = habilitado ? 'Almacén habilitado para facturar' : '';
            });
        }
        warehouse?.addEventListener('change', async () => {
            const next = warehouse.value;
            if (items.length && !await DesktopUI.confirm({ title: 'Cambiar almacén', okText: 'Cambiar y quitar partidas', danger: true, message: `El ticket ajustado solo admite productos del almacén elegido. Se quitarán las ${items.length} partidas actuales.` })) {
                warehouse.value = warehouseId; return;
            }
            warehouseId = next; items = []; limpiarErrores(); ocultarDeshacer(); results = []; lastQuery = null;
            marcarAlmacenOriginal(); render();
            if (search) {
                search.value = ''; search.disabled = !warehouseId;
                productInfo.textContent = warehouseId ? '' : 'Selecciona el almacén para buscar productos.';
                if (warehouseId) { buscarProductos(); search.focus(); }
            }
            if ($('ajuste-copiar')) $('ajuste-copiar').disabled = !warehouseId;
        });
        $('ajuste-copiar')?.addEventListener('click', async () => {
            if (busy) return;
            if (!warehouseId) { $('ajuste-almacen-error').textContent = 'Selecciona primero el almacén para facturar.'; warehouse.focus(); return; }
            const lineas = new Map();
            config.lineasOriginales.forEach(linea => {
                const actual = lineas.get(linea.psk_id);
                lineas.set(linea.psk_id, actual ? { ...actual, cantidad: round(actual.cantidad + Number(linea.cantidad)) } : { ...linea, cantidad: Number(linea.cantidad) });
            });
            const pendientes = [...lineas.values()].filter(linea => !items.some(item => item.psk_id === linea.psk_id));
            if (!pendientes.length) { DesktopUI.toast('Las partidas del original ya están en el ticket ajustado.', 'info'); return; }
            const params = new URLSearchParams({ almacen_id: warehouseId });
            pendientes.forEach(linea => params.append('ids[]', linea.psk_id));
            try {
                const { data } = await api(`${config.base}/productos?${params}`);
                const disponibles = new Map(data.map(sku => [sku.psk_id, sku]));
                let copiadas = 0;
                pendientes.forEach(linea => {
                    const sku = disponibles.get(linea.psk_id);
                    if (sku) { agregar(sku, sku.decimal ? linea.cantidad : Math.round(linea.cantidad), linea.precio, true); copiadas++; }
                });
                const faltan = pendientes.length - copiadas;
                const almacen = warehouse.options[warehouse.selectedIndex]?.text || 'el almacén';
                const texto = `${copiadas ? `Se copiaron ${copiadas} partida(s).` : 'No se copió ninguna partida.'}${faltan ? ` ${faltan} no existe(n) en ${almacen}; sustitúyelas buscando productos.` : ''}`;
                if (productInfo) productInfo.textContent = texto;
                DesktopUI.toast(texto, faltan ? 'info' : 'success');
                search?.focus();
            } catch (error) { errorBox.textContent = error.message; }
        });

        // ---------- Edición en tabla ----------
        tbody.addEventListener('input', event => {
            const input = event.target.closest('[data-field]'); if (!input || busy) return;
            const index = Number(input.dataset.index), item = items[index];
            item[input.dataset.field] = input.value;
            item.importe = round(Number(item.cantidad) * Number(item.precio));
            const celda = tbody.querySelector(`[data-importe="${index}"]`);
            if (celda) celda.innerHTML = `<strong>${Number.isFinite(item.importe) ? money(item.importe) : '—'}</strong>`;
            const key = `${index}.${input.dataset.field}`;
            if (errors[key]) {
                delete errors[key];
                input.removeAttribute('aria-invalid'); input.nextElementSibling?.remove();
                if (!Object.keys(errors).some(k => k.startsWith(index + '.'))) input.closest('tr').classList.remove('is-error');
            }
            errorBox.textContent = '';
            actualizar();
        });
        tbody.addEventListener('keydown', event => {
            const input = event.target.closest('[data-field]'); if (!input || event.key !== 'Enter') return;
            event.preventDefault();
            // Enter avanza: cantidad → precio → buscador, para seguir capturando sin mouse.
            if (input.dataset.field === 'cantidad') { const precio = tbody.querySelector(`[data-index="${input.dataset.index}"][data-field="precio"]`); precio?.focus(); precio?.select(); }
            else search?.focus();
        });
        tbody.addEventListener('focusin', event => { if (event.target.matches('[data-field]')) event.target.select(); });
        tbody.addEventListener('click', event => {
            const button = event.target.closest('[data-quitar]'); if (!button || busy) return;
            quitar(Number(button.dataset.quitar));
        });
        undoBox?.querySelector('[data-deshacer]').addEventListener('click', () => {
            if (!removed) return;
            items.splice(Math.min(removed.index, items.length), 0, removed.item);
            nuevo = removed.item.psk_id; ocultarDeshacer(); render();
        });
        notesInput?.addEventListener('input', () => { notas = notesInput.value; if ($('ajuste-notas-error')) $('ajuste-notas-error').textContent = ''; actualizar(); });
        form.addEventListener('submit', event => event.preventDefault());

        // ---------- Borrador local: evita perder cambios por recarga, cierre o conflicto ----------
        function guardarBorrador() {
            if (!config.editable) return;
            try {
                if (isDirty()) localStorage.setItem(draftKey, JSON.stringify({ almacen: warehouseId, notas, items, version, at: Date.now() }));
                else localStorage.removeItem(draftKey);
            } catch (_) { /* Sin almacenamiento local: se mantiene la protección al salir. */ }
        }
        function programarBorrador() { clearTimeout(draftTimer); draftTimer = setTimeout(guardarBorrador, 400); }
        function borrarBorrador() { clearTimeout(draftTimer); try { localStorage.removeItem(draftKey); } catch (_) { /* ignorado */ } }
        function ofrecerBorrador() {
            if (!config.editable || !draftBox) return;
            let draft = null;
            try { draft = JSON.parse(localStorage.getItem(draftKey) || 'null'); } catch (_) { return; }
            if (!draft || !Array.isArray(draft.items) || snapshot(draft.almacen, draft.items, draft.notas) === savedSnapshot) { if (draft) borrarBorrador(); return; }
            const hora = new Date(draft.at).toLocaleString('es-MX', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
            draftBox.querySelector('[data-texto]').innerHTML = `<strong>Hay cambios sin guardar de este ticket</strong> (${esc(hora)}, ${draft.items.length} partida(s)).`
                + (Number(draft.version) !== version ? ' El ticket se guardó después en otra sesión: revisa antes de guardar.' : '');
            draftBox.hidden = false;
            draftBox.querySelector('[data-recuperar]').onclick = () => {
                const almacenValido = warehouse && [...warehouse.options].some(option => option.value === String(draft.almacen));
                if (almacenValido) { warehouseId = String(draft.almacen); warehouse.value = warehouseId; }
                items = draft.items.map(normalizar); notas = draft.notas || '';
                if (notesInput) notesInput.value = notas;
                draftBox.hidden = true; marcarAlmacenOriginal(); render();
                if (search) { search.disabled = !warehouseId; if (warehouseId) buscarProductos(); }
                DesktopUI.toast('Cambios recuperados. Recuerda guardar el ajuste.', 'info');
            };
            draftBox.querySelector('[data-descartar]').onclick = () => { borrarBorrador(); draftBox.hidden = true; };
        }

        // ---------- Guardar y facturar ----------
        function freeze(value) {
            busy = value;
            form.querySelectorAll('input,select,textarea,button').forEach(element => {
                if (value) { element.dataset.wasDisabled = element.disabled ? '1' : '0'; element.disabled = true; }
                else element.disabled = element.dataset.wasDisabled === '1';
            });
            actualizar();
        }
        async function guardar() {
            if (!config.editable || busy || !isDirty()) return;
            abrirLista(false);
            if (!validar()) { DesktopUI.toast('Revisa los campos marcados.', 'error'); return; }
            const payload = { almacen_id: Number(warehouseId), version, notas, items: items.map(item => ({ psk_id: item.psk_id, cantidad: Number(item.cantidad), precio: Number(item.precio) })) };
            freeze(true); if (save) save.textContent = 'Guardando...';
            try {
                const response = await api(`${config.base}/ajuste`, 'PUT', payload);
                const previos = new Map(items.map(item => [item.psk_id, item]));
                version = response.data.fac_version; guardado = true;
                items = response.data.fac_partidas.map(item => normalizar({ ...item, existencia: previos.get(item.psk_id)?.existencia, disponible: true }));
                notas = response.data.fac_notas || ''; if (notesInput) notesInput.value = notas;
                savedSnapshot = snapshot(); borrarBorrador(); conflictBox.hidden = true; draftBox.hidden = true;
                DesktopUI.toast(response.message, 'success');
            } catch (error) {
                if (save) save.textContent = 'Guardar ajuste';
                freeze(false); erroresServidor(error); return;
            }
            if (save) save.textContent = 'Guardar ajuste';
            freeze(false); render(); emit?.focus();
        }
        save?.addEventListener('click', guardar);
        emit?.addEventListener('click', async () => {
            if (emit.disabled || busy) return;
            const diferencia = round(total() - config.original);
            const detalle = config.mixta ? `Total ajustado ${money(total())}; diferencia con el original ${signed(diferencia)}.` : `Total ${money(total())}.`;
            if (!await DesktopUI.confirm({ title: 'Generar factura simulada', okText: 'Facturar', message: `${detalle} El ticket quedará cerrado para edición. Es una simulación sin valor fiscal y no mueve inventario.` })) return;
            freeze(true); emit.textContent = 'Facturando...';
            try {
                await api(`${config.base}/emitir`, 'POST', { version });
                borrarBorrador(); leaving = true; window.location.reload();
            } catch (error) {
                emit.textContent = 'Facturar'; freeze(false);
                if (error.errors?.version) { conflictBox.hidden = false; return; }
                errorBox.textContent = error.message; DesktopUI.toast(error.message, 'error');
            }
        });
        conflictBox?.querySelector('[data-recargar]').addEventListener('click', () => { guardarBorrador(); leaving = true; window.location.reload(); });

        // ---------- Salida y atajos ----------
        $('ajuste-volver')?.addEventListener('click', async event => {
            if (!isDirty()) return;
            event.preventDefault();
            const destino = event.currentTarget.href;
            if (await DesktopUI.confirm({ title: 'Salir sin guardar', okText: 'Salir sin guardar', danger: true, message: 'Hay cambios sin guardar en el ticket ajustado. Quedarán como borrador en este equipo para recuperarlos después.' })) {
                guardarBorrador(); leaving = true; window.location.href = destino;
            }
        });
        window.addEventListener('beforeunload', event => {
            if (leaving || !isDirty()) return;
            guardarBorrador(); event.preventDefault(); event.returnValue = '';
        });
        document.addEventListener('keydown', event => {
            if (document.querySelector('#dx-dialog.is-open')) return;
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                event.preventDefault(); if (config.editable) guardar();
            } else if (event.key === 'F2' && search && !search.disabled) {
                event.preventDefault(); search.focus(); search.select();
            }
        });

        if ($('ajuste-copiar')) $('ajuste-copiar').disabled = !warehouseId;
        marcarAlmacenOriginal();
        render();
        ofrecerBorrador();
        if (config.editable && warehouseId && search) { search.disabled = false; buscarProductos(); if (!items.length) search.focus(); }
    }

    return { listado, ajuste };
})();
