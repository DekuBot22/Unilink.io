<?php
$authFlash = null;
if (in_array(($_GET['auth'] ?? ''), ['login', 'success'], true)) {
    $authFlash = getFlash();
}
?>

<div class="modal-overlay hidden" id="authModalOverlay" role="dialog" aria-modal="true" aria-labelledby="authModalTitle">
    <div class="modal auth-modal">
        <button class="modal-close" type="button" data-close-auth aria-label="Cerrar">X</button>

        <section class="auth-view" data-auth-view="login">
            <h3 id="authModalTitle">Iniciar sesion</h3>
            <p class="auth-subtitle">Accede con tu correo y contrasena para continuar.</p>
            <?php if ($authFlash): ?>
                <div class="auth-alert <?= $authFlash['type'] === 'error' ? 'auth-alert-error' : 'auth-alert-success' ?>">
                    <?= e($authFlash['message']) ?>
                </div>
            <?php endif; ?>
            <a href="index.php?action=auth/google" class="btn-google">
                <svg width="20" height="20" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    <path fill="none" d="M0 0h48v48H0z"/>
                </svg>
                Continuar con Google
            </a>
            <div class="auth-divider"><span>o</span></div>
            <form id="loginForm" class="auth-form" method="post" action="index.php?action=auth/login" data-server-auth="1">
                <div class="form-group">
                    <label for="loginEmail">Correo institucional *</label>
                    <input id="loginEmail" name="login_email" type="email" placeholder="nombre@unimagdalena.edu.co" value="<?= e(old('login_email')) ?>" required>
                </div>
                <div class="form-group">
                    <label for="loginPassword">Contrasena *</label>
                    <input id="loginPassword" name="login_password" type="password" minlength="8" placeholder="Ingresa tu contrasena" required>
                </div>
                <button type="submit" class="btn-submit">Entrar</button>
            </form>
            <div class="auth-links">
                <button type="button" class="auth-link" data-auth-target="recover">Olvidaste tu contrasena?</button>
                <button type="button" class="auth-link" data-auth-target="resend-verify">No recibiste el correo de verificacion?</button>
                <button type="button" class="auth-link" data-auth-target="register">Crear cuenta</button>
            </div>
        </section>

        <section class="auth-view hidden" data-auth-view="register">
            <h3>Crear cuenta</h3>
            <p class="auth-subtitle">Registrate para buscar tutorias o brindar apoyo academico.</p>
            <a href="index.php?action=auth/google" class="btn-google">
                <svg width="20" height="20" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    <path fill="none" d="M0 0h48v48H0z"/>
                </svg>
                Registrarse con Google
            </a>
            <div class="auth-divider"><span>o con correo</span></div>
            <form id="registerForm" class="auth-form" method="post" action="index.php?action=auth/register" data-server-auth="1">
                <div class="form-group">
                    <label for="registerName">Nombre completo *</label>
                    <input id="registerName" name="register_name" type="text" placeholder="Tu nombre completo" required>
                </div>
                <div class="form-group">
                    <label for="registerEmail">Correo *</label>
                    <input id="registerEmail" name="register_email" type="email" placeholder="nombre@unimagdalena.edu.co" required>
                </div>
                <div class="form-group">
                    <label for="registerCarrera">Carrera *</label>
                    <select id="registerCarrera" name="register_carrera" required>
                        <option value="">Selecciona tu carrera</option>
                        <option>Ingenieria de Sistemas</option>
                        <option>Ingenieria Civil</option>
                        <option>Medicina</option>
                        <option>Derecho</option>
                        <option>Administracion de Empresas</option>
                        <option>Biologia</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="registerCodigo">Codigo estudiantil *</label>
                    <input id="registerCodigo" name="register_codigo" type="text" inputmode="numeric" maxlength="10" pattern="\d{1,10}" placeholder="Codigo estudiantil" required>
                </div>
                <div class="form-group">
                    <label for="registerTelefono">Telefono / WhatsApp *</label>
                    <input id="registerTelefono" name="register_telefono" type="text" inputmode="numeric" maxlength="10" pattern="\d{1,10}" placeholder="Numero de telefono" required>
                </div>
                <div class="form-group">
                    <label for="registerPassword">Contrasena *</label>
                    <input id="registerPassword" name="register_password" type="password" minlength="8" placeholder="Minimo 8 caracteres" required>
                </div>
                <button type="submit" class="btn-submit">Registrarme</button>
            </form>
            <div class="auth-links auth-links-single">
                <button type="button" class="auth-link" data-auth-target="login">Ya tengo cuenta</button>
            </div>
        </section>

        <section class="auth-view hidden" data-auth-view="recover">
            <h3>Recuperar contrasena</h3>
            <p class="auth-subtitle">Ingresa tu correo y te enviaremos un enlace para restablecer tu contrasena. El enlace expira en 1 hora.</p>
            <form class="auth-form" method="post" action="index.php?action=password/solicitar-reset">
                <div class="form-group">
                    <label for="resetEmail">Correo registrado *</label>
                    <input id="resetEmail" name="reset_email" type="email" placeholder="nombre@ejemplo.com" required>
                </div>
                <button type="submit" class="btn-submit">Enviar enlace</button>
            </form>
            <div class="auth-links auth-links-single">
                <button type="button" class="auth-link" data-auth-target="login">Volver al inicio de sesion</button>
            </div>
        </section>

        <section class="auth-view hidden" data-auth-view="resend-verify">
            <h3>Reenviar verificacion</h3>
            <p class="auth-subtitle">Ingresa tu correo y te enviaremos un nuevo enlace de activacion.</p>
            <form class="auth-form" method="post" action="index.php?action=auth/resend">
                <div class="form-group">
                    <label for="resendEmail">Correo registrado *</label>
                    <input id="resendEmail" name="resend_email" type="email" placeholder="nombre@ejemplo.com" required>
                </div>
                <button type="submit" class="btn-submit">Reenviar enlace</button>
            </form>
            <div class="auth-links auth-links-single">
                <button type="button" class="auth-link" data-auth-target="login">Volver al inicio de sesion</button>
            </div>
        </section>
    </div>
</div>

<script>
(function () {
    // Pre-rellenar correo desde login al cambiar a recover o resend-verify
    function prefillEmail(targetAttr, targetInputId) {
        document.querySelectorAll('[data-auth-target="' + targetAttr + '"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var loginEmail  = document.getElementById('loginEmail');
                var targetInput = document.getElementById(targetInputId);
                if (loginEmail && targetInput && loginEmail.value.trim() !== '') {
                    targetInput.value = loginEmail.value.trim();
                }
            });
        });
    }
    prefillEmail('recover',        'resetEmail');
    prefillEmail('resend-verify',  'resendEmail');
})();
</script>
