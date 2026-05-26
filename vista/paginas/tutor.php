<?php
$pageTitle = 'Panel Tutor';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/flash.php';
require __DIR__ . '/../partials/auth-modal.php';

$user = $user ?? authUser();
$fotoPerfil = ($user['foto_perfil'] ?? '') !== '' ? $user['foto_perfil'] : null;
$tutor = $tutor ?? null;
$tutorMissing = $tutorMissing ?? false;
$citas = $citas ?? [];
$contenidos = $contenidos ?? [];
$tutorResenas = $tutorResenas ?? [];
$citaStats = $citaStats ?? ['total' => 0, 'completada' => 0, 'cancelada' => 0, 'pendiente' => 0, 'confirmada' => 0, 'en_curso' => 0];

$displayName = $tutor['nombre'] ?? ($user['nombre'] ?? 'Tutor');
$correo = $user['email'] ?? '';
$sesiones = isset($tutor['sesiones']) ? (int) $tutor['sesiones'] : 0;
$rating = isset($tutor['rating']) ? (float) $tutor['rating'] : 0.0;
$materias = $tutor['materias'] ?? [];
$materias = is_array($materias) ? $materias : [];
$disponibilidad = $tutor['disponibilidad'] ?? [];
$disponibilidad = is_array($disponibilidad) ? $disponibilidad : [];
$bio = (string) ($tutor['bio'] ?? '');

$agendaDias = [
    'Lunes' => 'Lunes',
    'Martes' => 'Martes',
    'Miercoles' => 'Miercoles',
    'Jueves' => 'Jueves',
    'Viernes' => 'Viernes',
    'Sabado' => 'Sabado',
    'Domingo' => 'Domingo',
];

$agendaMap = [];
foreach ($disponibilidad as $bloque) {
    $dia = trim((string) ($bloque['dia'] ?? ''));
    if ($dia === '') {
        continue;
    }

    $horas = (string) ($bloque['horas'] ?? '');
    $desde = '';
    $hasta = '';
    if ($horas !== '' && preg_match('/(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/', $horas, $matches)) {
        $desde = $matches[1];
        $hasta = $matches[2];
    }

    $agendaMap[$dia] = [
        'desde' => $desde,
        'hasta' => $hasta,
        'horas' => $horas,
    ];
}

$initials = 'TU';
$parts = preg_split('/\s+/', trim($displayName)) ?: [];
$letters = '';
foreach ($parts as $part) {
    if ($part === '') {
        continue;
    }
    $letters .= strtoupper(substr($part, 0, 1));
    if (strlen($letters) >= 2) {
        break;
    }
}
if ($letters !== '') {
    $initials = $letters;
}

$pendientes = 0;
$pendientesList = [];
$otrasCitas = [];
foreach ($citas as $cita) {
    $estadoKey = strtolower((string) ($cita['estado'] ?? 'pendiente'));
    if ($estadoKey === 'pendiente') {
        $pendientes++;
        $pendientesList[] = $cita;
    } else {
        $otrasCitas[] = $cita;
    }
}

$proximaLabel = 'Sin citas';
$proximaFecha = null;
$now = new DateTime('now');
foreach ($citas as $cita) {
    $fecha = (string) ($cita['fecha'] ?? '');
    $hora = (string) ($cita['hora'] ?? '');
    if ($fecha === '' || $hora === '') {
        continue;
    }

    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $fecha . ' ' . $hora)
        ?: DateTime::createFromFormat('Y-m-d H:i', $fecha . ' ' . $hora);

    if (!$dt || $dt < $now) {
        continue;
    }

    if ($proximaFecha === null || $dt < $proximaFecha) {
        $proximaFecha = $dt;
    }
}

if ($proximaFecha) {
    $proximaLabel = $proximaFecha->format('d/m/Y H:i');
}

$estadoOptions = [
    'pendiente' => 'Pendiente',
    'confirmada' => 'Confirmada',
    'en_curso' => 'En curso',
    'cancelada' => 'Cancelada',
    'completada' => 'Completada',
];
?>

