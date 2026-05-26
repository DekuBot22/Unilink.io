<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class CitaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS cita_participantes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cita_id INT NOT NULL,
                usuario_id INT NOT NULL,
                nombre VARCHAR(120) NOT NULL,
                correo VARCHAR(150) NOT NULL,
                creado_en DATETIME NOT NULL,
                UNIQUE KEY unique_cita_usuario (cita_id, usuario_id)
            )'
        );
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO citas (
                usuario_id,
                tutor_id,
                tutor_nombre,
                estudiante_nombre,
                estudiante_correo,
                materia,
                modalidad,
                enlace_reunion,
                fecha,
                hora,
                estado,
                calificacion,
                cancelada_por,
                cancelacion_motivo,
                creado_en
            ) VALUES (
                :usuario_id,
                :tutor_id,
                :tutor_nombre,
                :estudiante_nombre,
                :estudiante_correo,
                :materia,
                :modalidad,
                :enlace_reunion,
                :fecha,
                :hora,
                :estado,
                :calificacion,
                :cancelada_por,
                :cancelacion_motivo,
                NOW()
            )'
        );

        return $stmt->execute([
            'usuario_id' => $data['usuario_id'],
            'tutor_id' => $data['tutor_id'],
            'tutor_nombre' => $data['tutor_nombre'],
            'estudiante_nombre' => $data['estudiante_nombre'],
            'estudiante_correo' => $data['estudiante_correo'],
            'materia' => $data['materia'],
            'modalidad' => $data['modalidad'] ?? 'Virtual (Meet/Zoom)',
            'enlace_reunion' => $data['enlace_reunion'] ?? null,
            'fecha' => $data['fecha'],
            'hora' => $data['hora'],
            'estado' => $data['estado'] ?? 'pendiente',
            'calificacion' => $data['calificacion'] ?? null,
            'cancelada_por' => $data['cancelada_por'] ?? null,
            'cancelacion_motivo' => $data['cancelacion_motivo'] ?? null,
        ]);
    }

    public function getReportSummary(): array
    {
        $totalsStmt = $this->db->query(
            'SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado = "pendiente" THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN estado = "cancelada" THEN 1 ELSE 0 END) AS canceladas,
                SUM(CASE WHEN estado = "completada" THEN 1 ELSE 0 END) AS completadas,
                AVG(CASE WHEN estado = "completada" THEN calificacion END) AS promedio_calificacion
             FROM citas'
        );

        $totals = $totalsStmt->fetch() ?: [];

        $modalidadStmt = $this->db->query(
            'SELECT COALESCE(modalidad, "Sin definir") AS modalidad,
                    COUNT(*) AS total
             FROM citas
             GROUP BY COALESCE(modalidad, "Sin definir")
             ORDER BY total DESC, modalidad ASC
             LIMIT 1'
        );

        $modalidadTop = $modalidadStmt->fetch() ?: [];

        $tutorStmt = $this->db->query(
            'SELECT tutor_id, tutor_nombre, COUNT(*) AS total
             FROM citas
             GROUP BY tutor_id, tutor_nombre
             ORDER BY total DESC, tutor_nombre ASC
             LIMIT 1'
        );

        $tutorTop = $tutorStmt->fetch() ?: [];

        return [
            'totals' => $totals,
            'modalidad_top' => $modalidadTop,
            'tutor_top' => $tutorTop,
        ];
    }

    public function getByUsuarioId(int $usuarioId): array
    {
        $stmt = $this->db->prepare(
                     'SELECT id, tutor_id, tutor_nombre, materia, modalidad, enlace_reunion, fecha, hora, estado, cancelada_por, cancelacion_motivo
             FROM citas
             WHERE usuario_id = :usuario_id
             ORDER BY fecha DESC, hora DESC, id DESC'
        );

        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function getByTutorId(int $tutorId): array
    {
        $stmt = $this->db->prepare(
                     'SELECT id, usuario_id, estudiante_nombre, estudiante_correo, materia, modalidad, enlace_reunion, fecha, hora, estado, cancelada_por, cancelacion_motivo
             FROM citas
             WHERE tutor_id = :tutor_id
             ORDER BY fecha DESC, hora DESC, id DESC'
        );

        $stmt->execute(['tutor_id' => $tutorId]);

        return $stmt->fetchAll();
    }

    public function getByIdForTutor(int $citaId, int $tutorId): ?array
    {
        $stmt = $this->db->prepare(
                     'SELECT id, usuario_id, estudiante_nombre, estudiante_correo, materia, modalidad, enlace_reunion, fecha, hora, estado, cancelada_por, cancelacion_motivo
             FROM citas
             WHERE id = :id AND tutor_id = :tutor_id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $citaId,
            'tutor_id' => $tutorId,
        ]);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function updateEstadoByTutor(
        int $citaId,
        int $tutorId,
        string $estado,
        ?string $motivo = null,
        ?string $enlaceReunion = null
    ): bool
    {
        $canceladaPor = null;
        $cancelacionMotivo = null;

        if ($estado === 'cancelada') {
            $canceladaPor = 'tutor';
            $cancelacionMotivo = $motivo !== null && trim($motivo) !== '' ? $motivo : null;
        }

        $setEnlace = $enlaceReunion !== null;
        $sql = 'UPDATE citas
             SET estado = :estado,
                 cancelada_por = :cancelada_por,
                 cancelacion_motivo = :cancelacion_motivo';

        if ($setEnlace) {
            $sql .= ', enlace_reunion = :enlace_reunion';
        }

        $sql .= ' WHERE id = :id AND tutor_id = :tutor_id';

        $stmt = $this->db->prepare($sql);

        $params = [
            'estado' => $estado,
            'cancelada_por' => $canceladaPor,
            'cancelacion_motivo' => $cancelacionMotivo,
            'id' => $citaId,
            'tutor_id' => $tutorId,
        ];

        if ($setEnlace) {
            $params['enlace_reunion'] = $enlaceReunion;
        }

        return $stmt->execute($params);
    }

    public function getByIdForUsuario(int $citaId, int $usuarioId): ?array
    {
        $stmt = $this->db->prepare(
                     'SELECT id, tutor_id, tutor_nombre, materia, modalidad, enlace_reunion, fecha, hora, estado, cancelada_por, cancelacion_motivo
             FROM citas
             WHERE id = :id AND usuario_id = :usuario_id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $citaId,
            'usuario_id' => $usuarioId,
        ]);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function updateByUsuarioId(int $citaId, int $usuarioId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE citas
             SET materia = :materia,
                 fecha = :fecha,
                 hora = :hora
             WHERE id = :id AND usuario_id = :usuario_id'
        );

        return $stmt->execute([
            'materia' => $data['materia'],
            'fecha' => $data['fecha'],
            'hora' => $data['hora'],
            'id' => $citaId,
            'usuario_id' => $usuarioId,
        ]);
    }

    public function cancelByUsuarioId(int $citaId, int $usuarioId, string $motivo): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE citas
             SET estado = :estado,
                 cancelada_por = :cancelada_por,
                 cancelacion_motivo = :cancelacion_motivo
             WHERE id = :id AND usuario_id = :usuario_id'
        );

        return $stmt->execute([
            'estado' => 'cancelada',
            'cancelada_por' => 'estudiante',
            'cancelacion_motivo' => $motivo,
            'id' => $citaId,
            'usuario_id' => $usuarioId,
        ]);
    }

    public function deleteByUsuarioId(int $citaId, int $usuarioId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM citas
             WHERE id = :id AND usuario_id = :usuario_id'
        );

        return $stmt->execute([
            'id' => $citaId,
            'usuario_id' => $usuarioId,
        ]);
    }

    public function getProximasByTutorId(int $tutorId, int $minutes = 30): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, materia, tutor_nombre
             FROM citas
             WHERE tutor_id = :tutor_id
               AND estado = "confirmada"
               AND TIMESTAMP(fecha, hora) > NOW()
               AND TIMESTAMP(fecha, hora) <= DATE_ADD(NOW(), INTERVAL :mins MINUTE)'
        );
        $stmt->execute(['tutor_id' => $tutorId, 'mins' => $minutes]);
        return $stmt->fetchAll();
    }

    public function autoCancelPendientesVencidas(): array
    {
        $sel = $this->db->query(
            'SELECT id, usuario_id, materia, tutor_nombre
             FROM citas
             WHERE estado = "pendiente"
               AND TIMESTAMP(fecha, hora) <= NOW()'
        );
        $affected = $sel->fetchAll();

        if ($affected) {
            $this->db->exec(
                'UPDATE citas
                 SET estado = "cancelada",
                     cancelada_por = "sistema",
                     cancelacion_motivo = "No confirmada por el tutor a tiempo"
                 WHERE estado = "pendiente"
                   AND TIMESTAMP(fecha, hora) <= NOW()'
            );
        }

        return $affected;
    }

    public function markEnCursoByTutorId(int $tutorId): array
    {
        $sel = $this->db->prepare(
            'SELECT id, usuario_id, materia, tutor_nombre
             FROM citas
             WHERE tutor_id = :tutor_id
               AND estado = "confirmada"
               AND TIMESTAMP(fecha, hora) <= NOW()'
        );
        $sel->execute(['tutor_id' => $tutorId]);
        $affected = $sel->fetchAll();

        if ($affected) {
            $upd = $this->db->prepare(
                'UPDATE citas SET estado = "en_curso"
                 WHERE tutor_id = :tutor_id
                   AND estado = "confirmada"
                   AND TIMESTAMP(fecha, hora) <= NOW()'
            );
            $upd->execute(['tutor_id' => $tutorId]);
        }

        return $affected;
    }

    public function getConfirmadasByTutorId(int $tutorId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.usuario_id, c.materia, c.modalidad, c.enlace_reunion, c.fecha, c.hora,
                    (SELECT COUNT(*) FROM cita_participantes cp WHERE cp.cita_id = c.id) AS num_participantes
             FROM citas c
             WHERE c.tutor_id = :tutor_id
               AND c.estado = "confirmada"
               AND TIMESTAMP(c.fecha, c.hora) >= NOW()
             ORDER BY c.fecha ASC, c.hora ASC'
        );
        $stmt->execute(['tutor_id' => $tutorId]);
        return $stmt->fetchAll();
    }

    public function hasJoined(int $citaId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM cita_participantes
             WHERE cita_id = :cita_id AND usuario_id = :usuario_id LIMIT 1'
        );
        $stmt->execute(['cita_id' => $citaId, 'usuario_id' => $userId]);
        return (bool) $stmt->fetch();
    }

    public function joinCita(int $citaId, int $userId, string $nombre, string $correo): string
    {
        $stmt = $this->db->prepare(
            'SELECT id, usuario_id FROM citas
             WHERE id = :id AND estado = "confirmada" LIMIT 1'
        );
        $stmt->execute(['id' => $citaId]);
        $cita = $stmt->fetch();

        if (!$cita) {
            return 'not_found';
        }

        if ((int) $cita['usuario_id'] === $userId || $this->hasJoined($citaId, $userId)) {
            return 'already_joined';
        }

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO cita_participantes (cita_id, usuario_id, nombre, correo, creado_en)
                 VALUES (:cita_id, :usuario_id, :nombre, :correo, NOW())'
            );
            $ok = $stmt->execute([
                'cita_id'    => $citaId,
                'usuario_id' => $userId,
                'nombre'     => $nombre,
                'correo'     => $correo,
            ]);
            return $ok ? 'ok' : 'error';
        } catch (PDOException $e) {
            return $e->getCode() === '23000' ? 'already_joined' : 'error';
        }
    }

    public function getJoinedCitaIdsByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT cita_id FROM cita_participantes WHERE usuario_id = :usuario_id'
        );
        $stmt->execute(['usuario_id' => $userId]);
        return array_column($stmt->fetchAll(), 'cita_id');
    }

    public function markEnCursoByUsuarioId(int $usuarioId): array
    {
        $sel = $this->db->prepare(
            'SELECT id, tutor_id, tutor_nombre, materia
             FROM citas
             WHERE usuario_id = :usuario_id
               AND estado = :estado_actual
               AND TIMESTAMP(fecha, hora) <= NOW()'
        );
        $sel->execute([
            'usuario_id'   => $usuarioId,
            'estado_actual' => 'confirmada',
        ]);
        $affected = $sel->fetchAll();

        if ($affected) {
            $upd = $this->db->prepare(
                'UPDATE citas SET estado = "en_curso"
                 WHERE usuario_id = :usuario_id
                   AND estado = :estado_actual
                   AND TIMESTAMP(fecha, hora) <= NOW()'
            );
            $upd->execute([
                'usuario_id'   => $usuarioId,
                'estado_actual' => 'confirmada',
            ]);
        }

        return $affected;
    }
}
