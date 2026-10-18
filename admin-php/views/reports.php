<?php
declare(strict_types=1);

$typeCls = ['Green fee' => '#1E5631', 'Pro shop' => '#D4C4A8', 'Food & bev' => '#F4A261', 'Event' => '#E8C766', 'Membership' => '#6b8f71'];
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Reports</h1>
    <p>Revenue, bookings and member analytics</p>
  </div>
  <div class="page-header-actions">
    <div class="segmented">
      <?php foreach (['7' => '7 days', '30' => '30 days', '90' => '90 days'] as $k => $l): ?>
      <a href="reports.php?range=<?= $k ?>" class="tab-btn <?= $range === $k ? 'active' : '' ?>" style="padding:0 14px;height:36px;display:flex;align-items:center;border-radius:7px;font-size:.8rem;font-weight:600"><?= $l ?></a>
      <?php endforeach; ?>
    </div>
    <a href="reports.php?action=export&range=<?= $range ?>" class="btn btn-secondary">⬇ Export</a>
  </div>
</div>

<!-- Summary stats -->
<div class="stat-grid" style="margin-bottom:16px">
  <div class="stat-card highlight">
    <p class="stat-label">Total Revenue</p>
    <p class="stat-value"><?= currencySymbol() ?><?= number_format((float)$totalRev) ?></p>
    <p class="stat-sub">Last <?= $range ?> days · paid only</p>
  </div>
  <div class="stat-card">
    <p class="stat-label">Total Bookings</p>
    <p class="stat-value"><?= $totalBookings ?></p>
    <p class="stat-sub green"><?= $bookStats['Checked in'] ?? 0 ?> checked in · <?= $bookStats['No-show'] ?? 0 ?> no-shows</p>
  </div>
  <div class="stat-card">
    <p class="stat-label">Active Members</p>
    <p class="stat-value"><?= $memberStats['Active'] ?? 0 ?></p>
    <p class="stat-sub"><?= $memberStats['Pending'] ?? 0 ?> pending · <?= $totalMembers ?> total</p>
  </div>
  <div class="stat-card">
    <p class="stat-label">Avg per Booking</p>
    <p class="stat-value"><?= currencySymbol() ?><?= $totalBookings > 0 ? number_format((float)($totalRev / $totalBookings)) : '0' ?></p>
    <p class="stat-sub">Green fees + add-ons</p>
  </div>
</div>

<div class="grid-3-2" style="margin-bottom:16px">
  <!-- Revenue chart -->
  <div class="card">
    <h2 style="margin-bottom:16px">Daily revenue</h2>
    <div style="display:flex;align-items:flex-end;gap:3px;height:140px;overflow-x:auto;-webkit-overflow-scrolling:touch;padding-bottom:4px">
      <?php
      $start = new DateTime($fromDate);
      $end   = new DateTime($today);
      $interval = new DateInterval('P1D');
      $period   = new DatePeriod($start, $interval, $end->modify('+1 day'));
      foreach ($period as $date):
        $ds  = $date->format('Y-m-d');
        $rev = (float)($dailyMap[$ds] ?? 0);
        $pct = $rev > 0 ? round($rev / $maxRev * 100) : 0;
        $isToday = $ds === $today;
      ?>
      <div title="<?= currencySymbol() ?><?= number_format($rev) ?> on <?= date('M j', strtotime($ds)) ?>"
           style="flex:1;min-width:6px;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%">
        <div style="width:100%;border-radius:3px 3px 0 0;background:<?= $isToday ? 'var(--forest)' : 'rgba(30,86,49,.3)' ?>;height:<?= $pct ?>%;min-height:<?= $rev > 0 ? 2 : 0 ?>px"></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Revenue by type -->
  <div class="card">
    <h2 style="margin-bottom:16px">Revenue by category</h2>
    <?php foreach ($revByType as $r):
      $pct = $totalRev > 0 ? round((float)$r['total'] / $totalRev * 100) : 0;
    ?>
    <div style="margin-bottom:10px">
      <div style="display:flex;justify-content:space-between;font-size:.8rem;margin-bottom:4px">
        <span style="display:flex;align-items:center;gap:6px">
          <i style="width:8px;height:8px;border-radius:2px;background:<?= $typeCls[$r['type']] ?? '#9ca3af' ?>;display:inline-block"></i>
          <?= htmlspecialchars($r['type']) ?>
        </span>
        <span style="font-weight:700"><?= currencySymbol() ?><?= number_format((float)$r['total']) ?> <span style="color:#9ca3af;font-weight:400">(<?= $pct ?>%)</span></span>
      </div>
      <div class="stat-bar"><div class="stat-bar-fill" style="width:<?= $pct ?>%;background:<?= $typeCls[$r['type']] ?? 'var(--forest)' ?>"></div></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="grid-3-2">
  <!-- Bookings by day of week -->
  <div class="card">
    <h2 style="margin-bottom:16px">Bookings by day of week</h2>
    <div class="chart-bar-wrap" style="height:100px">
      <?php for ($d = 1; $d <= 7; $d++):
        // DAYOFWEEK: 1=Sun...7=Sat
        $dow = ($d % 7) + 1;
        $cnt = (int)($byDow[$dow] ?? 0);
        $pct = $cnt > 0 ? round($cnt / $maxDow * 100) : 0;
        $dayIdx = $d - 1; // 0=Mon...6=Sun
        $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $isWeekend = $d >= 6;
      ?>
      <div class="chart-bar-col" title="<?= $cnt ?> bookings on <?= $dayNames[$dayIdx] ?>">
        <div class="chart-bar" style="background:<?= $isWeekend ? 'var(--forest)' : 'rgba(30,86,49,.3)' ?>;height:<?= $pct ?>%"></div>
        <span class="chart-label"><?= $dayNames[$dayIdx] ?></span>
      </div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- Top members -->
  <div class="card">
    <h2 style="margin-bottom:12px">Top members by spend</h2>
    <?php foreach ($topSpend as $i => $m): ?>
    <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f9fafb">
      <span style="width:20px;text-align:center;font-size:.75rem;color:#9ca3af;font-weight:700"><?= $i + 1 ?></span>
      <div class="avatar" style="width:28px;height:28px;font-size:.65rem"><?= strtoupper(substr($m['name'], 0, 2)) ?></div>
      <div style="flex:1;min-width:0">
        <p style="font-size:.875rem;font-weight:600"><?= htmlspecialchars($m['name']) ?></p>
        <p style="font-size:.75rem;color:#9ca3af"><?= $m['rounds'] ?> rounds</p>
      </div>
      <p style="font-weight:700;font-size:.9rem"><?= currencySymbol() ?><?= number_format((float)$m['spend']) ?></p>
    </div>
    <?php endforeach; ?>
    <?php if (empty($topSpend)): ?>
    <p style="color:#9ca3af;text-align:center;padding:20px;font-size:.875rem">No data yet</p>
    <?php endif; ?>
  </div>
</div>
