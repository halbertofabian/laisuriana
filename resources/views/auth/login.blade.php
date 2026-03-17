@php($templateAssetBase = asset('vendor-template/assets'))
<!doctype html>
<html
    lang="es"
    class="layout-wide customizer-hide"
    dir="ltr"
    data-skin="default"
    data-bs-theme="light"
    data-assets-path="{{ $templateAssetBase }}/"
>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Inicio de sesión | {{ config('app.name', 'La iSuriana Retail') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ $templateAssetBase }}/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="{{ $templateAssetBase }}/vendor/fonts/iconify-icons.css" />
    <link rel="stylesheet" href="{{ $templateAssetBase }}/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="{{ $templateAssetBase }}/vendor/libs/pickr/pickr-themes.css" />
    <link rel="stylesheet" href="{{ $templateAssetBase }}/vendor/css/core.css" />
    <link rel="stylesheet" href="{{ $templateAssetBase }}/css/demo.css" />
    <link rel="stylesheet" href="{{ $templateAssetBase }}/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="{{ $templateAssetBase }}/vendor/css/pages/page-auth.css" />

    <script src="{{ $templateAssetBase }}/vendor/js/helpers.js"></script>
    <script src="{{ $templateAssetBase }}/js/config.js"></script>
    <style>
        #messageModal .modal-content {
            border: 0;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 1.25rem 2.5rem rgba(32, 41, 68, 0.2);
        }

        #messageModal.fade .modal-dialog {
            transform: translateY(18px) scale(0.985);
            opacity: 0;
            transition: transform 0.28s cubic-bezier(0.2, 0.9, 0.25, 1), opacity 0.24s ease;
        }

        #messageModal.show .modal-dialog {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .app-message-shell {
            border-top: 4px solid transparent;
            padding: 0.95rem 1.1rem 0.9rem;
            background: linear-gradient(180deg, #ffffff 0%, #fcfcff 100%);
            animation: message-shell-enter 0.26s ease-out;
        }

        .app-message-shell.is-success { border-top-color: #28c76f; }
        .app-message-shell.is-error { border-top-color: #ea5455; }
        .app-message-shell.is-warning { border-top-color: #ff9f43; }
        .app-message-shell.is-info { border-top-color: #00cfe8; }

        .app-message-head {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 0.65rem;
            padding-right: 2rem;
        }

        .app-message-icon {
            width: 2.15rem;
            height: 2.15rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
        }

        .app-message-shell.is-success .app-message-icon { background: rgba(40, 199, 111, 0.16); color: #28c76f; }
        .app-message-shell.is-error .app-message-icon { background: rgba(234, 84, 85, 0.14); color: #ea5455; }
        .app-message-shell.is-warning .app-message-icon { background: rgba(255, 159, 67, 0.16); color: #ff9f43; }
        .app-message-shell.is-info .app-message-icon { background: rgba(0, 207, 232, 0.16); color: #00a5bb; }

        .app-message-title {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: #2f2b3d;
        }

        .app-message-body {
            font-size: 1rem;
            line-height: 1.5;
            color: #514d66;
            margin-bottom: 0.9rem;
            white-space: pre-line;
        }

        .app-message-actions {
            display: flex;
            justify-content: end;
        }

        .app-message-actions .btn {
            min-width: 7.2rem;
            font-weight: 600;
            border-radius: 0.6rem;
        }

        .app-message-close {
            position: absolute;
            top: 0.55rem;
            right: 0.55rem;
            z-index: 2;
            transform: none !important;
            padding: 0.45rem !important;
            border-radius: 0.5rem;
            background-color: transparent !important;
            box-shadow: none !important;
            opacity: 0.8;
        }

        .app-message-close::before {
            background-color: #8f8aa8 !important;
        }

        .app-message-close:hover,
        .app-message-close:focus,
        .app-message-close:active {
            transform: none !important;
            background-color: #f3f4f8 !important;
            opacity: 1;
        }

        @media (max-width: 575.98px) {
            .app-message-actions {
                justify-content: stretch;
            }

            .app-message-actions .btn {
                width: 100%;
            }
        }

        @keyframes message-shell-enter {
            0% {
                opacity: 0.92;
                transform: translateY(6px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            #messageModal.fade .modal-dialog {
                transition: none;
            }

            .app-message-shell {
                animation: none;
            }
        }
    </style>
</head>
<style>
    :root {
        --ls-accent: #635bff;
        --ls-accent-hover: #4f46e5;
        --ls-text-primary: #0a2540;
        --ls-text-secondary: #425466;
        --ls-text-muted: #6b7c93;
        --ls-border: #e6ebf1;
        --ls-surface: #ffffff;
        --ls-surface-2: #f6f9fc;
        --ls-radius: .5rem;
        --ls-radius-lg: .75rem;
        --ls-radius-xl: 1rem;
        --ls-shadow-sm: 0 1px 3px rgba(10,37,64,.06), 0 1px 2px rgba(10,37,64,.04);
        --ls-shadow-lg: 0 8px 24px rgba(10,37,64,.10), 0 1px 3px rgba(10,37,64,.06);
    }

    html, body { height: 100%; }

    body {
        background: var(--ls-surface-2);
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
    }

    .login-shell {
        width: 100%;
        max-width: 420px;
        padding: 1.5rem;
    }

    .login-brand {
        display: flex;
        align-items: center;
        gap: .6rem;
        justify-content: center;
        margin-bottom: 2rem;
    }

    .login-brand__icon {
        width: 2.4rem;
        height: 2.4rem;
        border-radius: .6rem;
        background: var(--ls-accent);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.15rem;
    }

    .login-brand__name {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--ls-text-primary);
        letter-spacing: -.01em;
    }

    .login-card {
        background: var(--ls-surface);
        border: 1px solid var(--ls-border);
        border-radius: var(--ls-radius-xl);
        box-shadow: var(--ls-shadow-lg);
        padding: 2rem 2.25rem;
    }

    .login-card__title {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--ls-text-primary);
        margin: 0 0 .3rem;
    }

    .login-card__sub {
        font-size: .9rem;
        color: var(--ls-text-muted);
        margin: 0 0 1.75rem;
    }

    .login-card .form-label {
        font-size: .8rem;
        font-weight: 600;
        color: var(--ls-text-secondary);
        margin-bottom: .35rem;
    }

    .login-card .form-control {
        font-size: .9rem;
        border: 1px solid var(--ls-border);
        border-radius: var(--ls-radius);
        color: var(--ls-text-primary);
        box-shadow: 0 1px 2px rgba(10,37,64,.04);
        transition: border-color .15s, box-shadow .15s;
    }

    .login-card .form-control:focus {
        border-color: var(--ls-accent);
        box-shadow: 0 0 0 3px rgba(99,91,255,.12);
        outline: none;
    }

    .login-card .form-hint {
        font-size: .74rem;
        color: var(--ls-text-muted);
        margin: .3rem 0 0;
    }

    .login-btn {
        width: 100%;
        padding: .65rem;
        font-size: .9rem;
        font-weight: 700;
        border-radius: var(--ls-radius);
        background: var(--ls-accent);
        border: none;
        color: #fff;
        cursor: pointer;
        transition: background .15s;
        margin-top: .25rem;
    }

    .login-btn:hover { background: var(--ls-accent-hover); }

    .login-footer {
        text-align: center;
        margin-top: 1.5rem;
        font-size: .78rem;
        color: var(--ls-text-muted);
    }
</style>
<body>
<div class="login-shell">

    <div class="login-brand">
        <div class="login-brand__icon">
            <i class="ti tabler-building-store"></i>
        </div>
        <span class="login-brand__name">La iSuriana Retail</span>
    </div>

    <div class="login-card">
        <h1 class="login-card__title">Iniciar sesión</h1>
        <p class="login-card__sub">Ingresa con tu usuario y contraseña para continuar.</p>

        <form id="login-form">
            <div class="mb-4">
                <label for="usuario" class="form-label">Usuario</label>
                <input
                    type="text"
                    class="form-control"
                    id="usuario"
                    name="usuario"
                    list="usuarios_sugeridos"
                    placeholder="Escribe tu usuario…"
                    autocomplete="off"
                    required
                    autofocus
                />
                <datalist id="usuarios_sugeridos"></datalist>
                <p class="form-hint">Escribe al menos 2 caracteres para buscar.</p>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <input
                    type="password"
                    id="password"
                    class="form-control"
                    name="password"
                    placeholder="••••••••"
                    required
                />
            </div>

            <button class="login-btn" type="submit">Entrar al sistema</button>
        </form>
    </div>

    <div class="login-footer">
        Sistema de gestión retail &mdash; {{ now()->year }}
    </div>

</div>

<div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <button type="button" class="btn-close app-message-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            <div class="app-message-shell is-info" id="messageContent">
                <div class="app-message-head">
                    <span class="app-message-icon">
                        <i class="icon-base ti tabler-info-circle" id="messageIcon" aria-hidden="true"></i>
                    </span>
                    <h5 class="app-message-title" id="messageTitle">Mensaje</h5>
                </div>
                <div class="app-message-body" id="messageBody"></div>
                <div class="app-message-actions">
                    <button type="button" class="btn btn-primary" id="messageButton" data-bs-dismiss="modal">Aceptar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ $templateAssetBase }}/vendor/libs/jquery/jquery.js"></script>
<script src="{{ $templateAssetBase }}/vendor/libs/popper/popper.js"></script>
<script src="{{ $templateAssetBase }}/vendor/js/bootstrap.js"></script>
<script src="{{ $templateAssetBase }}/vendor/libs/node-waves/node-waves.js"></script>
<script src="{{ $templateAssetBase }}/vendor/libs/pickr/pickr.js"></script>
<script src="{{ $templateAssetBase }}/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="{{ $templateAssetBase }}/vendor/libs/hammer/hammer.js"></script>
<script src="{{ $templateAssetBase }}/vendor/js/menu.js"></script>
<script src="{{ $templateAssetBase }}/js/main.js"></script>
<script>
    (function () {
        const form = document.getElementById('login-form');
        const usuarioInput = document.getElementById('usuario');
        const passwordInput = document.getElementById('password');
        const dataList = document.getElementById('usuarios_sugeridos');
        let debounceTimeout = null;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        function showMessage(title, body, type) {
            const normalizedTitle = (title || '')
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
            const normalizedType = (type || '').toString().toLowerCase();

            const resolvedType = normalizedType || (
                normalizedTitle.includes('error') || normalizedTitle.includes('denegado')
                    ? 'error'
                    : normalizedTitle.includes('validacion') || normalizedTitle.includes('warning') || normalizedTitle.includes('advertencia')
                        ? 'warning'
                        : normalizedTitle.includes('exito') || normalizedTitle.includes('correcto')
                            ? 'success'
                            : 'info'
            );

            const variants = {
                success: { shell: 'is-success', icon: 'tabler-circle-check', button: 'btn-success' },
                error: { shell: 'is-error', icon: 'tabler-alert-circle', button: 'btn-danger' },
                warning: { shell: 'is-warning', icon: 'tabler-alert-triangle', button: 'btn-warning' },
                info: { shell: 'is-info', icon: 'tabler-info-circle', button: 'btn-primary' }
            };

            const variant = variants[resolvedType] || variants.info;
            const content = document.getElementById('messageContent');
            const button = document.getElementById('messageButton');

            content.classList.remove('is-success', 'is-error', 'is-warning', 'is-info');
            content.classList.add(variant.shell);
            button.classList.remove('btn-primary', 'btn-success', 'btn-danger', 'btn-warning');
            button.classList.add(variant.button);

            document.getElementById('messageIcon').className = 'icon-base ti ' + variant.icon;
            document.getElementById('messageTitle').textContent = title || 'Mensaje';
            document.getElementById('messageBody').textContent = body || '';
            new bootstrap.Modal(document.getElementById('messageModal')).show();
        }

        function renderSugerencias(usuarios) {
            dataList.innerHTML = '';

            usuarios.forEach(function (usuario) {
                const option = document.createElement('option');
                option.value = usuario.usr_usuario;
                option.textContent = usuario.usr_usuario + ' - ' + usuario.usr_nombre;
                dataList.appendChild(option);
            });
        }

        function usuarioCoincideConSugerencia(valor) {
            const opciones = Array.from(dataList.options).map(function (option) {
                return option.value;
            });

            return opciones.includes(valor);
        }

        function buscarUsuarios() {
            const valor = usuarioInput.value.trim();

            if (valor.length < 2) {
                dataList.innerHTML = '';
                return;
            }

            $.getJSON('{{ route('login.buscar_usuarios') }}', { q: valor })
                .done(function (response) {
                    renderSugerencias(response.data || []);
                });
        }

        usuarioInput.addEventListener('input', function () {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(buscarUsuarios, 250);

            if (usuarioCoincideConSugerencia(usuarioInput.value.trim())) {
                passwordInput.focus();
            }
        });

        usuarioInput.addEventListener('change', function () {
            if (usuarioInput.value.trim().length > 0) {
                passwordInput.focus();
            }
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: '{{ route('login.attempt') }}',
                method: 'POST',
                data: $(form).serialize(),
                dataType: 'json'
            }).done(function (response) {
                window.location.href = response.redirect;
            }).fail(function (xhr) {
                const message = xhr.responseJSON?.message || 'No fue posible iniciar sesión.';
                showMessage('Acceso denegado', message);
            });
        });
    })();
</script>
</body>
</html>
