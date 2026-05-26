<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../modelo/CitaModel.php';
require_once __DIR__ . '/../modelo/TutorModel.php';

final class VideollamadaController
{
    private ?array $cfg = null;

    private function cfg(): array
    {
        if ($this->cfg === null) {
            $this->cfg = require __DIR__ . '/../config/config.php';
        }
        return $this->cfg;
    }

    private function apiKey(): string
    {
        return (string) ($this->cfg()['daily']['api_key'] ?? '');
    }

    private function subdomain(): string
    {
        return (string) ($this->cfg()['daily']['subdomain'] ?? '');
    }

    private function dailyRequest(string $method, string $path, array $body = []): ?array
    {
        $apiKey = $this->apiKey();
        if ($apiKey === '') {
            return null;
        }

        $ch = curl_init('https://api.daily.co/v1' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        if ($method !== 'GET' && !empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function getOrCreateRoom(string $roomName, int $expTs): ?string
    {
        // Check if room already exists
        $existing = $this->dailyRequest('GET', '/rooms/' . rawurlencode($roomName));
        if ($existing && isset($existing['url']) && !isset($existing['error'])) {
            return (string) $existing['url'];
        }

        // Create the room
        $room = $this->dailyRequest('POST', '/rooms', [
            'name'       => $roomName,
            'privacy'    => 'private',
            'properties' => [
                'exp'               => $expTs,
                'enable_prejoin_ui' => false,
                'start_video_off'   => false,
                'start_audio_off'   => false,
                'max_participants'  => 4,
                'lang'              => 'es',
            ],
        ]);

        if (!$room || !isset($room['url'])) {
            return null;
        }

        return (string) $room['url'];
    }

    private function createMeetingToken(string $roomName, string $displayName, bool $isOwner, int $expTs): ?string
    {
        $result = $this->dailyRequest('POST', '/meeting-tokens', [
            'properties' => [
                'room_name' => $roomName,
                'user_name' => $displayName,
                'exp'       => $expTs,
                'is_owner'  => $isOwner,
            ],
        ]);

        return isset($result['token']) ? (string) $result['token'] : null;
    }

    public function sala(): void
    {
        if (!isLoggedIn()) {
            setFlash('error', 'Inicia sesion para unirte a la videollamada.');
            redirect('index.php?page=inicio');
        }

        $citaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($citaId <= 0) {
            setFlash('error', 'Sala no valida.');
            redirect('index.php?page=perfil');
        }

        $user        = authUser();
        $userId      = (int) $user['id'];
        $citaModel   = new CitaModel();
        $cita        = null;
        $isTutorUser = false;

        // Check as student
        try {
            $cita = $citaModel->getByIdForUsuario($citaId, $userId);
        } catch (Throwable $e) {}

        // Check as tutor
        if (!$cita) {
            try {
                $tutor = (new TutorModel())->findByUsuarioId($userId);
                if ($tutor) {
                    $cita = $citaModel->getByIdForTutor($citaId, (int) $tutor['id']);
                    if ($cita) {
                        $isTutorUser = true;
                    }
                }
            } catch (Throwable $e) {}
        }

        if (!$cita) {
            setFlash('error', 'No tienes acceso a esta sala o no existe.');
            redirect('index.php?page=perfil');
        }

        $modalidad = strtolower((string) ($cita['modalidad'] ?? ''));
        if (!str_contains($modalidad, 'videollamada unilink')) {
            setFlash('error', 'Esta sesion no es de videollamada UniLink.');
            redirect('index.php?page=perfil');
        }

        // Verify API is configured
        if ($this->apiKey() === '' || $this->subdomain() === '') {
            setFlash('error', 'El servicio de videollamada no esta configurado. Contacta al administrador.');
            $dest = $isTutorUser ? 'index.php?page=tutor' : 'index.php?page=perfil';
            redirect($dest);
        }

        $estado    = strtolower((string) ($cita['estado'] ?? ''));
        $fecha     = (string) ($cita['fecha'] ?? '');
        $hora      = (string) ($cita['hora'] ?? '');
        $horaShort = strlen($hora) === 8 ? substr($hora, 0, 5) : $hora;
        $fechaHora = null;

        if ($fecha !== '' && $horaShort !== '') {
            $fechaHora = DateTime::createFromFormat('Y-m-d H:i', $fecha . ' ' . $horaShort) ?: null;
        }

        $now       = new DateTime('now');
        $canAccess = false;

        if ($estado === 'en_curso') {
            $canAccess = true;
        } elseif ($estado === 'confirmada' && $fechaHora instanceof DateTime) {
            try {
                $antes     = (clone $fechaHora)->sub(new DateInterval('PT10M'));
                $canAccess = $now >= $antes;
            } catch (Throwable $e) {
                $canAccess = $now >= $fechaHora;
            }
        }

        if (!$canAccess) {
            setFlash('error', 'La sala aun no esta disponible. Podras acceder 10 minutos antes del inicio.');
            $dest = $isTutorUser ? 'index.php?page=tutor' : 'index.php?page=perfil';
            redirect($dest);
        }

        // Expiry: session time + 3 hours, minimum 2 hours from now
        $expTs = time() + 7200;
        if ($fechaHora instanceof DateTime) {
            $fin   = (clone $fechaHora)->add(new DateInterval('PT3H'));
            $expTs = max($expTs, $fin->getTimestamp());
        }

        $roomName    = 'unilink-sesion-' . $citaId;
        $displayName = (string) ($user['nombre'] ?? 'Usuario');
        $destino     = $isTutorUser ? 'index.php?page=tutor' : 'index.php?page=perfil';

        $roomUrl = $this->getOrCreateRoom($roomName, $expTs);
        $token   = $roomUrl ? $this->createMeetingToken($roomName, $displayName, $isTutorUser, $expTs) : null;

        if (!$roomUrl || !$token) {
            setFlash('error', 'No se pudo iniciar la videollamada. Intenta de nuevo.');
            redirect($destino);
        }

        $iframeUrl  = rtrim($roomUrl, '/') . '?t=' . rawurlencode($token);
        $activePage = 'videollamada';
        $pageTitle  = 'Videollamada · ' . ($cita['materia'] ?? 'Sesion');

        require __DIR__ . '/../vista/paginas/videollamada.php';
    }
}