<section class="perfil-page">
    <div class="perfil-container">
        <aside class="perfil-aside">
            <div class="avatar-wrapper">
                <?php if ($fotoPerfil): ?>
                    <img src="<?= e($fotoPerfil) ?>" alt="Foto de perfil" class="perfil-avatar-img">
                <?php else: ?>
                    <div class="perfil-avatar-grande"><?= e($initials) ?></div>
                <?php endif; ?>
            </div>
            <h2><?= e($displayName) ?></h2>
            <p class="carrera">Tutor - Universidad del Magdalena</p>
            <span class="badge-verificado">Tutor activo</span>
            <div class="perfil-stats">
                <div class="stat-item">
                    <div class="stat-num"><?= e((string) $sesiones) ?></div>
                    <div class="stat-label">Sesiones</div>
                </div>
                <div class="stat-item">
                    <div class="stat-num"><?= e((string) $pendientes) ?></div>
                    <div class="stat-label">Pendientes</div>
                </div>
            </div>
            <?php if (!$tutorMissing): ?>
                <button type="button" class="btn-agendar-perfil" data-open-agenda>Configurar agenda</button>
            <?php endif; ?>
            <a href="index.php?action=auth/logout" class="btn-contactar" style="text-decoration:none;display:block;">Cerrar sesion</a>
        </aside>

        <div class="perfil-main">
            <?php if ($tutorMissing): ?>
                <div class="perfil-card">
                    <h3>Perfil de tutor no vinculado</h3>
                    <p>Tu cuenta aun no esta vinculada a un perfil de tutor aprobado. Contacta al administrador para completar la vinculacion.</p>
                </div>
            <?php else: ?>
                <div class="perfil-card">
                    <h3>Resumen</h3>
                    <p><strong>Correo:</strong> <?= e($correo) ?></p>
                    <p><strong>Calificacion:</strong> <?= e(number_format($rating, 1)) ?> / 5</p>
                    <p><strong>Proxima sesion:</strong> <?= e($proximaLabel) ?></p>
                </div>

                <div class="perfil-card">
                    <h3>Estadisticas</h3>
                    <div class="tutor-stats-grid">
                        <div class="tutor-stat-card">
                            <div class="tutor-stat-num"><?= e((string) ($citaStats['total'] ?? 0)) ?></div>
                            <div class="tutor-stat-label">Total citas</div>
                        </div>
                        <div class="tutor-stat-card">
                            <div class="tutor-stat-num tutor-stat-num--green"><?= e((string) ($citaStats['completada'] ?? 0)) ?></div>
                            <div class="tutor-stat-label">Completadas</div>
                        </div>
                        <div class="tutor-stat-card">
                            <div class="tutor-stat-num tutor-stat-num--red"><?= e((string) ($citaStats['cancelada'] ?? 0)) ?></div>
                            <div class="tutor-stat-label">Canceladas</div>
                        </div>
                        <div class="tutor-stat-card">
                            <div class="tutor-stat-num"><?= e(number_format($rating, 1)) ?> <span style="font-size:14px;font-weight:400;color:#94a3b8;">/ 5</span></div>
                            <div class="tutor-stat-label">Calificacion</div>
                        </div>
                        <div class="tutor-stat-card">
                            <div class="tutor-stat-num"><?= e((string) count($tutorResenas)) ?></div>
                            <div class="tutor-stat-label">Resenas</div>
                        </div>
                        <div class="tutor-stat-card">
                            <div class="tutor-stat-num"><?= e((string) count($contenidos)) ?></div>
                            <div class="tutor-stat-label">Contenido subido</div>
                        </div>
                    </div>
                </div>

                <?php if ($tutorResenas): ?>
                <div class="perfil-card">
                    <h3>Resenas recibidas</h3>
                    <div class="tutor-resenas-list">
                        <?php foreach ($tutorResenas as $resena): ?>
                            <div class="tutor-resena-item">
                                <div class="tutor-resena-autor"><?= e((string) ($resena['nombre_usuario'] ?? '')) ?></div>
                                <div class="tutor-resena-texto"><?= e((string) ($resena['texto'] ?? '')) ?></div>
                                <?php
                                $fechaResena = (string) ($resena['creado_en'] ?? '');
                                $dtResena = $fechaResena !== '' ? DateTime::createFromFormat('Y-m-d H:i:s', $fechaResena) : false;
                                ?>
                                <?php if ($dtResena): ?>
                                    <div class="tutor-resena-fecha"><?= e($dtResena->format('d/m/Y')) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="perfil-card">
                    <h3>Sobre mi</h3>
                    <?php if ($bio !== ''): ?>
                        <p style="color:#555;font-size:15px;line-height:1.7;"><?= e($bio) ?></p>
                    <?php else: ?>
                        <p>Aun no hay una descripcion registrada para tu perfil.</p>
                    <?php endif; ?>
                </div>

                <div class="perfil-card">
                    <h3>Materias asignadas</h3>
                    <?php if ($materias): ?>
                        <div class="materias-tags">
                            <?php foreach ($materias as $materia): ?>
                                <span class="materia-tag"><?= e((string) $materia) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No tienes materias registradas aun.</p>
                    <?php endif; ?>
                </div>

                <div class="perfil-card">
                    <h3>Disponibilidad</h3>
                    <?php if ($disponibilidad): ?>
                        <div class="disponibilidad-display">
                            <?php foreach ($disponibilidad as $bloque): ?>
                                <div class="disponibilidad-item">
                                    <strong><?= e((string) ($bloque['dia'] ?? '')) ?></strong>
                                    <span><?= e((string) ($bloque['horas'] ?? '')) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Sin disponibilidad registrada.</p>
                    <?php endif; ?>
                </div>

                <div class="perfil-card">
                    <div class="tutor-contenido-header">
                        <h3>Mis contenidos</h3>
                        <button type="button" class="btn-contenido" data-open-contenido>Subir contenido</button>
                    </div>
                    <?php if (!$contenidos): ?>
                        <p>No has subido contenido aun.</p>
                    <?php else: ?>
                        <div class="contenido-grid tutor-contenido-grid">
                            <?php
                            $tipoLabels = [
                                'pdf' => 'PDF',
                                'imagen' => 'Imagen',
                                'recurso' => 'Recurso',
                            ];
                            foreach ($contenidos as $contenido):
                                $tipoKey = strtolower((string) ($contenido['tipo'] ?? 'recurso'));
                                if (!isset($tipoLabels[$tipoKey])) {
                                    $tipoKey = 'recurso';
                                }
                                $tipoLabel = $tipoLabels[$tipoKey];
                                $estadoKey = strtolower((string) ($contenido['estado'] ?? 'pendiente'));
                                if (!in_array($estadoKey, ['pendiente', 'aprobado'], true)) {
                                    $estadoKey = 'pendiente';
                                }
                                $estadoLabel = $estadoKey === 'aprobado' ? 'Aprobado' : 'Pendiente';
                                $extension = strtolower((string) ($contenido['extension'] ?? ''));
                                $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true);
                                $fechaLabel = (string) ($contenido['fecha'] ?? '');
                                if ($fechaLabel !== '') {
                                    $fecha = DateTime::createFromFormat('Y-m-d', $fechaLabel);
                                    if ($fecha) {
                                        $fechaLabel = $fecha->format('d/m/Y');
                                    }
                                }
                                $archivoNombre = (string) ($contenido['archivo_nombre'] ?? '');
                                $contenidoId = (string) ($contenido['id'] ?? '');
                            ?>
                                <article class="contenido-card tutor-content-card">
                                    <div class="contenido-modulo-preview">
                                        <?php if ($isImage): ?>
                                            <img src="<?= e((string) ($contenido['enlace'] ?? '#')) ?>" alt="Vista previa de <?= e((string) ($contenido['titulo'] ?? '')) ?>" loading="lazy">
                                        <?php else: ?>
                                            <div class="contenido-modulo-preview-fallback contenido-thumb-<?= e($tipoKey) ?>">
                                                <span><?= e($tipoLabel) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="contenido-card-body">
                                        <span class="contenido-pill contenido-pill-<?= e($tipoKey) ?>"><?= e($tipoLabel) ?></span>
                                        <span class="contenido-pill contenido-pill-estado contenido-pill-estado-<?= e($estadoKey) ?>"><?= e($estadoLabel) ?></span>
                                        <h4><?= e((string) ($contenido['titulo'] ?? '')) ?></h4>
                                        <div class="contenido-meta">
                                            <span>Archivo: <?= e($archivoNombre) ?></span>
                                            <span>Fecha: <?= e($fechaLabel) ?></span>
                                        </div>
                                    </div>
                                    <div class="admin-content-actions">
                                        <a class="btn-contenido btn-contenido-outline" href="<?= e((string) ($contenido['enlace'] ?? '#')) ?>" target="_blank" rel="noopener">Abrir</a>
                                        <button
                                            type="button"
                                            class="btn-contenido btn-contenido-outline"
                                            data-edit-contenido
                                            data-contenido-id="<?= e($contenidoId) ?>"
                                            data-contenido-titulo="<?= e((string) ($contenido['titulo'] ?? '')) ?>"
                                            data-contenido-materia="<?= e((string) ($contenido['materia'] ?? '')) ?>"
                                            data-contenido-tema="<?= e((string) ($contenido['tema'] ?? '')) ?>"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            type="button"
                                            class="btn-contenido btn-contenido-outline"
                                            data-delete-contenido
                                            data-contenido-id="<?= e($contenidoId) ?>"
                                            data-contenido-titulo="<?= e((string) ($contenido['titulo'] ?? '')) ?>"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="perfil-card">
                    <h3>Citas pendientes</h3>
                    <?php if (!$pendientesList): ?>
                        <p>No tienes citas pendientes por confirmar.</p>
                    <?php else: ?>
                        <div class="citas-list">
                            <?php foreach ($pendientesList as $cita): ?>
                                <?php
                                $estadoKey = 'pendiente';
                                $estadoLabel = $estadoOptions[$estadoKey];

                                $fechaValue = (string) ($cita['fecha'] ?? '');
                                $horaValue = (string) ($cita['hora'] ?? '');
                                if ($horaValue !== '') {
                                    $hora = DateTime::createFromFormat('H:i:s', $horaValue)
                                        ?: DateTime::createFromFormat('H:i', $horaValue);
                                    if ($hora) {
                                        $horaValue = $hora->format('H:i');
                                    }
                                }
                                $fechaLabel = $fechaValue;
                                if ($fechaLabel !== '') {
                                    $fecha = DateTime::createFromFormat('Y-m-d', $fechaLabel);
                                    if ($fecha) {
                                        $fechaLabel = $fecha->format('d/m/Y');
                                    }
                                }
                                $materiaValue = (string) ($cita['materia'] ?? '');
                                $modalidadValue = (string) ($cita['modalidad'] ?? '');
                                $enlaceReunion = (string) ($cita['enlace_reunion'] ?? '');
                                $estudianteNombre = (string) ($cita['estudiante_nombre'] ?? '');
                                $estudianteCorreo = (string) ($cita['estudiante_correo'] ?? '');
                                $citaId = (string) ($cita['id'] ?? '');
                                ?>
                                <div class="cita-item">
                                    <div class="cita-info">
                                        <div class="cita-title">Estudiante: <?= e($estudianteNombre) ?></div>
                                        <div class="cita-meta">Correo: <?= e($estudianteCorreo) ?></div>
                                        <div class="cita-meta">Materia: <?= e($materiaValue) ?></div>
                                        <div class="cita-meta">Modalidad: <?= e($modalidadValue) ?></div>
                                        <div class="cita-meta">Fecha: <?= e($fechaLabel) ?> - Hora: <?= e($horaValue) ?></div>
                                    </div>
                                    <div class="cita-side">
                                        <span class="cita-status cita-status-<?= e($estadoKey) ?>"><?= e($estadoLabel) ?></span>
                                        <div class="cita-actions">
                                            <button
                                                type="button"
                                                class="btn-cita-action"
                                                data-confirm-tutor
                                                data-cita-id="<?= e($citaId) ?>"
                                                data-estudiante="<?= e($estudianteNombre) ?>"
                                                data-materia="<?= e($materiaValue) ?>"
                                                data-modalidad="<?= e($modalidadValue) ?>"
                                                data-fecha="<?= e($fechaValue) ?>"
                                                data-hora="<?= e($horaValue) ?>"
                                                data-enlace="<?= e($enlaceReunion) ?>"
                                            >
                                                Confirmar
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-cita-action btn-cita-danger"
                                                data-cancel-tutor
                                                data-cita-id="<?= e($citaId) ?>"
                                                data-estudiante="<?= e($estudianteNombre) ?>"
                                                data-materia="<?= e($materiaValue) ?>"
                                                data-fecha="<?= e($fechaValue) ?>"
                                                data-hora="<?= e($horaValue) ?>"
                                            >
                                                Cancelar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="perfil-card">
                    <h3>Historial de citas</h3>
                    <?php if (!$otrasCitas): ?>
                        <p>No tienes citas confirmadas o canceladas.</p>
                    <?php else: ?>
                        <div class="citas-list">
                            <?php foreach ($otrasCitas as $cita): ?>
                                <?php
                                $estadoKey = strtolower((string) ($cita['estado'] ?? 'pendiente'));
                                if (!isset($estadoOptions[$estadoKey])) {
                                    $estadoKey = 'pendiente';
                                }
                                $estadoLabel = $estadoOptions[$estadoKey];

                                $fechaValue = (string) ($cita['fecha'] ?? '');
                                $horaValue = (string) ($cita['hora'] ?? '');
                                if ($horaValue !== '') {
                                    $hora = DateTime::createFromFormat('H:i:s', $horaValue)
                                        ?: DateTime::createFromFormat('H:i', $horaValue);
                                    if ($hora) {
                                        $horaValue = $hora->format('H:i');
                                    }
                                }
                                $fechaLabel = $fechaValue;
                                if ($fechaLabel !== '') {
                                    $fecha = DateTime::createFromFormat('Y-m-d', $fechaLabel);
                                    if ($fecha) {
                                        $fechaLabel = $fecha->format('d/m/Y');
                                    }
                                }
                                $materiaValue = (string) ($cita['materia'] ?? '');
                                $estudianteNombre = (string) ($cita['estudiante_nombre'] ?? '');
                                $estudianteCorreo = (string) ($cita['estudiante_correo'] ?? '');
                                $citaId = (string) ($cita['id'] ?? '');
                                $modalidadValue = (string) ($cita['modalidad'] ?? '');
                                $enlaceReunion = (string) ($cita['enlace_reunion'] ?? '');
                                $canceladaPor = strtolower((string) ($cita['cancelada_por'] ?? ''));
                                $cancelacionMotivo = (string) ($cita['cancelacion_motivo'] ?? '');
                                $cancelLabel = '';
                                if ($estadoKey === 'cancelada') {
                                    if ($canceladaPor === 'estudiante') {
                                        $cancelLabel = 'Cancelada por el estudiante.';
                                    } elseif ($canceladaPor === 'tutor') {
                                        $cancelLabel = 'Cancelada por el tutor.';
                                    }
                                }
                                $fechaHora = null;
                                if ($fechaValue !== '' && $horaValue !== '') {
                                    $fechaHora = DateTime::createFromFormat('Y-m-d H:i', $fechaValue . ' ' . $horaValue);
                                }
                                $now = new DateTime('now');
                                $modalidadLower = strtolower($modalidadValue);
                                $esVirtual    = str_contains($modalidadLower, 'virtual') || str_contains($modalidadLower, 'ambas');
                                $esUniLink    = str_contains($modalidadLower, 'videollamada unilink');

                                // canJoin: external-link virtual sessions only
                                $canJoin = false;
                                if ($esVirtual && !$esUniLink && $enlaceReunion !== '' && in_array($estadoKey, ['confirmada', 'en_curso'], true)) {
                                    if ($estadoKey === 'en_curso') {
                                        $canJoin = true;
                                    } elseif ($fechaHora instanceof DateTime) {
                                        try {
                                            $antes = (clone $fechaHora)->sub(new DateInterval('PT5M'));
                                            $canJoin = $now >= $antes;
                                        } catch (Exception $e) {
                                            $canJoin = $now >= $fechaHora;
                                        }
                                    }
                                }

                                // canUniLink: in-app video call sessions
                                $canUniLink = false;
                                if ($esUniLink && in_array($estadoKey, ['confirmada', 'en_curso'], true)) {
                                    if ($estadoKey === 'en_curso') {
                                        $canUniLink = true;
                                    } elseif ($fechaHora instanceof DateTime) {
                                        try {
                                            $antes = (clone $fechaHora)->sub(new DateInterval('PT10M'));
                                            $canUniLink = $now >= $antes;
                                        } catch (Exception $e) {
                                            $canUniLink = $now >= $fechaHora;
                                        }
                                    }
                                }

                                // canFinalize: any session en_curso (all modalities)
                                $canFinalize = $estadoKey === 'en_curso';
                                ?>
                                <div class="cita-item">
                                    <div class="cita-info">
                                        <div class="cita-title">Estudiante: <?= e($estudianteNombre) ?></div>
                                        <div class="cita-meta">Correo: <?= e($estudianteCorreo) ?></div>
                                        <div class="cita-meta">Materia: <?= e($materiaValue) ?></div>
                                        <div class="cita-meta">Modalidad: <?= e($modalidadValue) ?></div>
                                        <div class="cita-meta">Fecha: <?= e($fechaLabel) ?> - Hora: <?= e($horaValue) ?></div>
                                        <?php if ($estadoKey === 'cancelada' && $cancelLabel !== ''): ?>
                                            <div class="cita-meta"><?= e($cancelLabel) ?></div>
                                        <?php endif; ?>
                                        <?php if ($estadoKey === 'cancelada' && $cancelacionMotivo !== ''): ?>
                                            <div class="cita-meta">Motivo: <?= e($cancelacionMotivo) ?></div>
                                        <?php endif; ?>
                                        <?php if (in_array($estadoKey, ['confirmada', 'en_curso'], true) && $esVirtual && !$esUniLink): ?>
                                            <?php if ($enlaceReunion !== ''): ?>
                                                <div class="cita-meta">Enlace: <a href="<?= e($enlaceReunion) ?>" target="_blank" rel="noopener">abrir enlace</a></div>
                                            <?php else: ?>
                                                <div class="cita-meta" style="color:#b00;">Enlace no disponible. Recuerda ingresar el link al confirmar.</div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="cita-side">
                                        <span class="cita-status cita-status-<?= e($estadoKey) ?>"><?= e($estadoLabel) ?></span>
                                        <?php if ($estadoKey === 'confirmada'): ?>
                                            <div class="cita-actions">
                                                <button
                                                    type="button"
                                                    class="btn-cita-action btn-cita-danger"
                                                    data-cancel-tutor
                                                    data-cita-id="<?= e($citaId ?? '') ?>"
                                                    data-estudiante="<?= e($estudianteNombre) ?>"
                                                    data-materia="<?= e($materiaValue) ?>"
                                                    data-fecha="<?= e($fechaValue) ?>"
                                                    data-hora="<?= e($horaValue) ?>"
                                                >
                                                    Cancelar
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($canJoin || $canUniLink || $canFinalize): ?>
                                            <div class="cita-actions">
                                                <?php if ($canJoin): ?>
                                                    <a class="btn-cita-action" href="<?= e($enlaceReunion) ?>" target="_blank" rel="noopener">Unirse</a>
                                                <?php endif; ?>
                                                <?php if ($canUniLink): ?>
                                                    <a class="btn-cita-action btn-unilink" href="index.php?page=videollamada&id=<?= e($citaId) ?>">
                                                        Entrar a videollamada
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($canFinalize): ?>
                                                    <form method="post" action="index.php?action=tutor/citas/update">
                                                        <input type="hidden" name="cita_id" value="<?= e($citaId) ?>">
                                                        <input type="hidden" name="cita_estado" value="completada">
                                                        <button type="submit" class="btn-cita-action">Finalizar sesion</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
