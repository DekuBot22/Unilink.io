<?php
$pageTitle = 'Solicitudes de tutor';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/flash.php';
require __DIR__ . '/../partials/auth-modal.php';

$postulaciones = $postulaciones ?? [];
?>

<section class="admin-page">
    <div class="admin-hero">
        <div>
            <h2>Solicitudes de tutor</h2>
            <p>Revisa, aprueba o rechaza las postulaciones recibidas.</p>
        </div>
        <div class="admin-badge"><?= e((string) count($postulaciones)) ?> pendientes</div>
    </div>

    <div class="admin-requests">
        <?php if (!$postulaciones): ?>
            <div class="admin-card admin-empty">
                <h3>Sin solicitudes</h3>
                <p>No hay postulaciones pendientes por revisar.</p>
                <a class="btn-contenido" href="index.php?page=admin">Volver al panel</a>
            </div>
        <?php else: ?>
            <?php foreach ($postulaciones as $postulacion): ?>
                <?php
                $fecha = (string) ($postulacion['creado_en'] ?? '');
                $fechaLabel = $fecha;
                if ($fecha !== '') {
                    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $fecha) ?: DateTime::createFromFormat('Y-m-d', $fecha);
                    if ($dt) {
                        $fechaLabel = $dt->format('d/m/Y H:i');
                    }
                }

                $materiasRaw = (string) ($postulacion['materia'] ?? '');
                $materiasList = [];
                if ($materiasRaw !== '') {
                    $decoded = json_decode($materiasRaw, true);
                    if (is_array($decoded)) {
                        $materiasList = array_values(array_filter(array_map('strval', $decoded)));
                    } else {
                        $materiasList = [$materiasRaw];
                    }
                }
                $materiasLabel = $materiasList ? implode(', ', $materiasList) : 'Sin materias';
                ?>
                <article class="admin-card admin-request-card">
                    <div class="admin-request-header">
                        <div>
                            <h3><?= e((string) ($postulacion['nombre'] ?? '')) ?></h3>
                            <p><?= e((string) ($postulacion['correo'] ?? '')) ?> · <?= e((string) ($postulacion['telefono'] ?? '')) ?></p>
                        </div>
                        <span class="admin-request-date"><?= e($fechaLabel) ?></span>
                    </div>
                    <div class="admin-request-grid">
                        <div>
                            <span class="admin-request-label">Codigo</span>
                            <strong><?= e((string) ($postulacion['codigo'] ?? '')) ?></strong>
                        </div>
                        <div>
                            <span class="admin-request-label">Carrera</span>
                            <strong><?= e((string) ($postulacion['carrera'] ?? '')) ?></strong>
                        </div>
                        <div>
                            <span class="admin-request-label">Semestre</span>
                            <strong><?= e((string) ($postulacion['semestre'] ?? '')) ?></strong>
                        </div>
                        <div>
                            <span class="admin-request-label">Materia</span>
                            <strong><?= e($materiasLabel) ?></strong>
                        </div>
                        <div>
                            <span class="admin-request-label">Promedio</span>
                            <strong><?= e((string) ($postulacion['promedio'] ?? '')) ?></strong>
                        </div>
                        <div>
                            <span class="admin-request-label">Modalidad</span>
                            <strong><?= e((string) ($postulacion['modalidad'] ?? '')) ?></strong>
                        </div>
                    </div>
                    <div class="admin-request-note">
                        <strong>Motivacion</strong>
                        <p><?= e((string) ($postulacion['motivacion'] ?? '')) ?></p>
                    </div>
                    <div class="admin-request-actions">
                        <button
                            type="button"
                            class="btn-contenido btn-contenido-outline"
                            data-reject
                            data-postulacion-id="<?= e((string) ($postulacion['id'] ?? '')) ?>"
                            data-nombre="<?= e((string) ($postulacion['nombre'] ?? '')) ?>"
                            data-correo="<?= e((string) ($postulacion['correo'] ?? '')) ?>"
                            data-carrera="<?= e((string) ($postulacion['carrera'] ?? '')) ?>"
                            data-promedio="<?= e((string) ($postulacion['promedio'] ?? '')) ?>"
                        >
                            Rechazar
                        </button>
                        <button
                            type="button"
                            class="btn-contenido"
                            data-approve
                            data-postulacion-id="<?= e((string) ($postulacion['id'] ?? '')) ?>"
                            data-nombre="<?= e((string) ($postulacion['nombre'] ?? '')) ?>"
                            data-correo="<?= e((string) ($postulacion['correo'] ?? '')) ?>"
                            data-carrera="<?= e((string) ($postulacion['carrera'] ?? '')) ?>"
                            data-promedio="<?= e((string) ($postulacion['promedio'] ?? '')) ?>"
                        >
                            Aprobar
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<div class="modal-overlay hidden" id="modalAprobarTutor">
    <div class="modal">
        <button class="modal-close" type="button" onclick="cerrarModalAprobarTutor()">X</button>
        <h3>Aprobar tutor</h3>
        <p class="modal-text" id="modalAprobarDetalle"></p>
        <form method="post" action="index.php?action=admin/tutores/approve">
            <input type="hidden" name="postulacion_id" id="modalAprobarId" value="">
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModalAprobarTutor()">Cancelar</button>
                <button type="submit" class="btn-submit">Aprobar tutor</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay hidden" id="modalRechazarTutor">
    <div class="modal">
        <button class="modal-close" type="button" onclick="cerrarModalRechazarTutor()">X</button>
        <h3>Rechazar solicitud</h3>
        <p class="modal-text" id="modalRechazarDetalle"></p>
        <form method="post" action="index.php?action=admin/tutores/reject">
            <input type="hidden" name="postulacion_id" id="modalRechazarId" value="">
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModalRechazarTutor()">Cancelar</button>
                <button type="submit" class="btn-submit btn-submit-danger">Rechazar solicitud</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById("modalAprobarTutor");
    const overlayReject = document.getElementById("modalRechazarTutor");
    if ((!overlay && !overlayReject) || window.__adminApproveBound) return;
    window.__adminApproveBound = true;

    window.cerrarModalAprobarTutor = window.cerrarModalAprobarTutor || function () {
        overlay?.classList.add("hidden");
    };

    window.cerrarModalRechazarTutor = window.cerrarModalRechazarTutor || function () {
        overlayReject?.classList.add("hidden");
    };

    const detalle = document.getElementById("modalAprobarDetalle");
    const idInput = document.getElementById("modalAprobarId");
    const detalleReject = document.getElementById("modalRechazarDetalle");
    const idReject = document.getElementById("modalRechazarId");

    const buildDetalle = (btn) => {
        const nombre = btn.dataset.nombre || "";
        const correo = btn.dataset.correo || "";
        const carrera = btn.dataset.carrera || "";
        const promedio = btn.dataset.promedio || "";
        const lineaCarrera = carrera ? `Carrera: ${carrera}` : "";
        const lineaPromedio = promedio ? `Promedio: ${promedio}` : "";
        return `${nombre} (${correo}) ${lineaCarrera}${lineaCarrera && lineaPromedio ? ' · ' : ''}${lineaPromedio}`.trim();
    };

    const abrir = (btn) => {
        if (!btn) return;

        if (detalle) {
            detalle.textContent = buildDetalle(btn);
        }

        if (idInput) idInput.value = btn.dataset.postulacionId || "";
        overlay?.classList.remove("hidden");
    };

    const abrirRechazo = (btn) => {
        if (!btn || !overlayReject) return;

        if (detalleReject) {
            detalleReject.textContent = buildDetalle(btn);
        }

        if (idReject) idReject.value = btn.dataset.postulacionId || "";
        overlayReject.classList.remove("hidden");
    };

    document.querySelectorAll("[data-approve]").forEach((btn) => {
        btn.addEventListener("click", () => abrir(btn));
    });

    document.querySelectorAll("[data-reject]").forEach((btn) => {
        btn.addEventListener("click", () => abrirRechazo(btn));
    });

    overlay?.addEventListener("click", (event) => {
        if (event.target === overlay) {
            window.cerrarModalAprobarTutor();
        }
    });

    overlayReject?.addEventListener("click", (event) => {
        if (event.target === overlayReject) {
            window.cerrarModalRechazarTutor();
        }
    });
})();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
