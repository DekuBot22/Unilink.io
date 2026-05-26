<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../modelo/NotificacionModel.php';

final class NotificacionController
{
    /** GET — devuelve notificaciones del usuario autenticado como JSON. */
    public function getJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = authUser();
        if (!$user) {
            echo json_encode(['error' => 'no_auth', 'notificaciones' => [], 'unread' => 0]);
            exit;
        }

        try {
            $model  = new NotificacionModel();
            $items  = $model->getByUsuarioId((int) $user['id'], 30);
            $unread = $model->getUnreadCount((int) $user['id']);
        } catch (Throwable $e) {
            echo json_encode(['error' => 'db_error', 'notificaciones' => [], 'unread' => 0]);
            exit;
        }

        echo json_encode([
            'notificaciones' => $items,
            'unread'         => $unread,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST — marca todas las notificaciones del usuario como leídas. */
    public function readAll(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = authUser();
        if (!$user) {
            echo json_encode(['ok' => false]);
            exit;
        }

        try {
            (new NotificacionModel())->markAllAsRead((int) $user['id']);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false]);
            exit;
        }

        echo json_encode(['ok' => true]);
        exit;
    }

    /** POST — marca una notificación individual como leída. */
    public function readOne(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = authUser();
        if (!$user) {
            echo json_encode(['ok' => false]);
            exit;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['ok' => false]);
            exit;
        }

        try {
            (new NotificacionModel())->markAsRead($id, (int) $user['id']);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false]);
            exit;
        }

        echo json_encode(['ok' => true]);
        exit;
    }
}
