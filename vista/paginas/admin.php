<?php
$pageTitle = 'Panel administrador';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/flash.php';
require __DIR__ . '/../partials/auth-modal.php';

$user = $user ?? authUser();
$nombre = $user['nombre'] ?? 'Administrador';
?>

<section class="admin-page">
    <div class="admin-hero">
        <div>
            <h2>Panel administrador</h2>
            <p>Hola, <?= e($nombre) ?>. Desde aqui puedes gestionar roles y revisar el estado general de la plataforma.</p>
        </div>
        <div class="admin-badge">Acceso administrador</div>
    </div>

    <div class="admin-grid">
        <div class="admin-card">
            <h3>Gestion de roles</h3>
            <p>Asigna o retira permisos de administrador a los usuarios registrados.</p>
            <form class="admin-form" method="post" action="index.php?action=admin/promote">
                <div class="form-group">
                    <label for="adminEmail">Correo del usuario *</label>
                    <input id="adminEmail" name="admin_email" type="email" placeholder="correo@unimagdalena.edu.co" required>
                </div>
                <div class="form-group">
                    <label for="adminRole">Rol</label>
                    <select id="adminRole" name="admin_role" required>
                        <option value="estudiante">Estudiante</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Actualizar rol</button>
            </form>
        </div>
        <div class="admin-card">
            <h3>Banear usuario</h3>
            <p>Restringe el acceso de un usuario indicando el motivo del baneo.</p>
            <form class="admin-form" method="post" action="index.php?action=admin/ban">
                <div class="form-group">
                    <label for="banEmail">Correo del usuario *</label>
                    <input id="banEmail" name="ban_email" type="email" placeholder="correo@unimagdalena.edu.co" required>
                </div>
                <div class="form-group">
                    <label for="banMotivo">Motivo del baneo *</label>
                    <textarea id="banMotivo" name="ban_motivo" rows="3" maxlength="300" required></textarea>
                </div>
                <button type="submit" class="btn-submit btn-submit-danger">Banear usuario</button>
            </form>
        </div>
        <div class="admin-card">
            <h3>Activar usuario</h3>
            <p>Restablece el acceso de un usuario previamente baneado.</p>
            <form class="admin-form" method="post" action="index.php?action=admin/activate">
                <div class="form-group">
                    <label for="activateEmail">Correo del usuario *</label>
                    <input id="activateEmail" name="activate_email" type="email" placeholder="correo@unimagdalena.edu.co" required>
                </div>
                <button type="submit" class="btn-submit">Activar usuario</button>
            </form>
        </div>
        <div class="admin-card">
            <h3>Solicitudes de tutor</h3>
            <p>Aprueba o rechaza usuarios que desean convertirse en tutores.</p>
            <a class="btn-contenido" href="index.php?page=admin-tutores">Ver solicitudes</a>
        </div>
        <div class="admin-card">
            <h3>Gestion de tutores</h3>
            <p>Administra los tutores registrados y elimina perfiles si es necesario.</p>
            <a class="btn-contenido" href="index.php?page=admin-tutores-gestion">Ver tutores</a>
        </div>
        <div class="admin-card">
            <h3>Gestion de contenido</h3>
            <p>Elimina recursos publicados por tutores en la plataforma.</p>
            <a class="btn-contenido" href="index.php?page=admin-contenido">Ver contenido</a>
        </div>
        <div class="admin-card">
            <h3>Reportes de citas</h3>
            <p>Consulta estadisticas globales de las citas agendadas.</p>
            <a class="btn-contenido" href="index.php?page=admin-reportes">Ver reportes</a>
        </div>
        <div class="admin-card admin-card-soft">
            <h3>Estado general</h3>
            <ul class="admin-list">
                <li>Usuarios registrados: pendiente por integrar</li>
                <li>Contenido publicado: disponible en vista de contenido</li>
                <li>Solicitudes de tutor: por habilitar</li>
            </ul>
            <div class="admin-tip">
                Cuando tengamos el modulo de administracion completo, aqui veras los reportes en tiempo real.
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
