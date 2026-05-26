<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../modelo/UsuarioModel.php';

final class GoogleAuthController
{
    private const AUTH_URL     = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL    = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/config.php';
        $this->clientId     = $config['google']['client_id'];
        $this->clientSecret = $config['google']['client_secret'];
    }

    // Calcula la redirect_uri desde la URL real del servidor,
    // así funciona independientemente de cómo esté configurado Apache.
    private function getRedirectUri(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $dir    = dirname($script);
        $dir    = ($dir === '.' || $dir === '/') ? '' : rtrim($dir, '/');
        return $scheme . '://' . $host . $dir . '/index.php?action=auth/google/callback';
    }

    public function redirect(): void
    {
        $redirectUri = $this->getRedirectUri();
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        $_SESSION['oauth_redirect_uri'] = $redirectUri;

        $params = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
        ]);

        redirect(self::AUTH_URL . '?' . $params);
    }

    public function callback(): void
    {
        $state = $_GET['state'] ?? '';
        $code  = $_GET['code'] ?? '';
        $error = $_GET['error'] ?? '';

        if ($error !== '') {
            setFlash('error', 'Inicio de sesion con Google cancelado.');
            redirect('index.php?page=inicio');
        }

        if ($state === '' || $state !== ($_SESSION['oauth_state'] ?? '')) {
            setFlash('error', 'Error de seguridad en la autenticacion con Google. Intenta de nuevo.');
            redirect('index.php?page=inicio');
        }

        unset($_SESSION['oauth_state'], $_SESSION['oauth_redirect_uri']);

        if ($code === '') {
            setFlash('error', 'No se recibio el codigo de autorizacion de Google.');
            redirect('index.php?page=inicio');
        }

        try {
            $tokens  = $this->exchangeCode($code);
            $profile = $this->getUserInfo($tokens['access_token']);
        } catch (Throwable $e) {
            setFlash('error', 'No se pudo completar el inicio de sesion con Google. Intenta de nuevo.');
            redirect('index.php?page=inicio');
        }

        $googleId = (string) ($profile['sub'] ?? '');
        $email    = (string) ($profile['email'] ?? '');
        $nombre   = (string) ($profile['name'] ?? '');
        $foto     = (string) ($profile['picture'] ?? '');

        if ($googleId === '' || $email === '') {
            setFlash('error', 'No se pudo obtener la informacion del perfil de Google.');
            redirect('index.php?page=inicio');
        }

        $model = new UsuarioModel();
        $user  = $model->findByGoogleId($googleId);

        if (!$user) {
            $existing = $model->findByEmail($email);

            if ($existing) {
                $model->linkGoogle((int) $existing['id'], $googleId);
                $user = $model->findByEmail($email);
            } else {
                $rol = isAdminEmail($email) ? 'admin' : 'estudiante';
                $model->createFromGoogle($googleId, $email, $nombre, $foto, $rol);
                $user = $model->findByEmail($email);
            }
        }

        if (!$user) {
            setFlash('error', 'No se pudo crear o encontrar la cuenta. Intenta de nuevo.');
            redirect('index.php?page=inicio');
        }

        $estado = strtolower((string) ($user['estado'] ?? 'activo'));
        if ($estado === 'baneado') {
            $motivo = trim((string) ($user['ban_motivo'] ?? ''));
            $mensaje = $motivo !== ''
                ? 'Tu cuenta ha sido bloqueada. Motivo: ' . $motivo
                : 'Tu cuenta ha sido bloqueada. Contacta al administrador.';
            setFlash('error', $mensaje);
            redirect('index.php?page=inicio');
        }

        $_SESSION['auth_user'] = [
            'id'          => (int) $user['id'],
            'nombre'      => $user['nombre'],
            'email'       => $user['email'],
            'carrera'     => $user['carrera'] ?? '',
            'codigo'      => $user['codigo'] ?? '',
            'telefono'    => $user['telefono'] ?? '',
            'rol'         => $user['rol'],
            'foto_perfil' => $user['foto_perfil'] ?? null,
        ];

        clearOld();

        $perfilIncompleto = ($user['carrera'] ?? '') === '' || ($user['codigo'] ?? '0') === '0';

        if ($perfilIncompleto) {
            setFlash('success', 'Bienvenido, ' . $user['nombre'] . '. Completa tu perfil para continuar.');
            redirect('index.php?page=completar-perfil');
        }

        setFlash('success', 'Bienvenido, ' . $user['nombre'] . '.');

        if ($user['rol'] === 'tutor') {
            redirect('index.php?page=tutor');
        }

        if ($user['rol'] === 'admin') {
            redirect('index.php?page=admin');
        }

        redirect('index.php?page=inicio');
    }

    private function exchangeCode(string $code): array
    {
        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'code'          => $code,
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri'  => $_SESSION['oauth_redirect_uri'] ?? $this->getRedirectUri(),
                'grant_type'    => 'authorization_code',
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('cURL error al obtener token: ' . $curlError);
        }

        $data = json_decode((string) $response, true);

        if (!is_array($data) || !isset($data['access_token'])) {
            throw new RuntimeException('Respuesta de token invalida de Google');
        }

        return $data;
    }

    private function getUserInfo(string $accessToken): array
    {
        $ch = curl_init(self::USERINFO_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('cURL error al obtener perfil: ' . $curlError);
        }

        $data = json_decode((string) $response, true);

        if (!is_array($data)) {
            throw new RuntimeException('Respuesta de perfil invalida de Google');
        }

        return $data;
    }
}
