<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body>
    <h2>Password Reset Request</h2>
    <p>Hello <?= htmlspecialchars($name ?? 'Member', ENT_QUOTES, 'UTF-8') ?>,</p>
    <p>A request was received to reset your password for Pine Valley Golf Club. Click the link below to set a new password:</p>
    <p><a href="<?= htmlspecialchars($resetUrl ?? '#', ENT_QUOTES, 'UTF-8') ?>" style="padding:10px 18px;background:#15803d;color:#ffffff;text-decoration:none;border-radius:6px;display:inline-block;">Reset Password</a></p>
    <p>This password reset link is valid for 24 hours.</p>
    <p>If you did not request this, please ignore this email.</p>
</body>
</html>
