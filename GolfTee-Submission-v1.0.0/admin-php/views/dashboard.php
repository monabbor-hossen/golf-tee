<?php
declare(strict_types=1);

\App\Core\Session::requireAuth();

use App\Domain\SlotFormatter;

$toneColors = ['forest'=>'dot-green','wait'=>'dot-amber','danger'=>'dot-red','sand'=>'dot-sand','gray'=>'dot-gray'];
$staffName = htmlspecialchars(\App\Core\Session::getStaffName());
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Good <?= date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening') ?>, <?= $staffName ?></h1>
    <p>
      <?= date('l, F j') ?> &nbsp;·&nbsp;
      <span style="display:inline-flex;align-items:center;gap:4px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="5"/>
          <line x1="12" y1="1" x2="12" y2="3"/>
          <line x1="12" y1="21" x2="12" y2="23"/>
          <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
          <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
          <line x1="1" y1="12" x2="3" y2="12"/>
          <line x1="21" y1="12" x2="23" y2="12"/>
          <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
          <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
        </svg>
        Course open
      </span>
      &nbsp;·&nbsp;
      <span style="color:var(--forest);font-weight:600;">
        <span class="pulse-dot" style="display:inline-block;margin-right:4px;"></span>Live
      </span>
    </p>
  </div>
  <div class="page-header-actions">
    <a href="mastergrid.php" class="btn btn-secondary">Open Master Grid</a>
    <button class="btn btn-primary" onclick="showModal('newBookingModal')" style="display:inline-flex;align-items:center;gap:6px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/>
        <line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      New Booking
    </button>
  </div>
</div>

<!-- ── Stat Cards ──────────────────────────────── -->
<div class="stat-grid">
  <div class="stat-card highlight">
    <p class="stat-label">Today's Revenue</p>
    <p class="stat-value"><?= currencySymbol() ?><?= number_format($totalRevenue) ?></p>
    <p class="stat-sub"><?= $revDelta >= 0 ? '+' : '' ?><?= $revDelta ?>% vs last <?= $dayName ?></p>
  </div>
  <div class="stat-card">
    <p class="stat-label">Players Today</p>
    <p class="stat-value"><?= $totalPlayers ?></p>
    <p class="stat-sub green"><?= count($todayBookings) ?> groups · <?= $checkedInCount ?> checked in</p>
  </div>
  <div class="stat-card">
    <p class="stat-label">Utilization</p>
    <p class="stat-value"><?= $utilization ?>%</p>
    <div class="stat-bar"><div class="stat-bar-fill" style="width:<?= min(100, $utilization) ?>%"></div></div>
  </div>
  <div class="stat-card">
    <p class="stat-label">Waitlist</p>
    <p class="stat-value"><?= $waitlistCount ?></p>
    <p class="stat-sub <?= $todayWaitCount ? 'amber' : '' ?>"><?= $todayWaitCount ?> for today</p>
  </div>
</div>

