<?php
$pageTitle = 'Perfil Tutor';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/flash.php';
require __DIR__ . '/../partials/auth-modal.php';
$tutores = $tutores ?? [];
$tutoresJson = json_encode($tutores, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($tutoresJson === false) {
    $tutoresJson = '[]';
}
$user = authUser();
$selectedMateria = old('cita_materia');
$selectedModalidad = old('cita_modalidad', 'Virtual (Meet/Zoom)');
$perfilId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$redirectTo = $perfilId > 0 ? 'perfil-tutor&id=' . $perfilId : 'perfil-tutor';
?>

<section id="perfilPage" class="perfil-page"></section>

<!-- El botón de calificar ahora se inserta dentro del recuadro del perfil por JS -->

<div class="modal-overlay hidden" id="modalResena">
    <div class="modal">
        <button class="modal-close" onclick="cerrarModalResena()">X</button>
        <h3>Dejar calificación</h3>
        <form method="post" action="index.php?action=tutores/rate">
            <input type="hidden" name="tutor_id" id="resenaTutorId" value="<?= e($perfilId) ?>">
            <div class="form-group">
                <label>Calificación *</label>
                <div id="starPreview" class="star-preview" aria-hidden="true">
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star">★</span>
                </div>
                <div id="starGroup" class="star-group">
                    <label class="star-option"><input type="radio" name="rating" value="5"><span class="star-icon">★</span><span class="star-value">5</span></label>
                    <label class="star-option"><input type="radio" name="rating" value="4"><span class="star-icon">★</span><span class="star-value">4</span></label>
                    <label class="star-option"><input type="radio" name="rating" value="3"><span class="star-icon">★</span><span class="star-value">3</span></label>
                    <label class="star-option"><input type="radio" name="rating" value="2"><span class="star-icon">★</span><span class="star-value">2</span></label>
                    <label class="star-option"><input type="radio" name="rating" value="1"><span class="star-icon">★</span><span class="star-value">1</span></label>
                </div>
            </div>
            <!-- No se permiten reseñas textuales: solo calificación -->
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModalResena()">Volver</button>
                <button type="submit" class="btn-submit">Enviar calificación</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    window.abrirModalResena = function () {
        document.getElementById('modalResena')?.classList.remove('hidden');
    };

    window.cerrarModalResena = function () {
        document.getElementById('modalResena')?.classList.add('hidden');
    };
    // Setup star hover preview and selection
    function setupStarPreview() {
        const modal = document.getElementById('modalResena');
        if (!modal) return;
        const preview = modal.querySelector('#starPreview');
        const options = Array.from(modal.querySelectorAll('.star-option'));

        function renderPreview(n) {
            if (!preview) return;
            const stars = preview.querySelectorAll('.star');
            stars.forEach((s, i) => s.classList.toggle('active', i < n));
        }

        options.forEach(opt => {
            const input = opt.querySelector('input[name="rating"]');
            const val = parseInt(input.value, 10) || 0;
            opt.addEventListener('mouseenter', () => renderPreview(val));
            opt.addEventListener('mouseleave', () => {
                const sel = modal.querySelector('input[name="rating"]:checked');
                renderPreview(sel ? parseInt(sel.value, 10) : 0);
            });
            input.addEventListener('change', () => renderPreview(val));
        });

        // ensure preview reflects current selection when modal opens
        const observer = new MutationObserver(() => {
            const hidden = modal.classList.contains('hidden');
            if (!hidden) {
                const sel = modal.querySelector('input[name="rating"]:checked');
                renderPreview(sel ? parseInt(sel.value, 10) : 0);
            }
        });
        observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupStarPreview);
    } else {
        setupStarPreview();
    }
})();
</script>