$materiasList = array_values(array_filter(array_map('strval', $materias ?? [])));
$materiasCount = count($materiasList);
?>

<div class="modal-overlay hidden" id="modalSubirContenido">
    <div class="modal">
        <button class="modal-close" type="button" onclick="cerrarModalSubirContenido()">X</button>
        <h3>Subir contenido</h3>
        <p class="modal-text">Selecciona un archivo y ajusta el nombre antes de subirlo.</p>
        <form method="post" action="index.php?action=tutor/contenido/store" enctype="multipart/form-data">
            <div class="form-group">
                <label>Nombre del contenido *</label>
                <input type="text" id="contenidoTitulo" name="contenido_titulo" required>
            </div>
            <div class="form-group">
                <label>Materia *</label>
                <?php if ($materiasList): ?>
                    <select id="contenidoMateria" name="contenido_materia" required>
                        <?php if ($materiasCount > 1): ?>
                            <option value="">Selecciona una materia</option>
                        <?php endif; ?>
                        <?php foreach ($materiasList as $materiaItem): ?>
                            <option value="<?= e($materiaItem) ?>"><?= e($materiaItem) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" id="contenidoMateria" name="contenido_materia" required>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Tema *</label>
                <input type="text" id="contenidoTema" name="contenido_tema" required>
            </div>
            <div class="form-group">
                <label>Archivo *</label>
                <input
                    type="file"
                    id="contenidoArchivo"
                    name="contenido_archivo"
                    accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                    required
                >
                <p class="contenido-error" id="contenidoUploadError"></p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModalSubirContenido()">Cancelar</button>
                <button type="submit" class="btn-submit">Subir contenido</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay hidden" id="modalEditarContenido">
    <div class="modal">
        <button class="modal-close" type="button" onclick="cerrarModalEditarContenido()">X</button>
        <h3>Editar contenido</h3>
        <p class="modal-text">Actualiza el nombre o reemplaza el archivo si lo necesitas.</p>
        <form method="post" action="index.php?action=tutor/contenido/update" enctype="multipart/form-data">
            <input type="hidden" name="contenido_id" id="contenidoEditarId" value="">
            <div class="form-group">
                <label>Nombre del contenido *</label>
                <input type="text" id="contenidoEditarTitulo" name="contenido_titulo" required>
            </div>
            <div class="form-group">
                <label>Materia *</label>
                <?php if ($materiasList): ?>
                    <select id="contenidoEditarMateria" name="contenido_materia" required>
                        <?php if ($materiasCount > 1): ?>
                            <option value="">Selecciona una materia</option>
                        <?php endif; ?>
                        <?php foreach ($materiasList as $materiaItem): ?>
                            <option value="<?= e($materiaItem) ?>"><?= e($materiaItem) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" id="contenidoEditarMateria" name="contenido_materia" required>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Tema *</label>
                <input type="text" id="contenidoEditarTema" name="contenido_tema" required>
            </div>
            <div class="form-group">
                <label>Reemplazar archivo (opcional)</label>
                <input
                    type="file"
                    id="contenidoEditarArchivo"
                    name="contenido_archivo"
                    accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                >
                <p class="contenido-error" id="contenidoEditError"></p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModalEditarContenido()">Cancelar</button>
                <button type="submit" class="btn-submit">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay hidden" id="modalEliminarContenidoTutor">
    <div class="modal">
        <button class="modal-close" type="button" onclick="cerrarModalEliminarContenidoTutor()">X</button>
        <h3>Eliminar contenido</h3>
        <p class="modal-text" id="modalEliminarContenidoTutorDetalle"></p>
        <form method="post" action="index.php?action=tutor/contenido/delete">
            <input type="hidden" name="contenido_id" id="contenidoEliminarId" value="">
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModalEliminarContenidoTutor()">Cancelar</button>
                <button type="submit" class="btn-submit btn-submit-danger">Eliminar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay hidden" id="modalConfirmarTutor">
    <div class="modal">
        <button class="modal-close" type="button" onclick="cerrarModalConfirmarTutor()">X</button>
        <h3>Confirmar cita</h3>
        <p class="modal-text" id="modalConfirmarTutorDetalle"></p>
        <form method="post" action="index.php?action=tutor/citas/update">
            <input type="hidden" name="cita_id" id="confirmarTutorCitaId" value="">
            <input type="hidden" name="cita_estado" value="confirmada">
            <div class="form-group" id="confirmarTutorEnlaceGroup">
                <label>Enlace de la sesión virtual (Google Meet o Microsoft Teams) *</label>
                <input type="url" name="cita_enlace" id="confirmarTutorEnlace" placeholder="https://meet.google.com/xxx-xxxx-xxx  o  https://teams.microsoft.com/l/meetup-join/...">
                <p class="modal-text" style="margin-top:6px;">Acepta enlaces de Google Meet o Microsoft Teams. Comparte este enlace con los participantes.</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModalConfirmarTutor()">Volver</button>
                <button type="submit" class="btn-submit">Confirmar cita</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay hidden" id="modalCancelarTutor">
    <div class="modal">
        <button class="modal-close" type="button" onclick="cerrarModalCancelarTutor()">X</button>
        <h3>Cancelar cita</h3>
        <p class="modal-text" id="modalCancelarTutorDetalle"></p>
        <form method="post" action="index.php?action=tutor/citas/update">
            <input type="hidden" name="cita_id" id="cancelarTutorCitaId" value="">
            <input type="hidden" name="cita_estado" value="cancelada">
            <div class="form-group">
                <label>Motivo de cancelacion *</label>
                <textarea name="cita_motivo" id="cancelarTutorMotivo" rows="3" maxlength="300" required></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModalCancelarTutor()">Volver</button>
                <button type="submit" class="btn-submit btn-submit-danger">Cancelar cita</button>
            </div>
        </form>
    </div>
