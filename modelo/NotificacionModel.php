<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class NotificacionModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS notificaciones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                tipo VARCHAR(50) NOT NULL,
                titulo VARCHAR(200) NOT NULL,
                mensaje TEXT NOT NULL,
                leida TINYINT(1) NOT NULL DEFAULT 0,
                url VARCHAR(255) NULL DEFAULT NULL,
                referencia_id INT NULL DEFAULT NULL,
                creado_en DATETIME NOT NULL,
                INDEX idx_notif_usuario (usuario_id, leida),
                INDEX idx_notif_fecha (usuario_id, creado_en)
            )'
        );
        try {
            $this->db->exec('ALTER TABLE notificaciones ADD COLUMN referencia_id INT NULL DEFAULT NULL');
        } catch (Throwable $e) {
            // column already exists
        }
    }

    public function create(
        int $usuarioId,
        string $tipo,
        string $titulo,
        string $mensaje,
        ?string $url = null,
        ?int $referenciaId = null
    ): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, leida, url, creado_en)
             VALUES (:usuario_id, :tipo, :titulo, :mensaje, 0, :url, NOW())'
        );
        $ok = $stmt->execute([
            'usuario_id' => $usuarioId,
            'tipo'       => $tipo,
            'titulo'     => $titulo,
            'mensaje'    => $mensaje,
            'url'        => $url,
        ]);

        if ($ok && $referenciaId !== null) {
            try {
                $id  = (int) $this->db->lastInsertId();
                $upd = $this->db->prepare(
                    'UPDATE notificaciones SET referencia_id = :rid WHERE id = :id'
                );
                $upd->execute(['rid' => $referenciaId, 'id' => $id]);
            } catch (Throwable $e) {
                // referencia_id column may not exist yet — non-critical
            }
        }

        return $ok;
    }

    public function hasNotifForCita(int $usuarioId, string $tipo, int $citaId): bool
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT id FROM notificaciones
                 WHERE usuario_id = :usuario_id AND tipo = :tipo AND referencia_id = :referencia_id
                 LIMIT 1'
            );
            $stmt->execute([
                'usuario_id'    => $usuarioId,
                'tipo'          => $tipo,
                'referencia_id' => $citaId,
            ]);
            return (bool) $stmt->fetch();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getByUsuarioId(int $usuarioId, int $limit = 30): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, tipo, titulo, mensaje, leida, url, creado_en
             FROM notificaciones
             WHERE usuario_id = :usuario_id
             ORDER BY creado_en DESC
             LIMIT ' . max(1, $limit)
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['tiempo'] = $this->timeAgo((string) $row['creado_en']);
        }

        return $rows;
    }

    public function getUnreadCount(int $usuarioId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notificaciones
             WHERE usuario_id = :usuario_id AND leida = 0'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    public function markAllAsRead(int $usuarioId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notificaciones SET leida = 1
             WHERE usuario_id = :usuario_id AND leida = 0'
        );
        return $stmt->execute(['usuario_id' => $usuarioId]);
    }

    public function markAsRead(int $id, int $usuarioId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notificaciones SET leida = 1
             WHERE id = :id AND usuario_id = :usuario_id'
        );
        return $stmt->execute(['id' => $id, 'usuario_id' => $usuarioId]);
    }

    private function timeAgo(string $datetime): string
    {
        $diff = time() - strtotime($datetime);
        if ($diff < 60)      return 'Ahora mismo';
        if ($diff < 3600)    return 'Hace ' . floor($diff / 60) . ' min';
        if ($diff < 86400)   return 'Hace ' . floor($diff / 3600) . ' h';
        if ($diff < 604800)  return 'Hace ' . floor($diff / 86400) . ' dias';
        return date('d/m/Y', strtotime($datetime));
    }
}
