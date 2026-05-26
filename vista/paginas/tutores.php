<?php
$pageTitle = 'Tutores';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/flash.php';
require __DIR__ . '/../partials/auth-modal.php';
$user = authUser();
$selectedMateria = old('cita_materia');
$selectedModalidad = old('cita_modalidad', 'Virtual (Meet/Zoom)');
$tutores = $tutores ?? [];
$tutoresJson = json_encode($tutores, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($tutoresJson === false) {
    $tutoresJson = '[]';
}
?>

<section class="buscar-hero">
    <h2>Encuentra tu tutor ideal</h2>
    <p>Mas de 150 tutores listos para ayudarte</p>
    <div class="buscador-bar">
        <input type="text" id="searchInput" placeholder="Busca por nombre, materia o carrera...">
        <select id="filterMateria">
            <option value="">Todas las materias</option>
            <option value="calculo">Calculo</option>
            <option value="fisica">Fisica</option>
            <option value="programacion">Programacion</option>
            <option value="quimica">Quimica</option>
            <option value="estadistica">Estadistica</option>
            <option value="biologia">Biologia</option>
            <option value="derecho">Derecho</option>
            <option value="algebra">Algebra</option>
        </select>
        <select id="filterCarrera">
            <option value="">Todas las carreras</option>
            <option value="sistemas">Ing. de Sistemas</option>
            <option value="medicina">Medicina</option>
            <option value="derecho">Derecho</option>
            <option value="civil">Ing. Civil</option>
            <option value="administracion">Administracion</option>
            <option value="biologia">Biologia</option>
        </select>
        <button onclick="filtrarTutores()">Buscar</button>
    </div>
</section>

<section class="tutores-page">
    <div class="resultados-header">
        <p id="resultadosCount">Cargando tutores...</p>
        <select id="sortBy" onchange="filtrarTutores()">
            <option value="rating">Mejor calificacion</option>
            <option value="sesiones">Mas sesiones</option>
            <option value="nombre">Nombre A-Z</option>
        </select>
    </div>
    <div class="tutores-lista" id="tutoresLista"></div>
</section>

<div class="modal-overlay hidden" id="modalAgendar">
    <div class="modal">
        <button class="modal-close" onclick="cerrarModal()">X</button>
        <h3>Agendar sesion con <span id="modalNombreTutor"><?= e(old('cita_tutor_nombre')) ?></span></h3>
        <form method="post" action="index.php?action=citas/store">
            <input type="hidden" name="redirect_to" value="tutores">
            <input type="hidden" id="citaTutorId" name="cita_tutor_id" value="<?= e(old('cita_tutor_id')) ?>">
            <input type="hidden" id="citaTutorNombre" name="cita_tutor_nombre" value="<?= e(old('cita_tutor_nombre')) ?>">
            <div class="form-group">
                <label>Tu nombre completo *</label>
                <input type="text" name="cita_estudiante_nombre" placeholder="Nombre completo" value="<?= e(old('cita_estudiante_nombre', $user['nombre'] ?? '')) ?>" required>
            </div>
            <div class="form-group">
                <label>Correo *</label>
                <input type="email" name="cita_estudiante_correo" placeholder="nombre@unimagdalena.edu.co" value="<?= e(old('cita_estudiante_correo', $user['email'] ?? '')) ?>" required>
            </div>
            <div class="form-group">
                <label>Materia *</label>
                <select name="cita_materia" id="modalMateriaSelect" required data-selected="<?= e($selectedMateria) ?>">
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
                <label>Fecha *</label>
                <input type="date" name="cita_fecha" value="<?= e(old('cita_fecha')) ?>" required>
            </div>
            <div class="form-group">
                <label>Hora *</label>
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

<script>
window.tutoresData = <?= $tutoresJson ?>;
</script>

<script>
(function () {
    if (window.__agendarMateriasBound) return;
    window.__agendarMateriasBound = true;

    const select = document.getElementById("modalMateriaSelect");
    const inputTutorId = document.getElementById("citaTutorId");
    const inputTutorNombre = document.getElementById("citaTutorNombre");
    if (!select || !inputTutorId) return;

    const obtenerTutores = () => Array.isArray(window.tutoresData) ? window.tutoresData : [];
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

<?php require __DIR__ . '/../partials/footer.php'; ?>
