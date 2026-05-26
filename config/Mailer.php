<?php

declare(strict_types=1);

final class Mailer
{
    private string $host;
    private int    $port;
    private string $username;
    private string $password;
    private string $from;
    private string $fromName;

    public function __construct()
    {
        $config         = require __DIR__ . '/config.php';
        $m              = $config['mail'] ?? [];
        $this->host     = (string) ($m['host']      ?? '');
        $this->port     = (int)    ($m['port']      ?? 587);
        $this->username = (string) ($m['username']  ?? '');
        $this->password = (string) ($m['password']  ?? '');
        $this->from     = (string) ($m['from']      ?? '');
        $this->fromName = (string) ($m['from_name'] ?? 'UniLink');
    }

    public function send(string $to, string $subject, string $htmlBody): void
    {
        if ($this->host === '' || $this->username === '' || $this->password === '') {
            throw new RuntimeException(
                'La configuracion SMTP esta incompleta. Rellena mail.username, mail.password y mail.from en config/config.php.'
            );
        }

        $sock = @fsockopen($this->host, $this->port, $errno, $errstr, 15);
        if ($sock === false) {
            throw new RuntimeException(
                "No se pudo conectar al servidor SMTP ({$this->host}:{$this->port}): {$errstr} [{$errno}]"
            );
        }

        try {
            stream_set_timeout($sock, 15);

            $this->read($sock);                                            // 220 greeting
            $this->cmd($sock, 'EHLO ' . (gethostname() ?: 'localhost'));
            $this->cmd($sock, 'STARTTLS');

            if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('No se pudo establecer la conexion TLS con el servidor de correo.');
            }

            $this->cmd($sock, 'EHLO ' . (gethostname() ?: 'localhost'));   // EHLO de nuevo tras TLS
            $this->cmd($sock, 'AUTH LOGIN');
            $this->cmd($sock, base64_encode($this->username));
            $this->cmd($sock, base64_encode($this->password));
            $this->cmd($sock, "MAIL FROM:<{$this->from}>");
            $this->cmd($sock, "RCPT TO:<{$to}>");
            $this->cmd($sock, 'DATA');                                     // 354

            $raw = $this->buildRaw($to, $subject, $htmlBody);
            fputs($sock, $raw . "\r\n.\r\n");
            $this->read($sock);                                            // 250 queued

            fputs($sock, "QUIT\r\n");
            $this->read($sock);
        } finally {
            fclose($sock);
        }
    }

    private function buildRaw(string $to, string $subject, string $htmlBody): string
    {
        $msgId      = bin2hex(random_bytes(12)) . '@unilink.local';
        $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encFrom    = '=?UTF-8?B?' . base64_encode($this->fromName) . '?=';

        $headers = implode("\r\n", [
            'Date: '       . date('r'),
            "Message-ID: <{$msgId}>",
            "From: {$encFrom} <{$this->from}>",
            "To: <{$to}>",
            "Subject: {$encSubject}",
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ]);

        // Dot-stuffing per RFC 5321: lines starting with '.' need an extra '.'
        $content = chunk_split(base64_encode($htmlBody));
        $content = preg_replace('/^\./m', '..', $content) ?? $content;

        return $headers . "\r\n\r\n" . $content;
    }

    private function cmd($sock, string $command): string
    {
        fputs($sock, $command . "\r\n");
        $response = $this->read($sock);
        $code     = (int) substr($response, 0, 3);
        if ($code >= 400) {
            throw new RuntimeException("SMTP error {$code}: " . trim($response));
        }
        return $response;
    }

    private function read($sock): string
    {
        $data = '';
        while ($line = fgets($sock, 512)) {
            $data .= $line;
            // Multi-line responses use '-' at pos 3; last line uses ' '
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }
        return $data;
    }
}
