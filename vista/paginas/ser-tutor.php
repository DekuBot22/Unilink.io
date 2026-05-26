<?php
$pageTitle = 'Ser Tutor';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/flash.php';
require __DIR__ . '/../partials/auth-modal.php';

$user = authUser();
$prefillNombre = $user['nombre'] ?? old('tutor_nombre');
$prefillCodigo = $user['codigo'] ?? old('tutor_codigo');
$prefillCorreo = $user['email'] ?? old('tutor_correo');
$prefillTelefono = $user['telefono'] ?? old('tutor_telefono');
$prefillCarrera = $user['carrera'] ?? old('tutor_carrera');

$oldMaterias = $_SESSION['old']['tutor_materias'] ?? [];
if (!is_array($oldMaterias)) {
    $oldMaterias = $oldMaterias !== '' ? [$oldMaterias] : [];
}

$lockNombre = $user && $prefillNombre !== '';
$lockCodigo = $user && $prefillCodigo !== '';
$lockCorreo = $user && $prefillCorreo !== '';
$lockTelefono = $user && $prefillTelefono !== '';
$lockCarrera = $user && $prefillCarrera !== '';
?>

<section class="tutor-hero">
    <h2>Conviertete en Tutor Voluntario</h2>
    <p>Comparte tu conocimiento y ayuda a tus companeros.</p>
    <a href="#postulacion" class="btn-primary">Postularme ahora</a>
</section>

<section class="postulacion" id="postulacion">
    <div class="form-container">
        <h2>Formulario de Postulacion</h2>
        <p>Completa todos los campos para iniciar tu proceso</p>

        <form id="postulacionForm" method="post" action="index.php?action=postulacion/store" data-server-postulacion="1">
            <div class="form-row">
                <div class="form-group">
                    <label>Nombre completo *</label>
                    <input id="tutorNombre" name="tutor_nombre" type="text" required value="<?= e($prefillNombre) ?>" <?= $lockNombre ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label>Codigo estudiantil *</label>
                    <input id="tutorCodigo" name="tutor_codigo" type="text" inputmode="numeric" maxlength="10" required value="<?= e($prefillCodigo) ?>" <?= $lockCodigo ? 'readonly' : '' ?>>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Correo institucional *</label>
                    <input id="tutorCorreo" name="tutor_correo" type="email" required value="<?= e($prefillCorreo) ?>" <?= $lockCorreo ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label>Telefono / WhatsApp *</label>
                    <input id="tutorTelefono" name="tutor_telefono" type="text" inputmode="numeric" maxlength="10" required value="<?= e($prefillTelefono) ?>" <?= $lockTelefono ? 'readonly' : '' ?>>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Carrera *</label>
                    <select id="tutorCarrera" name="tutor_carrera" required <?= $lockCarrera ? 'disabled' : '' ?>>
                        <option value="">Selecciona tu carrera</option>
                        <option <?= $prefillCarrera === 'Ingenieria de Sistemas' ? 'selected' : '' ?>>Ingenieria de Sistemas</option>
                        <option <?= $prefillCarrera === 'Ingenieria Civil' ? 'selected' : '' ?>>Ingenieria Civil</option>
                        <option <?= $prefillCarrera === 'Medicina' ? 'selected' : '' ?>>Medicina</option>
                        <option <?= $prefillCarrera === 'Derecho' ? 'selected' : '' ?>>Derecho</option>
                        <option <?= $prefillCarrera === 'Administracion de Empresas' ? 'selected' : '' ?>>Administracion de Empresas</option>
                        <option <?= $prefillCarrera === 'Biologia' ? 'selected' : '' ?>>Biologia</option>
                    </select>
                    <?php if ($lockCarrera): ?>
                        <input type="hidden" name="tutor_carrera" value="<?= e($prefillCarrera) ?>">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Semestre actual *</label>
                    <select id="tutorSemestre" name="tutor_semestre" required>
                        <option value="">Semestre</option>
                        <option <?= old('tutor_semestre') === '3er semestre' ? 'selected' : '' ?>>3er semestre</option>
                        <option <?= old('tutor_semestre') === '4to semestre' ? 'selected' : '' ?>>4to semestre</option>
                        <option <?= old('tutor_semestre') === '5to semestre' ? 'selected' : '' ?>>5to semestre</option>
                        <option <?= old('tutor_semestre') === '6to semestre' ? 'selected' : '' ?>>6to semestre</option>
                        <option <?= old('tutor_semestre') === '7mo semestre' ? 'selected' : '' ?>>7mo semestre</option>
                        <option <?= old('tutor_semestre') === '8vo semestre' ? 'selected' : '' ?>>8vo semestre</option>
                        <option <?= old('tutor_semestre') === '9no semestre' ? 'selected' : '' ?>>9no semestre</option>
                        <option <?= old('tutor_semestre') === '10mo semestre+' ? 'selected' : '' ?>>10mo semestre+</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Materias a las que deseas postularte *</label>
                <p class="form-hint">Debes seleccionar al menos una materia y puedes escoger mas.</p>
                <div class="materias-check-grid" id="materiasSelectTutor">
                    <?php
                    $materiasOpciones = [
                        'Programacion',
                        'Calculo I',
                        'Calculo II',
                        'Algebra Lineal',
                        'Fisica I',
                        'Quimica',
                        'Estadistica',
                        'Biologia',
                        'Derecho Constitucional',
                        'Bases de Datos',
                    ];
                    foreach ($materiasOpciones as $index => $materia):
                        $checked = in_array($materia, $oldMaterias, true);
                        $required = $index === 0 ? 'required' : '';
                    ?>
                        <label class="materia-check">
                            <input type="checkbox" name="tutor_materias[]" value="<?= e($materia) ?>" <?= $checked ? 'checked' : '' ?> <?= $required ?>>
                            <span><?= e($materia) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Por que quieres ser tutor? *</label>
                <textarea id="tutorMotivacion" name="tutor_motivacion" rows="4" required><?= e(old('tutor_motivacion')) ?></textarea>
            </div>
            <div class="form-group">
                <label>Modalidad preferida</label>
                <select id="tutorModalidad" name="tutor_modalidad">
                    <option <?= old('tutor_modalidad', 'Virtual (Meet/Zoom)') === 'Virtual (Meet/Zoom)' ? 'selected' : '' ?>>Virtual (Meet/Zoom)</option>
                    <option <?= old('tutor_modalidad') === 'Presencial' ? 'selected' : '' ?>>Presencial</option>
                    <option <?= old('tutor_modalidad') === 'Ambas modalidades' ? 'selected' : '' ?>>Ambas modalidades</option>
                </select>
            </div>
            <button type="submit" class="btn-submit">Enviar postulacion</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
