<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/Mailer.php';
require_once __DIR__ . '/../modelo/UsuarioModel.php';

final class PasswordController
{
    /** Solicitud desde perfil (usuario autenticado). */
    public function solicitarCambio(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=perfil');
        }

        $user = authUser();
        if (!$user) {
            setFlash('error', 'Inicia sesion para cambiar tu contrasena.');
            redirect('index.php?page=inicio');
        }

        try {
            $token  = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            (new UsuarioModel())->setPasswordToken((int) $user['id'], $token, $expira);

            $url    = $this->getBaseUrl() . '/index.php?page=nueva-contrasena&token=' . urlencode($token);
            $mailer = new Mailer();
            $mailer->send(
                (string) $user['email'],
                'Cambio de contrasena - UniLink',
                $this->buildResetEmail((string) $user['nombre'], $url)
            );
        } catch (Throwable $e) {
            setFlash('error', 'No se pudo enviar el correo. Verifica la configuracion SMTP en config/config.php.');
            redirect('index.php?page=perfil');
        }

        setFlash('success', 'Enlace enviado a ' . $user['email'] . '. Tienes 1 hora para usarlo. Revisa tu bandeja de entrada.');
        redirect('index.php?page=perfil');
    }

    /** Solicitud desde el modal de login (cualquier visitante). */
    public function solicitarReset(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=inicio');
        }

        $email = trim($_POST['reset_email'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Ingresa un correo valido.');
            redirect('index.php?page=inicio&auth=login');
        }

        $genericMsg = 'Si el correo esta registrado, recibiras un enlace para restablecer tu contrasena. Revisa tu bandeja de entrada.';

        try {
            $model = new UsuarioModel();
            $user  = $model->findByEmail($email);

            if (!$user) {
                setFlash('success', $genericMsg);
                redirect('index.php?page=inicio&auth=login');
            }

            $token  = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $model->setPasswordToken((int) $user['id'], $token, $expira);

            $url    = $this->getBaseUrl() . '/index.php?page=nueva-contrasena&token=' . urlencode($token);
            $mailer = new Mailer();
            $mailer->send(
                $email,
                'Recuperacion de contrasena - UniLink',
                $this->buildResetEmail((string) $user['nombre'], $url)
            );
        } catch (Throwable $e) {
            setFlash('error', 'No se pudo enviar el correo. Verifica la configuracion SMTP en config/config.php.');
            redirect('index.php?page=inicio&auth=login');
        }

        setFlash('success', $genericMsg);
        redirect('index.php?page=inicio&auth=login');
    }

    /** Muestra el formulario de nueva contrasena (GET con token). */
    public function showResetForm(): void
    {
        $token = trim($_GET['token'] ?? '');

        if ($token === '') {
            setFlash('error', 'Enlace de restablecimiento no valido.');
            redirect('index.php?page=inicio');
        }

        try {
            $user = (new UsuarioModel())->findByPasswordToken($token);
        } catch (Throwable $e) {
            setFlash('error', 'Error al validar el enlace. Intenta de nuevo.');
            redirect('index.php?page=inicio');
        }

        if (!$user) {
            setFlash('error', 'El enlace ha expirado o ya fue utilizado. Solicita uno nuevo.');
            redirect('index.php?page=inicio');
        }

        $pageTitle  = 'Nueva contrasena';
        $resetToken = $token;
        $activePage = '';
        require __DIR__ . '/../vista/paginas/nueva-contrasena.php';
    }

    /** Procesa el formulario (POST). */
    public function doReset(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=inicio');
        }

        $token    = trim($_POST['reset_token']      ?? '');
        $password = trim($_POST['new_password']     ?? '');
        $confirm  = trim($_POST['confirm_password'] ?? '');

        if ($token === '') {
            setFlash('error', 'Token no valido.');
            redirect('index.php?page=inicio');
        }

        $backUrl = 'index.php?page=nueva-contrasena&token=' . urlencode($token);

        if ($password === '' || $confirm === '') {
            setFlash('error', 'Completa ambos campos de contrasena.');
            redirect($backUrl);
        }

        if (strlen($password) < 8) {
            setFlash('error', 'La contrasena debe tener al menos 8 caracteres.');
            redirect($backUrl);
        }

        if ($password !== $confirm) {
            setFlash('error', 'Las contrasenas no coinciden. Verifica e intenta de nuevo.');
            redirect($backUrl);
        }

        try {
            $model = new UsuarioModel();
            $user  = $model->findByPasswordToken($token);

            if (!$user) {
                setFlash('error', 'El enlace ha expirado o ya fue utilizado. Solicita uno nuevo.');
                redirect('index.php?page=inicio');
            }

            $model->updatePassword((int) $user['id'], $password);
        } catch (Throwable $e) {
            setFlash('error', 'No se pudo actualizar la contrasena. Intenta de nuevo.');
            redirect($backUrl);
        }

        // Cerrar sesion si el usuario que cambio la contrasena era el autenticado
        if (isLoggedIn()) {
            $auth = authUser();
            if ($auth && (int) $auth['id'] === (int) $user['id']) {
                unset($_SESSION['auth_user']);
                session_regenerate_id(true);
            }
        }

        setFlash('success', 'Contrasena actualizada correctamente. Ya puedes iniciar sesion con tu nueva contrasena.');
        redirect('index.php?page=inicio&auth=login');
    }

    private function getBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $dir    = dirname($script);
        $dir    = ($dir === '.' || $dir === '/') ? '' : rtrim($dir, '/');
        return $scheme . '://' . $host . $dir;
    }

    private function buildResetEmail(string $nombre, string $resetUrl): string
    {
        $nombre   = htmlspecialchars($nombre,   ENT_QUOTES, 'UTF-8');
        $resetUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Restablece tu contrasena - UniLink</title></head>
<body style="font-family:Arial,sans-serif;background:#f4f6f9;margin:0;padding:30px;">
<div style="max-width:520px;margin:0 auto;background:#fff;border-radius:12px;padding:40px;box-shadow:0 2px 14px rgba(0,0,0,.08);">
    <div style="text-align:center;margin-bottom:28px;">
        <h1 style="color:#1E3A5F;margin:0;font-size:28px;letter-spacing:-0.5px;">UniLink</h1>
        <p style="color:#999;margin:4px 0 0;font-size:13px;">Universidad del Magdalena</p>
    </div>
    <h2 style="color:#1E3A5F;font-size:20px;margin:0 0 12px;">Restablece tu contrasena</h2>
    <p style="color:#444;margin:0 0 8px;">Hola <strong>{$nombre}</strong>,</p>
    <p style="color:#555;line-height:1.7;margin:0 0 8px;">
        Recibimos una solicitud para cambiar la contrasena de tu cuenta en UniLink.
        Haz clic en el boton de abajo para establecer una nueva. El enlace es valido por <strong>1 hora</strong>.
    </p>
    <p style="color:#e07b00;font-size:13px;margin:0 0 24px;">
        Si no solicitaste este cambio, ignora este correo. Tu contrasena no sera modificada.
    </p>
    <div style="text-align:center;margin:0 0 28px;">
        <a href="{$resetUrl}"
           style="background:#1E3A5F;color:#fff;padding:14px 36px;border-radius:8px;text-decoration:none;
                  font-weight:bold;font-size:15px;display:inline-block;letter-spacing:.3px;">
            Cambiar contrasena
        </a>
    </div>
    <p style="color:#999;font-size:13px;margin:0 0 6px;">Si el boton no funciona, copia y pega este enlace:</p>
    <p style="font-size:12px;word-break:break-all;margin:0 0 24px;">
        <a href="{$resetUrl}" style="color:#4a90d9;">{$resetUrl}</a>
    </p>
    <hr style="border:none;border-top:1px solid #eee;margin:0 0 16px;">
    <p style="color:#ccc;font-size:11px;text-align:center;margin:0;">
        Este enlace expira automaticamente despues de 1 hora o tras ser utilizado una vez.
    </p>
</div>
</body>
</html>
HTML;
    }
}
