<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../modelo/UsuarioModel.php';
require_once __DIR__ . '/../modelo/PostulacionModel.php';
require_once __DIR__ . '/../modelo/TutorModel.php';
require_once __DIR__ . '/../modelo/ContenidoModel.php';
require_once __DIR__ . '/../modelo/CitaModel.php';
require_once __DIR__ . '/../modelo/NotificacionModel.php';

final class AdminController
{
    private UsuarioModel $model;
    private PostulacionModel $postulacionModel;
    private TutorModel $tutorModel;
    private ContenidoModel $contenidoModel;
    private CitaModel $citaModel;

    public function __construct()
    {
        $this->model = new UsuarioModel();
        $this->postulacionModel = new PostulacionModel();
        $this->tutorModel = new TutorModel();
        $this->contenidoModel = new ContenidoModel();
        $this->citaModel = new CitaModel();
    }

    public function index(): void
    {
        requireAdmin();
        $activePage = 'admin';
        $user = authUser();

        require __DIR__ . '/../vista/paginas/admin.php';
    }

    public function promote(): void
    {
        requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=admin');
        }

        $email = trim($_POST['admin_email'] ?? '');
        $rol = trim($_POST['admin_role'] ?? 'estudiante');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Ingresa un correo valido para actualizar el rol.');
            redirect('index.php?page=admin');
        }

        if (!in_array($rol, ['admin', 'estudiante'], true)) {
            setFlash('error', 'El rol seleccionado no es valido.');
            redirect('index.php?page=admin');
        }

        try {
            $user = $this->model->findByEmail($email);
            if (!$user) {
                setFlash('error', 'No existe un usuario con ese correo.');
                redirect('index.php?page=admin');
            }

            $this->model->updateRoleByEmail($email, $rol);
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo actualizar el rol del usuario.');
            redirect('index.php?page=admin');
        }

        setFlash('success', 'Rol actualizado correctamente para ' . $email . '.');
        redirect('index.php?page=admin');
    }

    public function banUser(): void
    {
        requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=admin');
        }

        $email = trim($_POST['ban_email'] ?? '');
        $motivo = trim($_POST['ban_motivo'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Ingresa un correo valido para banear al usuario.');
            redirect('index.php?page=admin');
        }

        if ($motivo === '') {
            setFlash('error', 'Debes indicar el motivo del baneo.');
            redirect('index.php?page=admin');
        }

        try {
            $user = $this->model->findByEmail($email);
            if (!$user) {
                setFlash('error', 'No existe un usuario con ese correo.');
                redirect('index.php?page=admin');
            }

            if (($user['rol'] ?? '') === 'admin') {
                setFlash('error', 'No puedes banear a un administrador.');
                redirect('index.php?page=admin');
            }

            if (!$this->model->banByEmail($email, $motivo)) {
                setFlash('error', 'No se pudo banear al usuario.');
                redirect('index.php?page=admin');
            }
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo banear al usuario.');
            redirect('index.php?page=admin');
        }

        setFlash('success', 'Usuario baneado correctamente.');
        redirect('index.php?page=admin');
    }

    public function activateUser(): void
    {
        requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=admin');
        }

        $email = trim($_POST['activate_email'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Ingresa un correo valido para activar al usuario.');
            redirect('index.php?page=admin');
        }

        try {
            $user = $this->model->findByEmail($email);
            if (!$user) {
                setFlash('error', 'No existe un usuario con ese correo.');
                redirect('index.php?page=admin');
            }

            if (!$this->model->activateByEmail($email)) {
                setFlash('error', 'No se pudo activar al usuario.');
                redirect('index.php?page=admin');
            }
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo activar al usuario.');
            redirect('index.php?page=admin');
        }

        setFlash('success', 'Usuario activado correctamente.');
        redirect('index.php?page=admin');
    }

    public function tutorRequests(): void
    {
        requireAdmin();
        $activePage = 'admin';
        $postulaciones = [];

        try {
            $postulaciones = $this->postulacionModel->getAll();
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudieron cargar las solicitudes de tutor.');
        }

        require __DIR__ . '/../vista/paginas/admin-tutores.php';
    }

    public function manageTutores(): void
    {
        requireAdmin();
        $activePage = 'admin';
        $tutores = [];

        try {
            $tutores = $this->tutorModel->getAll();
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudieron cargar los tutores.');
        }

        require __DIR__ . '/../vista/paginas/admin-tutores-gestion.php';
    }

    public function deleteTutor(): void
    {
        requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=admin-tutores-gestion');
        }

        $tutorId = $this->normalizeId($_POST['tutor_id'] ?? '');
        if ($tutorId === null) {
            setFlash('error', 'El tutor seleccionado no es valido.');
            redirect('index.php?page=admin-tutores-gestion');
        }

        try {
            $this->tutorModel->deleteById($tutorId);
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo eliminar el tutor.');
            redirect('index.php?page=admin-tutores-gestion');
        }

        setFlash('success', 'Tutor eliminado correctamente.');
        redirect('index.php?page=admin-tutores-gestion');
    }

    public function manageContenido(): void
    {
        requireAdmin();
        $activePage = 'admin';
        $contenidos = [];

        try {
            $contenidos = $this->contenidoModel->getAll();
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo cargar el contenido.');
        }

        require __DIR__ . '/../vista/paginas/admin-contenido.php';
    }

    public function reportesCitas(): void
    {
        requireAdmin();
        $activePage = 'admin';
        $summary = [];

        try {
            $summary = $this->citaModel->getReportSummary();
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudieron cargar los reportes de citas.');
        }

        require __DIR__ . '/../vista/paginas/admin-reportes.php';
    }

    public function deleteContenido(): void
    {
        requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=admin-contenido');
        }

        $contenidoId = $this->normalizeId($_POST['contenido_id'] ?? '');
        if ($contenidoId === null) {
            setFlash('error', 'El contenido seleccionado no es valido.');
            redirect('index.php?page=admin-contenido');
        }

        try {
            $contenido = $this->contenidoModel->findById($contenidoId);
            if (!$contenido) {
                setFlash('error', 'No se encontro el contenido a eliminar.');
                redirect('index.php?page=admin-contenido');
            }

            if (!$this->contenidoModel->deleteById($contenidoId)) {
                setFlash('error', 'No se pudo eliminar el contenido.');
                redirect('index.php?page=admin-contenido');
            }

            $this->deleteContenidoFile($contenido['archivo_ruta'] ?? '');
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo eliminar el contenido.');
            redirect('index.php?page=admin-contenido');
        }

        setFlash('success', 'Contenido eliminado correctamente.');
        redirect('index.php?page=admin-contenido');
    }

    public function approveContenido(): void
    {
        requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=admin-contenido');
        }

        $contenidoId = $this->normalizeId($_POST['contenido_id'] ?? '');
        if ($contenidoId === null) {
            setFlash('error', 'El contenido seleccionado no es valido.');
            redirect('index.php?page=admin-contenido');
        }

        try {
            $contenido = $this->contenidoModel->findById($contenidoId);
            if (!$contenido) {
                setFlash('error', 'No se encontro el contenido a aprobar.');
                redirect('index.php?page=admin-contenido');
            }

            if (($contenido['estado'] ?? '') === 'aprobado') {
                setFlash('success', 'El contenido ya estaba aprobado.');
                redirect('index.php?page=admin-contenido');
            }

            if (!$this->contenidoModel->updateEstadoById($contenidoId, 'aprobado')) {
                setFlash('error', 'No se pudo aprobar el contenido.');
                redirect('index.php?page=admin-contenido');
            }
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo aprobar el contenido.');
            redirect('index.php?page=admin-contenido');
        }

        try {
            $tutorData = $this->tutorModel->findById((int) $contenido['tutor_id']);
            if ($tutorData && !empty($tutorData['usuario_id'])) {
                (new NotificacionModel())->create(
                    (int) $tutorData['usuario_id'],
                    'contenido_aprobado',
                    'Contenido aprobado',
                    'Tu contenido "' . $contenido['titulo'] . '" fue aprobado y ya esta disponible en la plataforma.',
                    'index.php?page=tutor'
                );
            }
        } catch (Throwable $e) {
            // silently ignore
        }

        setFlash('success', 'Contenido aprobado correctamente.');
        redirect('index.php?page=admin-contenido');
    }

    private function deleteContenidoFile(string $relativePath): void
    {
        $relativePath = trim($relativePath);
        if ($relativePath === '') {
            return;
        }

        $fullPath = __DIR__ . '/../' . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    public function approveTutor(): void
    {
        requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=admin-tutores');
        }

        $postulacionId = $this->normalizeId($_POST['postulacion_id'] ?? '');
        if ($postulacionId === null) {
            setFlash('error', 'La solicitud seleccionada no es valida.');
            redirect('index.php?page=admin-tutores');
        }

        try {
            $postulacion = $this->postulacionModel->findById($postulacionId);
            if (!$postulacion) {
                setFlash('error', 'No se encontro la solicitud a aprobar.');
                redirect('index.php?page=admin-tutores');
            }

            $correo = trim((string) ($postulacion['correo'] ?? ''));
            if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                setFlash('error', 'La solicitud no tiene un correo valido para vincular el tutor.');
                redirect('index.php?page=admin-tutores');
            }

            $usuario = $this->model->findByEmail($correo);
            if (!$usuario) {
                setFlash('error', 'No existe una cuenta registrada con ese correo. El estudiante debe registrarse antes de aprobar.');
                redirect('index.php?page=admin-tutores');
            }

            if (($usuario['rol'] ?? '') !== 'admin') {
                if (!$this->model->updateRoleByEmail($correo, 'tutor')) {
                    setFlash('error', 'No se pudo actualizar el rol del usuario a tutor.');
                    redirect('index.php?page=admin-tutores');
                }
            }

            $this->tutorModel->createFromPostulacion($postulacion, (int) $usuario['id']);
            $this->postulacionModel->deleteById($postulacionId);
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo aprobar la solicitud del tutor.');
            redirect('index.php?page=admin-tutores');
        }

        setFlash('success', 'Tutor aprobado correctamente.');
        redirect('index.php?page=admin-tutores');
    }

    public function rejectTutor(): void
    {
        requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=admin-tutores');
        }

        $postulacionId = $this->normalizeId($_POST['postulacion_id'] ?? '');
        if ($postulacionId === null) {
            setFlash('error', 'La solicitud seleccionada no es valida.');
            redirect('index.php?page=admin-tutores');
        }

        try {
            $this->postulacionModel->deleteById($postulacionId);
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo rechazar la solicitud del tutor.');
            redirect('index.php?page=admin-tutores');
        }

        setFlash('success', 'Solicitud rechazada correctamente.');
        redirect('index.php?page=admin-tutores');
    }

    private function normalizeId($value): ?int
    {
        $value = trim((string) $value);
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        $id = (int) $value;
        return $id > 0 ? $id : null;
    }
}
