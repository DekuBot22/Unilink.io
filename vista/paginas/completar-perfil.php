<?php
$pageTitle = 'Completa tu perfil';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/flash.php';
?>

<div class="completar-perfil-wrap">
    <div class="completar-perfil-card">

        <div class="completar-perfil-icon">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="24" cy="24" r="24" fill="#EBF3FF"/>
                <path d="M24 14a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 12c5.52 0 10 2.24 10 5v2H14v-2c0-2.76 4.48-5 10-5z" fill="#1E3A5F"/>
            </svg>
        </div>

        <h2 class="completar-perfil-title">Completa tu perfil</h2>
        <p class="completar-perfil-sub">
            Iniciaste sesion con Google. Necesitamos un poco mas de informacion para personalizar tu experiencia en UniLink.
        </p>

        <form class="completar-perfil-form" method="post" action="index.php?action=perfil/completar">

            <div class="form-group">
                <label for="cpCarrera">Carrera *</label>
                <select id="cpCarrera" name="carrera" required>
                    <option value="">Selecciona tu carrera</option>
                    <option <?= old('carrera') === 'Ingenieria de Sistemas'         ? 'selected' : '' ?>>Ingenieria de Sistemas</option>
                    <option <?= old('carrera') === 'Ingenieria Civil'               ? 'selected' : '' ?>>Ingenieria Civil</option>
                    <option <?= old('carrera') === 'Medicina'                       ? 'selected' : '' ?>>Medicina</option>
                    <option <?= old('carrera') === 'Derecho'                        ? 'selected' : '' ?>>Derecho</option>
                    <option <?= old('carrera') === 'Administracion de Empresas'     ? 'selected' : '' ?>>Administracion de Empresas</option>
                    <option <?= old('carrera') === 'Biologia'                       ? 'selected' : '' ?>>Biologia</option>
                </select>
            </div>

            <div class="form-group">
                <label for="cpCodigo">Codigo estudiantil *</label>
                <input id="cpCodigo" name="codigo" type="text" inputmode="numeric" maxlength="10"
                       pattern="\d{1,10}" placeholder="Codigo estudiantil"
                       value="<?= e(old('codigo')) ?>" required>
            </div>

            <div class="form-group">
                <label for="cpTelefono">Telefono / WhatsApp *</label>
                <input id="cpTelefono" name="telefono" type="text" inputmode="numeric" maxlength="10"
                       pattern="\d{1,10}" placeholder="Numero de telefono"
                       value="<?= e(old('telefono')) ?>" required>
            </div>

            <button type="submit" class="btn-submit">Guardar y continuar</button>
        </form>

    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
