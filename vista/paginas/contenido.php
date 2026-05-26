<?php
$pageTitle = 'Contenido';
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
$contenidosJson = json_encode($contenidos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($contenidosJson === false) {
    $contenidosJson = '[]';
}
$normalizar = function (string $value): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/\s+/', '-', $value);
    return $value === null ? '' : $value;
};
?>

<section class="contenido-page">
    <div class="contenido-hero">
        <div>
            <h2>Contenido de tutores</h2>
            <p>Explora recursos creados por tutores para apoyar tus sesiones y estudios.</p>
        </div>
        <div class="contenido-hero-tags">
            <span class="contenido-pill contenido-pill-pdf">PDF</span>
            <span class="contenido-pill contenido-pill-imagen">Imagen</span>
            <span class="contenido-pill contenido-pill-video">Video</span>
            <span class="contenido-pill contenido-pill-enlace">Enlace</span>
        </div>
    </div>

    <div class="contenido-filters">
        <div class="contenido-filter-grid">
            <label class="contenido-filter">
                <span>Tipo</span>
                <select id="contenidoFiltroTipo">
                    <option value="">Todos</option>
                </select>
            </label>
            <label class="contenido-filter">
                <span>Materia</span>
                <select id="contenidoFiltroMateria">
                    <option value="">Todas</option>
                </select>
            </label>
            <label class="contenido-filter">
                <span>Tema</span>
                <select id="contenidoFiltroTema">
                    <option value="">Todos</option>
                </select>
            </label>
            <button type="button" class="btn-contenido btn-contenido-outline" id="contenidoReset">Limpiar filtros</button>
        </div>
        <p class="contenido-count" id="contenidoCount">Mostrando 0 recursos</p>
    </div>

    <?php if (!$contenidos): ?>
        <div class="contenido-empty">
            <h3>Sin contenido disponible</h3>
            <p>Muy pronto los tutores compartiran sus recursos aqui.</p>
        </div>
    <?php else: ?>
        <div class="contenido-grid">
            <?php foreach ($contenidos as $contenido): ?>
                <?php
                $tipoKey = strtolower((string) ($contenido['tipo'] ?? 'recurso'));
                if (!isset($tipoLabels[$tipoKey])) {
                    $tipoKey = 'recurso';
                }
                $tipoLabel = $tipoLabels[$tipoKey];
                $materia = (string) ($contenido['materia'] ?? '');
                $tema = (string) ($contenido['tema'] ?? '');
                $enlace = (string) ($contenido['enlace'] ?? '');
                $enlace = $enlace !== '' ? $enlace : '#';
                $archivoNombre = (string) ($contenido['archivo_nombre'] ?? 'recurso');
                $materiaKey = $normalizar($materia);
                $temaKey = $normalizar($tema);
                $fechaLabel = (string) ($contenido['fecha'] ?? '');
                if ($fechaLabel !== '') {
                    $fecha = DateTime::createFromFormat('Y-m-d', $fechaLabel);
                    if ($fecha) {
                        $fechaLabel = $fecha->format('d/m/Y');
                    }
                }
                if ($fechaLabel === '') {
                    $fechaLabel = 'Sin fecha';
                }
                $titulo = (string) ($contenido['titulo'] ?? '');
                $tutorNombre = (string) ($contenido['tutor'] ?? '');
                $extension = strtolower((string) ($contenido['extension'] ?? ''));
                $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true);
                ?>
                <article class="contenido-card contenido-modulo" data-tipo="<?= e($tipoKey) ?>" data-materia="<?= e($materiaKey) ?>" data-tema="<?= e($temaKey) ?>">
                    <div class="contenido-card-body contenido-modulo-body">
                        <div class="contenido-modulo-preview">
                            <?php if ($isImage): ?>
                                <img src="<?= e($enlace) ?>" alt="Vista previa de <?= e($titulo) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="contenido-modulo-preview-fallback contenido-thumb-<?= e($tipoKey) ?>">
                                    <span><?= e($tipoLabel) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="contenido-modulo-content">
                            <div class="contenido-modulo-top">
                                <div class="contenido-modulo-type">
                                    <span class="contenido-modulo-label">Tipo</span>
                                    <span class="contenido-pill contenido-pill-<?= e($tipoKey) ?>"><?= e($tipoLabel) ?></span>
                                </div>
                                <span class="contenido-modulo-date"><?= e($fechaLabel) ?></span>
                            </div>
                            <h4 class="contenido-modulo-title"><?= e($titulo) ?></h4>
                            <div class="contenido-modulo-info">
                                <div><span class="contenido-modulo-label">Tutor:</span><?= e($tutorNombre) ?></div>
                                <div><span class="contenido-modulo-label">Materia:</span><?= e($materia) ?></div>
                                <div><span class="contenido-modulo-label">Tema:</span><?= e($tema) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="contenido-actions">
                        <a class="btn-contenido" href="<?= e($enlace) ?>" target="_blank" rel="noopener">Abrir recurso</a>
                        <a class="btn-contenido btn-contenido-outline" href="<?= e($enlace) ?>" download="<?= e($archivoNombre) ?>">Descargar</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script>
