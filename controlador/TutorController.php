<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../modelo/TutorModel.php';
require_once __DIR__ . '/../modelo/UsuarioModel.php';
require_once __DIR__ . '/../modelo/CitaModel.php';
require_once __DIR__ . '/../modelo/ContenidoModel.php';
require_once __DIR__ . '/../modelo/NotificacionModel.php';

final class TutorController
{
    private TutorModel $model;

    public function __construct()
    {
        $this->model = new TutorModel();
    }

    public function index(): void
    {
        $activePage = 'tutores';
        $tutores = [];

        try {
            $tutores = $this->model->getAll();
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudieron cargar los tutores desde la base de datos.');
        }

        require __DIR__ . '/../vista/paginas/tutores.php';
    }

    public function panel(): void
    {
        requireTutor();

        $activePage = 'tutor';
        $user = authUser();
        $tutor = null;
        $citas = [];
        $contenidos = [];
        $tutorResenas = [];
        $citaStats = ['total' => 0, 'completada' => 0, 'cancelada' => 0, 'pendiente' => 0, 'confirmada' => 0, 'en_curso' => 0];
        $tutorMissing = false;

        if ($user && isset($user['id'])) {
            try {
                $fresh = (new UsuarioModel())->findById((int) $user['id']);
                if ($fresh) {
                    $_SESSION['auth_user']['foto_perfil'] = $fresh['foto_perfil'] ?? null;
                    $user = authUser();
                }
            } catch (Throwable $e) {
                // silently ignore
            }

            try {
                $tutor = $this->model->findByUsuarioId((int) $user['id']);
            } catch (Throwable $exception) {
                setFlash('error', 'No se pudo cargar tu informacion como tutor.');
            }
        }

        if (!$tutor) {
            $tutorMissing = true;
        } else {
            $autoCanceladas = [];
            $enCurso        = [];
            $proximas       = [];
            $tutorUsuarioId = (int) ($tutor['usuario_id'] ?? 0);

            // ── Carga de citas y estadísticas ────────────────────────────
            try {
                $citaModel    = new CitaModel();
                $autoCanceladas = $citaModel->autoCancelPendientesVencidas();
                $enCurso        = $citaModel->markEnCursoByTutorId((int) $tutor['id']);
                $citas          = $citaModel->getByTutorId((int) $tutor['id']);

                foreach ($citas as $c) {
                    $citaStats['total']++;
                    $est = strtolower((string) ($c['estado'] ?? 'pendiente'));
                    if (isset($citaStats[$est])) {
                        $citaStats[$est]++;
                    }
                }

                if ($tutorUsuarioId > 0) {
                    $proximas = $citaModel->getProximasByTutorId((int) $tutor['id']);
                }
            } catch (Throwable $exception) {
                setFlash('error', 'No se pudieron cargar las citas asignadas.');
            }

            // ── Creación de notificaciones (no aborta la carga de citas) ─
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
                    if (empty($ac['usuario_id'])) continue;
                    $notifModel->create(
                        (int) $ac['usuario_id'],
                        'cita_en_curso',
                        'Tu sesion ha comenzado',
                        'La sesion de ' . $ac['materia'] . ' con ' . $ac['tutor_nombre'] . ' esta en curso.',
                        'index.php?page=perfil'
                    );
                }

                if ($tutorUsuarioId > 0) {
                    foreach ($proximas as $prox) {
                        if ($notifModel->hasNotifForCita($tutorUsuarioId, 'cita_proxima', (int) $prox['id'])) {
                            continue;
                        }
                        $notifModel->create(
                            $tutorUsuarioId,
                            'cita_proxima',
                            'Sesion proxima a comenzar',
                            'Tu sesion de ' . $prox['materia'] . ' comienza en menos de 30 minutos.',
                            'index.php?page=tutor',
                            (int) $prox['id']
                        );
                    }
                }
            } catch (Throwable $e) {
                // silently ignore — notification errors must not affect page load
            }

            try {
                $tutorResenas = $this->model->getResenasByTutorId((int) $tutor['id']);
            } catch (Throwable $exception) {
                // silently ignore
            }

