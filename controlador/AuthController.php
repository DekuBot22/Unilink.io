<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/Mailer.php';
require_once __DIR__ . '/../modelo/UsuarioModel.php';

final class AuthController
{
    private UsuarioModel $model;

    public function __construct()
    {
        $this->model = new UsuarioModel();
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=inicio');
        }

        $email    = trim($_POST['login_email']    ?? '');
        $password = trim($_POST['login_password'] ?? '');

        if ($email === '' || $password === '') {
            setFlash('error', 'Completa correo y contrasena para iniciar sesion.');
            setOld(['login_email' => $email]);
            redirect('index.php?page=inicio&auth=login');
        }

        try {
            $user = $this->model->findByEmail($email);
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo conectar con la base de datos para iniciar sesion.');
            setOld(['login_email' => $email]);
            redirect('index.php?page=inicio&auth=login');
        }

        if (!$user) {
            setFlash('error', 'No existe una cuenta registrada con ese correo.');
            setOld(['login_email' => $email]);
            redirect('index.php?page=inicio&auth=login');
        }

        if (!password_verify($password, $user['password'])) {
            setFlash('error', 'Contrasena incorrecta.');
            setOld(['login_email' => $email]);
            redirect('index.php?page=inicio&auth=login');
        }

        // Verificacion de correo — si la columna no existe en la BD aun, ?? 1 asume verificado
        if (!(bool) ($user['email_verificado'] ?? 1)) {
            setFlash('error', 'Debes verificar tu correo antes de iniciar sesion. Revisa tu bandeja de entrada o usa "Reenviar verificacion".');
            setOld(['login_email' => $email]);
            redirect('index.php?page=inicio&auth=login');
        }

        $estado = strtolower((string) ($user['estado'] ?? 'activo'));
        if ($estado === 'baneado') {
            $motivo  = trim((string) ($user['ban_motivo'] ?? ''));
            $mensaje = $motivo !== ''
                ? 'Tu cuenta ha sido bloqueada. Motivo: ' . $motivo
                : 'Tu cuenta ha sido bloqueada. Contacta al administrador.';
            setFlash('error', $mensaje);
            setOld(['login_email' => $email]);
            redirect('index.php?page=inicio&auth=login');
        }

        $_SESSION['auth_user'] = [
            'id'          => (int) $user['id'],
            'nombre'      => $user['nombre'],
            'email'       => $user['email'],
            'carrera'     => $user['carrera'] ?? '',
            'codigo'      => $user['codigo']  ?? '',
            'telefono'    => $user['telefono'] ?? '',
            'rol'         => $user['rol'],
            'foto_perfil' => $user['foto_perfil'] ?? null,
        ];

        clearOld();
        setFlash('success', 'Sesion iniciada correctamente. Bienvenido, ' . $user['nombre'] . '.');

        if ($user['rol'] === 'tutor') {
            redirect('index.php?page=tutor');
        }

        if ($user['rol'] === 'admin') {
            redirect('index.php?page=admin');
        }