<!-- ── Row 1 ───────────────────────────────────── -->
<div class="grid-3-2" style="margin-bottom:16px">
  <!-- Starting soon -->
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h2>Starting soon</h2>
      <a href="mastergrid.php" class="link-btn" style="font-size:.8rem">View all</a>
    </div>
    <?php if (empty($soonBookings)): ?>
      <p style="color:#9ca3af;text-align:center;padding:24px;font-size:.875rem">No more tee times today</p>
    <?php else: ?>
    <?php foreach ($soonBookings as $b):
      $isLate = ($b['status'] === 'Booked' && (int)$b['tee_slot'] < $nowMin);
      $st = $b['status'] === 'Checked in' ? 'Checked in' : ($isLate ? 'Late' : 'Expected');
      $pillCls = $st === 'Checked in' ? 'pill-green' : ($st === 'Late' ? 'pill-amber' : 'pill-gray');
      $teeName = $b['tee'] == 0 ? 'Hole 1' : 'Hole 10';
    ?>
    <div class="soon-row">
      <span class="soon-time"><?= SlotFormatter::format((int)$b['tee_slot']) ?></span>
      <div class="soon-info">
        <p class="soon-name"><?= htmlspecialchars($b['name']) ?></p>
        <p class="soon-meta"><?= $b['party'] ?> players · <?= $b['holes'] ?> holes · <?= $teeName ?></p>
      </div>
      <div class="soon-action">
        <span class="pill <?= $pillCls ?>"><?= $st ?></span>
        <?php if ($st !== 'Checked in'): ?>
        <form method="post" action="api/bookings.php" style="margin:0">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="check_in">
          <input type="hidden" name="id" value="<?= $b['id'] ?>">
          <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--forest)">Check in</button>
        </form>
        <?php else: ?>
        <span class="soon-done">Done</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Recent activity -->
  <div class="card">
    <h2 style="margin-bottom:12px">Recent activity</h2>
    <?php foreach ($activities as $a): $dot = $toneColors[$a['tone']] ?? 'dot-gray'; ?>
    <div class="activity-item">
      <i class="activity-dot <?= $dot ?>"></i>
      <div class="activity-content">
        <p class="activity-title"><?= htmlspecialchars($a['title']) ?></p>
        <p class="activity-desc"><?= htmlspecialchars($a['description']) ?></p>
      </div>
      <span class="activity-time"><?= SlotFormatter::timeAgo($a['created_at']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── Row 2 ───────────────────────────────────── -->
<div class="grid-3-2">
  <!-- Hourly chart -->
  <div class="card">
    <?php $peakHour = array_search(max($byHour), $byHour); ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px">
      <h2>Players on course by hour</h2>
      <span style="font-size:.75rem;color:#6b7280">
        Peak: <?= $peakHour > 12 ? $peakHour - 12 : $peakHour ?><?= $peakHour < 12 ? 'AM' : 'PM' ?> · <?= $byHour[$peakHour] ?> players
      </span>
    </div>
    <div class="chart-bar-wrap">
    <?php foreach ($byHour as $h => $v):
      $cls = $h < $nowHour ? 'past' : ($h === $nowHour ? 'now' : 'future');
      $pct = $v > 0 ? round(($v / $maxH) * 100) : 0;
      $label = (($h + 11) % 12) + 1;
      $period = $h < 12 ? 'a' : 'p';
    ?>
    <div class="chart-bar-col" title="<?= $v ?> players at <?= $label ?><?= $period ?>m">
      <div class="chart-bar <?= $cls ?> <?= $v == 0 ? 'empty' : '' ?>" style="height:<?= $pct ?>%"></div>
      <span class="chart-label <?= $h === $nowHour ? 'now' : '' ?>"><?= $label ?><?= $period ?></span>
    </div>
    <?php endforeach; ?>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:8px 16px;margin-top:12px;font-size:.75rem;color:#6b7280">
      <span style="display:flex;align-items:center;gap:4px"><i style="width:10px;height:10px;border-radius:2px;background:rgba(30,86,49,.3);display:inline-block"></i>Completed</span>
      <span style="display:flex;align-items:center;gap:4px"><i style="width:10px;height:10px;border-radius:2px;background:var(--forest);display:inline-block"></i>Now</span>
      <span style="display:flex;align-items:center;gap:4px"><i style="width:10px;height:10px;border-radius:2px;background:var(--sand);display:inline-block"></i>Booked ahead</span>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px">
    <!-- Needs attention -->
    <div class="card">
      <h2 style="margin-bottom:12px">Needs attention</h2>
      <?php
      $attentions = [];
      if ($pendingMembers):
        $names = implode(', ', array_map(fn($m) => $m['name'], $pendingMembers));
        $attentions[] = ['warn', count($pendingMembers) . ' member' . (count($pendingMembers) > 1 ? 's' : '') . ' pending approval', $names, 'members.php'];
      endif;
      if ($lateBookings):
        $attentions[] = ['danger', count($lateBookings) . ' group' . (count($lateBookings) > 1 ? 's' : '') . ' not checked in', 'Check master grid', 'mastergrid.php'];
      endif;
      if ($offeredWaitlist):
        $attentions[] = ['warn', count($offeredWaitlist) . ' waitlist offer' . (count($offeredWaitlist) > 1 ? 's' : '') . ' awaiting reply', 'Respond quickly', 'waitlist.php'];
      endif;
      if ($todayWaitCount):
        $attentions[] = ['gray', "{$todayWaitCount} on today's waitlist", 'Fill cancellations fast', 'waitlist.php'];
      endif;
      if ($pendingPayCount):
        $attentions[] = ['gray', "{$pendingPayCount} pending payment" . (($pendingPayCount > 1) ? 's' : ''), '$' . number_format($pendingPaySum) . ' outstanding', 'payments.php'];
      endif;
      ?>
      <?php if (empty($attentions)): ?>
        <p style="color:#6b7280;font-size:.875rem;display:flex;align-items:center;gap:6px">
          <span style="color:var(--forest);display:inline-flex;align-items:center">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </span>
          All clear
        </p>
      <?php else: ?>
      <?php foreach (array_slice($attentions, 0, 4) as [$tone, $title, $desc, $url]): ?>
        <a href="<?= $url ?>" class="attention-item attention-<?= $tone ?>" style="display:flex;align-items:center;text-decoration:none;margin-bottom:6px">
          <div style="min-width:0;flex:1">
            <p class="attention-title"><?= htmlspecialchars($title) ?></p>
            <p class="attention-desc"><?= htmlspecialchars($desc) ?></p>
          </div>
          <span style="display:inline-flex;align-items:center;color:#9ca3af;margin-left:8px;flex-shrink:0">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 18 15 12 9 6"/>
            </svg>
          </span>
        </a>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Next event -->
    <?php if ($nextEvent): ?>
    <a href="events.php?id=<?= $nextEvent['id'] ?>" class="card" style="text-decoration:none;display:block;transition:border-color .15s" onmouseover="this.style.borderColor='var(--forest)'" onmouseout="this.style.borderColor='var(--border)'">
      <p style="font-size:.7rem;font-weight:700;text-transform:uppercase;color:var(--forest);display:flex;align-items:center;gap:6px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/>
          <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/>
          <path d="M4 22h16"/>
          <path d="M10 14.66V17c0 .55-.45 1-1 1H8c-.55 0-1 .45-1 1v1h10v-1c0-.55-.45-1-1-1h-1c-.55 0-1-.45-1-1v-2.34"/>
          <path d="M6 4h12v7a6 6 0 0 1-12 0V4z"/>
        </svg>
        Next event · <?= date('M j', strtotime($nextEvent['event_date'])) ?>
      </p>
      <p style="font-weight:700;margin-top:6px"><?= htmlspecialchars($nextEvent['name']) ?></p>
      <div class="event-fill">
        <div class="event-fill-bar" style="width:<?= min(100, round($nextEventReg / max(1, (int)$nextEvent['capacity']) * 100)) ?>%"></div>
      </div>
      <p style="font-size:.75rem;color:#6b7280;margin-top:4px"><?= $nextEventReg ?>/<?= $nextEvent['capacity'] ?> registered</p>
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- ── New Booking Modal ─────────────────────────── -->
<div class="modal-backdrop hidden" id="newBookingModal">
  <div class="modal">
    <div class="modal-header">
      <div>
        <p class="modal-title">New Booking</p>
        <p class="modal-desc">Add a tee time for today</p>
      </div>
      <button class="close-btn" onclick="closeModal('newBookingModal')" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <form method="post" action="api/bookings.php">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="field-group">
          <label class="field-label">Name</label>
          <input name="name" class="input" required placeholder="Guest or member name" list="member-names"/>
          <datalist id="member-names">
            <?php foreach ($memberNames as $m): ?>
              <option value="<?= htmlspecialchars($m['name']) ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="modal-grid-2">
          <div class="field-group">
            <label class="field-label">Time</label>
            <select name="tee_slot" class="input select-field">
              <?php for ($m = 420; $m <= 1070; $m += 10):
                // Fast in-memory check instead of 132 SQL queries!
                $taken0 = !empty($bookedSlots['0-' . $m]);
                $taken1 = !empty($bookedSlots['1-' . $m]);
                if ($taken0 && $taken1) continue; // both full, skip
              ?>
              <option value="<?= $m ?>"><?= SlotFormatter::format($m) ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label">Tee</label>
            <select name="tee" class="input select-field">
              <option value="0">Hole 1</option>
              <option value="1">Hole 10</option>
            </select>
          </div>
        </div>
        <div class="modal-grid-2">
          <div class="field-group">
            <label class="field-label">Players</label>
            <select name="party" class="input select-field">
              <option>1</option><option>2</option><option selected>3</option><option>4</option>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label">Holes</label>
            <select name="holes" class="input select-field">
              <option value="18" selected>18 holes</option>
              <option value="9">9 holes</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('newBookingModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Book tee time</button>
      </div>
    </form>
  </div>
</div>
