<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class ContenidoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id, tutor_id, tutor_nombre, titulo, descripcion, materia, tema, tipo, archivo_nombre, archivo_ruta, extension, estado, creado_en
             FROM contenidos
             ORDER BY creado_en DESC, id DESC'
        );

        return array_map([$this, 'mapRow'], $stmt->fetchAll());
    }

    public function getByTutorId(int $tutorId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, tutor_id, tutor_nombre, titulo, descripcion, materia, tema, tipo, archivo_nombre, archivo_ruta, extension, estado, creado_en
             FROM contenidos
             WHERE tutor_id = :tutor_id
             ORDER BY creado_en DESC, id DESC'
        );

        $stmt->execute(['tutor_id' => $tutorId]);

        return array_map([$this, 'mapRow'], $stmt->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, tutor_id, tutor_nombre, titulo, descripcion, materia, tema, tipo, archivo_nombre, archivo_ruta, extension, estado, creado_en
             FROM contenidos
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->mapRow($row) : null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO contenidos (
                tutor_id,
                tutor_nombre,
                titulo,
                descripcion,
                materia,
                tema,
                tipo,
                archivo_nombre,
                archivo_ruta,
                extension,
                estado,
                creado_en
            ) VALUES (
                :tutor_id,
                :tutor_nombre,
                :titulo,
                :descripcion,
                :materia,
                :tema,
                :tipo,
                :archivo_nombre,
                :archivo_ruta,
                :extension,
                :estado,
                NOW()
            )'
        );

        return $stmt->execute([
            'tutor_id' => $data['tutor_id'],
            'tutor_nombre' => $data['tutor_nombre'],
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? '',
            'materia' => $data['materia'] ?? 'General',
            'tema' => $data['tema'] ?? 'Recurso',
            'tipo' => $data['tipo'],
            'archivo_nombre' => $data['archivo_nombre'],
            'archivo_ruta' => $data['archivo_ruta'],
            'extension' => $data['extension'],
            'estado' => $data['estado'] ?? 'pendiente',
        ]);
    }

    public function updateById(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE contenidos
             SET titulo = :titulo,
                 descripcion = :descripcion,
                 materia = :materia,
                 tema = :tema,
                 tipo = :tipo,
                 archivo_nombre = :archivo_nombre,
                 archivo_ruta = :archivo_ruta,
                 extension = :extension,
                 estado = :estado
             WHERE id = :id'
        );

        return $stmt->execute([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? '',
            'materia' => $data['materia'] ?? 'General',
            'tema' => $data['tema'] ?? 'Recurso',
            'tipo' => $data['tipo'],
            'archivo_nombre' => $data['archivo_nombre'],
            'archivo_ruta' => $data['archivo_ruta'],
            'extension' => $data['extension'],
            'estado' => $data['estado'] ?? 'pendiente',
            'id' => $id,
        ]);
    }

    public function getApproved(): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, tutor_id, tutor_nombre, titulo, descripcion, materia, tema, tipo, archivo_nombre, archivo_ruta, extension, estado, creado_en
             FROM contenidos
             WHERE estado = :estado
             ORDER BY creado_en DESC, id DESC'
        );

        $stmt->execute(['estado' => 'aprobado']);

        return array_map([$this, 'mapRow'], $stmt->fetchAll());
    }

    public function updateEstadoById(int $id, string $estado): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE contenidos
             SET estado = :estado
             WHERE id = :id'
        );

        return $stmt->execute([
            'estado' => $estado,
            'id' => $id,
        ]);
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM contenidos WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    private function mapRow(array $row): array
    {
        $fecha = (string) ($row['creado_en'] ?? '');
        $fecha = $fecha !== '' ? substr($fecha, 0, 10) : '';

        return [
            'id' => (int) $row['id'],
            'tutor_id' => (int) $row['tutor_id'],
            'tutor' => (string) $row['tutor_nombre'],
            'titulo' => (string) $row['titulo'],
            'descripcion' => (string) ($row['descripcion'] ?? ''),
            'materia' => (string) ($row['materia'] ?? 'General'),
            'tema' => (string) ($row['tema'] ?? 'Recurso'),
            'tipo' => (string) $row['tipo'],
            'archivo_nombre' => (string) $row['archivo_nombre'],
            'archivo_ruta' => (string) $row['archivo_ruta'],
            'extension' => (string) $row['extension'],
            'estado' => (string) ($row['estado'] ?? 'pendiente'),
            'fecha' => $fecha,
            'enlace' => (string) $row['archivo_ruta'],
        ];
    }
}
