<?php
/** @var string $activePage */
$user = authUser();

$headerFoto      = null;
$headerIniciales = '?';

if ($user) {
    $foto = $user['foto_perfil'] ?? null;
    $headerFoto = ($foto && $foto !== '') ? $foto : null;

    $nombre = trim((string) ($user['nombre'] ?? ''));
    $partes  = preg_split('/\s+/', $nombre) ?: [];
    $letras  = '';
    foreach ($partes as $parte) {
        if ($parte === '') continue;
        $letras .= strtoupper(substr($parte, 0, 1));
        if (strlen($letras) >= 2) break;
    }
    $headerIniciales = $letras !== '' ? $letras : '?';
}
?>
<header>
    <div class="logo">
        <a href="index.php?page=inicio" class="logo" style="text-decoration:none;color:white;display:flex;align-items:center;gap:10px">
            <img src="img/LogoProyecto.png" alt="Logo UniLink" class="logo-icon logo-icon-img">
            <div class="logo-text">
                <h1>UniLink</h1>
                <span>Universidad del Magdalena</span>
            </div>
        </a>
    </div>
    <nav>
        <a href="index.php?page=inicio"    class="<?= $activePage === 'inicio'    ? 'active' : '' ?>">Inicio</a>
        <a href="index.php?page=tutores"   class="<?= $activePage === 'tutores'   ? 'active' : '' ?>">Tutores</a>
        <a href="index.php?page=contenido" class="<?= $activePage === 'contenido' ? 'active' : '' ?>">Contenido</a>
        <a href="index.php?page=ser-tutor" class="<?= $activePage === 'ser-tutor' ? 'active' : '' ?>">Ser Tutor</a>
        <?php if (isTutor()): ?>
            <a href="index.php?page=tutor" class="<?= $activePage === 'tutor' ? 'active' : '' ?>">Panel Tutor</a>
        <?php endif; ?>
        <?php if (isAdmin()): ?>
            <a href="index.php?page=admin" class="<?= $activePage === 'admin' ? 'active' : '' ?>">Admin</a>
        <?php endif; ?>
        <a href="index.php?page=inicio#contacto" class="btn-contacto">Contacto</a>

        <?php if ($user): ?>
            <a href="index.php?page=mensajes" class="header-msg-btn" id="headerMsgBtn" aria-label="Mensajes" title="Mensajes">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span class="header-msg-badge" id="headerMsgBadge" hidden>0</span>
            </a>
            <div class="header-notif" id="headerNotifMenu">
                <button class="header-notif-btn" id="headerNotifBtn" type="button" aria-label="Notificaciones" aria-expanded="false" aria-haspopup="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span class="header-notif-badge" id="headerNotifBadge" hidden>0</span>
                </button>
                <div class="header-notif-dropdown" id="headerNotifDropdown" hidden>
                    <div class="header-notif-header">
                        <span>Notificaciones</span>
                        <button type="button" class="header-notif-read-all" id="headerNotifReadAll">Marcar todo como leido</button>
                    </div>
                    <div class="header-notif-list" id="headerNotifList">
                        <div class="header-notif-empty">Sin notificaciones</div>
                    </div>
                </div>
            </div>

            <div class="header-user" id="headerUserMenu">
                <button class="header-avatar-btn" id="headerAvatarBtn" type="button" aria-label="Menu de usuario" aria-expanded="false" aria-haspopup="true">
                    <?php if ($headerFoto): ?>
                        <img src="<?= e($headerFoto) ?>" alt="Foto de perfil" class="header-avatar-img">
                    <?php else: ?>
                        <span class="header-avatar-iniciales"><?= e($headerIniciales) ?></span>
                    <?php endif; ?>
                </button>
                <div class="header-dropdown" id="headerDropdown" hidden>
                    <a href="index.php?page=perfil" class="header-dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Ver perfil
                    </a>
                    <a href="index.php?action=auth/logout" class="header-dropdown-item header-dropdown-item--danger">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Cerrar sesion
                    </a>
                </div>
            </div>
        <?php else: ?>
            <button type="button" class="btn-login" data-open-auth>Iniciar sesion</button>
        <?php endif; ?>
    </nav>
</header>

<script>
(function () {
    var btn      = document.getElementById('headerAvatarBtn');
    var dropdown = document.getElementById('headerDropdown');
    if (!btn || !dropdown) return;

    function openMenu() {
        dropdown.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
    }

    function closeMenu() {
        dropdown.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.hidden ? openMenu() : closeMenu();
    });

    document.addEventListener('click', function (e) {
        if (!document.getElementById('headerUserMenu').contains(e.target)) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMenu();
    });
})();

