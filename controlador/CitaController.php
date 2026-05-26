<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../modelo/CitaModel.php';
require_once __DIR__ . '/../modelo/TutorModel.php';
require_once __DIR__ . '/../modelo/NotificacionModel.php';

final class CitaController
{
    private CitaModel $model;

    public function __construct()
    {
        $this->model = new CitaModel();
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=tutores');
        }

        $user = authUser();
        $redirectTo = $this->resolveRedirect($_POST['redirect_to'] ?? 'tutores');

        $data = [
            'usuario_id' => $user ? (int) $user['id'] : null,
            'tutor_id' => $this->normalizeTutorId($_POST['cita_tutor_id'] ?? ''),
            'tutor_nombre' => trim($_POST['cita_tutor_nombre'] ?? ''),
            'estudiante_nombre' => trim($_POST['cita_estudiante_nombre'] ?? ''),
            'estudiante_correo' => trim($_POST['cita_estudiante_correo'] ?? ''),
            'materia' => trim($_POST['cita_materia'] ?? ''),
            'modalidad' => trim($_POST['cita_modalidad'] ?? ''),
            'enlace_reunion' => '',
            'fecha' => trim($_POST['cita_fecha'] ?? ''),
            'hora' => trim($_POST['cita_hora'] ?? ''),
            'estado' => 'pendiente',
        ];

        if (!$user) {
            setFlash('error', 'Inicia sesion para agendar una cita.');
            setOld($this->oldFrom($data));
            redirect('index.php?page=' . $redirectTo);
        }

        if (!$this->validate($data)) {
            setOld($this->oldFrom($data));
            redirect('index.php?page=' . $redirectTo);
        }

        try {
            $this->model->create($data);
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo guardar la cita en la base de datos.');
            setOld($this->oldFrom($data));
            redirect('index.php?page=' . $redirectTo);
        }

        if ($data['tutor_id'] !== null) {
            try {
                $tutorData = (new TutorModel())->findById((int) $data['tutor_id']);
                if ($tutorData && !empty($tutorData['usuario_id'])) {
                    (new NotificacionModel())->create(
                        (int) $tutorData['usuario_id'],
                        'nueva_cita',
                        'Nueva solicitud de cita',
                        $data['estudiante_nombre'] . ' agendo una cita de ' . $data['materia'] . ' para el ' . $data['fecha'] . ' a las ' . $data['hora'] . '.',
                        'index.php?page=tutor'
                    );
                }
            } catch (Throwable $e) {
                // silently ignore
            }
        }

