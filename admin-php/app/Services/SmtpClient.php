<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Lightweight native SMTP client for delivering emails directly
 * to external mail servers (e.g. Gmail SMTP, SendGrid, Brevo, Mailgun)
 * without requiring third-party libraries.
 */
class SmtpClient
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private int $timeout;

    public function __construct(
        string $host = '',
        int $port = 0,
        string $username = '',
        string $password = '',
        string $encryption = '',
        int $timeout = 10
    ) {
        $this->host = $host ?: (string)($_ENV['MAIL_HOST'] ?? '');
        $this->port = $port ?: (int)($_ENV['MAIL_PORT'] ?? 587);
        $this->username = $username ?: (string)($_ENV['MAIL_USERNAME'] ?? '');
        $this->password = $password ?: (string)($_ENV['MAIL_PASSWORD'] ?? '');
        $this->encryption = strtolower($encryption ?: (string)($_ENV['MAIL_ENCRYPTION'] ?? 'tls'));
        $this->timeout = $timeout;
    }

    /**
     * Check if SMTP credentials have been entered in .env
     */
    public function isConfigured(): bool
    {
        return !empty($this->host) && !empty($this->username) && !empty($this->password);
    }

    /**
     * Send email via SMTP socket connection.
     */
    public function send(string $to, string $subject, string $htmlBody, string $from = '', string $fromName = ''): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error'   => 'SMTP is not configured in .env. Falling back to local logging.'
            ];
        }

        $fromAddress = $from ?: (string)($_ENV['MAIL_FROM_ADDRESS'] ?? $this->username);
        $fromName    = $fromName ?: (string)($_ENV['MAIL_FROM_NAME'] ?? 'Pine Valley Golf Club');

        $remoteSocket = ($this->encryption === 'ssl' ? 'ssl://' : '') . $this->host . ':' . $this->port;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ]);

        $socket = @stream_socket_client($remoteSocket, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            return [
                'success' => false,
                'error'   => "Cannot connect to SMTP server {$this->host}:{$this->port}: $errstr ($errno)"
            ];
        }

        stream_set_timeout($socket, $this->timeout);

        $readResponse = function () use ($socket): string {
            $response = '';
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $response;
        };

        $sendCommand = function (string $cmd) use ($socket, $readResponse): string {
            fwrite($socket, $cmd . "\r\n");
            return $readResponse();
        };

        $banner = $readResponse();
        if (substr($banner, 0, 3) !== '220') {
            fclose($socket);
            return ['success' => false, 'error' => "SMTP connection rejected: $banner"];
        }

        $sendCommand('EHLO ' . gethostname());

        // Handle STARTTLS on port 587
        if ($this->encryption === 'tls') {
            $tlsRes = $sendCommand('STARTTLS');
            if (substr($tlsRes, 0, 3) !== '220') {
                fclose($socket);
                return ['success' => false, 'error' => "STARTTLS failed: $tlsRes"];
            }

            $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$crypto) {
                fclose($socket);
                return ['success' => false, 'error' => 'Failed to establish TLS encryption with SMTP server.'];
            }
            $sendCommand('EHLO ' . gethostname());
        }

        // AUTH LOGIN
        $authRes = $sendCommand('AUTH LOGIN');
        if (substr($authRes, 0, 3) !== '334') {
            fclose($socket);
            return ['success' => false, 'error' => "AUTH LOGIN rejected: $authRes"];
        }

        $userRes = $sendCommand(base64_encode($this->username));
        if (substr($userRes, 0, 3) !== '334') {
            fclose($socket);
            return ['success' => false, 'error' => "SMTP username rejected: $userRes"];
        }

        $passRes = $sendCommand(base64_encode($this->password));
        if (substr($passRes, 0, 3) !== '235') {
            fclose($socket);
            return ['success' => false, 'error' => "SMTP authentication failed (verify Gmail App Password): $passRes"];
        }

        // MAIL FROM
        $mailFromRes = $sendCommand('MAIL FROM:<' . $fromAddress . '>');
        if (substr($mailFromRes, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'error' => "MAIL FROM rejected: $mailFromRes"];
        }

        // RCPT TO
        $rcptRes = $sendCommand('RCPT TO:<' . $to . '>');
        if (substr($rcptRes, 0, 3) !== '250' && substr($rcptRes, 0, 3) !== '251') {
            fclose($socket);
            return ['success' => false, 'error' => "Recipient <$to> rejected: $rcptRes"];
        }

        // DATA
        $dataPrompt = $sendCommand('DATA');
        if (substr($dataPrompt, 0, 3) !== '354') {
            fclose($socket);
            return ['success' => false, 'error' => "DATA command rejected: $dataPrompt"];
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromAddress . '>',
            'To: <' . $to . '>',
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'Date: ' . date('r'),
            'X-Mailer: PineValleyGolfClub-Mailer'
        ];

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.";
        $dataResult = $sendCommand($message);

        $sendCommand('QUIT');
        fclose($socket);

        if (substr($dataResult, 0, 3) === '250') {
            return [
                'success' => true,
                'message' => 'Email delivered successfully to ' . $to
            ];
        }

        return [
            'success' => false,
            'error'   => "Data transmission failed: $dataResult"
        ];
    }
}
