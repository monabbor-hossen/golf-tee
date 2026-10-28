<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

/**
 * Centralized email delivery service supporting invitations, password resets, and notifications.
 * Finding 5.18: Email subjects and templates loaded via config/emails.php and views/emails/.
 */
class EmailService
{
    private SmtpClient $smtpClient;
    private array $emailConfig;

    public function __construct(?SmtpClient $smtpClient = null)
    {
        $this->smtpClient = $smtpClient ?? new SmtpClient();
        $configPath = dirname(__DIR__, 2) . '/config/emails.php';
        $this->emailConfig = file_exists($configPath) ? (require $configPath) : [];
    }

    /**
     * Send official member account setup invitation email.
     */
    public function sendInvitation(string $name, string $email, string $token): array
    {
        $appUrl = rtrim((string)Config::get('APP_URL', 'http://localhost/golf-tee/admin-php'), '/');
        $setupUrl = $appUrl . '/set-password.php?token=' . urlencode($token);
        
        $subject = $this->emailConfig['invitation']['subject'] ?? 'Welcome to Pine Valley Golf Club — Set Up Your Account';
        $templatePath = $this->emailConfig['invitation']['template'] ?? (dirname(__DIR__, 2) . '/views/emails/invitation.php');

        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            $html = ob_get_clean();
        } else {
            $html = "<p>Hello " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ",</p><p>Please set up your account: <a href='" . htmlspecialchars($setupUrl, ENT_QUOTES, 'UTF-8') . "'>Set Password</a></p>";
        }

        return $this->sendNotification($email, $subject, $html, $name);
    }

    /**
     * Send secure password reset link to member or staff account.
     */
    public function sendPasswordReset(string $name, string $email, string $token): array
    {
        $appUrl = rtrim((string)Config::get('APP_URL', 'http://localhost/golf-tee/admin-php'), '/');
        $resetUrl = $appUrl . '/set-password.php?token=' . urlencode($token);
        
        $subject = $this->emailConfig['password_reset']['subject'] ?? 'Reset Your Pine Valley Golf Club Password';
        $templatePath = $this->emailConfig['password_reset']['template'] ?? (dirname(__DIR__, 2) . '/views/emails/password_reset.php');

        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            $html = ob_get_clean();
        } else {
            $html = "<p>Hello " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ",</p><p>Reset password: <a href='" . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . "'>Reset Password</a></p>";
        }

        return $this->sendNotification($email, $subject, $html, $name);
    }

    /**
     * Dispatch general email notification with SMTP fallback to native mail.
     */
    public function sendNotification(string $email, string $subject, string $message, ?string $name = null): array
    {
        $delivered = false;
        $deliveryMethod = 'log_only';
        $error = null;

        if ($this->smtpClient->isConfigured()) {
            $res = $this->smtpClient->send($email, $subject, $message);
            $delivered = $res['success'];
            $deliveryMethod = 'smtp';
            $error = $res['error'] ?? null;
        } else {
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . (string)Config::get('MAIL_FROM_NAME', 'Pine Valley Golf Club') . ' <' . (string)Config::get('MAIL_FROM_ADDRESS', 'noreply@pinevalley.com') . '>',
                'X-Mailer: PHP/' . phpversion()
            ];
            try {
                $delivered = @mail($email, $subject, $message, implode("\r\n", $headers));
                $deliveryMethod = $delivered ? 'php_mail' : 'local_log';
            } catch (\Throwable $e) {
                $delivered = false;
                $error = $e->getMessage();
            }
        }

        return [
            'sent' => $delivered,
            'method' => $deliveryMethod,
            'error' => $error,
            'to' => $email,
            'subject' => $subject,
        ];
    }
}
