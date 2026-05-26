<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class TutorModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query(
            'SELECT t.id, t.nombre, t.carrera, t.semestre, t.rating, t.sesiones, t.materias, t.tags, t.bio, t.disponibilidad, t.resenas,
                    COALESCE(u.foto_perfil, \'\') AS foto_perfil
             FROM tutores t
             LEFT JOIN usuarios u ON u.id = t.usuario_id
             ORDER BY t.nombre'
        );

        $rows = $stmt->fetchAll();
        $tutores = [];

        foreach ($rows as $row) {
            $tutores[] = $this->mapTutor($row);
        }

        return $tutores;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.id, t.usuario_id, t.nombre, t.carrera, t.semestre, t.rating, t.sesiones, t.materias, t.tags, t.bio, t.disponibilidad, t.resenas,
                    COALESCE(u.foto_perfil, \'\') AS foto_perfil
             FROM tutores t
             LEFT JOIN usuarios u ON u.id = t.usuario_id
             WHERE t.id = :id
             LIMIT 1'
        );

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->mapTutor($row);
    }

    public function findByUsuarioId(int $usuarioId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.id, t.usuario_id, t.nombre, t.carrera, t.semestre, t.rating, t.sesiones, t.materias, t.tags, t.bio, t.disponibilidad, t.resenas,
                    COALESCE(u.foto_perfil, \'\') AS foto_perfil
             FROM tutores t
             LEFT JOIN usuarios u ON u.id = t.usuario_id
             WHERE t.usuario_id = :usuario_id
             LIMIT 1'
        );

        $stmt->execute(['usuario_id' => $usuarioId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->mapTutor($row);
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM tutores WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    public function incrementSesiones(int $tutorId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE tutores SET sesiones = sesiones + 1 WHERE id = :id'
        );
        return $stmt->execute(['id' => $tutorId]);
    }

    public function updateDisponibilidad(int $tutorId, array $disponibilidad): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE tutores
             SET disponibilidad = :disponibilidad
             WHERE id = :id'
        );

        return $stmt->execute([
            'disponibilidad' => $this->encodeJsonList($disponibilidad),
            'id' => $tutorId,
        ]);
    }

    public function createFromPostulacion(array $postulacion, ?int $usuarioId = null): bool
    {
        $carrera = (string) ($postulacion['carrera'] ?? '');
        $materias = [];
        $materiaRaw = $postulacion['materia'] ?? '';

        if (is_array($materiaRaw)) {
            $materias = array_values(array_filter(array_map('trim', $materiaRaw), function ($materia) {
                return $materia !== '';
            }));
        } else {
            $materias = $this->decodeJsonList((string) $materiaRaw);
            if (!$materias) {
                $materiaTexto = trim((string) $materiaRaw);
                if ($materiaTexto !== '') {
                    $materias = [$materiaTexto];
                }
            }
        }

        if (!$materias) {
            $materias = $this->materiasPorCarrera($carrera);
        }

        $tags = $this->tagsFromMaterias($materias);

        $stmt = $this->db->prepare(
            'INSERT INTO tutores (
                usuario_id,
                nombre,
                carrera,
                semestre,
                rating,
                sesiones,
                materias,
                tags,
                bio,
                disponibilidad,
                resenas,
                creado_en
            ) VALUES (
                :usuario_id,
                :nombre,
                :carrera,
                :semestre,
                :rating,
                :sesiones,
                :materias,
                :tags,
                :bio,
                :disponibilidad,
                :resenas,
                NOW()
            )'
        );

        return $stmt->execute([
            'usuario_id' => $usuarioId,
            'nombre' => (string) ($postulacion['nombre'] ?? ''),
            'carrera' => (string) ($postulacion['carrera'] ?? ''),
            'semestre' => (string) ($postulacion['semestre'] ?? ''),
            'rating' => 0,
            'sesiones' => 0,
            'materias' => $this->encodeJsonList($materias),
            'tags' => $this->encodeJsonList($tags),
            'bio' => (string) ($postulacion['motivacion'] ?? ''),
            'disponibilidad' => $this->encodeJsonList([]),
            'resenas' => $this->encodeJsonList([]),
        ]);
    }

    private function mapTutor(array $row): array
    {
        $nombre = (string) $row['nombre'];

        return [
            'id' => (int) $row['id'],
            'usuario_id' => isset($row['usuario_id']) ? (int) $row['usuario_id'] : null,
            'nombre' => $nombre,
            'iniciales' => $this->buildInitials($nombre),
            'carrera' => (string) $row['carrera'],
            'semestre' => (string) $row['semestre'],
            'rating' => (float) $row['rating'],
            'sesiones' => (int) $row['sesiones'],
            'materias' => $this->decodeJsonList($row['materias'] ?? ''),
            'tags' => $this->decodeJsonList($row['tags'] ?? ''),
            'bio' => (string) $row['bio'],
            'disponibilidad' => $this->decodeJsonList($row['disponibilidad'] ?? ''),
            'resenas' => $this->decodeJsonList($row['resenas'] ?? ''),
            'foto_perfil' => (string) ($row['foto_perfil'] ?? ''),
        ];
    }

    private function decodeJsonList(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function encodeJsonList(array $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
        return $encoded === false ? '[]' : $encoded;
    }

    public function hasResena(int $tutorId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM tutor_resenas WHERE tutor_id = :tutor_id AND usuario_id = :usuario_id LIMIT 1'
        );
        $stmt->execute(['tutor_id' => $tutorId, 'usuario_id' => $userId]);
        return (bool) $stmt->fetch();
    }

    public function addResena(int $tutorId, int $userId, string $nombreUsuario, string $texto): string
    {
        if ($this->hasResena($tutorId, $userId)) {
            return 'already_reviewed';
        }

        $stmt = $this->db->prepare(
            'INSERT INTO tutor_resenas (tutor_id, usuario_id, nombre_usuario, texto, creado_en)
             VALUES (:tutor_id, :usuario_id, :nombre_usuario, :texto, NOW())'
        );
        $ok = $stmt->execute([
            'tutor_id'       => $tutorId,
            'usuario_id'     => $userId,
            'nombre_usuario' => $nombreUsuario,
            'texto'          => $texto,
        ]);

        return $ok ? 'ok' : 'error';
    }

    public function getResenasByTutorId(int $tutorId): array
    {
        $stmt = $this->db->prepare(
            'SELECT nombre_usuario, texto, creado_en
             FROM tutor_resenas
             WHERE tutor_id = :tutor_id
             ORDER BY creado_en DESC'
        );
        $stmt->execute(['tutor_id' => $tutorId]);
        return $stmt->fetchAll();
    }

    public function hasRated(int $tutorId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM tutor_calificaciones WHERE tutor_id = :tutor_id AND usuario_id = :usuario_id LIMIT 1'
        );
        $stmt->execute(['tutor_id' => $tutorId, 'usuario_id' => $userId]);
        return (bool) $stmt->fetch();
    }

    public function addRating(int $tutorId, int $userId, int $rating): string
    {
        if ($this->hasRated($tutorId, $userId)) {
            return 'already_rated';
        }

        $stmt = $this->db->prepare('SELECT rating, num_calificaciones FROM tutores WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $tutorId]);
        $row = $stmt->fetch();

        $currentRating = 0.0;
        $currentCount = 0;
        if ($row) {
            $currentRating = isset($row['rating']) ? (float) $row['rating'] : 0.0;
            $currentCount = isset($row['num_calificaciones']) ? (int) $row['num_calificaciones'] : 0;
        }

        $newCount = $currentCount + 1;
        $newRating = round(($currentRating * $currentCount + $rating) / $newCount, 2);

        $insert = $this->db->prepare(
            'INSERT INTO tutor_calificaciones (tutor_id, usuario_id, calificacion, creado_en)
             VALUES (:tutor_id, :usuario_id, :calificacion, NOW())'
        );
        $insert->execute(['tutor_id' => $tutorId, 'usuario_id' => $userId, 'calificacion' => $rating]);

        $update = $this->db->prepare(
            'UPDATE tutores SET rating = :rating, num_calificaciones = :num_calificaciones WHERE id = :id'
        );
        $ok = $update->execute(['rating' => $newRating, 'num_calificaciones' => $newCount, 'id' => $tutorId]);

        return $ok ? 'ok' : 'error';
    }

    public function getMateriasDemandadas(int $limit = 3): array
    {
        $stmt = $this->db->query('SELECT materias FROM tutores');
        $rows = $stmt->fetchAll();

        $counts = [];
        foreach ($rows as $row) {
            $materias = $this->decodeJsonList($row['materias'] ?? '');
            foreach ($materias as $materia) {
                $materia = trim((string) $materia);
                if ($materia === '') {
                    continue;
                }
                $counts[$materia] = ($counts[$materia] ?? 0) + 1;
            }
        }

        arsort($counts);

        $result = [];
        foreach (array_slice($counts, 0, $limit, true) as $nombre => $count) {
            $result[] = [
                'nombre' => $nombre,
                'count'  => $count,
                'tag'    => $this->tagFromMateria($nombre),
            ];
        }

        return $result;
    }

    private function materiasPorCarrera(string $carrera): array
    {
        $key = $this->normalizeKey($carrera);

        $map = [
            'ing-de-sistemas' => ['Programacion', 'Bases de Datos', 'Calculo I', 'Algebra Lineal', 'Redes'],
            'ingenieria-de-sistemas' => ['Programacion', 'Bases de Datos', 'Calculo I', 'Algebra Lineal', 'Redes'],
            'sistemas' => ['Programacion', 'Bases de Datos', 'Calculo I', 'Algebra Lineal', 'Redes'],
            'medicina' => ['Biologia', 'Quimica', 'Anatomia'],
            'derecho' => ['Derecho Constitucional', 'Redaccion', 'Argumentacion'],
            'ing-civil' => ['Fisica I', 'Calculo II', 'Estatica'],
            'ingenieria-civil' => ['Fisica I', 'Calculo II', 'Estatica'],
            'civil' => ['Fisica I', 'Calculo II', 'Estatica'],
            'administracion' => ['Estadistica', 'Contabilidad', 'Finanzas'],
            'biologia' => ['Biologia', 'Quimica General', 'Genetica'],
        ];

        return $map[$key] ?? [];
    }

    private function tagsFromMaterias(array $materias): array
    {
        $tags = [];

        foreach ($materias as $materia) {
            $tag = $this->tagFromMateria((string) $materia);
            if ($tag !== '' && !in_array($tag, $tags, true)) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }

    private function tagFromMateria(string $materia): string
    {
        $key = $this->normalizeKey($materia);

        $map = [
            'calculo' => 'calculo',
            'fisica' => 'fisica',
            'programacion' => 'programacion',
            'quimica' => 'quimica',
            'estadistica' => 'estadistica',
            'biologia' => 'biologia',
            'derecho' => 'derecho',
            'algebra' => 'algebra',
        ];

        foreach ($map as $needle => $tag) {
            if (str_contains($key, $needle)) {
                return $tag;
            }
        }

        return '';
    }

    private function normalizeKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(['.', '/', ','], ' ', $value);
        $value = $this->stripAccents($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim((string) $value, '-');

        return $value;
    }

    private function stripAccents(string $value): string
    {
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                return $converted;
            }
        }

        return $value;
    }

    private function buildInitials(string $nombre): string
    {
        $parts = preg_split('/\s+/', trim($nombre)) ?: [];
        $letters = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $letters .= strtoupper(substr($part, 0, 1));

            if (strlen($letters) >= 2) {
                break;
            }
        }

        return $letters !== '' ? $letters : 'TU';
    }
}
