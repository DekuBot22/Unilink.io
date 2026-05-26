<?php
$pageTitle = 'Reportes de citas';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/flash.php';
require __DIR__ . '/../partials/auth-modal.php';

$summary = $summary ?? [];
$totals = $summary['totals'] ?? [];
$total = (int) ($totals['total'] ?? 0);
$pendientes = (int) ($totals['pendientes'] ?? 0);
$canceladas = (int) ($totals['canceladas'] ?? 0);
$completadas = (int) ($totals['completadas'] ?? 0);
$promedioCal = $totals['promedio_calificacion'] ?? null;
$promedioLabel = $promedioCal !== null ? number_format((float) $promedioCal, 1) . ' / 5' : 'Sin calificaciones';
$porcentaje = static function (int $count, int $total): string {
    if ($total <= 0) {
        return '0.0';
    }

    return number_format(($count / $total) * 100, 1);
};

$modalidadTop = $summary['modalidad_top'] ?? [];
$modalidadNombre = (string) ($modalidadTop['modalidad'] ?? 'Sin datos');
$modalidadTotal = (int) ($modalidadTop['total'] ?? 0);
$modalidadPct = $porcentaje($modalidadTotal, $total);

$tutorTop = $summary['tutor_top'] ?? [];
$tutorNombre = (string) ($tutorTop['tutor_nombre'] ?? 'Sin datos');
$tutorTotal = (int) ($tutorTop['total'] ?? 0);
?>

<section class="admin-page">
    <div class="admin-hero">
        <div>
            <h2>Reportes de citas</h2>
            <p>Resumen general de citas, calificaciones y modalidades mas usadas.</p>
        </div>
        <div class="admin-badge">Total: <?= e((string) $total) ?> citas</div>
    </div>

    <div class="admin-grid">
        <div class="admin-card">
            <h3>Estado de citas</h3>
            <ul class="admin-list">
                <li>Total: <?= e((string) $total) ?></li>
                <li>Pendientes: <?= e((string) $pendientes) ?> (<?= e($porcentaje($pendientes, $total)) ?>%)</li>
                <li>Completadas: <?= e((string) $completadas) ?> (<?= e($porcentaje($completadas, $total)) ?>%)</li>
                <li>Canceladas: <?= e((string) $canceladas) ?> (<?= e($porcentaje($canceladas, $total)) ?>%)</li>
            </ul>
        </div>
        <div class="admin-card">
            <h3>Calificaciones</h3>
            <p>Promedio total de calificaciones en citas completadas.</p>
            <p><strong><?= e($promedioLabel) ?></strong></p>
        </div>
        <div class="admin-card">
            <h3>Modalidad mas usada</h3>
            <p>Modalidad con mayor cantidad de citas registradas.</p>
            <ul class="admin-list">
                <li>Modalidad: <?= e($modalidadNombre) ?></li>
                <li>Citas: <?= e((string) $modalidadTotal) ?> (<?= e($modalidadPct) ?>%)</li>
            </ul>
        </div>
        <div class="admin-card">
            <h3>Tutor con mas citas</h3>
            <p>Mayor volumen de sesiones agendadas.</p>
            <ul class="admin-list">
                <li>Tutor: <?= e($tutorNombre) ?></li>
                <li>Citas: <?= e((string) $tutorTotal) ?></li>
            </ul>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
