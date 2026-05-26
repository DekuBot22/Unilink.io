<?php
$pageTitle = 'Gestion de contenido';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/flash.php';
require __DIR__ . '/../partials/auth-modal.php';

$contenidos = $contenidos ?? [];
$tipoLabels = [
    'pdf' => 'PDF',
    'imagen' => 'Imagen',
    'video' => 'Video',
    'enlace' => 'Enlace',
    'recurso' => 'Recurso',
];
?>

<section class="admin-page">
    <div class="admin-hero">
        <div>
            <h2>Gestion de contenido</h2>
            <p>Administra y elimina recursos publicados por los tutores.</p>
        </div>
        <div class="admin-badge"><?= e((string) count($contenidos)) ?> recursos</div>
    </div>

    <div class="admin-content-grid">
        <?php if (!$contenidos): ?>
            <div class="admin-card admin-empty">
                <h3>Sin contenido</h3>
                <p>No hay recursos disponibles actualmente.</p>
                <a class="btn-contenido" href="index.php?page=admin">Volver al panel</a>
            </div>
        <?php else: ?>
            <?php foreach ($contenidos as $contenido): ?>
                <?php
                $tipoKey = strtolower((string) ($contenido['tipo'] ?? 'recurso'));
                if (!isset($tipoLabels[$tipoKey])) {
                    $tipoKey = 'recurso';
                }
                $tipoLabel = $tipoLabels[$tipoKey];
                $fechaLabel = (string) ($contenido['fecha'] ?? '');
                if ($fechaLabel !== '') {
                    $fecha = DateTime::createFromFormat('Y-m-d', $fechaLabel);
                    if ($fecha) {
                        $fechaLabel = $fecha->format('d/m/Y');
                    }
                }
                $estadoKey = strtolower((string) ($contenido['estado'] ?? 'pendiente'));
                if (!in_array($estadoKey, ['pendiente', 'aprobado'], true)) {
                    $estadoKey = 'pendiente';
                }
                $estadoLabel = $estadoKey === 'aprobado' ? 'Aprobado' : 'Pendiente';
                $descripcion = (string) ($contenido['descripcion'] ?? '');
                if ($descripcion === '') {
                    $descripcion = 'Recurso compartido por el tutor.';
                }
                $enlace = (string) ($contenido['enlace'] ?? '');
                $enlace = $enlace !== '' ? $enlace : '#';
                $titulo = (string) ($contenido['titulo'] ?? '');
                $tutorNombre = (string) ($contenido['tutor'] ?? '');
                $extension = strtolower((string) ($contenido['extension'] ?? ''));
                $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true);
                ?>
                <article class="contenido-card admin-content-card">
                    <div class="contenido-modulo-preview">
                        <?php if ($isImage): ?>
                            <img src="<?= e($enlace) ?>" alt="Vista previa de <?= e($titulo) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="contenido-modulo-preview-fallback contenido-thumb-<?= e($tipoKey) ?>">
                                <span><?= e($tipoLabel) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="contenido-card-body">
                        <span class="contenido-pill contenido-pill-<?= e($tipoKey) ?>"><?= e($tipoLabel) ?></span>
                        <span class="contenido-pill contenido-pill-estado contenido-pill-estado-<?= e($estadoKey) ?>"><?= e($estadoLabel) ?></span>
                        <h4><?= e($titulo) ?></h4>
                        <p class="contenido-desc"><?= e($descripcion) ?></p>
                        <div class="contenido-meta">
                            <span>Materia: <?= e((string) ($contenido['materia'] ?? '')) ?></span>
                            <span>Tema: <?= e((string) ($contenido['tema'] ?? '')) ?></span>
                            <span>Tutor: <?= e($tutorNombre) ?></span>
                            <span>Fecha: <?= e($fechaLabel) ?></span>
                        </div>
                    </div>
                    <div class="admin-content-actions">
                        <a class="btn-contenido btn-contenido-outline" href="<?= e($enlace) ?>" target="_blank" rel="noopener">Abrir</a>
                        <?php if ($estadoKey === 'pendiente'): ?>
                            <button
                                type="button"
                                class="btn-contenido"
                                data-approve-contenido
                                data-contenido-id="<?= e((string) ($contenido['id'] ?? '')) ?>"
                                data-titulo="<?= e($titulo) ?>"
                                data-tutor="<?= e($tutorNombre) ?>"
                            >
                                Aceptar
                            </button>
                        <?php endif; ?>
                        <button
                            type="button"
                            class="btn-contenido btn-contenido-outline"
                            data-delete-contenido
                            data-contenido-id="<?= e((string) ($contenido['id'] ?? '')) ?>"
                            data-titulo="<?= e($titulo) ?>"
                            data-tipo="<?= e($tipoLabel) ?>"
                            data-materia="<?= e((string) ($contenido['materia'] ?? '')) ?>"
                        >
                            Eliminar contenido
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<div class="modal-overlay hidden" id="modalEliminarContenido">
    <div class="modal">
        <button class="modal-close" type="button" onclick="cerrarModalEliminarContenido()">X</button>
        <h3>Eliminar contenido</h3>
        <p class="modal-text" id="modalEliminarContenidoDetalle"></p>
        <form method="post" action="index.php?action=admin/contenido/delete">
            <input type="hidden" name="contenido_id" id="modalEliminarContenidoId" value="">
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModalEliminarContenido()">Cancelar</button>
                <button type="submit" class="btn-submit btn-submit-danger">Eliminar contenido</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay hidden" id="modalAprobarContenido">
    <div class="modal">
        <button class="modal-close" type="button" onclick="cerrarModalAprobarContenido()">X</button>
        <h3>Aprobar contenido</h3>
        <p class="modal-text" id="modalAprobarContenidoDetalle"></p>
        <form method="post" action="index.php?action=admin/contenido/approve">
            <input type="hidden" name="contenido_id" id="modalAprobarContenidoId" value="">
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModalAprobarContenido()">Cancelar</button>
                <button type="submit" class="btn-submit">Aprobar contenido</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById("modalEliminarContenido");
    const overlayApprove = document.getElementById("modalAprobarContenido");
    if ((!overlay && !overlayApprove) || window.__adminContenidoDeleteBound) return;
    window.__adminContenidoDeleteBound = true;

    window.cerrarModalEliminarContenido = window.cerrarModalEliminarContenido || function () {
        overlay.classList.add("hidden");
    };

    window.cerrarModalAprobarContenido = window.cerrarModalAprobarContenido || function () {
        overlayApprove?.classList.add("hidden");
    };

    const detalle = document.getElementById("modalEliminarContenidoDetalle");
    const idInput = document.getElementById("modalEliminarContenidoId");
    const detalleAprobar = document.getElementById("modalAprobarContenidoDetalle");
    const idAprobar = document.getElementById("modalAprobarContenidoId");

    const abrir = (btn) => {
        if (!btn) return;

        const titulo = btn.dataset.titulo || "";
        const tipo = btn.dataset.tipo || "";
        const materia = btn.dataset.materia || "";
        const id = btn.dataset.contenidoId || "";

        if (detalle) {
            const meta = [tipo, materia].filter(Boolean).join(" · ");
            detalle.textContent = `${titulo}${meta ? " (" + meta + ")" : ""}. Esta accion no se puede deshacer.`.trim();
        }

        if (idInput) idInput.value = id;
        overlay.classList.remove("hidden");
    };

    document.querySelectorAll("[data-delete-contenido]").forEach((btn) => {
        btn.addEventListener("click", () => abrir(btn));
    });

    document.querySelectorAll("[data-approve-contenido]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const titulo = btn.dataset.titulo || "";
            const tutor = btn.dataset.tutor || "";
            const id = btn.dataset.contenidoId || "";
            if (detalleAprobar) {
                const detalleText = `${titulo}${tutor ? " · " + tutor : ""}`.trim();
                detalleAprobar.textContent = detalleText;
            }
            if (idAprobar) idAprobar.value = id;
            overlayApprove?.classList.remove("hidden");
        });
    });

    overlay.addEventListener("click", (event) => {
        if (event.target === overlay) {
            window.cerrarModalEliminarContenido();
        }
    });

    overlayApprove?.addEventListener("click", (event) => {
        if (event.target === overlayApprove) {
            window.cerrarModalAprobarContenido();
        }
    });
})();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