<div class="modal-overlay hidden" id="modalAgendar">
    <div class="modal">
        <button class="modal-close" onclick="cerrarModal()">X</button>
        <h3>Agendar sesion con <span id="modalNombreTutor"><?= e(old('cita_tutor_nombre')) ?></span></h3>
        <form method="post" action="index.php?action=citas/store">
            <input type="hidden" name="redirect_to" value="<?= e($redirectTo) ?>">
            <input type="hidden" id="citaTutorId" name="cita_tutor_id" value="<?= e(old('cita_tutor_id')) ?>">
            <input type="hidden" id="citaTutorNombre" name="cita_tutor_nombre" value="<?= e(old('cita_tutor_nombre')) ?>">
            <div class="form-group">
                <label>Tu nombre</label>
                <input type="text" name="cita_estudiante_nombre" value="<?= e(old('cita_estudiante_nombre', $user['nombre'] ?? '')) ?>" required>
            </div>
            <div class="form-group">
                <label>Correo *</label>
                <input type="email" name="cita_estudiante_correo" placeholder="nombre@unimagdalena.edu.co" value="<?= e(old('cita_estudiante_correo', $user['email'] ?? '')) ?>" required>
            </div>
            <div class="form-group">
                <label>Materia</label>
                <select id="modalMateriaSelect" name="cita_materia" required data-selected="<?= e($selectedMateria) ?>">
                    <option value="">Selecciona materia</option>
                </select>
            </div>
            <div class="form-group">
                <label>Modalidad *</label>
                <select name="cita_modalidad" required>
                    <option <?= $selectedModalidad === 'Videollamada UniLink' ? 'selected' : '' ?>>Videollamada UniLink</option>
                    <option <?= $selectedModalidad === 'Virtual (Meet/Zoom)' ? 'selected' : '' ?>>Virtual (Meet/Zoom)</option>
                    <option <?= $selectedModalidad === 'Presencial' ? 'selected' : '' ?>>Presencial</option>
                    <option <?= $selectedModalidad === 'Ambas modalidades' ? 'selected' : '' ?>>Ambas modalidades</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha</label>
                <input type="date" name="cita_fecha" value="<?= e(old('cita_fecha')) ?>" required>
            </div>
            <div class="form-group">
                <label>Hora</label>
                <input type="time" name="cita_hora" value="<?= e(old('cita_hora')) ?>" required>
            </div>
            <p class="agenda-error" id="agendaError"></p>
            <div class="agenda-preview">
                <div class="agenda-preview-title">Agenda disponible</div>
                <div id="modalAgendaList" class="agenda-preview-list"></div>
            </div>
            <button type="submit" class="btn-submit">Confirmar sesion</button>
        </form>
    </div>
</div>

<div class="modal-overlay hidden" id="modalResenaTexto">
    <div class="modal">
        <button class="modal-close" onclick="cerrarModalResenaTexto()">X</button>
        <h3>Hacer reseña</h3>
        <form method="post" action="index.php?action=tutores/resena">
            <input type="hidden" name="tutor_id" id="resenaTutorTextoId" value="<?= e($perfilId) ?>">
            <div class="form-group">
                <label>Tu reseña *</label>
                <textarea name="resena_texto" id="resenaTextoArea" rows="5" maxlength="1000" placeholder="Comparte tu experiencia con este tutor..." required style="width:100%;resize:vertical;padding:10px;border:1px solid #dde;border-radius:8px;font-size:14px;font-family:inherit;"></textarea>
                <small style="color:#888;font-size:12px;">Máximo 1000 caracteres</small>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModalResenaTexto()">Cancelar</button>
                <button type="submit" class="btn-submit">Publicar reseña</button>
            </div>
        </form>
    </div>
</div>

<script>
window.tutoresDB = <?= $tutoresJson ?>;
window.yaCalificado = <?= json_encode($yaCalificado ?? false) ?>;
window.yaReseno = <?= json_encode($yaReseno ?? false) ?>;
window.resenasDB = <?= json_encode(array_map(function($r) {
    return [
        'nombre'  => $r['nombre_usuario'],
        'texto'   => $r['texto'],
        'fecha'   => date('d/m/Y', strtotime($r['creado_en'])),
    ];
}, $resenas ?? []), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<script>
window.abrirModalResenaTexto = function(tutorId) {
    document.getElementById('resenaTutorTextoId').value = tutorId;
    document.getElementById('resenaTextoArea').value = '';
    document.getElementById('modalResenaTexto')?.classList.remove('hidden');
};
window.cerrarModalResenaTexto = function() {
    document.getElementById('modalResenaTexto')?.classList.add('hidden');
};
document.getElementById('modalResenaTexto')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalResenaTexto();
});
</script>

