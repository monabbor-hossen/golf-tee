<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class MailService
{
    /**
     * Send account setup invitation email to new member.
     */
    public function sendMemberSetupInvitation(string $name, string $email, string $token): array
    {
        $appUrl = rtrim((string)($_ENV['APP_URL'] ?? 'http://localhost/golf-tee/admin-php'), '/');
        $setupUrl = $appUrl . '/set-password.php?token=' . urlencode($token);

        $subject = 'Activate Your Pine Valley Golf Club Account';
        
        $html = '<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . htmlspecialchars($subject) . '</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #f6f8f5; margin: 0; padding: 24px; color: #1f2937; }
    .card { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; }
    .header { background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%); padding: 32px 24px; text-align: center; color: #ffffff; }
    .header h1 { margin: 8px 0 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; }
    .header p { margin: 4px 0 0; font-size: 13px; opacity: 0.85; }
    .badge { display: inline-block; padding: 4px 12px; background: rgba(255,255,255,0.18); border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .content { padding: 32px 28px; line-height: 1.6; }
    .greeting { font-size: 17px; font-weight: 700; color: #111827; margin-bottom: 12px; }
    .btn { display: inline-block; background-color: #1b4332; color: #ffffff !important; text-decoration: none; font-weight: 700; font-size: 15px; padding: 14px 28px; border-radius: 12px; margin: 24px 0; text-align: center; }
    .btn:hover { background-color: #143225; }
    .link-box { background: #f9fafb; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb; word-break: break-all; font-size: 12px; color: #4b5563; }
    .notice { font-size: 12px; color: #6b7280; border-top: 1px solid #f3f4f6; padding-top: 18px; margin-top: 24px; }
    .footer { text-align: center; font-size: 12px; color: #9ca3af; margin-top: 24px; }
  </style>
</head>
<body>
  <div class="card">
    <div class="header">
      <div class="badge">Official Invitation</div>
      <h1>Pine Valley Golf Club</h1>
      <p>Member Access Portal</p>
    </div>
    <div class="content">
      <div class="greeting">Welcome, ' . htmlspecialchars($name) . '!</div>
      <p>An official membership account has been created for you by Pine Valley Club Administration.</p>
      <p>To finalize your account and access the mobile app, please set your personal security password using the link below:</p>
      
      <div style="text-align: center;">
        <a href="' . htmlspecialchars($setupUrl) . '" class="btn" target="_blank">Set Password & Activate Account</a>
      </div>

      <p style="font-size: 13px; color: #4b5563;">If the button does not open, copy and paste this secure activation URL into your browser:</p>
      <div class="link-box">' . htmlspecialchars($setupUrl) . '</div>

      <div class="notice">
        <strong>Important security notice:</strong>
        <ul style="margin: 6px 0 0; padding-left: 18px;">
          <li>Public self-registration is disabled; only admin-registered emails can access the app.</li>
          <li>You can also sign in with Google once your account is active (using this registered email: <strong>' . htmlspecialchars($email) . '</strong>).</li>
          <li>This activation link is valid for 7 days.</li>
        </ul>
      </div>
    </div>
  </div>
  <div class="footer">
    Pine Valley Golf Club · 1200 Fairway Drive · (555) 301-2200<br>
    Please do not reply directly to this automated notification.
  </div>
</body>
</html>';

        $smtp = new SmtpClient();
        $delivered = false;
        $deliveryMethod = 'log_only';
        $deliveryError = null;

        if ($smtp->isConfigured()) {
            $smtpResult = $smtp->send($email, $subject, $html);
            $delivered = $smtpResult['success'];
            $deliveryMethod = 'smtp';
            if (!$delivered) {
                $deliveryError = $smtpResult['error'] ?? 'SMTP delivery failed';
            }
        } else {
            // Fall back to native PHP mail() if SMTP not filled in .env
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: Pine Valley Golf Club <noreply@pinevalley.com>',
                'Reply-To: members@pinevalley.com',
                'X-Mailer: PHP/' . phpversion()
            ];
            try {
                $delivered = @mail($email, $subject, $html, implode("\r\n", $headers));
                $deliveryMethod = $delivered ? 'php_mail' : 'local_log';
            } catch (\Throwable $e) {
                $delivered = false;
                $deliveryError = $e->getMessage();
            }
        }

        // Always save to scratch mail log for immediate local verification
        $logDir = __DIR__ . '/../../scratch';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logEntry = [
            'timestamp'       => date('c'),
            'to'              => $email,
            'name'            => $name,
            'subject'         => $subject,
            'setup_url'       => $setupUrl,
            'token'           => $token,
            'delivered'       => $delivered,
            'delivery_method' => $deliveryMethod,
            'error'           => $deliveryError,
        ];
        @file_put_contents($logDir . '/mail_outbox.log', json_encode($logEntry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL . "---" . PHP_EOL, FILE_APPEND);

        return [
            'sent'            => $delivered,
            'method'          => $deliveryMethod,
            'error'           => $deliveryError,
            'setup_url'       => $setupUrl,
            'subject'         => $subject,
            'to'              => $email
        ];
    }
}
