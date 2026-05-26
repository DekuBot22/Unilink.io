<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../modelo/CitaModel.php';
require_once __DIR__ . '/../modelo/TutorModel.php';
require_once __DIR__ . '/../modelo/UsuarioModel.php';
require_once __DIR__ . '/../modelo/NotificacionModel.php';

final class PerfilController
{
    public function index(): void
    {
        if (!isLoggedIn()) {
            setFlash('error', 'Inicia sesion para ver tu perfil.');
            redirect('index.php?page=inicio');
        }

        $activePage = 'perfil';
        $user = authUser();
        $citas = [];
        $tutores = [];

        if ($user && isset($user['id'])) {
            $autoCanceladas = [];
            $enCurso        = [];

            // ── Carga de citas ────────────────────────────────────────────
            try {
                $citaModel      = new CitaModel();
                $autoCanceladas = $citaModel->autoCancelPendientesVencidas();
                $enCurso        = $citaModel->markEnCursoByUsuarioId((int) $user['id']);
                $citas          = $citaModel->getByUsuarioId((int) $user['id']);
            } catch (Throwable $exception) {
                setFlash('error', 'No se pudieron cargar tus citas en este momento.');
            }

            // ── Notificaciones (no abortan la carga de citas) ────────────
            try {
                $notifModel = new NotificacionModel();

                foreach ($autoCanceladas as $ac) {
                    if (empty($ac['usuario_id'])) continue;
                    $notifModel->create(
                        (int) $ac['usuario_id'],
                        'cita_cancelada',
                        'Cita cancelada automaticamente',
                        'Tu cita de ' . $ac['materia'] . ' con ' . $ac['tutor_nombre'] . ' fue cancelada porque el tutor no la confirmo a tiempo.',
                        'index.php?page=perfil'
                    );
                }

                foreach ($enCurso as $ac) {
                    $notifModel->create(
                        (int) $user['id'],
                        'cita_en_curso',
                        'Tu sesion ha comenzado',
                        'La sesion de ' . $ac['materia'] . ' con ' . $ac['tutor_nombre'] . ' esta en curso.',
                        'index.php?page=perfil'
                    );
                }
            } catch (Throwable $e) {
                // silently ignore — notification errors must not affect page load
            }

            // Refresh user data from DB to get latest foto_perfil
            try {
                $fresh = (new UsuarioModel())->findById((int) $user['id']);
                if ($fresh) {
                    $_SESSION['auth_user']['foto_perfil'] = $fresh['foto_perfil'] ?? null;
                    $user = authUser();
                }
            } catch (Throwable $exception) {
                // silently ignore
            }
        }

        try {
            $tutores = (new TutorModel())->getAll();
        } catch (Throwable $exception) {
            $tutores = [];
        }
        require __DIR__ . '/../vista/paginas/perfil-usuario.php';
    }

    public function completarPerfil(): void
    {
        if (!isLoggedIn()) {
            redirect('index.php?page=inicio');
        }

        $pageTitle = 'Completa tu perfil';
        $user = authUser();
        require __DIR__ . '/../vista/paginas/completar-perfil.php';
    }

    public function guardarPerfil(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=completar-perfil');
        }

        if (!isLoggedIn()) {
            redirect('index.php?page=inicio');
        }

        $carrera  = trim($_POST['carrera'] ?? '');
        $codigo   = trim($_POST['codigo'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        if ($carrera === '' || $codigo === '' || $telefono === '') {
            setFlash('error', 'Completa todos los campos para continuar.');
            redirect('index.php?page=completar-perfil');
        }

        if (!preg_match('/^\d{1,10}$/', $codigo) || !preg_match('/^\d{1,10}$/', $telefono)) {
            setFlash('error', 'Codigo y telefono deben ser numericos de maximo 10 digitos.');
            redirect('index.php?page=completar-perfil');
        }

        $user = authUser();

        try {
            (new UsuarioModel())->updatePerfil((int) $user['id'], $carrera, $codigo, $telefono);
        } catch (Throwable $e) {
            setFlash('error', 'No se pudo guardar el perfil. Intenta de nuevo.');
            redirect('index.php?page=completar-perfil');
        }

        $_SESSION['auth_user']['carrera']  = $carrera;
        $_SESSION['auth_user']['codigo']   = $codigo;
        $_SESSION['auth_user']['telefono'] = $telefono;

        setFlash('success', 'Perfil completado. Bienvenido a UniLink.');
        redirect('index.php?page=inicio');
    }

    public function uploadFoto(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=perfil');
        }

        $user = authUser();
        if (!$user) {
            setFlash('error', 'Inicia sesion para cambiar tu foto.');
            redirect('index.php?page=perfil');
        }

        $userId = (int) $user['id'];

        if (!isset($_FILES['foto_perfil']) || $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_OK) {
            setFlash('error', 'No se recibio ningun archivo o hubo un error en la subida.');
            redirect('index.php?page=perfil');
        }

        $file    = $_FILES['foto_perfil'];
        $maxSize = 2 * 1024 * 1024; // 2 MB

        if ($file['size'] > $maxSize) {
            setFlash('error', 'La imagen no puede superar los 2 MB.');
            redirect('index.php?page=perfil');
        }

        $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMime, true)) {
            setFlash('error', 'Solo se permiten imagenes JPG, PNG, WebP o GIF.');
            redirect('index.php?page=perfil');
        }

        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        $ext = $extMap[$mime];

        $uploadDir = __DIR__ . '/../uploads/avatares/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Remove old photo if exists
        $oldFoto = $user['foto_perfil'] ?? null;
        if ($oldFoto) {
            $oldPath = __DIR__ . '/../' . ltrim($oldFoto, '/');
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $filename    = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            setFlash('error', 'No se pudo guardar la imagen. Intenta de nuevo.');
            redirect('index.php?page=perfil');
        }

        $rutaRelativa = 'uploads/avatares/' . $filename;

        try {
            $model = new UsuarioModel();
            if (!$model->updateFotoPerfil($userId, $rutaRelativa)) {
                setFlash('error', 'No se pudo actualizar la foto en la base de datos.');
                redirect('index.php?page=perfil');
            }
        } catch (Throwable $e) {
            setFlash('error', 'Error al actualizar la foto de perfil.');
            redirect('index.php?page=perfil');
        }

        $_SESSION['auth_user']['foto_perfil'] = $rutaRelativa;

        setFlash('success', 'Foto de perfil actualizada correctamente.');
        redirect('index.php?page=perfil');
    }
}