</div>

<?php if (!$tutorMissing): ?>
    <div class="modal-overlay hidden" id="modalAgendaTutor">
        <div class="modal modal-agenda">
            <button class="modal-close" type="button" onclick="cerrarModalAgenda()">X</button>
            <h3>Configurar agenda</h3>
            <p class="modal-text">Selecciona los dias y el horario en que estaras disponible esta semana.</p>
            <form method="post" action="index.php?action=tutor/agenda">
                <div class="agenda-grid">
                    <?php foreach ($agendaDias as $dayKey => $dayLabel): ?>
                        <?php
                        $agendaData = $agendaMap[$dayKey] ?? ['desde' => '', 'hasta' => ''];
                        $checked = $agendaData['desde'] !== '' && $agendaData['hasta'] !== '';
                        ?>
                        <div class="agenda-row">
                            <label class="agenda-day">
                                <input type="checkbox" name="agenda_dias[]" value="<?= e($dayKey) ?>" <?= $checked ? 'checked' : '' ?>>
                                <span><?= e($dayLabel) ?></span>
                            </label>
                            <div class="agenda-time">
                                <div class="form-group">
                                    <label>Desde</label>
                                    <input type="time" name="agenda_desde[<?= e($dayKey) ?>]" value="<?= e($agendaData['desde']) ?>">
                                </div>
                                <div class="form-group">
                                    <label>Hasta</label>
                                    <input type="time" name="agenda_hasta[<?= e($dayKey) ?>]" value="<?= e($agendaData['hasta']) ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="cerrarModalAgenda()">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar agenda</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        const overlay = document.getElementById("modalAgendaTutor");
        if (!overlay || window.__agendaTutorBound) return;
        window.__agendaTutorBound = true;

        window.cerrarModalAgenda = window.cerrarModalAgenda || function () {
            overlay.classList.add("hidden");
        };

        document.querySelectorAll("[data-open-agenda]").forEach((btn) => {
            btn.addEventListener("click", () => {
                overlay.classList.remove("hidden");
            });
        });

        overlay.addEventListener("click", (event) => {
            if (event.target === overlay) {
                window.cerrarModalAgenda();
            }
        });
    })();
    </script>
