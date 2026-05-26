<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../modelo/PostulacionModel.php';

final class PostulacionController
{
    private PostulacionModel $model;

    public function __construct()
    {
        $this->model = new PostulacionModel();
    }

    public function form(): void
    {
        if (!isLoggedIn()) {
            setFlash('error', 'Inicia sesion para postularte como tutor.');
            redirect('index.php?page=inicio&auth=login');
        }

        $activePage = 'ser-tutor';
        require __DIR__ . '/../vista/paginas/ser-tutor.php';
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=ser-tutor');
        }

        if (!isLoggedIn()) {
            setFlash('error', 'Inicia sesion para postularte como tutor.');
            redirect('index.php?page=inicio&auth=login');
        }

        $user = authUser() ?? [];

        $materias = $_POST['tutor_materias'] ?? [];
        if (!is_array($materias)) {
            $materias = [];
        }

        $materias = array_values(array_unique(array_filter(array_map(function ($materia) {
            return trim((string) $materia);
        }, $materias), function ($materia) {
            return $materia !== '';
        })));

        if (!$materias) {
            setFlash('error', 'Selecciona al menos una materia para postularte.');
            setOld($_POST);
            redirect('index.php?page=ser-tutor#postulacion');
        }

        $materiasJson = json_encode($materias, JSON_UNESCAPED_UNICODE);
        if ($materiasJson === false) {
            $materiasJson = '[]';
        }

        $data = [
            'nombre' => trim((string) ($user['nombre'] ?? $_POST['tutor_nombre'] ?? '')),
            'codigo' => trim((string) ($user['codigo'] ?? $_POST['tutor_codigo'] ?? '')),
            'correo' => trim((string) ($user['email'] ?? $_POST['tutor_correo'] ?? '')),
            'telefono' => trim((string) ($user['telefono'] ?? $_POST['tutor_telefono'] ?? '')),
            'carrera' => trim((string) ($user['carrera'] ?? $_POST['tutor_carrera'] ?? '')),
            'semestre' => trim($_POST['tutor_semestre'] ?? ''),
            'materia' => $materiasJson,
            'promedio' => trim($_POST['tutor_promedio'] ?? '0'),
            'motivacion' => trim($_POST['tutor_motivacion'] ?? ''),
            'modalidad' => trim($_POST['tutor_modalidad'] ?? 'Virtual (Meet/Zoom)'),
        ];

        foreach (['nombre', 'codigo', 'correo', 'telefono', 'carrera', 'semestre', 'materia', 'motivacion'] as $campo) {
            if ($data[$campo] === '') {
                setFlash('error', 'Completa todos los campos obligatorios del formulario.');
                setOld($_POST);
                redirect('index.php?page=ser-tutor#postulacion');
            }
        }

        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'El correo de postulacion no es valido.');
            setOld($_POST);
            redirect('index.php?page=ser-tutor#postulacion');
        }

        if (!preg_match('/^\d{1,10}$/', $data['codigo']) || !preg_match('/^\d{1,10}$/', $data['telefono'])) {
            setFlash('error', 'Codigo y telefono deben ser numericos de maximo 10 digitos.');
            setOld($_POST);
            redirect('index.php?page=ser-tutor#postulacion');
        }

        try {
            $this->model->create($data);
        } catch (Throwable $exception) {
            setFlash('error', 'No se pudo guardar la postulacion en la base de datos.');
            setOld($_POST);
            redirect('index.php?page=ser-tutor#postulacion');
        }

        clearOld();
        setFlash('success', 'Postulacion enviada correctamente. Te contactaremos por correo institucional.');

        redirect('index.php?page=ser-tutor#postulacion');
    }
}
