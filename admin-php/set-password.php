<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Core\Session;
use App\Repositories\MemberRepository;
use App\Services\MemberService;

Session::start();

$memberRepo = new MemberRepository();
$memberService = new MemberService($memberRepo);

$error = '';
$success = false;
$member = null;

// IP-based Rate Limiting (max 5 password setup attempts per hour)
$ipKey = '_rate_limit_pwd_' . md5($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
$attempts = (int)Session::get($ipKey, 0);
$lastAttemptTime = (int)Session::get($ipKey . '_time', 0);
if ($attempts >= 5 && (time() - $lastAttemptTime) < 3600) {
    http_response_code(429);
    $error = 'Rate limit exceeded: Maximum 5 password setup attempts per hour reached from this IP address. Please wait before trying again.';
}

use App\Core\Request;

$request = Request::createFromGlobals();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Session::validateRequestCsrf();
}

$passwordResetService = new \App\Services\PasswordResetService();

// Extract token using centralized Request helper
$token = $request->getToken();
$pdo = \App\Core\Database::pdo();

if (!empty($token)) {
    // Finding 3.6: Verify token exists in database and is unexpired before allowing password set
    $stmt = $pdo->prepare('SELECT id, password_reset_expires FROM members WHERE password_reset_token = ?');
    $stmt->execute([$token]);
    $member = $stmt->fetch();
    if (!$member) {
        $stmt = $pdo->prepare('SELECT id, setup_token_expires as password_reset_expires FROM members WHERE setup_token = ?');
        $stmt->execute([$token]);
        $member = $stmt->fetch();
    }
    if (!$member || strtotime((string)($member['password_reset_expires'] ?? '')) < time()) {
        die('Invalid or expired token');
    }

    // Validate format (alphanumeric hex string)
    if (!preg_match('/^[a-f0-9]{32,64}$/i', $token)) {
        $error = 'Invalid activation token format. Please check the link from your email.';
    } else {
        // Explicit SELECT query to verify token exists, is not expired, and belongs to valid member
        $member = $passwordResetService->verifyToken($token);
        if (!$member) {
            $error = 'This password setup link is invalid or has expired. Please contact Pine Valley Club Administration at (555) 301-2200 to request a new link.';
        } else {
            // Validate token ownership against authenticated user ID before processing password update
            $authenticatedUserId = Session::getUserId();
            if ($authenticatedUserId !== null && (int)$member['id'] !== (int)$authenticatedUserId && !Session::isStaff()) {
                $error = 'Access denied: Token does not belong to the currently authenticated user.';
                $member = null;
            }
        }
    }
} else {
    $error = 'No activation token provided. Please click the link sent to your email.';
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error) && $member) {
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'The passwords entered do not match. Please verify and try again.';
    } else {
        $res = $passwordResetService->resetPassword($token, $password);
        if ($res['success']) {
            $success = true;
            Session::remove($ipKey);
            Session::remove($ipKey . '_time');
        } else {
            $error = $res['message'];
            Session::set($ipKey, $attempts + 1);
            Session::set($ipKey . '_time', time());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set Password · Pine Valley Golf Club</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    :root {
      --forest: #1E5631;
      --forest-dark: #143e22;
      --forest-light: #2d6a4f;
      --sand: #c9a96e;
      --sand-gold: #d4af37;
      --bg: #f4f6f2;
      --card-bg: #ffffff;
      --text-main: #111827;
      --text-muted: #6b7280;
      --danger: #dc2626;
      --danger-bg: #fef2f2;
      --success: #16a34a;
      --success-bg: #f0fdf4;
      --border: #e5e7eb;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
    }
    .wrapper {
      width: 100%;
      max-width: 460px;
    }
    .brand-header {
      text-align: center;
      margin-bottom: 24px;
    }
    .crest {
      width: 60px;
      height: 60px;
      margin: 0 auto 14px;
      background: linear-gradient(135deg, var(--forest-dark), var(--forest));
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 24px rgba(30, 86, 49, 0.25);
      border: 2px solid var(--sand-gold);
    }
    .crest svg { width: 32px; height: 32px; color: #fff; }
    .brand-title {
      font-size: 22px;
      font-weight: 800;
      letter-spacing: -0.5px;
      color: var(--forest-dark);
    }
    .brand-sub {
      font-size: 13px;
      color: var(--text-muted);
      margin-top: 4px;
    }
    .card {
      background: var(--card-bg);
      border-radius: 24px;
      padding: 32px 28px;
      box-shadow: 0 12px 36px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.04);
      border: 1px solid var(--border);
    }
    .member-banner {
      background: #f8faf6;
      border: 1px solid #e2ebd9;
      border-radius: 14px;
      padding: 14px 16px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .member-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: var(--sand);
      color: var(--forest-dark);
      font-weight: 800;
      font-size: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .member-info h4 { font-size: 14px; font-weight: 700; color: var(--forest-dark); }
    .member-info p { font-size: 12px; color: var(--text-muted); }
    .field-group { margin-bottom: 18px; }
    label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      color: #374151;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
    }
    .input-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }
    .input-wrap input {
      width: 100%;
      height: 48px;
      padding: 0 44px 0 16px;
      font-size: 15px;
      border: 1.5px solid var(--border);
      border-radius: 14px;
      background: #fdfdfd;
      outline: none;
      transition: all 0.2s;
      font-family: inherit;
    }
    .input-wrap input:focus {
      border-color: var(--forest);
      background: #fff;
      box-shadow: 0 0 0 3px rgba(30, 86, 49, 0.12);
    }
    .toggle-pwd {
      position: absolute;
      right: 12px;
      background: none;
      border: none;
      cursor: pointer;
      color: #9ca3af;
      padding: 4px;
    }
    .toggle-pwd:hover { color: var(--text-main); }
    .strength-bar {
      height: 4px;
      background: #e5e7eb;
      border-radius: 2px;
      margin-top: 8px;
      overflow: hidden;
    }
    .strength-fill {
      height: 100%;
      width: 0%;
      transition: width 0.3s, background-color 0.3s;
    }
    .btn-submit {
      width: 100%;
      height: 50px;
      background: var(--forest);
      color: #fff;
      border: none;
      border-radius: 14px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 10px;
      transition: all 0.2s;
      box-shadow: 0 4px 14px rgba(30, 86, 49, 0.25);
    }
    .btn-submit:hover {
      background: var(--forest-dark);
      transform: translateY(-1px);
    }
    .alert-error {
      background: var(--danger-bg);
      border: 1px solid #fecaca;
      color: var(--danger);
      padding: 14px 16px;
      border-radius: 14px;
      font-size: 13px;
      font-weight: 500;
      margin-bottom: 20px;
      display: flex;
      align-items: flex-start;
      gap: 10px;
    }
    .alert-success {
      background: var(--success-bg);
      border: 1px solid #bbf7d0;
      color: var(--success);
      padding: 24px;
      border-radius: 20px;
      text-align: center;
    }
    .success-icon {
      width: 64px;
      height: 64px;
      background: #dcfce7;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      color: var(--success);
    }
    .instructions-card {
      background: #f9fafb;
      border-radius: 14px;
      padding: 16px;
      text-align: left;
      margin: 20px 0;
      font-size: 13px;
      color: #4b5563;
      line-height: 1.5;
    }
    .instructions-card ol {
      margin-left: 18px;
      margin-top: 8px;
    }
    .instructions-card li { margin-bottom: 6px; }
    .rules-box {
      font-size: 11px;
      color: var(--text-muted);
      line-height: 1.5;
      margin-top: 16px;
      border-top: 1px solid var(--border);
      padding-top: 14px;
    }
  </style>
</head>
<body>

<div class="wrapper">
  <div class="brand-header">
    <div class="crest">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M5 21V4M5 4h11l-2 4 2 4H5"/>
      </svg>
    </div>
    <h1 class="brand-title">Pine Valley Golf Club</h1>
    <p class="brand-sub">Member Security & Account Setup</p>
  </div>

  <div class="card">
    <?php if ($success): ?>
      <div class="alert-success">
        <div class="success-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="36" height="36" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
        <h2 style="font-size:20px;font-weight:800;color:#14532d;margin-bottom:8px">Account Activated!</h2>
        <p style="font-size:14px;color:#166534">Your password has been saved securely. Your membership account is now ready for app access.</p>

        <div class="instructions-card">
          <strong style="color:#111827">How to login now:</strong>
          <ol>
            <li>Open the <strong>Golf Tee Mobile App</strong> or Web Portal.</li>
            <li>Sign in with your email: <strong><?= htmlspecialchars($member['email'] ?? '') ?></strong> and the password you just created.</li>
            <li>You may also choose <strong>"Continue with Google"</strong> using this exact pre-approved email.</li>
          </ol>
        </div>

        <a href="login.php" class="btn-submit" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;gap:8px">
          Go to Club Login Portal
        </a>
      </div>

    <?php elseif ($error && !$member): ?>
      <div class="alert-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" style="flex-shrink:0">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <div>
          <strong>Activation Link Error</strong>
          <p style="margin-top:4px"><?= htmlspecialchars($error) ?></p>
        </div>
      </div>
      <p style="font-size:13px;color:var(--text-muted);text-align:center;line-height:1.5">
        Accounts at Pine Valley Golf Club are restricted and created exclusively by Club Administration.
        If your invitation expired, please contact Member Services.
      </p>

    <?php else: ?>
      <?php if (!empty($error)): ?>
        <div class="alert-error">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="flex-shrink:0">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <div><?= htmlspecialchars($error) ?></div>
        </div>
      <?php endif; ?>

      <div class="member-banner">
        <div class="member-avatar">
          <?= strtoupper(substr($member['name'], 0, 2)) ?>
        </div>
        <div class="member-info">
          <h4>Welcome, <?= htmlspecialchars($member['name']) ?></h4>
          <p><?= htmlspecialchars($member['email']) ?> · <?= htmlspecialchars($member['tier']) ?> Member</p>
        </div>
      </div>

      <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px;line-height:1.5">
        Your membership account was initialized by club administration. Please choose a secure password to activate your mobile app access.
      </p>

      <form method="post" id="setupForm" onsubmit="return validateForm()">
        <?= csrfField() ?>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="field-group">
          <label for="password">Create Password</label>
          <div class="input-wrap">
            <input type="password" id="password" name="password" required minlength="6" placeholder="At least 6 characters" oninput="checkStrength(this.value)">
            <button type="button" class="toggle-pwd" onclick="toggleVisibility('password')" aria-label="Toggle password visibility">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
        </div>

        <div class="field-group">
          <label for="confirm_password">Confirm Password</label>
          <div class="input-wrap">
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Re-enter password">
            <button type="button" class="toggle-pwd" onclick="toggleVisibility('confirm_password')" aria-label="Toggle password visibility">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-submit">
          Save Password & Activate Account
        </button>
      </form>

      <div class="rules-box">
        🔒 <strong>Private Membership Protection:</strong> Public registration is strictly disabled. Only accounts added by the club manager can sign in. Once activated, you can also log in instantly via "Continue with Google" using this authorized email.
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function toggleVisibility(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.type = el.type === 'password' ? 'text' : 'password';
}

function checkStrength(pwd) {
  const fill = document.getElementById('strengthFill');
  if (!fill) return;
  let score = 0;
  if (pwd.length >= 6) score += 25;
  if (pwd.length >= 8) score += 25;
  if (/[0-9]/.test(pwd)) score += 25;
  if (/[^A-Za-z0-9]/.test(pwd)) score += 25;

  fill.style.width = score + '%';
  if (score <= 25) fill.style.backgroundColor = '#ef4444';
  else if (score <= 50) fill.style.backgroundColor = '#f59e0b';
  else if (score <= 75) fill.style.backgroundColor = '#3b82f6';
  else fill.style.backgroundColor = '#16a34a';
}

function validateForm() {
  const p1 = document.getElementById('password').value;
  const p2 = document.getElementById('confirm_password').value;
  if (p1.length < 6) {
    alert('Password must be at least 6 characters.');
    return false;
  }
  if (p1 !== p2) {
    alert('Passwords do not match. Please check and re-enter.');
    return false;
  }
  return true;
}
</script>

</body>
</html>