<script>
(function () {
    if (window.__agendarMateriasBound) return;
    window.__agendarMateriasBound = true;

    const select = document.getElementById("modalMateriaSelect");
    const inputTutorId = document.getElementById("citaTutorId");
    const inputTutorNombre = document.getElementById("citaTutorNombre");
    if (!select || !inputTutorId) return;

    const obtenerTutores = () => Array.isArray(window.tutoresDB) ? window.tutoresDB : [];
    const buscarTutor = (id, nombre) => {
        const idNumero = Number(id);
        if (Number.isFinite(idNumero)) {
            const porId = obtenerTutores().find((tutor) => Number(tutor.id) === idNumero);
            if (porId) return porId;
        }

        const texto = String(nombre || "").trim().toLowerCase();
        if (!texto) return null;
        return obtenerTutores().find((tutor) => String(tutor.nombre || "").toLowerCase() === texto) || null;
    };

    const llenarMaterias = (materias, selected) => {
        const lista = Array.isArray(materias) ? materias.slice() : [];
        select.innerHTML = "";

        const placeholder = document.createElement("option");
        placeholder.value = "";
        placeholder.textContent = "Selecciona materia";
        select.appendChild(placeholder);

        lista.forEach((materia) => {
            const option = document.createElement("option");
            option.value = materia;
            option.textContent = materia;
            select.appendChild(option);
        });

        if (selected) {
            select.value = selected;
        }
    };

    const actualizarMaterias = () => {
        const tutor = buscarTutor(inputTutorId.value, inputTutorNombre?.value);
        const materias = tutor && Array.isArray(tutor.materias) ? tutor.materias : [];
        const selected = select.dataset.selected || "";
        llenarMaterias(materias, selected);
    };

    document.addEventListener("click", (event) => {
        const btn = event.target.closest("[data-agendar], .btn-agendar, .btn-agendar-perfil");
        if (!btn) return;
        setTimeout(actualizarMaterias, 0);
    });
})();
</script>

<?php
$citasConfirmadas = $citasConfirmadas ?? [];
$yaUnidoIds       = $yaUnidoIds ?? [];

if ($perfilId > 0 && !empty($citasConfirmadas)):
?>
<section class="sesiones-section">
    <div class="sesiones-container">
        <h2 class="sesiones-title">Proximas sesiones abiertas</h2>
        <p class="sesiones-sub">Sesiones confirmadas por el tutor. Puedes unirte y compartir la monitoria con otros estudiantes.</p>
        <div class="sesiones-grid">
            <?php foreach ($citasConfirmadas as $sesion):
                $sesionId  = (int) $sesion['id'];
                $esCreador = $user && (int) ($sesion['usuario_id'] ?? -1) === (int) $user['id'];
                $yaUnido   = $esCreador || in_array($sesionId, $yaUnidoIds, true);
                $enlace    = trim((string) ($sesion['enlace_reunion'] ?? ''));
                $total     = (int) ($sesion['num_participantes'] ?? 0) + 1;
                $fecha     = date('d/m/Y', strtotime((string) $sesion['fecha']));
                $hora      = substr((string) ($sesion['hora'] ?? ''), 0, 5);
            ?>
            <div class="sesion-card <?= $yaUnido ? 'sesion-card--joined' : '' ?>">
                <div class="sesion-card-header">
                    <span class="sesion-materia"><?= e($sesion['materia']) ?></span>
                    <span class="sesion-inscritos">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <?= $total ?> inscrito<?= $total !== 1 ? 's' : '' ?>
                    </span>
                </div>
                <div class="sesion-info">
                    <span class="sesion-info-item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <?= e($fecha) ?> &mdash; <?= e($hora) ?>
                    </span>
                    <span class="sesion-info-item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?= e($sesion['modalidad']) ?>
                    </span>
                </div>

                <?php if ($yaUnido && $enlace !== ''): ?>
                    <a href="<?= e($enlace) ?>" target="_blank" rel="noopener noreferrer" class="sesion-enlace">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        Acceder a la sesion
                    </a>
                <?php elseif ($yaUnido): ?>
                    <p class="sesion-enlace-pending">El tutor compartira el enlace pronto</p>
                <?php endif; ?>

                <div class="sesion-actions">
                    <?php if (!$user): ?>
                        <button class="btn-unirse" type="button" data-open-auth>Inicia sesion para unirte</button>
                    <?php elseif ($yaUnido): ?>
                        <button class="btn-unirse btn-unirse--joined" type="button" disabled>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Ya estas inscrito
                        </button>
                    <?php else: ?>
                        <form method="post" action="index.php?action=tutores/unirse">
                            <input type="hidden" name="cita_id"  value="<?= $sesionId ?>">
                            <input type="hidden" name="tutor_id" value="<?= e($perfilId) ?>">
                            <button class="btn-unirse" type="submit">Unirse a esta sesion</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
