<?php
declare(strict_types=1);

use App\Core\Config;

$appUrl = Config::get('APP_URL');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign in — <?= htmlspecialchars($courseName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= htmlspecialchars($appUrl) ?>/assets/css/main.css"/>
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh;
           background: linear-gradient(135deg,#0f2418 0%,#1e5631 100%); }
    .login-card { background:#fff; border-radius:24px; padding:40px; width:100%;
                  max-width:420px; box-shadow:0 24px 64px rgba(0,0,0,.25); }
    .login-logo { display:flex; align-items:center; gap:10px; margin-bottom:28px; }
    .login-logo-icon { width:44px;height:44px; background:var(--forest); border-radius:14px;
                       display:flex;align-items:center;justify-content:center;font-size:22px; }
    .login-logo strong { font-size:1.1rem; font-weight:800; display:block; }
    .login-logo small  { font-size:.75rem; color:var(--text-muted); }
    h1 { font-size:1.4rem; font-weight:900; margin-bottom:4px; }
    .login-sub { font-size:.875rem; color:var(--text-muted); margin-bottom:24px; }
    .error-msg { background:rgba(220,38,38,.08); color:var(--danger); border-radius:10px;
                 padding:10px 14px; font-size:.875rem; font-weight:600; margin-bottom:16px; }
    .form-group { margin-bottom:14px; }
    .demo-hint  { margin-top:16px; padding:12px; background:var(--offwhite); border-radius:10px;
                  font-size:.8rem; color:var(--text-muted); text-align:center; }
    .demo-hint strong { color:var(--forest); }
    @media (max-width: 480px) {
      .login-card { padding: 26px 20px; border-radius: 20px; margin: 16px; width: calc(100% - 32px); }
      h1 { font-size: 1.25rem; }
    }
  </style>
</head>
<body>
<div class="login-card">
  <div class="login-logo">
    <div class="login-logo-icon">⛳</div>
    <div>
      <strong><?= htmlspecialchars($courseName) ?></strong>
      <small>Course Administration</small>
    </div>
  </div>

  <h1>Welcome back</h1>
  <p class="login-sub">Sign in with your staff account to continue</p>

  <?php if (!empty($error)): ?>
    <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" action="login.php">
    <?= csrfField() ?>
    <div class="form-group">
      <label class="field-label" for="email">Staff Email</label>
      <input type="email" id="email" name="email" class="input" placeholder="admin@pinevalley.com"
             value="<?= htmlspecialchars($_POST['email'] ?? 'admin@pinevalley.com') ?>" required autofocus />
    </div>

    <div class="form-group" style="margin-bottom:20px">
      <label class="field-label" for="password">Password</label>
      <input type="password" id="password" name="password" class="input" value="password" required />
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">
      Sign In →
    </button>
  </form>

  <div class="demo-hint">
    Demo account: <strong>admin@pinevalley.com</strong> / <strong>password</strong>
  </div>
</div>
</body>
</html>
