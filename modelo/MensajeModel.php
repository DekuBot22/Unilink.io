<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class MensajeModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS mensajes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                emisor_id INT NOT NULL,
                receptor_id INT NOT NULL,
                texto TEXT NOT NULL,
                leido TINYINT(1) NOT NULL DEFAULT 0,
                creado_en DATETIME NOT NULL,
                INDEX idx_conv (emisor_id, receptor_id),
                INDEX idx_receptor (receptor_id, leido)
            )'
        );
    }

    public function send(int $emisorId, int $receptorId, string $texto): ?array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO mensajes (emisor_id, receptor_id, texto, leido, creado_en)
             VALUES (:emisor_id, :receptor_id, :texto, 0, NOW())'
        );
        $ok = $stmt->execute([
            'emisor_id'   => $emisorId,
            'receptor_id' => $receptorId,
            'texto'       => $texto,
        ]);

        if (!$ok) {
            return null;
        }

        $id  = (int) $this->db->lastInsertId();
        $get = $this->db->prepare('SELECT * FROM mensajes WHERE id = :id');
        $get->execute(['id' => $id]);
        $row = $get->fetch();
        return $row ?: null;
    }

    public function getConversacion(int $userA, int $userB, int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, emisor_id, receptor_id, texto, leido, creado_en
             FROM mensajes
             WHERE (emisor_id = ? AND receptor_id = ?)
                OR (emisor_id = ? AND receptor_id = ?)
             ORDER BY creado_en ASC
             LIMIT ' . max(1, $limit)
        );
        $stmt->execute([$userA, $userB, $userB, $userA]);
        return $stmt->fetchAll();
    }

    public function getConversaciones(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT m.id, m.emisor_id, m.receptor_id, m.texto, m.leido, m.creado_en,
                    u.nombre AS otro_nombre, u.foto_perfil AS otro_foto,
                    CASE WHEN m.emisor_id = ? THEN m.receptor_id ELSE m.emisor_id END AS otro_id
             FROM mensajes m
             JOIN usuarios u ON u.id = CASE WHEN m.emisor_id = ? THEN m.receptor_id ELSE m.emisor_id END
             WHERE m.id IN (
                 SELECT MAX(m2.id) FROM mensajes m2
                 WHERE m2.emisor_id = ? OR m2.receptor_id = ?
                 GROUP BY LEAST(m2.emisor_id, m2.receptor_id), GREATEST(m2.emisor_id, m2.receptor_id)
             )
             ORDER BY m.creado_en DESC'
        );
        $stmt->execute([$userId, $userId, $userId, $userId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['otro_id']   = (int) $row['otro_id'];
            $row['no_leidos'] = $this->getUnreadCountFrom($userId, (int) $row['otro_id']);
            $row['tiempo']    = $this->timeAgo((string) $row['creado_en']);
        }

        return $rows;
    }

    public function marcarLeidos(int $receptorId, int $emisorId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE mensajes SET leido = 1
             WHERE receptor_id = :receptor_id AND emisor_id = :emisor_id AND leido = 0'
        );
        return $stmt->execute(['receptor_id' => $receptorId, 'emisor_id' => $emisorId]);
    }

    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM mensajes WHERE receptor_id = :uid AND leido = 0'
        );
        $stmt->execute(['uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function getUnreadCountFrom(int $receptorId, int $emisorId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM mensajes
             WHERE receptor_id = :rid AND emisor_id = :eid AND leido = 0'
        );
        $stmt->execute(['rid' => $receptorId, 'eid' => $emisorId]);
        return (int) $stmt->fetchColumn();
    }

    public function getUsuarioIdFromTutorId(int $tutorId): ?int
    {
        $stmt = $this->db->prepare(
            'SELECT usuario_id FROM tutores WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $tutorId]);
        $row = $stmt->fetch();
        if (!$row || $row['usuario_id'] === null) {
            return null;
        }
        return (int) $row['usuario_id'];
    }

    private function timeAgo(string $datetime): string
    {
        $diff = time() - strtotime($datetime);
        if ($diff < 60)     return 'Ahora';
        if ($diff < 3600)   return 'Hace ' . floor($diff / 60) . ' min';
        if ($diff < 86400)  return 'Hace ' . floor($diff / 3600) . ' h';
        if ($diff < 604800) return 'Hace ' . floor($diff / 86400) . ' dias';
        return date('d/m/Y', strtotime($datetime));
    }
}
