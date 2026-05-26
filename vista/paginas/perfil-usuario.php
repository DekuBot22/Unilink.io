<?php
$pageTitle = 'Mi perfil';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/flash.php';
require __DIR__ . '/../partials/auth-modal.php';

$user = $user ?? authUser();
$nombre = $user['nombre'] ?? 'Usuario';
$rol = $user['rol'] ?? 'estudiante';
$correo = $user['email'] ?? '';
$fotoPerfil = $user['foto_perfil'] ?? null;
$initials = 'U';
$citas = $citas ?? [];
$tutores = $tutores ?? [];
$tutoresJson = json_encode($tutores, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($tutoresJson === false) {
    $tutoresJson = '[]';
}
$estadoOptions = [
    'pendiente' => 'Pendiente',
    'confirmada' => 'Confirmada',
    'en_curso' => 'En curso',
    'cancelada' => 'Cancelada',
    'completada' => 'Completada',
];

$parts = preg_split('/\s+/', trim($nombre)) ?: [];
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
?>

<section class="perfil-page">
    <div class="perfil-container">
        <aside class="perfil-aside">
            <div class="avatar-wrapper">
                <?php if ($fotoPerfil): ?>
                    <img src="<?= e($fotoPerfil) ?>" alt="Foto de perfil" class="perfil-avatar-img" id="previewAvatar">
                <?php else: ?>
                    <div class="perfil-avatar-grande" id="previewAvatarInitials"><?= e($initials) ?></div>
                <?php endif; ?>
                <label class="btn-cambiar-foto" for="inputFotoPerfil" title="Cambiar foto">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                </label>
            </div>
            <form method="post" action="index.php?action=perfil/foto" enctype="multipart/form-data" id="formFotoPerfil">
                <input type="file" name="foto_perfil" id="inputFotoPerfil" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;">
            </form>
            <h2><?= e($nombre) ?></h2>
            <p class="carrera"><?= e(ucfirst($rol)) ?> · Universidad del Magdalena</p>
            <span class="badge-verificado">Cuenta activa</span>
            <div class="perfil-stats">
                <div class="stat-item">
                    <div class="stat-num"><?= e(ucfirst($rol)) ?></div>
                    <div class="stat-label">Rol</div>
                </div>
            </div>
            <a href="index.php?action=auth/logout" class="btn-contactar" style="text-decoration:none;display:block;">Cerrar sesion</a>
        </aside>

        <div class="perfil-main">
            <div class="perfil-card">
                <h3>Datos de cuenta</h3>
                <p><strong>Nombre:</strong> <?= e($nombre) ?></p>
                <p><strong>Correo:</strong> <?= e($correo) ?></p>
            </div>

            <div class="perfil-card">
                <h3>Cambiar contrasena</h3>
                <p style="color:#555;font-size:14px;line-height:1.6;margin:0 0 16px;">
                    Para cambiar tu contrasena te enviaremos un enlace seguro a
                    <strong><?= e($correo) ?></strong>.
                    El enlace expira en <strong>1 hora</strong>.
                </p>
                <form method="post" action="index.php?action=password/solicitar-cambio">
                    <button type="submit" class="btn-submit" style="max-width:280px;">
                        Enviar enlace de cambio de contrasena
                    </button>
                </form>
            </div>
            <div class="perfil-card">
                <h3>Mis citas</h3>
                <?php if (!$citas): ?>
                    <p>No tienes citas agendadas aun.</p>
                <?php else: ?>
                    <div class="citas-list">
                        <?php
                        foreach ($citas as $cita):
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
                            $materiaValue = (string) ($cita['materia'] ?? '');
                            $modalidadValue = (string) ($cita['modalidad'] ?? '');
                            $enlaceReunion = (string) ($cita['enlace_reunion'] ?? '');
                            $tutorName = (string) ($cita['tutor_nombre'] ?? '');
                            $citaId = (string) ($cita['id'] ?? '');
                            $tutorId = isset($cita['tutor_id']) ? (string) $cita['tutor_id'] : '';
                            $canceladaPor = strtolower((string) ($cita['cancelada_por'] ?? ''));
                            $cancelacionMotivo = (string) ($cita['cancelacion_motivo'] ?? '');
                            $cancelLabel = '';
                            if ($estadoKey === 'cancelada') {
                                if ($canceladaPor === 'tutor') {
                                    $cancelLabel = 'Cancelada por el tutor.';
                                } elseif ($canceladaPor === 'estudiante') {
                                    $cancelLabel = 'Cancelada por ti.';
                                }
                            }
                            $canEdit = $estadoKey === 'pendiente';
                            $canCancel = in_array($estadoKey, ['pendiente', 'confirmada'], true);
                            $fechaLabel = $fechaValue;
                            $fechaHora = null;
                            if ($fechaLabel !== '') {
                                $fecha = DateTime::createFromFormat('Y-m-d', $fechaLabel);
                                if ($fecha) {
                                    $fechaLabel = $fecha->format('d/m/Y');
                                    if ($horaValue !== '') {
                                        $fechaHora = DateTime::createFromFormat('Y-m-d H:i', $fecha->format('Y-m-d') . ' ' . $horaValue);
                                    }
                                }
                            }
                            $now = new DateTime('now');
                            $modalidadLower = strtolower($modalidadValue);
                            $esVirtual  = str_contains($modalidadLower, 'virtual') || str_contains($modalidadLower, 'ambas');
                            $esUniLink  = str_contains($modalidadLower, 'videollamada unilink');

                            // canJoin: external-link virtual sessions
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
                        ?>
                            <div class="cita-item">
                                <div class="cita-info">
                                    <div class="cita-title">Tutor: <?= e($tutorName) ?></div>
                                    <div class="cita-meta">Materia: <?= e($materiaValue) ?></div>
                                    <div class="cita-meta">Modalidad: <?= e($modalidadValue) ?></div>
                                    <div class="cita-meta">Fecha: <?= e($fechaLabel) ?> · Hora: <?= e($horaValue) ?></div>
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
                                            <div class="cita-meta" style="color:#b00;">Enlace no disponible. Verifica que el tutor haya incluido el link al confirmar.</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="cita-side">
                                    <span class="cita-status cita-status-<?= e($estadoKey) ?>"><?= e($estadoLabel) ?></span>
                                    <?php if ($canEdit || $canCancel): ?>
                                        <div class="cita-actions">
                                            <?php if ($canEdit): ?>
                                                <button type="button" class="btn-cita-action" data-edit-cita data-cita-id="<?= e($citaId) ?>" data-tutor-id="<?= e($tutorId) ?>" data-tutor="<?= e($tutorName) ?>" data-materia="<?= e($materiaValue) ?>" data-fecha="<?= e($fechaValue) ?>" data-hora="<?= e($horaValue) ?>" onclick="abrirModalEditarCitaFromButton(this)">Editar</button>
                                            <?php endif; ?>
                                            <?php if ($canCancel): ?>
                                                <button type="button" class="btn-cita-action btn-cita-danger" data-cancel-cita data-cita-id="<?= e($citaId) ?>" data-tutor="<?= e($tutorName) ?>" data-materia="<?= e($materiaValue) ?>" data-fecha="<?= e($fechaValue) ?>" data-hora="<?= e($horaValue) ?>" onclick="abrirModalCancelarCita(this)">Cancelar</button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($canJoin): ?>
                                        <div class="cita-actions">
                                            <a class="btn-cita-action" href="<?= e($enlaceReunion) ?>" target="_blank" rel="noopener">Unirse</a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($canUniLink): ?>
                                        <div class="cita-actions">
                                            <a class="btn-cita-action btn-unilink" href="index.php?page=videollamada&id=<?= e($citaId) ?>">
                                                Entrar a videollamada
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
window.tutoresData = <?= $tutoresJson ?>;
</script>

<div class="modal-overlay hidden" id="modalEditarCita">
    <div class="modal">
        <button class="modal-close" type="button" onclick="cerrarModalEditarCita()">X</button>
        <h3>Editar cita con <span id="modalEditarTutor"></span></h3>
        <form method="post" action="index.php?action=citas/update">
            <input type="hidden" name="cita_id" id="editarCitaId" value="">
            <div class="form-group">
                <label>Materia</label>
                <select name="cita_materia" id="editarCitaMateria" required>
                    <option value="">Selecciona materia</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha</label>
                <input type="date" name="cita_fecha" id="editarCitaFecha" required>
            </div>
            <div class="form-group">
                <label>Hora</label>
                <input type="time" name="cita_hora" id="editarCitaHora" required>
            </div>
            <button type="submit" class="btn-submit">Guardar cambios</button>
        </form>
    </div>
</div>

<div class="modal-overlay hidden" id="modalCancelarCita">
    <div class="modal">
        <button class="modal-close" type="button" onclick="cerrarModalCancelarCita()">X</button>
        <h3>Cancelar cita</h3>
        <p class="modal-text" id="modalCancelarDetalle"></p>
        <form method="post" action="index.php?action=citas/cancel">
            <input type="hidden" name="cita_id" id="cancelarCitaId" value="">
            <div class="form-group">
                <label>Motivo de cancelacion *</label>
                <textarea name="cita_motivo" id="cancelarCitaMotivo" rows="3" maxlength="300" required></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModalCancelarCita()">Volver</button>
                <button type="submit" class="btn-submit btn-submit-danger">Cancelar cita</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const overlayEdit = document.getElementById("modalEditarCita");
    const overlayCancel = document.getElementById("modalCancelarCita");
    if ((!overlayEdit && !overlayCancel) || window.__citasEditBound) return;
    window.__citasEditBound = true;

    const obtenerTutores = () => Array.isArray(window.tutoresData) ? window.tutoresData : [];
    const buscarTutor = (id) => obtenerTutores().find((tutor) => String(tutor.id) === String(id)) || null;

    const llenarMaterias = (select, materias, selected) => {
        if (!select) return;

        const lista = Array.isArray(materias) ? materias.slice() : [];
        if (selected && !lista.includes(selected)) {
            lista.unshift(selected);
        }

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

    window.cerrarModalEditarCita = window.cerrarModalEditarCita || function () {
        overlayEdit?.classList.add("hidden");
    };

    window.abrirModalEditarCitaFromButton = window.abrirModalEditarCitaFromButton || function (btn) {
        if (!btn) return;

        const tutor = btn.dataset.tutor || "";
        const materia = btn.dataset.materia || "";
        const fecha = btn.dataset.fecha || "";
        const hora = btn.dataset.hora || "";
        const citaId = btn.dataset.citaId || "";
        const tutorId = btn.dataset.tutorId || "";

        const tutorSpan = document.getElementById("modalEditarTutor");
        if (tutorSpan) tutorSpan.textContent = tutor || "tutor";

        const idInput = document.getElementById("editarCitaId");
        if (idInput) idInput.value = citaId;

        const fechaInput = document.getElementById("editarCitaFecha");
        if (fechaInput) fechaInput.value = fecha;

        const horaInput = document.getElementById("editarCitaHora");
        if (horaInput) horaInput.value = hora;

        const materiaSelect = document.getElementById("editarCitaMateria");
        const tutorData = buscarTutor(tutorId);
        const materias = tutorData && Array.isArray(tutorData.materias) ? tutorData.materias : [];
        const materiasFinal = materias.length ? materias : (materia ? [materia] : []);
        llenarMaterias(materiaSelect, materiasFinal, materia);

        overlayEdit?.classList.remove("hidden");
        materiaSelect?.focus();
    };

    window.cerrarModalCancelarCita = window.cerrarModalCancelarCita || function () {
        overlayCancel?.classList.add("hidden");
    };

    window.abrirModalCancelarCita = window.abrirModalCancelarCita || function (btn) {
        if (!btn || !overlayCancel) return;

        const tutor = btn.dataset.tutor || "";
        const materia = btn.dataset.materia || "";
        const fecha = btn.dataset.fecha || "";
        const hora = btn.dataset.hora || "";
        const citaId = btn.dataset.citaId || "";

        const detalle = document.getElementById("modalCancelarDetalle");
        const fechaLabel = fecha && fecha.includes("-") ? fecha.split("-").reverse().join("/") : fecha;
        const texto = `Vas a cancelar la cita con ${tutor || "el tutor"} para ${materia || "la materia"} el ${fechaLabel || ""}${hora ? " a las " + hora : ""}.`;
        if (detalle) detalle.textContent = texto.trim();

        const idInput = document.getElementById("cancelarCitaId");
        if (idInput) idInput.value = citaId;

        const motivoInput = document.getElementById("cancelarCitaMotivo");
        if (motivoInput) motivoInput.value = "";

        overlayCancel.classList.remove("hidden");
    };

    document.querySelectorAll("[data-edit-cita]").forEach((btn) => {
        btn.addEventListener("click", () => window.abrirModalEditarCitaFromButton(btn));
    });

    document.querySelectorAll("[data-cancel-cita]").forEach((btn) => {
        btn.addEventListener("click", () => window.abrirModalCancelarCita(btn));
    });

    overlayEdit?.addEventListener("click", (event) => {
        if (event.target === overlayEdit) {
            window.cerrarModalEditarCita();
        }
    });

    overlayCancel?.addEventListener("click", (event) => {
        if (event.target === overlayCancel) {
            window.cerrarModalCancelarCita();
        }
    });
})();
</script>

<script>
(function () {
    const input = document.getElementById('inputFotoPerfil');
    const form  = document.getElementById('formFotoPerfil');
    if (!input || !form) return;

    input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            alert('La imagen no puede superar los 2 MB.');
            this.value = '';
            return;
        }

        const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!allowed.includes(file.type)) {
            alert('Solo se permiten imagenes JPG, PNG, WebP o GIF.');
            this.value = '';
            return;
        }

        // Live preview before upload
        const reader = new FileReader();
        reader.onload = function (e) {
            const existing = document.getElementById('previewAvatar');
            const initials = document.getElementById('previewAvatarInitials');
            if (existing) {
                existing.src = e.target.result;
            } else if (initials) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Foto de perfil';
                img.className = 'perfil-avatar-img';
                img.id = 'previewAvatar';
                initials.replaceWith(img);
            }
        };
        reader.readAsDataURL(file);

        form.submit();
    });
})();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