<?php endif; ?>

<script>
(function () {
    const modalSubir = document.getElementById("modalSubirContenido");
    const modalEditar = document.getElementById("modalEditarContenido");
    const modalEliminar = document.getElementById("modalEliminarContenidoTutor");
    if ((!modalSubir && !modalEditar && !modalEliminar) || window.__contenidoTutorBound) return;
    window.__contenidoTutorBound = true;

    const allowedExt = ["pdf", "jpg", "jpeg", "png", "webp", "doc", "docx", "xls", "xlsx", "ppt", "pptx"];
    const getExt = (name) => {
        const parts = String(name || "").toLowerCase().split(".");
        return parts.length > 1 ? parts.pop() : "";
    };

    const validarArchivo = (input, errorEl) => {
        if (!input) return true;
        const file = input.files && input.files[0];
        if (!file) {
            if (errorEl) errorEl.textContent = "";
            return true;
        }

        const ext = getExt(file.name);
        const valido = allowedExt.includes(ext);
        if (!valido) {
            if (errorEl) errorEl.textContent = "El formato del archivo no es valido.";
            input.value = "";
            return false;
        }

        if (errorEl) errorEl.textContent = "";
        return true;
    };

    window.cerrarModalSubirContenido = window.cerrarModalSubirContenido || function () {
        modalSubir?.classList.add("hidden");
    };

    window.cerrarModalEditarContenido = window.cerrarModalEditarContenido || function () {
        modalEditar?.classList.add("hidden");
    };

    window.cerrarModalEliminarContenidoTutor = window.cerrarModalEliminarContenidoTutor || function () {
        modalEliminar?.classList.add("hidden");
    };

    document.querySelectorAll("[data-open-contenido]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const titulo = document.getElementById("contenidoTitulo");
            const materia = document.getElementById("contenidoMateria");
            const tema = document.getElementById("contenidoTema");
            const archivo = document.getElementById("contenidoArchivo");
            const error = document.getElementById("contenidoUploadError");
            if (titulo) {
                titulo.value = "";
                titulo.dataset.manual = "0";
            }
            if (materia) {
                if (materia.tagName === "SELECT") {
                    materia.selectedIndex = 0;
                } else {
                    materia.value = "";
                }
            }
            if (tema) tema.value = "";
            if (archivo) archivo.value = "";
            if (error) error.textContent = "";
            modalSubir?.classList.remove("hidden");
        });
    });

    document.querySelectorAll("[data-edit-contenido]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const id = btn.dataset.contenidoId || "";
            const titulo = btn.dataset.contenidoTitulo || "";
            const materia = btn.dataset.contenidoMateria || "";
            const tema = btn.dataset.contenidoTema || "";
            const inputId = document.getElementById("contenidoEditarId");
            const inputTitulo = document.getElementById("contenidoEditarTitulo");
            const inputMateria = document.getElementById("contenidoEditarMateria");
            const inputTema = document.getElementById("contenidoEditarTema");
            const inputArchivo = document.getElementById("contenidoEditarArchivo");
            const error = document.getElementById("contenidoEditError");
            if (inputId) inputId.value = id;
            if (inputTitulo) inputTitulo.value = titulo;
            if (inputMateria) {
                if (inputMateria.tagName === "SELECT") {
                    inputMateria.value = materia;
                    if (inputMateria.value === "" && inputMateria.options.length > 0) {
                        inputMateria.selectedIndex = 0;
                    }
                } else {
                    inputMateria.value = materia;
                }
            }
            if (inputTema) inputTema.value = tema;
            if (inputArchivo) inputArchivo.value = "";
            if (error) error.textContent = "";
            modalEditar?.classList.remove("hidden");
        });
    });

    document.querySelectorAll("[data-delete-contenido]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const id = btn.dataset.contenidoId || "";
            const titulo = btn.dataset.contenidoTitulo || "";
            const inputId = document.getElementById("contenidoEliminarId");
            const detalle = document.getElementById("modalEliminarContenidoTutorDetalle");
            if (inputId) inputId.value = id;
            if (detalle) detalle.textContent = `${titulo}. Esta accion no se puede deshacer.`.trim();
            modalEliminar?.classList.remove("hidden");
        });
    });

    const tituloInput = document.getElementById("contenidoTitulo");
    const archivoInput = document.getElementById("contenidoArchivo");
    const errorUpload = document.getElementById("contenidoUploadError");
    if (tituloInput) {
        tituloInput.addEventListener("input", () => {
            tituloInput.dataset.manual = "1";
        });
    }

    archivoInput?.addEventListener("change", () => {
        if (!validarArchivo(archivoInput, errorUpload)) {
            return;
        }

        if (!tituloInput) return;
        if (tituloInput.dataset.manual === "1" && tituloInput.value.trim() !== "") {
            return;
        }

        const file = archivoInput.files && archivoInput.files[0];
        if (!file) return;
        const nombreBase = file.name.replace(/\.[^/.]+$/, "");
        tituloInput.value = nombreBase;
    });

    const archivoEditar = document.getElementById("contenidoEditarArchivo");
    const errorEditar = document.getElementById("contenidoEditError");
    archivoEditar?.addEventListener("change", () => {
        validarArchivo(archivoEditar, errorEditar);
    });

    [modalSubir, modalEditar, modalEliminar].forEach((overlay) => {
        overlay?.addEventListener("click", (event) => {
            if (event.target === overlay) {
                overlay.classList.add("hidden");
            }
        });
    });
})();
</script>