<?php if ($user): ?>
(function () {
    var notifBtn      = document.getElementById('headerNotifBtn');
    var notifDropdown = document.getElementById('headerNotifDropdown');
    var notifMenu     = document.getElementById('headerNotifMenu');
    var notifBadge    = document.getElementById('headerNotifBadge');
    var notifList     = document.getElementById('headerNotifList');
    var readAllBtn    = document.getElementById('headerNotifReadAll');

    if (!notifBtn || !notifDropdown) return;

    var iconMap = {
        cita_confirmada:     '✓',
        cita_en_curso:       '▶',
        cita_cancelada:      '✕',
        mensaje:             '✉',
        nueva_cita:          '📅',
        cita_cancelada_tutor:'✕',
        contenido_aprobado:  '✓',
        cita_proxima:        '⏰',
        resena_nueva:        '★'
    };

    function openNotif() {
        notifDropdown.hidden = false;
        notifBtn.setAttribute('aria-expanded', 'true');
    }

    function closeNotif() {
        notifDropdown.hidden = true;
        notifBtn.setAttribute('aria-expanded', 'false');
    }

    notifBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        notifDropdown.hidden ? openNotif() : closeNotif();
    });

    document.addEventListener('click', function (e) {
        if (!notifMenu.contains(e.target)) closeNotif();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeNotif();
    });

    function setBadge(unread) {
        if (unread > 0) {
            notifBadge.textContent = unread > 99 ? '99+' : unread;
            notifBadge.hidden = false;
        } else {
            notifBadge.hidden = true;
        }
    }

    function renderNotif(data) {
        setBadge(data.unread || 0);

        var items = data.notificaciones || [];
        if (items.length === 0) {
            notifList.innerHTML = '<div class="header-notif-empty">Sin notificaciones</div>';
            return;
        }

        notifList.innerHTML = items.map(function (n) {
            var icon  = iconMap[n.tipo] || '•';
            var cls   = 'header-notif-item' + (n.leida == 0 ? ' header-notif-item--unread' : '') + (n.url ? ' header-notif-item--link' : '');
            var attrs = 'data-notif-id="' + escHtml(String(n.id)) + '"'
                      + (n.url ? ' data-notif-url="' + escHtml(n.url) + '"' : '');
            var inner = '<span class="header-notif-icon header-notif-icon--' + escHtml(n.tipo) + '">' + icon + '</span>'
                      + '<div class="header-notif-body">'
                      + '<div class="header-notif-titulo">' + escHtml(n.titulo) + '</div>'
                      + '<div class="header-notif-msg">' + escHtml(n.mensaje) + '</div>'
                      + '<div class="header-notif-tiempo">' + escHtml(n.tiempo) + '</div>'
                      + '</div>';
            return '<div class="' + cls + '" ' + attrs + '>' + inner + '</div>';
        }).join('');
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function markOneRead(id) {
        var fd = new FormData();
        fd.append('id', id);
        fetch('index.php?action=notificaciones/read-one', {
            method: 'POST',
            credentials: 'same-origin',
            body: fd
        }).catch(function () {});
    }

    notifList.addEventListener('click', function (e) {
        var item = e.target.closest('[data-notif-id]');
        if (!item) return;

        var id  = item.getAttribute('data-notif-id');
        var url = item.getAttribute('data-notif-url');

        // Mark as read immediately in the DOM
        item.classList.remove('header-notif-item--unread');
        var currentUnread = parseInt(notifBadge.textContent, 10) || 0;
        if (currentUnread > 0) setBadge(currentUnread - 1);

        // Persist on server (fire-and-forget)
        if (id) markOneRead(id);

        // Navigate
        if (url) {
            closeNotif();
            window.location.href = url;
        }
    });

    function fetchNotif() {
        fetch('index.php?action=notificaciones/get', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(renderNotif)
            .catch(function () {});
    }

    if (readAllBtn) {
        readAllBtn.addEventListener('click', function (e) {
            e.stopPropagation();

            // Immediate DOM update — no waiting for server
            notifBadge.hidden = true;
            notifList.querySelectorAll('.header-notif-item--unread').forEach(function (el) {
                el.classList.remove('header-notif-item--unread');
            });

            // Persist on server in background
            fetch('index.php?action=notificaciones/read-all', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).catch(function () {});
        });
    }

    fetchNotif();
    setInterval(fetchNotif, 30000);
})();

(function () {
    var badge = document.getElementById('headerMsgBadge');
    if (!badge) return;

    function fetchMsgUnread() {
        fetch('index.php?action=mensajes/unread', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var n = parseInt(data.unread, 10) || 0;
                if (n > 0) {
                    badge.textContent = n > 99 ? '99+' : n;
                    badge.hidden = false;
                } else {
                    badge.hidden = true;
                }
            })
            .catch(function () {});
    }

    fetchMsgUnread();
    setInterval(fetchMsgUnread, 15000);
})();
<?php endif; ?>
</script>