        clearOld();
        setFlash('success', 'Cita agendada exitosamente.');
        redirect('index.php?page=' . $redirectTo);
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=perfil');
        }

        $user = authUser();
        if (!$user) {
            setFlash('error', 'Inicia sesion para editar tus citas.');
            redirect('index.php?page=inicio');
        }

        $citaId = $this->normalizeCitaId($_POST['cita_id'] ?? '');
        if ($citaId === null) {
            setFlash('error', 'La cita seleccionada no es valida.');
            redirect('index.php?page=perfil');
        }

        $usuarioId = (int) $user['id'];
        $cita = $this->model->getByIdForUsuario($citaId, $usuarioId);
        if (!$cita) {
            setFlash('error', 'No se encontro la cita a editar.');
            redirect('index.php?page=perfil');
        }

        $estadoActual = strtolower((string) ($cita['estado'] ?? 'pendiente'));
        if ($estadoActual !== 'pendiente') {
            setFlash('error', 'Solo puedes editar citas pendientes.');
            redirect('index.php?page=perfil');
        }

        $data = [
            'materia' => trim($_POST['cita_materia'] ?? ''),
            'fecha' => trim($_POST['cita_fecha'] ?? ''),
            'hora' => trim($_POST['cita_hora'] ?? ''),
        ];

        if (!$this->validateUpdate($data)) {
            redirect('index.php?page=perfil');
        }

        try {
            $this->model->updateByUsuarioId($citaId, $usuarioId, $data);
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo actualizar la cita en la base de datos.');
            redirect('index.php?page=perfil');
        }

        setFlash('success', 'Cita actualizada correctamente.');
        redirect('index.php?page=perfil');
    }

    public function cancel(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=perfil');
        }

        $user = authUser();
        if (!$user) {
            setFlash('error', 'Inicia sesion para cancelar tus citas.');
            redirect('index.php?page=inicio');
        }

        $citaId = $this->normalizeCitaId($_POST['cita_id'] ?? '');
        if ($citaId === null) {
            setFlash('error', 'La cita seleccionada no es valida.');
            redirect('index.php?page=perfil');
        }

        $motivo = trim($_POST['cita_motivo'] ?? '');
        if ($motivo === '') {
            setFlash('error', 'Indica el motivo de la cancelacion.');
            redirect('index.php?page=perfil');
        }

        if (strlen($motivo) > 300) {
            setFlash('error', 'El motivo no puede superar los 300 caracteres.');
            redirect('index.php?page=perfil');
        }

        $usuarioId = (int) $user['id'];
        $cita = $this->model->getByIdForUsuario($citaId, $usuarioId);
        if (!$cita) {
            setFlash('error', 'No se encontro la cita a cancelar.');
            redirect('index.php?page=perfil');
        }

        $estadoActual = strtolower((string) ($cita['estado'] ?? 'pendiente'));
        if (in_array($estadoActual, ['cancelada', 'completada'], true)) {
            setFlash('error', 'La cita ya se encuentra cerrada.');
            redirect('index.php?page=perfil');
        }

        try {
            $this->model->cancelByUsuarioId($citaId, $usuarioId, $motivo);
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo cancelar la cita en la base de datos.');
            redirect('index.php?page=perfil');
        }

        if (!empty($cita['tutor_id'])) {
            try {
                $tutorData = (new TutorModel())->findById((int) $cita['tutor_id']);
                if ($tutorData && !empty($tutorData['usuario_id'])) {
                    $estudianteNombre = (string) ($user['nombre'] ?? '');
                    (new NotificacionModel())->create(
                        (int) $tutorData['usuario_id'],
                        'cita_cancelada_tutor',
                        'Cita cancelada por el estudiante',
                        $estudianteNombre . ' cancelo la cita de ' . $cita['materia'] . '. Motivo: ' . $motivo,
                        'index.php?page=tutor'
                    );
                }
            } catch (Throwable $e) {
                // silently ignore
            }
        }

        setFlash('success', 'Cita cancelada correctamente.');
        redirect('index.php?page=perfil');
    }

    private function validate(array $data): bool
    {
        foreach (['tutor_nombre', 'estudiante_nombre', 'estudiante_correo', 'materia', 'modalidad', 'fecha', 'hora'] as $campo) {
            if ($data[$campo] === '') {
                setFlash('error', 'Completa todos los campos obligatorios para agendar la cita.');
                return false;
            }
        }



        if (!filter_var($data['estudiante_correo'], FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'El correo de contacto no es valido.');
            return false;
        }

        $fecha = DateTime::createFromFormat('Y-m-d', $data['fecha']);
        if (!$fecha || $fecha->format('Y-m-d') !== $data['fecha']) {
            setFlash('error', 'La fecha no tiene un formato valido.');
            return false;
        }

        $hora = DateTime::createFromFormat('H:i', $data['hora']);
        if (!$hora || $hora->format('H:i') !== $data['hora']) {
            setFlash('error', 'La hora no tiene un formato valido.');
            return false;
        }

        if ($data['tutor_id'] !== null) {
            try {
                $tutor = (new TutorModel())->findById((int) $data['tutor_id']);
            } catch (Throwable $exception) {
                setFlash('error', 'No se pudo validar la agenda del tutor.');
                return false;
            }

            if (!$tutor) {
                setFlash('error', 'No se encontro el tutor seleccionado.');
                return false;
            }

            $disponibilidad = $tutor['disponibilidad'] ?? [];
            if (!is_array($disponibilidad) || !$disponibilidad) {
                setFlash('error', 'El tutor no tiene agenda disponible.');
                return false;
            }

            $diasMap = [
                1 => 'Lunes',
                2 => 'Martes',
                3 => 'Miercoles',
                4 => 'Jueves',
                5 => 'Viernes',
                6 => 'Sabado',
                7 => 'Domingo',
            ];

            $diaKey = $diasMap[(int) $fecha->format('N')] ?? '';
            if ($diaKey === '') {
                setFlash('error', 'No se pudo validar el dia de la cita.');
                return false;
            }

            $bloque = null;
            foreach ($disponibilidad as $item) {
                if (isset($item['dia']) && (string) $item['dia'] === $diaKey) {
                    $bloque = $item;
                    break;
                }
            }

            if (!$bloque) {
                setFlash('error', 'El tutor no tiene disponibilidad para el dia seleccionado.');
                return false;
            }

            $horas = (string) ($bloque['horas'] ?? '');
            if (!preg_match('/(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/', $horas, $matches)) {
                setFlash('error', 'La agenda del tutor no tiene un horario valido.');
                return false;
            }

            $inicio = DateTime::createFromFormat('H:i', $matches[1]);
            $fin = DateTime::createFromFormat('H:i', $matches[2]);
            if (!$inicio || !$fin) {
                setFlash('error', 'La agenda del tutor no tiene un horario valido.');
                return false;
            }

            $horaMin = ((int) $hora->format('H')) * 60 + (int) $hora->format('i');
            $inicioMin = ((int) $inicio->format('H')) * 60 + (int) $inicio->format('i');
            $finMin = ((int) $fin->format('H')) * 60 + (int) $fin->format('i');

            if ($horaMin < $inicioMin || $horaMin > $finMin) {
                setFlash('error', 'La hora seleccionada no esta dentro del horario disponible del tutor.');
                return false;
            }
        }

        $fechaHora = DateTime::createFromFormat('Y-m-d H:i', $data['fecha'] . ' ' . $data['hora']);
        if (!$fechaHora) {
            setFlash('error', 'No se pudo interpretar la fecha y hora de la cita.');
            return false;
        }

        $ahora = new DateTime('now');
        if ($fechaHora <= $ahora) {
            setFlash('error', 'La cita debe agendarse en una fecha y hora futuras.');
            return false;
        }

        return true;
    }

    private function validateUpdate(array $data): bool
    {
        foreach (['materia', 'fecha', 'hora'] as $campo) {
            if ($data[$campo] === '') {
                setFlash('error', 'Completa todos los campos para editar la cita.');
                return false;
            }
        }

        $fecha = DateTime::createFromFormat('Y-m-d', $data['fecha']);
        if (!$fecha || $fecha->format('Y-m-d') !== $data['fecha']) {
            setFlash('error', 'La fecha no tiene un formato valido.');
            return false;
        }

        $hora = DateTime::createFromFormat('H:i', $data['hora']);
        if (!$hora || $hora->format('H:i') !== $data['hora']) {
            setFlash('error', 'La hora no tiene un formato valido.');
            return false;
        }

        $fechaHora = DateTime::createFromFormat('Y-m-d H:i', $data['fecha'] . ' ' . $data['hora']);
        if (!$fechaHora) {
            setFlash('error', 'No se pudo interpretar la fecha y hora de la cita.');
            return false;
        }

        $ahora = new DateTime('now');
        if ($fechaHora <= $ahora) {
            setFlash('error', 'La cita debe quedar en una fecha y hora futuras.');
            return false;
        }

        return true;
    }

    private function normalizeTutorId($value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return ctype_digit($value) ? (int) $value : null;
    }

    private function normalizeCitaId($value): ?int
    {
        $value = trim((string) $value);
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        $id = (int) $value;
        return $id > 0 ? $id : null;
    }

    private function oldFrom(array $data): array
    {
        return [
            'cita_tutor_id' => $data['tutor_id'] === null ? '' : (string) $data['tutor_id'],
            'cita_tutor_nombre' => $data['tutor_nombre'],
            'cita_estudiante_nombre' => $data['estudiante_nombre'],
            'cita_estudiante_correo' => $data['estudiante_correo'],
            'cita_materia' => $data['materia'],
            'cita_modalidad' => $data['modalidad'],
            'cita_fecha' => $data['fecha'],
            'cita_hora' => $data['hora'],
        ];
    }

    private function resolveRedirect($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 'tutores';
        }

        if (preg_match('/^(tutores|perfil|perfil-tutor)(?:&id=\d+)?$/', $value) === 1) {
            return $value;
        }

        return 'tutores';
    }
}