<script>
(function () {
    const overlay = document.getElementById("modalConfirmarTutor");
    if (!overlay || window.__confirmTutorBound) return;
    window.__confirmTutorBound = true;

    window.cerrarModalConfirmarTutor = window.cerrarModalConfirmarTutor || function () {
        overlay.classList.add("hidden");
    };

    const detalle = document.getElementById("modalConfirmarTutorDetalle");
    const idInput = document.getElementById("confirmarTutorCitaId");
    const enlaceInput = document.getElementById("confirmarTutorEnlace");
    const enlaceGroup = document.getElementById("confirmarTutorEnlaceGroup");

    const abrir = (btn) => {
        if (!btn) return;

        const estudiante = btn.dataset.estudiante || "";
        const materia = btn.dataset.materia || "";
        const fecha = btn.dataset.fecha || "";
        const hora = btn.dataset.hora || "";
        const modalidad = btn.dataset.modalidad || "";
        const enlace = btn.dataset.enlace || "";
        const fechaLabel = fecha && fecha.includes("-") ? fecha.split("-").reverse().join("/") : fecha;
        const texto = `Vas a confirmar la cita con ${estudiante || "el estudiante"} para ${materia || "la materia"} el ${fechaLabel || ""}${hora ? " a las " + hora : ""}.`;

        if (detalle) detalle.textContent = texto.trim();
        if (idInput) idInput.value = btn.dataset.citaId || "";

        const modalidadLower = String(modalidad).toLowerCase();
        const requiereEnlace = modalidadLower.includes("virtual") || modalidadLower.includes("ambas");
        if (enlaceGroup) enlaceGroup.style.display = requiereEnlace ? "block" : "none";
        if (enlaceInput) {
            enlaceInput.required = requiereEnlace;
            enlaceInput.disabled = !requiereEnlace;
            enlaceInput.value = requiereEnlace ? enlace : "";
        }

        overlay.classList.remove("hidden");
    };

    document.querySelectorAll("[data-confirm-tutor]").forEach((btn) => {
        btn.addEventListener("click", () => abrir(btn));
    });

    overlay.addEventListener("click", (event) => {
        if (event.target === overlay) {
            window.cerrarModalConfirmarTutor();
        }
    });
})();
</script>