        redirect('index.php?page=inicio&auth=success');
    }

    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=inicio');
        }

        $nombre   = trim($_POST['register_name']     ?? '');
        $email    = trim($_POST['register_email']    ?? '');
        $carrera  = trim($_POST['register_carrera']  ?? '');
        $codigo   = trim($_POST['register_codigo']   ?? '');
        $telefono = trim($_POST['register_telefono'] ?? '');
        $password = trim($_POST['register_password'] ?? '');

        if ($nombre === '' || $email === '' || $carrera === '' || $codigo === '' || $telefono === '' || $password === '') {
            setFlash('error', 'Completa todos los campos para crear tu cuenta.');
            redirect('index.php?page=inicio');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'El correo no tiene un formato valido.');
            redirect('index.php?page=inicio');
        }

        if (strlen($password) < 8) {
            setFlash('error', 'La contrasena debe tener minimo 8 caracteres.');
            redirect('index.php?page=inicio');
        }

        if (!preg_match('/^\d{1,10}$/', $codigo) || !preg_match('/^\d{1,10}$/', $telefono)) {
            setFlash('error', 'Codigo y telefono deben ser numericos de maximo 10 digitos.');
            redirect('index.php?page=inicio');
        }

        $token = '';

        try {
            if ($this->model->findByEmail($email)) {
                setFlash('error', 'Ya existe una cuenta con ese correo.');
                redirect('index.php?page=inicio');
            }

            $rol   = isAdminEmail($email) ? 'admin' : 'estudiante';
            $token = bin2hex(random_bytes(32));
            $this->model->create($nombre, $email, $carrera, $codigo, $telefono, $password, $rol, $token);
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo crear la cuenta por un problema de base de datos.');
            redirect('index.php?page=inicio');
        }

        try {
            $verifyUrl = $this->getBaseUrl() . '/index.php?action=auth/verify&token=' . urlencode($token);
            $mailer    = new Mailer();
            $mailer->send($email, 'Verifica tu correo - UniLink', $this->buildVerificationEmail($nombre, $verifyUrl));
            setFlash('success', 'Cuenta creada. Revisa tu correo (' . $email . ') y haz clic en el enlace de verificacion para activar tu cuenta.');
        } catch (Throwable $e) {
            setFlash('error', 'Cuenta creada pero no pudimos enviar el correo de verificacion. Usa "Reenviar verificacion" en el inicio de sesion para intentarlo de nuevo.');
        }

        redirect('index.php?page=inicio');
    }

    public function verify(): void
    {
        $token = trim($_GET['token'] ?? '');

        if ($token === '' || strlen($token) < 32) {
            setFlash('error', 'El enlace de verificacion no es valido.');
            redirect('index.php?page=inicio');
        }

        try {
            $user = $this->model->findByVerificationToken($token);
        } catch (Throwable $e) {
            setFlash('error', 'Error al verificar el correo. Intenta de nuevo.');
            redirect('index.php?page=inicio');
        }

        if (!$user) {
            setFlash('error', 'El enlace de verificacion no es valido o ya fue utilizado.');
            redirect('index.php?page=inicio');
        }

        try {
            $this->model->setEmailVerified((int) $user['id']);
        } catch (Throwable $e) {
            setFlash('error', 'No se pudo verificar el correo. Intenta de nuevo.');
            redirect('index.php?page=inicio');
        }

        setFlash('success', 'Correo verificado correctamente. Ya puedes iniciar sesion.');
        redirect('index.php?page=inicio&auth=login');
    }

    public function resendVerification(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=inicio');
        }

        $email = trim($_POST['resend_email'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Ingresa un correo valido.');
            redirect('index.php?page=inicio&auth=login');
        }

        $genericMsg = 'Si el correo esta registrado y pendiente de verificacion, recibiras un nuevo enlace.';

        try {
            $user = $this->model->findByEmail($email);
        } catch (Throwable $e) {
            setFlash('success', $genericMsg);
            redirect('index.php?page=inicio&auth=login');
        }

        // No revelar si el correo existe o no
        if (!$user || (int) ($user['email_verificado'] ?? 1) === 1) {
            setFlash('success', $genericMsg);
            redirect('index.php?page=inicio&auth=login');
        }

        try {
            $token = bin2hex(random_bytes(32));
            $this->model->updateVerificationToken((int) $user['id'], $token);
            $verifyUrl = $this->getBaseUrl() . '/index.php?action=auth/verify&token=' . urlencode($token);
            $mailer    = new Mailer();
            $mailer->send($email, 'Verifica tu correo - UniLink', $this->buildVerificationEmail($user['nombre'], $verifyUrl));
        } catch (Throwable $e) {
            setFlash('error', 'No se pudo enviar el correo. Verifica que la configuracion SMTP en config/config.php sea correcta.');
            redirect('index.php?page=inicio&auth=login');
        }

        setFlash('success', $genericMsg);
        redirect('index.php?page=inicio&auth=login');
    }

    public function logout(): void
    {
        unset($_SESSION['auth_user']);
        session_regenerate_id(true);

        setFlash('success', 'Sesion cerrada correctamente.');
        redirect('index.php?page=inicio');
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

    private function buildVerificationEmail(string $nombre, string $verifyUrl): string
    {
        $nombre    = htmlspecialchars($nombre,    ENT_QUOTES, 'UTF-8');
        $verifyUrl = htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Verifica tu correo - UniLink</title></head>
<body style="font-family:Arial,sans-serif;background:#f4f6f9;margin:0;padding:30px;">
<div style="max-width:520px;margin:0 auto;background:#fff;border-radius:12px;padding:40px;box-shadow:0 2px 14px rgba(0,0,0,.08);">
    <div style="text-align:center;margin-bottom:28px;">
        <h1 style="color:#1E3A5F;margin:0;font-size:28px;letter-spacing:-0.5px;">UniLink</h1>
        <p style="color:#999;margin:4px 0 0;font-size:13px;">Universidad del Magdalena</p>
    </div>
    <h2 style="color:#1E3A5F;font-size:20px;margin:0 0 12px;">Verifica tu correo electronico</h2>
    <p style="color:#444;margin:0 0 8px;">Hola <strong>{$nombre}</strong>,</p>
    <p style="color:#555;line-height:1.7;margin:0 0 24px;">
        Gracias por registrarte en UniLink. Para activar tu cuenta haz clic en el boton de abajo.
        El enlace es de un solo uso.
    </p>
    <div style="text-align:center;margin:0 0 28px;">
        <a href="{$verifyUrl}"
           style="background:#1E3A5F;color:#fff;padding:14px 36px;border-radius:8px;text-decoration:none;
                  font-weight:bold;font-size:15px;display:inline-block;letter-spacing:.3px;">
            Verificar mi cuenta
        </a>
    </div>
    <p style="color:#999;font-size:13px;margin:0 0 6px;">Si el boton no funciona, copia y pega este enlace:</p>
    <p style="font-size:12px;word-break:break-all;margin:0 0 24px;">
        <a href="{$verifyUrl}" style="color:#4a90d9;">{$verifyUrl}</a>
    </p>
    <hr style="border:none;border-top:1px solid #eee;margin:0 0 16px;">
    <p style="color:#ccc;font-size:11px;text-align:center;margin:0;">
        Si no creaste esta cuenta, ignora este correo. Nadie mas puede acceder sin verificacion.
    </p>
</div>
</body>
</html>
HTML;
    }
}
