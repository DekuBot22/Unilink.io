<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../modelo/MensajeModel.php';

final class MensajeController
{
    /** POST — envía un mensaje. Body: tutor_id o receptor_id, texto */
    public function send(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = authUser();
        if (!$user) {
            echo json_encode(['ok' => false, 'error' => 'no_auth']);
            exit;
        }

        $emisorId = (int) $user['id'];
        $texto    = trim((string) ($_POST['texto'] ?? ''));

        if ($texto === '' || mb_strlen($texto) > 1000) {
            echo json_encode(['ok' => false, 'error' => 'texto_invalido']);
            exit;
        }

        $model = new MensajeModel();

        if (!empty($_POST['tutor_id'])) {
            $tutorId    = (int) $_POST['tutor_id'];
            $receptorId = $model->getUsuarioIdFromTutorId($tutorId);
            if (!$receptorId) {
                echo json_encode(['ok' => false, 'error' => 'tutor_sin_cuenta']);
                exit;
            }
        } else {
            $receptorId = isset($_POST['receptor_id']) ? (int) $_POST['receptor_id'] : 0;
        }

        if ($receptorId <= 0 || $receptorId === $emisorId) {
            echo json_encode(['ok' => false, 'error' => 'receptor_invalido']);
            exit;
        }

        try {
            $msg = $model->send($emisorId, $receptorId, $texto);
            if (!$msg) {
                echo json_encode(['ok' => false, 'error' => 'db_error']);
                exit;
            }
            echo json_encode(['ok' => true, 'mensaje' => $msg], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'error' => 'db_error']);
        }
        exit;
    }

    /** GET — mensajes de una conversación. ?con=userId o ?tutor_id=X */
    public function getConversacion(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = authUser();
        if (!$user) {
            echo json_encode(['ok' => false, 'error' => 'no_auth', 'mensajes' => []]);
            exit;
        }

        $miId  = (int) $user['id'];
        $model = new MensajeModel();
        $conId = 0;

        if (!empty($_GET['con'])) {
            $conId = (int) $_GET['con'];
        } elseif (!empty($_GET['tutor_id'])) {
            $tutorId = (int) $_GET['tutor_id'];
            $conId   = $model->getUsuarioIdFromTutorId($tutorId) ?? 0;
            if ($conId === 0) {
                echo json_encode(['ok' => false, 'error' => 'tutor_sin_cuenta', 'mensajes' => []]);
                exit;
            }
        }

        if ($conId <= 0) {
            echo json_encode(['ok' => false, 'error' => 'receptor_invalido', 'mensajes' => []]);
            exit;
        }

        try {
            $mensajes = $model->getConversacion($miId, $conId, 100);
            $model->marcarLeidos($miId, $conId);
            echo json_encode([
                'ok'      => true,
                'mensajes' => $mensajes,
                'mi_id'   => $miId,
                'con_id'  => $conId,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'error' => 'db_error', 'mensajes' => []]);
        }
        exit;
    }

    /** GET — lista de conversaciones del usuario autenticado */
    public function getConversaciones(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = authUser();
        if (!$user) {
            echo json_encode(['ok' => false, 'conversaciones' => [], 'unread' => 0]);
            exit;
        }

        try {
            $model  = new MensajeModel();
            $convs  = $model->getConversaciones((int) $user['id']);
            $unread = $model->getUnreadCount((int) $user['id']);
            echo json_encode([
                'ok'             => true,
                'conversaciones' => $convs,
                'unread'         => $unread,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'conversaciones' => [], 'unread' => 0]);
        }
        exit;
    }

    /** GET — solo el conteo de no leídos */
    public function getUnread(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = authUser();
        if (!$user) {
            echo json_encode(['unread' => 0]);
            exit;
        }

        try {
            $model  = new MensajeModel();
            $unread = $model->getUnreadCount((int) $user['id']);
            echo json_encode(['unread' => $unread]);
        } catch (Throwable $e) {
            echo json_encode(['unread' => 0]);
        }
        exit;
    }

    /** Página: bandeja de mensajes */
    public function index(): void
    {
        $user = authUser();
        if (!$user) {
            setFlash('error', 'Inicia sesion para ver tus mensajes.');
            redirect('index.php?page=inicio&auth=login');
            return;
        }

        $activePage = 'mensajes';
        require __DIR__ . '/../vista/paginas/mensajes.php';
    }
}