<script>
(function () {
    const overlay = document.getElementById("modalCancelarTutor");
    if (!overlay || window.__cancelTutorBound) return;
    window.__cancelTutorBound = true;

    window.cerrarModalCancelarTutor = window.cerrarModalCancelarTutor || function () {
        overlay.classList.add("hidden");
    };

    const detalle = document.getElementById("modalCancelarTutorDetalle");
    const idInput = document.getElementById("cancelarTutorCitaId");
    const motivoInput = document.getElementById("cancelarTutorMotivo");

    const abrir = (btn) => {
        if (!btn) return;

        const estudiante = btn.dataset.estudiante || "";
        const materia = btn.dataset.materia || "";
        const fecha = btn.dataset.fecha || "";
        const hora = btn.dataset.hora || "";
        const fechaLabel = fecha && fecha.includes("-") ? fecha.split("-").reverse().join("/") : fecha;
        const texto = `Vas a cancelar la cita con ${estudiante || "el estudiante"} para ${materia || "la materia"} el ${fechaLabel || ""}${hora ? " a las " + hora : ""}.`;

        if (detalle) detalle.textContent = texto.trim();
        if (idInput) idInput.value = btn.dataset.citaId || "";
        if (motivoInput) motivoInput.value = "";
        overlay.classList.remove("hidden");
    };

    document.querySelectorAll("[data-cancel-tutor]").forEach((btn) => {
        btn.addEventListener("click", () => abrir(btn));
    });

    overlay.addEventListener("click", (event) => {
        if (event.target === overlay) {
            window.cerrarModalCancelarTutor();
        }
    });
})();
</script>

