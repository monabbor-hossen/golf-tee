<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body>
    <h2>Welcome to Pine Valley Golf Club</h2>
    <p>Hello <?= htmlspecialchars($name ?? 'Member', ENT_QUOTES, 'UTF-8') ?>,</p>
    <p>An official member account has been registered for you at Pine Valley Golf Club. Please click the link below to set your account password:</p>
    <p><a href="<?= htmlspecialchars($setupUrl ?? '#', ENT_QUOTES, 'UTF-8') ?>" style="padding:10px 18px;background:#15803d;color:#ffffff;text-decoration:none;border-radius:6px;display:inline-block;">Set Up Account Password</a></p>
    <p>This invitation link expires in 24 hours.</p>
</body>
</html>
