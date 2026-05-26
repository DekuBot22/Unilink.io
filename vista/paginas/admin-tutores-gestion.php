<?php
$pageTitle = 'Gestion de tutores';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/flash.php';
require __DIR__ . '/../partials/auth-modal.php';

$tutores = $tutores ?? [];
?>

<section class="admin-page">
    <div class="admin-hero">
        <div>
            <h2>Gestion de tutores</h2>
            <p>Elimina tutores registrados cuando sea necesario.</p>
        </div>
        <div class="admin-badge"><?= e((string) count($tutores)) ?> tutores</div>
    </div>

    <div class="admin-requests admin-tutores-grid">
        <?php if (!$tutores): ?>
            <div class="admin-card admin-empty">
                <h3>Sin tutores</h3>
                <p>No hay tutores registrados actualmente.</p>
                <a class="btn-contenido" href="index.php?page=admin">Volver al panel</a>
            </div>
        <?php else: ?>
            <?php foreach ($tutores as $tutor): ?>
                <?php
                $materias = $tutor['materias'] ?? [];
                $materias = is_array($materias) ? $materias : [];
                ?>
                <article class="admin-card admin-tutor-card">
                    <div class="admin-request-header">
                        <div>
                            <h3><?= e((string) ($tutor['nombre'] ?? '')) ?></h3>
                            <p><?= e((string) ($tutor['carrera'] ?? '')) ?> · <?= e((string) ($tutor['semestre'] ?? '')) ?></p>
                        </div>
                        <span class="admin-request-date">⭐ <?= e((string) ($tutor['rating'] ?? 0)) ?></span>
                    </div>
                    <div class="admin-tutor-meta">
                        <span>Sesiones: <?= e((string) ($tutor['sesiones'] ?? 0)) ?></span>
                        <span>Materias: <?= e((string) count($materias)) ?></span>
                    </div>
                    <?php if ($materias): ?>
                        <div class="admin-tutor-tags">
                            <?php foreach ($materias as $materia): ?>
                                <span class="admin-tag"><?= e((string) $materia) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="admin-request-actions">
                        <button
                            type="button"
                            class="btn-contenido btn-contenido-outline"
                            data-delete-tutor
                            data-tutor-id="<?= e((string) ($tutor['id'] ?? '')) ?>"
                            data-nombre="<?= e((string) ($tutor['nombre'] ?? '')) ?>"
                            data-carrera="<?= e((string) ($tutor['carrera'] ?? '')) ?>"
                        >
                            Eliminar tutor
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<div class="modal-overlay hidden" id="modalEliminarTutor">
    <div class="modal">
        <button class="modal-close" type="button" onclick="cerrarModalEliminarTutor()">X</button>
        <h3>Eliminar tutor</h3>
        <p class="modal-text" id="modalEliminarTutorDetalle"></p>
        <form method="post" action="index.php?action=admin/tutores/delete">
            <input type="hidden" name="tutor_id" id="modalEliminarTutorId" value="">
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModalEliminarTutor()">Cancelar</button>
                <button type="submit" class="btn-submit btn-submit-danger">Eliminar tutor</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById("modalEliminarTutor");
    if (!overlay || window.__adminTutorDeleteBound) return;
    window.__adminTutorDeleteBound = true;

    window.cerrarModalEliminarTutor = window.cerrarModalEliminarTutor || function () {
        overlay.classList.add("hidden");
    };

    const detalle = document.getElementById("modalEliminarTutorDetalle");
    const idInput = document.getElementById("modalEliminarTutorId");

    const abrir = (btn) => {
        if (!btn) return;

        const nombre = btn.dataset.nombre || "";
        const carrera = btn.dataset.carrera || "";
        const id = btn.dataset.tutorId || "";

        if (detalle) {
            detalle.textContent = `${nombre}${carrera ? " · " + carrera : ""}. Esta accion no se puede deshacer.`.trim();
        }

        if (idInput) idInput.value = id;
        overlay.classList.remove("hidden");
    };

    document.querySelectorAll("[data-delete-tutor]").forEach((btn) => {
        btn.addEventListener("click", () => abrir(btn));
    });

    overlay.addEventListener("click", (event) => {
        if (event.target === overlay) {
            window.cerrarModalEliminarTutor();
        }
    });
})();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
