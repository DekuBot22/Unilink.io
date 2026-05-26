<?php
/** @var string $iframeUrl */
/** @var string $destino */
/** @var array  $cita */
/** @var string $pageTitle */

$materia     = (string) ($cita['materia']     ?? '');
$tutorNombre = (string) ($cita['tutor_nombre'] ?? '');
$fechaCita   = (string) ($cita['fecha']       ?? '');
$horaCita    = (string) ($cita['hora']        ?? '');
$horaCortaF  = strlen($horaCita) === 8 ? substr($horaCita, 0, 5) : $horaCita;
$fechaLabel  = '';
if ($fechaCita !== '') {
    $dt = DateTime::createFromFormat('Y-m-d', $fechaCita);
    if ($dt) $fechaLabel = $dt->format('d/m/Y');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - UniLink</title>
    <link rel="icon" type="image/png" href="img/LogoProyecto.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <?php
    $cssPath    = __DIR__ . '/../../css/styles.css';
    $cssVersion = file_exists($cssPath) ? (string) filemtime($cssPath) : (string) time();
    ?>
    <link rel="stylesheet" href="css/styles.css?v=<?= e($cssVersion) ?>">
    <style>
        /* Override body margin for standalone call page */
        body { margin: 0; padding: 0; overflow: hidden; background: #0d1117; }
    </style>
</head>
<body>

<div class="videollamada-page">
    <div class="videollamada-topbar">
        <div class="videollamada-info">
            <span class="videollamada-badge">Videollamada UniLink</span>
            <div class="videollamada-meta">
                <strong><?= e($materia) ?></strong>
                <span>
                    <?= $tutorNombre !== '' ? e($tutorNombre) . ' &middot; ' : '' ?>
                    <?= $fechaLabel !== '' ? e($fechaLabel) : '' ?>
                    <?= $horaCortaF !== '' ? ' ' . e($horaCortaF) : '' ?>
                </span>
            </div>
        </div>
        <a href="<?= e($destino) ?>" class="videollamada-back">&#x2715; Salir de la sala</a>
    </div>

    <div class="jitsi-container">
        <iframe
            src="<?= e($iframeUrl) ?>"
            allow="camera; microphone; fullscreen; display-capture; autoplay; clipboard-write"
            allowfullscreen
        ></iframe>
    </div>
</div>

</body>
</html>
