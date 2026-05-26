<?php

declare(strict_types=1);

date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/admin.php';

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function old(string $key, string $default = ''): string
{
    return isset($_SESSION['old'][$key]) ? (string) $_SESSION['old'][$key] : $default;
}

function setOld(array $data): void
{
    $_SESSION['old'] = $data;
}

function clearOld(): void
{
    unset($_SESSION['old']);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn(): bool
{
    return isset($_SESSION['auth_user']);
}

function authUser(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function isAdminEmail(string $email): bool
{
    $email = strtolower(trim($email));
    return in_array($email, ADMIN_EMAILS, true);
}

function isAdmin(): bool
{
    $user = authUser();
    return $user && isset($user['rol']) && $user['rol'] === 'admin';
}

function isTutor(): bool
{
    $user = authUser();
    return $user && isset($user['rol']) && $user['rol'] === 'tutor';
}

function requireAdmin(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Inicia sesion para acceder al panel administrador.');
        redirect('index.php?page=inicio&auth=login');
    }

    if (!isAdmin()) {
        setFlash('error', 'No tienes permisos para acceder a esta seccion.');
        redirect('index.php?page=inicio');
    }
}

function requireTutor(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Inicia sesion para acceder al panel de tutor.');
        redirect('index.php?page=inicio&auth=login');
    }

    if (!isTutor()) {
        setFlash('error', 'Solo los tutores aprobados pueden acceder a esta seccion.');
        redirect('index.php?page=inicio');
    }
}