<?php
// Emit confirmed citas timestamps so JS can auto-reload when they go en_curso
$citasConfirmadasPHP = array_values(array_filter($citas ?? [], function($c) {
    return strtolower((string)($c['estado'] ?? '')) === 'confirmada';
}));
?>
<?php if ($citasConfirmadasPHP): ?>
<script>
(function () {
    var confirmadas = <?= json_encode(array_map(function($c) {
        return [
            'fecha' => (string)($c['fecha'] ?? ''),
            'hora'  => (string)($c['hora']  ?? ''),
        ];
    }, $citasConfirmadasPHP), JSON_UNESCAPED_UNICODE) ?>;

    function parseDateTime(fecha, hora) {
        var h = hora.length === 8 ? hora.slice(0, 5) : hora;
        return new Date(fecha + 'T' + h + ':00');
    }

    function checkAndReload() {
        var now = Date.now();
        for (var i = 0; i < confirmadas.length; i++) {
            var c = confirmadas[i];
            if (!c.fecha || !c.hora) continue;
            var dt = parseDateTime(c.fecha, c.hora);
            if (!isNaN(dt.getTime()) && dt.getTime() <= now) {
                window.location.reload();
                return;
            }
        }
    }

    // Check immediately, then every 30 seconds
    checkAndReload();
    setInterval(checkAndReload, 30000);
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
