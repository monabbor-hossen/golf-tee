<?php
declare(strict_types=1);

use App\Core\Config;
use App\Domain\SlotFormatter;
use App\Services\LayoutService;

$layout = LayoutService::getLayoutData();
$courseName = $layout['courseName'];
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
$appUrl = Config::get('APP_URL');
?>
<!DOCTYPE html>
<html lang="en" data-app-url="<?= htmlspecialchars($appUrl) ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($courseName) ?> — Admin</title>
  <meta name="description" content="Golf course tee time and member management dashboard." />
  <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>" />
  <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($appUrl) ?>/assets/img/favicon.svg" />
  <link rel="alternate icon" href="<?= htmlspecialchars($appUrl) ?>/favicon.svg" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= htmlspecialchars($appUrl) ?>/assets/css/main.css" />
</head>
<body>
<div class="app-shell">

  <!-- ── Desktop Sidebar ────────────────────────── -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <span class="logo-icon">⛳</span>
      <div class="logo-text">
        <strong><?= htmlspecialchars($courseName) ?></strong>
        <small>Course admin</small>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-group">
        <p class="nav-label">Overview</p>
        <a href="index.php" class="nav-item <?= $currentPage==='index'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z"/></svg>
          Dashboard
        </a>
        <a href="reports.php" class="nav-item <?= $currentPage==='reports'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18M7 15l4-4 3 3 5-6"/></svg>
          Reports
        </a>
      </div>
      <div class="nav-group">
        <p class="nav-label">Operations</p>
        <a href="mastergrid.php" class="nav-item <?= $currentPage==='mastergrid'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v15H4zM4 10h16M9 5v15M8 2v4M16 2v4"/></svg>
          Master Grid
        </a>
        <a href="waitlist.php" class="nav-item <?= $currentPage==='waitlist'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2"/></svg>
          Waitlist
          <?php if ($layout['waitlistCount'] > 0): ?>
            <span class="badge"><?= $layout['waitlistCount'] ?></span>
          <?php endif; ?>
        </a>
        <a href="events.php" class="nav-item <?= $currentPage==='events'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0zM17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/></svg>
          Events
          <?php if ($layout['eventCount'] > 0): ?>
            <span class="badge"><?= $layout['eventCount'] ?></span>
          <?php endif; ?>
        </a>
        <a href="news.php" class="nav-item <?= $currentPage==='news'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1m4 13a2 2 0 0 1-2-2V7m2 13a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2m-4-3H9M7 16h6M7 8h6m-6 4h10"/></svg>
          Club News
        </a>
      </div>
      <div class="nav-group">
        <p class="nav-label">Management</p>
        <a href="members.php" class="nav-item <?= $currentPage==='members'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21a7 7 0 0 1 14 0M16 3.5a4 4 0 0 1 0 7M22 21a7 7 0 0 0-4-6.3"/></svg>
          Members
          <?php if ($layout['pendingMemberCount'] > 0): ?>
            <span class="badge badge-warn"><?= $layout['pendingMemberCount'] ?></span>
          <?php endif; ?>
        </a>
        <a href="payments.php" class="nav-item <?= $currentPage==='payments'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6h20v12H2zM2 10h20M6 15h4"/></svg>
          Payments
          <?php if ($layout['pendingTxnCount'] > 0): ?>
            <span class="badge badge-warn"><?= $layout['pendingTxnCount'] ?></span>
          <?php endif; ?>
        </a>
        <a href="settings.php" class="nav-item <?= $currentPage==='settings'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h9M17 6h3M4 12h3M11 12h9M4 18h11M19 18h1M15 4v4M9 10v4M17 16v4"/></svg>
          Settings
        </a>
      </div>
    </nav>
    <div class="sidebar-status">
      <p class="status-label">Course status</p>
      <p class="status-val"><span class="pulse-dot"></span>Open · <?= date('g:i A') ?></p>
      <p class="status-sub">Carts on fairways</p>
    </div>
  </aside>

  <!-- ── Mobile Drawer Overlay ──────────────────── -->
  <div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
  <aside class="sidebar sidebar-drawer" id="sidebarDrawer">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid rgba(255,255,255,.08)">
      <div class="sidebar-logo" style="margin:0">
        <span class="logo-icon">⛳</span>
        <div class="logo-text">
          <strong><?= htmlspecialchars($courseName) ?></strong>
          <small>Course admin</small>
        </div>
      </div>
      <button class="drawer-close" onclick="closeDrawer()" aria-label="Close menu">✕</button>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-group">
        <p class="nav-label">Overview</p>
        <a href="index.php" class="nav-item <?= $currentPage==='index'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z"/></svg>
          Dashboard
        </a>
        <a href="reports.php" class="nav-item <?= $currentPage==='reports'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18M7 15l4-4 3 3 5-6"/></svg>
          Reports
        </a>
      </div>
      <div class="nav-group">
        <p class="nav-label">Operations</p>
        <a href="mastergrid.php" class="nav-item <?= $currentPage==='mastergrid'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v15H4zM4 10h16M9 5v15M8 2v4M16 2v4"/></svg>
          Master Grid
        </a>
        <a href="waitlist.php" class="nav-item <?= $currentPage==='waitlist'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2"/></svg>
          Waitlist
          <?php if ($layout['waitlistCount'] > 0): ?><span class="badge"><?= $layout['waitlistCount'] ?></span><?php endif; ?>
        </a>
        <a href="events.php" class="nav-item <?= $currentPage==='events'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0zM17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/></svg>
          Events
          <?php if ($layout['eventCount'] > 0): ?><span class="badge"><?= $layout['eventCount'] ?></span><?php endif; ?>
        </a>
        <a href="news.php" class="nav-item <?= $currentPage==='news'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1m4 13a2 2 0 0 1-2-2V7m2 13a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2m-4-3H9M7 16h6M7 8h6m-6 4h10"/></svg>
          Club News
        </a>
      </div>
      <div class="nav-group">
        <p class="nav-label">Management</p>
        <a href="members.php" class="nav-item <?= $currentPage==='members'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21a7 7 0 0 1 14 0M16 3.5a4 4 0 0 1 0 7M22 21a7 7 0 0 0-4-6.3"/></svg>
          Members
          <?php if ($layout['pendingMemberCount'] > 0): ?><span class="badge badge-warn"><?= $layout['pendingMemberCount'] ?></span><?php endif; ?>
        </a>
        <a href="payments.php" class="nav-item <?= $currentPage==='payments'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 6h20v12H2zM2 10h20M6 15h4"/></svg>
          Payments
          <?php if ($layout['pendingTxnCount'] > 0): ?><span class="badge badge-warn"><?= $layout['pendingTxnCount'] ?></span><?php endif; ?>
        </a>
        <a href="settings.php" class="nav-item <?= $currentPage==='settings'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h9M17 6h3M4 12h3M11 12h9M4 18h11M19 18h1M15 4v4M9 10v4M17 16v4"/></svg>
          Settings
        </a>
      </div>
    </nav>

    <div style="margin-top:auto;padding-top:16px;border-top:1px solid rgba(255,255,255,.08);display:flex;flex-direction:column;gap:12px">
      <div class="sidebar-status" style="margin-top:0">
        <p class="status-label">Course status</p>
        <p class="status-val"><span class="pulse-dot"></span>Open · <?= date('g:i A') ?></p>
        <p class="status-sub">Carts on fairways</p>
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:4px 8px">
        <div style="display:flex;align-items:center;gap:8px">
          <span class="avatar"><?= strtoupper(substr($layout['staffName'] ?: 'A', 0, 2)) ?></span>
          <div>
            <p style="font-size:.8rem;font-weight:700;color:#fff;line-height:1.2"><?= htmlspecialchars($layout['staffName'] ?: 'Admin') ?></p>
            <p style="font-size:.68rem;color:rgba(255,255,255,.4)"><?= htmlspecialchars($layout['staffRole'] ?: 'Owner') ?></p>
          </div>
        </div>
        <a href="logout.php" style="color:#ef4444;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:4px">
          🚪 Exit
        </a>
      </div>
    </div>
  </aside>

  <!-- ── Main Content Area ────────────────────────── -->
  <div class="main-area">
    <!-- Top Header -->
    <header class="topbar">
      <button class="hamburger" onclick="openDrawer()" aria-label="Open menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <!-- Global Search -->
      <div class="search-wrap" id="searchWrap">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM21 21l-4.3-4.3"/></svg>
        <input type="text" id="globalSearch" placeholder="Search…" autocomplete="off"
               class="search-input" onkeyup="handleGlobalSearch(this.value)" />
        <div id="searchResults" class="search-dropdown hidden"></div>
      </div>
      <div class="topbar-right">
        <a href="mastergrid.php" class="btn btn-ghost btn-sm hide-sm">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M4 5h16v15H4zM4 10h16M9 5v15"/></svg>
          Today's tee sheet
        </a>
        <!-- Notifications -->
        <div class="notif-wrap" id="notifWrap">
          <button class="icon-btn" id="notifBtn" onclick="toggleNotifs()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 8 3 8H3s3-1 3-8M10 21h4"/></svg>
            <?php if ($layout['unreadNotifCount'] > 0): ?>
              <span class="notif-badge"><?= $layout['unreadNotifCount'] ?></span>
            <?php endif; ?>
          </button>
          <div class="notif-dropdown hidden" id="notifDropdown">
            <div class="notif-header">
              <strong>Notifications</strong>
              <button onclick="markAllRead()" class="link-btn">Mark all as read</button>
            </div>
            <div id="notifList">
              <?php
              $dotClasses = [
                  'forest' => 'dot-green',
                  'wait'   => 'dot-amber',
                  'danger' => 'dot-red',
                  'sand'   => 'dot-sand',
                  'gray'   => 'dot-gray',
              ];
              foreach ($layout['recentNotifs'] as $n):
                $dotClass = $dotClasses[$n['tone']] ?? 'dot-gray';
              ?>
              <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>"
                   data-id="<?= (int)$n['id'] ?>"
                   data-page="<?= htmlspecialchars($n['page'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   data-focus="<?= htmlspecialchars($n['focus_data'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   onclick="handleNotifClick(this)">
                <i class="notif-dot <?= $dotClass ?>"></i>
                <div class="notif-body">
                  <p class="notif-title"><?= htmlspecialchars($n['title']) ?></p>
                  <p class="notif-desc"><?= htmlspecialchars($n['description']) ?></p>
                </div>
                <span class="notif-time"><?= SlotFormatter::timeAgo($n['created_at']) ?></span>
              </div>
              <?php endforeach; ?>
              <?php if (empty($layout['recentNotifs'])): ?><p class="notif-empty">You're all caught up</p><?php endif; ?>
            </div>
          </div>
        </div>
        <div class="divider-v hide-sm"></div>
        <!-- Profile -->
        <div class="profile-wrap" id="profileWrap">
          <button class="profile-btn" onclick="toggleProfile()" aria-label="User menu">
            <span class="avatar"><?= strtoupper(substr($layout['staffName'] ?: 'A', 0, 2)) ?></span>
            <div class="profile-info">
              <span class="profile-name"><?= htmlspecialchars($layout['staffName'] ?: 'Admin') ?></span>
              <span class="profile-role"><?= htmlspecialchars($layout['staffRole'] ?: 'Owner') ?></span>
            </div>
            <svg class="profile-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M6 9l6 6 6-6"/></svg>
          </button>
          <div class="profile-dropdown hidden" id="profileDropdown">
            <a href="settings.php" class="dropdown-item">⚙️ Settings</a>
            <a href="logout.php"   class="dropdown-item danger">🚪 Sign out</a>
          </div>
        </div>
      </div>
    </header>

    <!-- Page Content Slot -->
    <main class="page-content" id="pageContent">
