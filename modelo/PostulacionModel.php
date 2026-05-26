<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class PostulacionModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO postulaciones_tutor (
                nombre,
                codigo,
                correo,
                telefono,
                carrera,
                semestre,
                materia,
                promedio,
                motivacion,
                modalidad,
                creado_en
            ) VALUES (
                :nombre,
                :codigo,
                :correo,
                :telefono,
                :carrera,
                :semestre,
                :materia,
                :promedio,
                :motivacion,
                :modalidad,
                NOW()
            )'
        );

        return $stmt->execute([
            'nombre' => $data['nombre'],
            'codigo' => $data['codigo'],
            'correo' => $data['correo'],
            'telefono' => $data['telefono'],
            'carrera' => $data['carrera'],
            'semestre' => $data['semestre'],
            'materia' => $data['materia'],
            'promedio' => (int) $data['promedio'],
            'motivacion' => $data['motivacion'],
            'modalidad' => $data['modalidad'],
        ]);
    }

    public function getAll(): array
    {
        $stmt = $this->db->query(
              'SELECT id, nombre, codigo, correo, telefono, carrera, semestre, materia, promedio, motivacion, modalidad, creado_en
             FROM postulaciones_tutor
             ORDER BY creado_en DESC, id DESC'
        );

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
              'SELECT id, nombre, codigo, correo, telefono, carrera, semestre, materia, promedio, motivacion, modalidad, creado_en
             FROM postulaciones_tutor
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM postulaciones_tutor WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }
}
