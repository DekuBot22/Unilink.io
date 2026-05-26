<?php
$pageTitle = 'Mensajes';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/flash.php';
require __DIR__ . '/../partials/auth-modal.php';
$user = authUser();
?>

<div class="mensajes-page">
    <div class="mensajes-container">

        <!-- Sidebar: lista de conversaciones -->
        <aside class="mensajes-sidebar">
            <div class="mensajes-sidebar-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Mensajes
            </div>
            <div class="mensajes-lista" id="mensajesLista">
                <div class="mensajes-lista-loading">Cargando conversaciones...</div>
            </div>
        </aside>

        <!-- Panel derecho: chat activo -->
        <div class="mensajes-chat" id="mensajesChat">
            <div class="mensajes-chat-empty" id="mensajesChatEmpty">
                <div class="mensajes-chat-empty-inner">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#c8d6e8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <p>Selecciona una conversación para empezar</p>
                </div>
            </div>

            <div class="mensajes-chat-activo hidden" id="mensajesChatActivo">
                <div class="mensajes-chat-header" id="mensajesChatHeader">
                    <div class="mensajes-chat-avatar" id="mensajesChatAvatar"></div>
                    <div>
                        <strong id="mensajesChatNombre"></strong>
                        <span id="mensajesChatSub"></span>
                    </div>
                </div>
                <div class="mensajes-chat-body" id="mensajesChatBody"></div>
                <form class="mensajes-input-wrap" id="mensajesChatForm" onsubmit="enviarMensajePagina(event)">
                    <input
                        type="text"
                        id="mensajesChatInput"
                        maxlength="1000"
                        placeholder="Escribe un mensaje..."
                        autocomplete="off"
                        required
                    >
                    <button type="submit" class="chat-send-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    var miId        = <?= (int) ($user['id'] ?? 0) ?>;
    var conActualId = null;
    var pollTimer   = null;

    // Si la URL trae ?con=X o ?tutor_id=X, abrir esa conversación al cargar
    var params    = new URLSearchParams(window.location.search);
    var paramCon  = params.get('con')      ? parseInt(params.get('con'), 10)      : 0;
    var paramTutor = params.get('tutor_id') ? parseInt(params.get('tutor_id'), 10) : 0;

    // ── helpers ─────────────────────────────────────────────────────
    function esc(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function iniciales(nombre) {
        var partes = String(nombre || '').trim().split(/\s+/);
        return partes.slice(0, 2).map(function (p) { return p.charAt(0).toUpperCase(); }).join('');
    }

    function horaDesde(iso) {
        var d = new Date(iso.replace(' ', 'T'));
        return d.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
    }

    // ── conversaciones ───────────────────────────────────────────────
    function cargarConversaciones(autoAbrir) {
        fetch('index.php?action=mensajes/conversaciones', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                renderConversaciones(data.conversaciones || []);
                if (autoAbrir && paramCon > 0) {
                    abrirConversacionPorId(paramCon);
                } else if (autoAbrir && paramTutor > 0) {
                    abrirConversacionPorTutorId(paramTutor);
                }
            })
            .catch(function () {
                document.getElementById('mensajesLista').innerHTML =
                    '<div class="mensajes-lista-vacia">Error al cargar conversaciones.</div>';
            });
    }

    function renderConversaciones(convs) {
        var lista = document.getElementById('mensajesLista');
        if (!convs || convs.length === 0) {
            lista.innerHTML = '<div class="mensajes-lista-vacia">No tienes conversaciones aún.</div>';
            return;
        }
        lista.innerHTML = convs.map(function (c) {
            var otroId   = parseInt(c.otro_id, 10);
            var noLeidos = parseInt(c.no_leidos, 10) || 0;
            var activo   = otroId === conActualId ? ' mensajes-item--activo' : '';
            var badge    = noLeidos > 0
                ? '<span class="mensajes-badge">' + (noLeidos > 99 ? '99+' : noLeidos) + '</span>'
                : '';
            var ini      = iniciales(c.otro_nombre);
            var fotoHtml = c.otro_foto
                ? '<img src="' + esc(c.otro_foto) + '" alt="" class="mensajes-item-img">'
                : '<div class="mensajes-item-avatar">' + esc(ini) + '</div>';
            return '<div class="mensajes-item' + activo + '" data-con-id="' + otroId + '" onclick="abrirConversacionPorId(' + otroId + ')">'
                + fotoHtml
                + '<div class="mensajes-item-info">'
                + '<div class="mensajes-item-top">'
                + '<span class="mensajes-item-nombre">' + esc(c.otro_nombre) + '</span>'
                + '<span class="mensajes-item-tiempo">' + esc(c.tiempo) + '</span>'
                + '</div>'
                + '<div class="mensajes-item-preview">'
                + (parseInt(c.emisor_id, 10) === miId ? '<em>Tú: </em>' : '')
                + esc(c.texto.length > 45 ? c.texto.substring(0, 45) + '…' : c.texto)
                + '</div>'
                + '</div>'
                + badge
                + '</div>';
        }).join('');
    }

    // ── abrir conversación ───────────────────────────────────────────
    window.abrirConversacionPorId = function (otroId) {
        conActualId = otroId;
        mostrarChatActivo();
        document.getElementById('mensajesChatBody').innerHTML =
            '<div class="chat-loading">Cargando mensajes...</div>';
        document.getElementById('mensajesChatNombre').textContent = '';
        document.getElementById('mensajesChatSub').textContent = '';
        document.getElementById('mensajesChatAvatar').textContent = '';

        obtenerMensajes(otroId, true);

        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(function () { obtenerMensajes(otroId, false); }, 3000);
    };

    function abrirConversacionPorTutorId(tutorId) {
        fetch('index.php?action=mensajes/get&tutor_id=' + tutorId, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.con_id) {
                    abrirConversacionPorId(data.con_id);
                } else if (data.error === 'tutor_sin_cuenta') {
                    mostrarChatActivo();
                    document.getElementById('mensajesChatBody').innerHTML =
                        '<div class="mensajes-sin-cuenta">Este tutor aún no tiene cuenta activa.</div>';
                }
            })
            .catch(function () {});
    }

    function obtenerMensajes(otroId, scrollForzado) {
        fetch('index.php?action=mensajes/get&con=' + otroId, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) return;
                renderMensajes(data.mensajes || [], data.mi_id, scrollForzado);
                actualizarHeaderChat(otroId, data.mensajes || []);
                // refrescar sidebar sin scroll forzado
                cargarConversacionesSilencioso();
            })
            .catch(function () {});
    }

    function actualizarHeaderChat(otroId, mensajes) {
        // Buscar nombre del otro en la lista ya renderizada
        var item = document.querySelector('.mensajes-item[data-con-id="' + otroId + '"]');
        var nombre = item
            ? (item.querySelector('.mensajes-item-nombre') || {}).textContent || ''
            : '';
        if (nombre) {
            document.getElementById('mensajesChatNombre').textContent = nombre;
            document.getElementById('mensajesChatAvatar').textContent = iniciales(nombre);
        }
    }

    function cargarConversacionesSilencioso() {
        fetch('index.php?action=mensajes/conversaciones', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) { renderConversaciones(data.conversaciones || []); })
            .catch(function () {});
    }

    // ── render mensajes ──────────────────────────────────────────────
    function renderMensajes(mensajes, miIdServidor, scrollForzado) {
        var body = document.getElementById('mensajesChatBody');
        if (!body) return;

        var id = miIdServidor ? parseInt(miIdServidor, 10) : miId;

        if (mensajes.length === 0) {
            body.innerHTML = '<div class="chat-empty-hint">Sé el primero en escribir un mensaje.</div>';
            return;
        }

        var scrollEstaba = body.scrollTop + body.clientHeight >= body.scrollHeight - 10;

        body.innerHTML = mensajes.map(function (m) {
            var esMio = parseInt(m.emisor_id, 10) === id;
            var hora  = horaDesde(m.creado_en);
            return '<div class="chat-msg ' + (esMio ? 'chat-msg-out' : 'chat-msg-in') + '">'
                + '<p>' + esc(m.texto) + '</p>'
                + '<span>' + hora + '</span>'
                + '</div>';
        }).join('');

        if (scrollForzado || scrollEstaba) {
            body.scrollTop = body.scrollHeight;
        }
    }

    // ── enviar mensaje ───────────────────────────────────────────────
    window.enviarMensajePagina = function (e) {
        e.preventDefault();
        if (!conActualId) return;

        var input = document.getElementById('mensajesChatInput');
        var texto = (input.value || '').trim();
        if (!texto) return;

        input.value = '';
        input.focus();

        // Optimistic: añadir al DOM de inmediato
        var body = document.getElementById('mensajesChatBody');
        var div  = document.createElement('div');
        div.className = 'chat-msg chat-msg-out';
        div.innerHTML = '<p>' + esc(texto) + '</p><span>'
            + new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' })
            + '</span>';
        body.appendChild(div);
        body.scrollTop = body.scrollHeight;

        var fd = new FormData();
        fd.append('receptor_id', String(conActualId));
        fd.append('texto', texto);

        fetch('index.php?action=mensajes/send', {
            method: 'POST',
            credentials: 'same-origin',
            body: fd
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.ok) obtenerMensajes(conActualId, false);
        })
        .catch(function () {});
    };

    // ── ui helpers ───────────────────────────────────────────────────
    function mostrarChatActivo() {
        document.getElementById('mensajesChatEmpty').classList.add('hidden');
        document.getElementById('mensajesChatActivo').classList.remove('hidden');
        document.getElementById('mensajesChatInput').focus();
    }

    // ── arranque ─────────────────────────────────────────────────────
    cargarConversaciones(true);

    // Refrescar sidebar cada 15 s
    setInterval(cargarConversacionesSilencioso, 15000);
})();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
