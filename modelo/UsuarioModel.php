<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class UsuarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nombre, email, carrera, codigo, telefono, password, rol, estado, ban_motivo, foto_perfil,
                    email_verificado, email_token
             FROM usuarios
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nombre, email, carrera, codigo, telefono, rol, estado, foto_perfil
             FROM usuarios
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findByGoogleId(string $googleId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nombre, email, carrera, codigo, telefono, rol, estado, ban_motivo, foto_perfil, google_id,
                    email_verificado
             FROM usuarios
             WHERE google_id = :google_id
             LIMIT 1'
        );
        $stmt->execute(['google_id' => $googleId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function linkGoogle(int $id, string $googleId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET google_id = :google_id WHERE id = :id'
        );
        return $stmt->execute(['google_id' => $googleId, 'id' => $id]);
    }

    public function createFromGoogle(string $googleId, string $email, string $nombre, string $foto, string $rol): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nombre, email, carrera, codigo, telefono, password, rol, google_id, foto_perfil,
                                   email_verificado, email_token, creado_en)
             VALUES (:nombre, :email, :carrera, :codigo, :telefono, :password, :rol, :google_id, :foto_perfil, 1, NULL, NOW())'
        );
        return $stmt->execute([
            'nombre'      => $nombre,
            'email'       => $email,
            'carrera'     => '',
            'codigo'      => '0',
            'telefono'    => '0',
            'password'    => bin2hex(random_bytes(32)),
            'rol'         => $rol,
            'google_id'   => $googleId,
            'foto_perfil' => $foto !== '' ? $foto : null,
        ]);
    }

    public function updatePerfil(int $id, string $carrera, string $codigo, string $telefono): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET carrera = :carrera, codigo = :codigo, telefono = :telefono WHERE id = :id'
        );
        return $stmt->execute([
            'carrera'  => $carrera,
            'codigo'   => $codigo,
            'telefono' => $telefono,
            'id'       => $id,
        ]);
    }

    public function updateFotoPerfil(int $id, ?string $rutaFoto): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET foto_perfil = :foto_perfil WHERE id = :id'
        );

        return $stmt->execute(['foto_perfil' => $rutaFoto, 'id' => $id]);
    }

    public function create(
        string $nombre,
        string $email,
        string $carrera,
        string $codigo,
        string $telefono,
        string $password,
        string $rol = 'estudiante',
        string $verificacionToken = ''
    ): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nombre, email, carrera, codigo, telefono, password, rol,
                                   email_verificado, email_token, creado_en)
             VALUES (:nombre, :email, :carrera, :codigo, :telefono, :password, :rol, 0, :email_token, NOW())'
        );

        return $stmt->execute([
            'nombre'      => $nombre,
            'email'       => $email,
            'carrera'     => $carrera,
            'codigo'      => $codigo,
            'telefono'    => $telefono,
            'password'    => password_hash($password, PASSWORD_DEFAULT),
            'rol'         => $rol,
            'email_token' => $verificacionToken !== '' ? $verificacionToken : null,
        ]);
    }

    public function findByVerificationToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nombre, email
             FROM usuarios
             WHERE email_token = :token AND email_verificado = 0
             LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        return $stmt->fetch() ?: null;
    }

    public function setEmailVerified(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET email_verificado = 1, email_token = NULL WHERE id = :id'
        );
        return $stmt->execute(['id' => $id]);
    }

    public function updateVerificationToken(int $id, string $token): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET email_token = :token WHERE id = :id'
        );
        return $stmt->execute(['token' => $token, 'id' => $id]);
    }

    public function setPasswordToken(int $id, string $token, string $expira): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET password_token = :token, password_token_expira = :expira WHERE id = :id'
        );
        return $stmt->execute(['token' => $token, 'expira' => $expira, 'id' => $id]);
    }

    public function findByPasswordToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nombre, email
             FROM usuarios
             WHERE password_token = :token
               AND password_token_expira > NOW()
             LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        return $stmt->fetch() ?: null;
    }

    public function updatePassword(int $id, string $newPassword): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios
             SET password = :password, password_token = NULL, password_token_expira = NULL
             WHERE id = :id'
        );
        return $stmt->execute([
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id'       => $id,
        ]);
    }

    public function updateRoleByEmail(string $email, string $rol): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET rol = :rol WHERE email = :email'
        );

        return $stmt->execute([
            'rol' => $rol,
            'email' => $email,
        ]);
    }

    public function banByEmail(string $email, string $motivo): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios
             SET estado = :estado,
                 ban_motivo = :motivo,
                 ban_fecha = NOW()
             WHERE email = :email'
        );

        return $stmt->execute([
            'estado' => 'baneado',
            'motivo' => $motivo,
            'email' => $email,
        ]);
    }

    public function activateByEmail(string $email): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios
             SET estado = :estado,
                 ban_motivo = NULL,
                 ban_fecha = NULL
             WHERE email = :email'
        );

        return $stmt->execute([
            'estado' => 'activo',
            'email' => $email,
        ]);
    }
}
