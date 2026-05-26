<?php
$pageTitle = $pageTitle ?? 'Nueva contrasena';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/flash.php';
$resetToken = $resetToken ?? '';
?>

<div class="completar-perfil-wrap">
    <div class="completar-perfil-card">

        <div class="completar-perfil-icon">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="24" cy="24" r="24" fill="#EBF3FF"/>
                <path d="M32 21h-1v-3a7 7 0 1 0-14 0v3h-1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V23a2 2 0 0 0-2-2zm-9 7.72V31a1 1 0 0 0 2 0v-2.28A2 2 0 1 0 23 28.72zM29 21H19v-3a5 5 0 1 1 10 0v3z" fill="#1E3A5F"/>
            </svg>
        </div>

        <h2 class="completar-perfil-title">Nueva contrasena</h2>
        <p class="completar-perfil-sub">
            Ingresa tu nueva contrasena dos veces para confirmar el cambio.
        </p>

        <form class="completar-perfil-form" method="post" action="index.php?action=password/do-reset" id="resetForm">
            <input type="hidden" name="reset_token" value="<?= e($resetToken) ?>">

            <div class="form-group">
                <label for="newPassword">Nueva contrasena *</label>
                <div style="position:relative;">
                    <input id="newPassword" name="new_password" type="password" minlength="8"
                           placeholder="Minimo 8 caracteres" required autocomplete="new-password"
                           style="padding-right:44px;">
                    <button type="button" class="toggle-password" data-target="newPassword" aria-label="Mostrar contrasena">
                        <svg class="eye-icon eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-icon eye-hide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
                <small id="strengthMsg" style="font-size:12px;color:#888;margin-top:4px;display:block;"></small>
            </div>

            <div class="form-group">
                <label for="confirmPassword">Confirmar contrasena *</label>
                <div style="position:relative;">
                    <input id="confirmPassword" name="confirm_password" type="password" minlength="8"
                           placeholder="Repite la contrasena" required autocomplete="new-password"
                           style="padding-right:44px;">
                    <button type="button" class="toggle-password" data-target="confirmPassword" aria-label="Mostrar contrasena">
                        <svg class="eye-icon eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-icon eye-hide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
                <small id="matchMsg" style="font-size:12px;margin-top:4px;display:block;"></small>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">Cambiar contrasena</button>
        </form>

    </div>
</div>

<style>
.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #888;
    padding: 0;
    display: flex;
    align-items: center;
}
.toggle-password:hover { color: #1E3A5F; }
</style>

<script>
(function () {
    // Mostrar/ocultar contrasena
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = this.dataset.target;
            var input    = document.getElementById(targetId);
            if (!input) return;
            var isText = input.type === 'text';
            input.type = isText ? 'password' : 'text';
            this.querySelector('.eye-show').style.display = isText ? ''     : 'none';
            this.querySelector('.eye-hide').style.display = isText ? 'none' : '';
        });
    });

    var pwdInput  = document.getElementById('newPassword');
    var confInput = document.getElementById('confirmPassword');
    var strengthEl = document.getElementById('strengthMsg');
    var matchEl    = document.getElementById('matchMsg');
    var submitBtn  = document.getElementById('submitBtn');

    function checkStrength(val) {
        if (!strengthEl) return;
        if (val.length === 0) { strengthEl.textContent = ''; return; }
        if (val.length < 8)   { strengthEl.textContent = 'Muy corta (minimo 8 caracteres)'; strengthEl.style.color = '#c00'; return; }
        var score = 0;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;
        if (score === 0)      { strengthEl.textContent = 'Debil';  strengthEl.style.color = '#e07b00'; }
        else if (score === 1) { strengthEl.textContent = 'Regular'; strengthEl.style.color = '#e0a800'; }
        else                  { strengthEl.textContent = 'Fuerte';  strengthEl.style.color = '#2a7a2a'; }
    }

    function checkMatch() {
        if (!matchEl || !confInput || !pwdInput) return;
        var p = pwdInput.value;
        var c = confInput.value;
        if (c.length === 0)  { matchEl.textContent = ''; return; }
        if (p === c) { matchEl.textContent = 'Las contrasenas coinciden'; matchEl.style.color = '#2a7a2a'; }
        else         { matchEl.textContent = 'Las contrasenas no coinciden'; matchEl.style.color = '#c00'; }
    }

    if (pwdInput)  pwdInput.addEventListener('input',  function () { checkStrength(this.value); checkMatch(); });
    if (confInput) confInput.addEventListener('input',  checkMatch);

    // Validacion antes de enviar
    document.getElementById('resetForm')?.addEventListener('submit', function (e) {
        if (pwdInput.value !== confInput.value) {
            e.preventDefault();
            if (matchEl) { matchEl.textContent = 'Las contrasenas no coinciden'; matchEl.style.color = '#c00'; }
            confInput.focus();
        }
    });
})();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