window.contenidosData = <?= $contenidosJson ?>;
</script>

<script>
(function () {
    const data = Array.isArray(window.contenidosData) ? window.contenidosData : [];
    const selectTipo = document.getElementById("contenidoFiltroTipo");
    const selectMateria = document.getElementById("contenidoFiltroMateria");
    const selectTema = document.getElementById("contenidoFiltroTema");
    const resetBtn = document.getElementById("contenidoReset");
    const count = document.getElementById("contenidoCount");
    const cards = Array.from(document.querySelectorAll(".contenido-card"));

    if (!selectTipo || !selectMateria || !selectTema || !cards.length) {
        return;
    }

    const normalizar = (value) => String(value || "").toLowerCase().trim().replace(/\s+/g, "-");

    const mapTipo = {};
    const mapMateria = {};
    const mapTema = {};

    data.forEach((item) => {
        const tipoKey = normalizar(item.tipo);
        const materiaKey = normalizar(item.materia);
        const temaKey = normalizar(item.tema);

        if (tipoKey && !mapTipo[tipoKey]) {
            mapTipo[tipoKey] = String(item.tipo || tipoKey);
        }
        if (materiaKey && !mapMateria[materiaKey]) {
            mapMateria[materiaKey] = String(item.materia || materiaKey);
        }
        if (temaKey && !mapTema[temaKey]) {
            mapTema[temaKey] = String(item.tema || temaKey);
        }
    });

    const poblarSelect = (select, mapa, placeholder) => {
        select.innerHTML = "";
        const first = document.createElement("option");
        first.value = "";
        first.textContent = placeholder;
        select.appendChild(first);

        Object.keys(mapa)
            .sort((a, b) => mapa[a].localeCompare(mapa[b]))
            .forEach((key) => {
                const option = document.createElement("option");
                option.value = key;
                option.textContent = mapa[key];
                select.appendChild(option);
            });
    };

    poblarSelect(selectTipo, mapTipo, "Todos");
    poblarSelect(selectMateria, mapMateria, "Todas");
    poblarSelect(selectTema, mapTema, "Todos");

    const aplicarFiltros = () => {
        const filtroTipo = selectTipo.value;
        const filtroMateria = selectMateria.value;
        const filtroTema = selectTema.value;
        let visible = 0;

        cards.forEach((card) => {
            const matchTipo = !filtroTipo || card.dataset.tipo === filtroTipo;
            const matchMateria = !filtroMateria || card.dataset.materia === filtroMateria;
            const matchTema = !filtroTema || card.dataset.tema === filtroTema;
            const mostrar = matchTipo && matchMateria && matchTema;
            card.style.display = mostrar ? "" : "none";
            if (mostrar) {
                visible += 1;
            }
        });

        if (count) {
            count.textContent = `Mostrando ${visible} recurso${visible === 1 ? "" : "s"}`;
        }
    };

    selectTipo.addEventListener("change", aplicarFiltros);
    selectMateria.addEventListener("change", aplicarFiltros);
    selectTema.addEventListener("change", aplicarFiltros);

    resetBtn?.addEventListener("click", () => {
        selectTipo.value = "";
        selectMateria.value = "";
        selectTema.value = "";
        aplicarFiltros();
    });

    aplicarFiltros();
})();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