            try {
                $contenidos = (new ContenidoModel())->getByTutorId((int) $tutor['id']);
            } catch (Throwable $exception) {
                setFlash('error', 'No se pudo cargar tu contenido.');
            }
        }

        require __DIR__ . '/../vista/paginas/tutor.php';
    }

    public function updateAgenda(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=tutor');
        }

        requireTutor();

        $user = authUser();
        if (!$user || !isset($user['id'])) {
            setFlash('error', 'No se encontro tu sesion activa.');
            redirect('index.php?page=inicio');
        }

        try {
            $tutor = $this->model->findByUsuarioId((int) $user['id']);
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo cargar tu perfil de tutor.');
            redirect('index.php?page=tutor');
        }

        if (!$tutor) {
            setFlash('error', 'No se encontro tu perfil de tutor para guardar la agenda.');
            redirect('index.php?page=tutor');
        }

        $dias = $_POST['agenda_dias'] ?? [];
        $desde = $_POST['agenda_desde'] ?? [];
        $hasta = $_POST['agenda_hasta'] ?? [];

        if (!is_array($dias)) {
            $dias = [];
        }

        $diasPermitidos = [
            'Lunes',
            'Martes',
            'Miercoles',
            'Jueves',
            'Viernes',
            'Sabado',
            'Domingo',
        ];

        $disponibilidad = [];

        foreach ($dias as $diaRaw) {
            $dia = trim((string) $diaRaw);
            if ($dia === '' || !in_array($dia, $diasPermitidos, true)) {
                continue;
            }

            $desdeValor = is_array($desde) && isset($desde[$dia]) ? trim((string) $desde[$dia]) : '';
            $hastaValor = is_array($hasta) && isset($hasta[$dia]) ? trim((string) $hasta[$dia]) : '';

            if ($desdeValor === '' || $hastaValor === '') {
                setFlash('error', 'Debes indicar hora de inicio y fin para ' . $dia . '.');
                redirect('index.php?page=tutor');
            }

            $horaInicio = DateTime::createFromFormat('H:i', $desdeValor);
            $horaFin = DateTime::createFromFormat('H:i', $hastaValor);

            if (!$horaInicio || $horaInicio->format('H:i') !== $desdeValor) {
                setFlash('error', 'La hora de inicio no es valida para ' . $dia . '.');
                redirect('index.php?page=tutor');
            }

            if (!$horaFin || $horaFin->format('H:i') !== $hastaValor) {
                setFlash('error', 'La hora de fin no es valida para ' . $dia . '.');
                redirect('index.php?page=tutor');
            }

            if ($horaInicio >= $horaFin) {
                setFlash('error', 'La hora de inicio debe ser menor a la hora de fin para ' . $dia . '.');
                redirect('index.php?page=tutor');
            }

            $disponibilidad[] = [
                'dia' => $dia,
                'horas' => $desdeValor . ' - ' . $hastaValor,
            ];
        }

        if (!$disponibilidad) {
            setFlash('error', 'Selecciona al menos un dia con horario disponible.');
            redirect('index.php?page=tutor');
        }

        try {
            if (!$this->model->updateDisponibilidad((int) $tutor['id'], $disponibilidad)) {
                setFlash('error', 'No se pudo guardar la agenda.');
                redirect('index.php?page=tutor');
            }
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo guardar la agenda.');
            redirect('index.php?page=tutor');
        }

        setFlash('success', 'Agenda actualizada correctamente.');
        redirect('index.php?page=tutor');
    }

    public function updateCitaEstado(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=tutor');
        }

        requireTutor();

        $user = authUser();
        if (!$user || !isset($user['id'])) {
            setFlash('error', 'No se encontro tu sesion activa.');
            redirect('index.php?page=inicio');
        }

        $citaId = $this->normalizeId($_POST['cita_id'] ?? '');
        $estado = strtolower(trim($_POST['cita_estado'] ?? ''));
        $motivo = trim($_POST['cita_motivo'] ?? '');

        if ($citaId === null) {
            setFlash('error', 'La cita seleccionada no es valida.');
            redirect('index.php?page=tutor');
        }

        if (!in_array($estado, ['confirmada', 'cancelada', 'completada'], true)) {
            setFlash('error', 'El estado solicitado no es valido.');
            redirect('index.php?page=tutor');
        }

        if ($estado === 'cancelada') {
            if ($motivo === '') {
                setFlash('error', 'Debes indicar el motivo de la cancelacion.');
                redirect('index.php?page=tutor');
            }

            if (strlen($motivo) > 300) {
                setFlash('error', 'El motivo no puede superar los 300 caracteres.');
                redirect('index.php?page=tutor');
            }
        }

        try {
            $tutor = $this->model->findByUsuarioId((int) $user['id']);
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo cargar tu perfil de tutor.');
            redirect('index.php?page=tutor');
        }

        if (!$tutor) {
            setFlash('error', 'No se encontro tu perfil de tutor.');
            redirect('index.php?page=tutor');
        }

        $citaModel = new CitaModel();
        $cita = $citaModel->getByIdForTutor($citaId, (int) $tutor['id']);
        if (!$cita) {
            setFlash('error', 'No se encontro la cita seleccionada.');
            redirect('index.php?page=tutor');
        }

        $estadoActual = strtolower((string) ($cita['estado'] ?? 'pendiente'));
        if ($estado === 'cancelada' && in_array($estadoActual, ['cancelada', 'completada'], true)) {
            setFlash('error', 'La cita ya se encuentra cerrada.');
            redirect('index.php?page=tutor');
        }

        if ($estado === 'completada' && $estadoActual !== 'en_curso') {
            setFlash('error', 'Solo puedes finalizar citas en curso.');
            redirect('index.php?page=tutor');
        }

        try {
            $enlace = trim($_POST['cita_enlace'] ?? '');
            $enlaceVal = $enlace === '' ? null : $enlace;
            if (!$citaModel->updateEstadoByTutor($citaId, (int) $tutor['id'], $estado, $motivo, $enlaceVal)) {
                setFlash('error', 'No se pudo actualizar la cita.');
                redirect('index.php?page=tutor');
            }
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo actualizar la cita.');
            redirect('index.php?page=tutor');
        }

        if ($estado === 'completada') {
            try {
                $this->model->incrementSesiones((int) $tutor['id']);
            } catch (Throwable $e) {
                // silently ignore
            }
        }

        try {
            $notifModel = new NotificacionModel();
            $tutorNombre = (string) ($tutor['nombre'] ?? '');
            if ($estado === 'confirmada') {
                $notifModel->create(
                    (int) $cita['usuario_id'],
                    'cita_confirmada',
                    'Cita confirmada',
                    'Tu cita de ' . $cita['materia'] . ' con ' . $tutorNombre . ' fue confirmada.',
                    'index.php?page=perfil'
                );
            } elseif ($estado === 'cancelada') {
                $detalle = $motivo !== '' ? ': ' . $motivo : '.';
                $notifModel->create(
                    (int) $cita['usuario_id'],
                    'cita_cancelada',
                    'Cita cancelada',
                    'Tu cita de ' . $cita['materia'] . ' con ' . $tutorNombre . ' fue cancelada' . $detalle,
                    'index.php?page=perfil'
                );
            }
        } catch (Throwable $e) {
            // silently ignore notification errors
        }

        setFlash('success', 'Cita actualizada correctamente.');
        redirect('index.php?page=tutor');
    }

    public function contenidoStore(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=tutor');
        }

        requireTutor();

        $tutor = $this->getTutorOrRedirect();
        if (!$tutor) {
            return;
        }

        $titulo = trim($_POST['contenido_titulo'] ?? '');
        $materia = trim($_POST['contenido_materia'] ?? '');
        $tema = trim($_POST['contenido_tema'] ?? '');
        $archivo = $_FILES['contenido_archivo'] ?? null;

        if (!$archivo || !is_array($archivo)) {
            setFlash('error', 'Selecciona un archivo para subir.');
            redirect('index.php?page=tutor');
        }

        if ($materia === '' || $tema === '') {
            setFlash('error', 'Completa la materia y el tema del contenido.');
            redirect('index.php?page=tutor');
        }

        try {
            $fileData = $this->storeContenidoFile($archivo);
        } catch (Throwable $exception) {
            setFlash('error', $exception->getMessage());
            redirect('index.php?page=tutor');
        }

        if ($titulo === '') {
            $titulo = $fileData['nombre_base'];
        }

        if ($titulo === '') {
            setFlash('error', 'Ingresa un nombre para el contenido.');
            $this->deleteFileIfExists($fileData['archivo_ruta']);
            redirect('index.php?page=tutor');
        }

        $data = [
            'tutor_id' => (int) $tutor['id'],
            'tutor_nombre' => (string) ($tutor['nombre'] ?? ''),
            'titulo' => $titulo,
            'descripcion' => '',
            'materia' => $materia,
            'tema' => $tema,
            'tipo' => $fileData['tipo'],
            'archivo_nombre' => $fileData['archivo_nombre'],
            'archivo_ruta' => $fileData['archivo_ruta'],
            'extension' => $fileData['extension'],
            'estado' => 'pendiente',
        ];

        try {
            if (!(new ContenidoModel())->create($data)) {
                setFlash('error', 'No se pudo guardar el contenido.');
                $this->deleteFileIfExists($fileData['archivo_ruta']);
                redirect('index.php?page=tutor');
            }
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo guardar el contenido.');
            $this->deleteFileIfExists($fileData['archivo_ruta']);
            redirect('index.php?page=tutor');
        }

        setFlash('success', 'Contenido enviado a revision. Se publicara cuando sea aprobado.');
        redirect('index.php?page=tutor');
    }

    public function rate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=tutores');
        }

        $user = authUser();
        if (!$user) {
            setFlash('error', 'Inicia sesion para dejar una reseña.');
            redirect('index.php?page=tutores');
        }

        $tutorId = isset($_POST['tutor_id']) ? (int) $_POST['tutor_id'] : 0;
        $rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
        if ($tutorId <= 0 || $rating < 1 || $rating > 5) {
            setFlash('error', 'Selecciona una calificacion valida entre 1 y 5.');
            redirect('index.php?page=perfil-tutor&id=' . $tutorId);
        }

        try {
            $model = new TutorModel();
            $result = $model->addRating($tutorId, (int) $user['id'], $rating);
            if ($result === 'already_rated') {
                setFlash('error', 'Ya calificaste a este tutor.');
                redirect('index.php?page=perfil-tutor&id=' . $tutorId);
            }
            if ($result !== 'ok') {
                setFlash('error', 'No se pudo guardar la calificacion.');
                redirect('index.php?page=perfil-tutor&id=' . $tutorId);
            }
        } catch (Throwable $e) {
            setFlash('error', 'No se pudo guardar la calificacion.');
            redirect('index.php?page=perfil-tutor&id=' . $tutorId);
        }

        setFlash('success', 'Gracias por tu calificacion.');
        redirect('index.php?page=perfil-tutor&id=' . $tutorId);
    }

    public function contenidoUpdate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=tutor');
        }

        requireTutor();

        $tutor = $this->getTutorOrRedirect();
        if (!$tutor) {
            return;
        }

        $contenidoId = $this->normalizeId($_POST['contenido_id'] ?? '');
        $titulo = trim($_POST['contenido_titulo'] ?? '');
        $materia = trim($_POST['contenido_materia'] ?? '');
        $tema = trim($_POST['contenido_tema'] ?? '');
        if ($contenidoId === null || $titulo === '' || $materia === '' || $tema === '') {
            setFlash('error', 'Completa el nombre, la materia y el tema del contenido.');
            redirect('index.php?page=tutor');
        }

        $model = new ContenidoModel();
        $contenido = $model->findById($contenidoId);
        if (!$contenido || (int) $contenido['tutor_id'] !== (int) $tutor['id']) {
            setFlash('error', 'No tienes permisos para editar este contenido.');
            redirect('index.php?page=tutor');
        }

        $updateData = [
            'titulo' => $titulo,
            'descripcion' => $contenido['descripcion'] ?? '',
            'materia' => $materia,
            'tema' => $tema,
            'tipo' => $contenido['tipo'] ?? 'recurso',
            'archivo_nombre' => $contenido['archivo_nombre'] ?? '',
            'archivo_ruta' => $contenido['archivo_ruta'] ?? '',
            'extension' => $contenido['extension'] ?? '',
            'estado' => 'pendiente',
        ];

        $archivo = $_FILES['contenido_archivo'] ?? null;
        if ($archivo && is_array($archivo) && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $fileData = $this->storeContenidoFile($archivo);
                $updateData['tipo'] = $fileData['tipo'];
                $updateData['archivo_nombre'] = $fileData['archivo_nombre'];
                $updateData['archivo_ruta'] = $fileData['archivo_ruta'];
                $updateData['extension'] = $fileData['extension'];
            } catch (Throwable $exception) {
                setFlash('error', $exception->getMessage());
                redirect('index.php?page=tutor');
            }
        }

        try {
            if (!$model->updateById($contenidoId, $updateData)) {
                setFlash('error', 'No se pudo actualizar el contenido.');
                if (isset($fileData)) {
                    $this->deleteFileIfExists($fileData['archivo_ruta']);
                }
                redirect('index.php?page=tutor');
            }
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo actualizar el contenido.');
            if (isset($fileData)) {
                $this->deleteFileIfExists($fileData['archivo_ruta']);
            }
            redirect('index.php?page=tutor');
        }

        if (isset($fileData)) {
            $this->deleteFileIfExists($contenido['archivo_ruta'] ?? '');
        }

        setFlash('success', 'Contenido actualizado y enviado a revision.');
        redirect('index.php?page=tutor');
    }

    public function contenidoDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=tutor');
        }

        requireTutor();

        $tutor = $this->getTutorOrRedirect();
        if (!$tutor) {
            return;
        }

        $contenidoId = $this->normalizeId($_POST['contenido_id'] ?? '');
        if ($contenidoId === null) {
            setFlash('error', 'El contenido seleccionado no es valido.');
            redirect('index.php?page=tutor');
        }

        $model = new ContenidoModel();
        $contenido = $model->findById($contenidoId);
        if (!$contenido || (int) $contenido['tutor_id'] !== (int) $tutor['id']) {
            setFlash('error', 'No tienes permisos para eliminar este contenido.');
            redirect('index.php?page=tutor');
        }

        try {
            if (!$model->deleteById($contenidoId)) {
                setFlash('error', 'No se pudo eliminar el contenido.');
                redirect('index.php?page=tutor');
            }
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo eliminar el contenido.');
            redirect('index.php?page=tutor');
        }

        $this->deleteFileIfExists($contenido['archivo_ruta'] ?? '');

        setFlash('success', 'Contenido eliminado correctamente.');
        redirect('index.php?page=tutor');
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

    private function getTutorOrRedirect(): ?array
    {
        $user = authUser();
        if (!$user || !isset($user['id'])) {
            setFlash('error', 'No se encontro tu sesion activa.');
            redirect('index.php?page=inicio');
        }

        try {
            $tutor = $this->model->findByUsuarioId((int) $user['id']);
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo cargar tu perfil de tutor.');
            redirect('index.php?page=tutor');
        }

        if (!$tutor) {
            setFlash('error', 'No se encontro tu perfil de tutor.');
            redirect('index.php?page=tutor');
        }

        return $tutor;
    }

    private function storeContenidoFile(array $archivo): array
    {
        if (!isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo leer el archivo seleccionado.');
        }

        $originalName = (string) ($archivo['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = $this->allowedContenidoExtensions();

        if ($extension === '' || !isset($allowed[$extension])) {
            throw new RuntimeException('El formato del archivo no es valido.');
        }

        $dir = $this->ensureContenidoUploadDir();
        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $target = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($archivo['tmp_name'], $target)) {
            throw new RuntimeException('No se pudo guardar el archivo.');
        }

        $nombreBase = pathinfo($originalName, PATHINFO_FILENAME);
        $relative = 'uploads/contenido/' . $filename;

        return [
            'archivo_nombre' => $originalName,
            'archivo_ruta' => $relative,
            'extension' => $extension,
            'tipo' => $allowed[$extension],
            'nombre_base' => $nombreBase,
        ];
    }

    private function allowedContenidoExtensions(): array
    {
        return [
            'pdf' => 'pdf',
            'jpg' => 'imagen',
            'jpeg' => 'imagen',
            'png' => 'imagen',
            'webp' => 'imagen',
            'doc' => 'recurso',
            'docx' => 'recurso',
            'xls' => 'recurso',
            'xlsx' => 'recurso',
            'ppt' => 'recurso',
            'pptx' => 'recurso',
        ];
    }

    private function ensureContenidoUploadDir(): string
    {
        $dir = __DIR__ . '/../uploads/contenido';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private function deleteFileIfExists(string $relativePath): void
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

    public function perfil(): void
    {
        $this->perfilTutor();
    }

    public function resena(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=tutores');
        }

        $user = authUser();
        if (!$user) {
            setFlash('error', 'Inicia sesion para dejar una resena.');
            redirect('index.php?page=tutores');
        }

        $tutorId = isset($_POST['tutor_id']) ? (int) $_POST['tutor_id'] : 0;
        $texto   = trim($_POST['resena_texto'] ?? '');

        if ($tutorId <= 0 || $texto === '') {
            setFlash('error', 'Escribe tu resena antes de enviar.');
            redirect('index.php?page=perfil-tutor&id=' . $tutorId);
        }

        if (mb_strlen($texto) > 1000) {
            setFlash('error', 'La resena no puede superar los 1000 caracteres.');
            redirect('index.php?page=perfil-tutor&id=' . $tutorId);
        }

        try {
            $result = $this->model->addResena($tutorId, (int) $user['id'], (string) $user['nombre'], $texto);
            if ($result === 'already_reviewed') {
                setFlash('error', 'Ya dejaste una resena para este tutor.');
                redirect('index.php?page=perfil-tutor&id=' . $tutorId);
            }
            if ($result !== 'ok') {
                setFlash('error', 'No se pudo guardar la resena.');
                redirect('index.php?page=perfil-tutor&id=' . $tutorId);
            }
        } catch (Throwable $e) {
            setFlash('error', 'No se pudo guardar la resena.');
            redirect('index.php?page=perfil-tutor&id=' . $tutorId);
        }

        try {
            $tutorData = $this->model->findById($tutorId);
            if ($tutorData && !empty($tutorData['usuario_id'])) {
                (new NotificacionModel())->create(
                    (int) $tutorData['usuario_id'],
                    'resena_nueva',
                    'Nueva resena recibida',
                    ($user['nombre'] ?? 'Un estudiante') . ' te dejo una resena en tu perfil.',
                    'index.php?page=tutor'
                );
            }
        } catch (Throwable $e) {
            // silently ignore
        }

        setFlash('success', 'Tu resena fue publicada.');
        redirect('index.php?page=perfil-tutor&id=' . $tutorId);
    }

    public function perfilTutor(): void
    {
        $activePage = 'tutores';
        $tutores = [];
        $yaCalificado = false;
        $yaReseno = false;
        $resenas = [];
        $citasConfirmadas = [];
        $yaUnidoIds = [];

        try {
            $tutores = $this->model->getAll();
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudieron cargar los tutores desde la base de datos.');
        }

        $user = authUser();
        $perfilId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($perfilId > 0) {
            try {
                $resenas = $this->model->getResenasByTutorId($perfilId);
            } catch (Throwable $exception) {
                // silently ignore
            }

            $citaModel = new CitaModel();

            try {
                $citasConfirmadas = $citaModel->getConfirmadasByTutorId($perfilId);
            } catch (Throwable $exception) {
                // silently ignore
            }

            if ($user) {
                try {
                    $yaCalificado = $this->model->hasRated($perfilId, (int) $user['id']);
                    $yaReseno     = $this->model->hasResena($perfilId, (int) $user['id']);
                    $joinedIds    = $citaModel->getJoinedCitaIdsByUserId((int) $user['id']);
                    $yaUnidoIds   = array_map('intval', $joinedIds);
                } catch (Throwable $exception) {
                    // silently ignore
                }
            }
        }

        require __DIR__ . '/../vista/paginas/perfil-tutor.php';
    }

    public function unirse(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=tutores');
        }

        $user = authUser();
        if (!$user) {
            setFlash('error', 'Inicia sesion para unirte a una sesion.');
            redirect('index.php?page=inicio&auth=login');
        }

        $citaId  = isset($_POST['cita_id'])  ? (int) $_POST['cita_id']  : 0;
        $tutorId = isset($_POST['tutor_id']) ? (int) $_POST['tutor_id'] : 0;

        if ($citaId <= 0) {
            setFlash('error', 'Sesion no valida.');
            redirect('index.php?page=perfil-tutor&id=' . $tutorId);
        }

        try {
            $result = (new CitaModel())->joinCita(
                $citaId,
                (int) $user['id'],
                (string) $user['nombre'],
                (string) $user['email']
            );
        } catch (Throwable $e) {
            setFlash('error', 'No se pudo procesar la inscripcion. Intenta de nuevo.');
            redirect('index.php?page=perfil-tutor&id=' . $tutorId);
        }

        if ($result === 'already_joined') {
            setFlash('error', 'Ya estas inscrito en esta sesion.');
        } elseif ($result === 'not_found') {
            setFlash('error', 'La sesion no esta disponible o ya no esta confirmada.');
        } elseif ($result !== 'ok') {
            setFlash('error', 'No se pudo completar la inscripcion. Intenta de nuevo.');
        } else {
            setFlash('success', 'Te inscribiste correctamente. El tutor compartira el enlace pronto.');
        }

        redirect('index.php?page=perfil-tutor&id=' . $tutorId);
    }
}
