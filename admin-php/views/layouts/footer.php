<?php
declare(strict_types=1);

use App\Core\Config;

$appUrl = Config::get('APP_URL');
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
?>
    </main><!-- /page-content -->
  </div><!-- /main-area -->
</div><!-- /app-shell -->

<!-- Mobile Bottom Navigation Bar (Screens <= 768px) -->
<nav class="mobile-bottom-bar" aria-label="Mobile Navigation">
  <a href="index.php" class="mobile-nav-link <?= ($currentPage ?? '') === 'index' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z"/></svg>
    <span>Home</span>
  </a>
  <a href="mastergrid.php" class="mobile-nav-link <?= ($currentPage ?? '') === 'mastergrid' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M4 5h16v15H4zM4 10h16M9 5v15M8 2v4M16 2v4"/></svg>
    <span>Tee Sheet</span>
  </a>
  <a href="members.php" class="mobile-nav-link <?= ($currentPage ?? '') === 'members' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21a7 7 0 0 1 14 0M16 3.5a4 4 0 0 1 0 7M22 21a7 7 0 0 0-4-6.3"/></svg>
    <span>Members</span>
  </a>
  <a href="payments.php" class="mobile-nav-link <?= ($currentPage ?? '') === 'payments' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M2 6h20v12H2zM2 10h20M6 15h4"/></svg>
    <span>Payments</span>
  </a>
  <button type="button" class="mobile-nav-link" onclick="openDrawer()" aria-label="Open full menu">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    <span>More</span>
  </button>
</nav>

<!-- Toast Container -->
<div id="toast" class="toast hidden"></div>

<script src="<?= htmlspecialchars($appUrl) ?>/assets/js/app.js"></script>
</body>
</html>
