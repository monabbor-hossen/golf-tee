<?php
declare(strict_types=1);

use App\Domain\SlotFormatter;

$tab   = $tab   ?? 'all';
$error = $error ?? '';

// ── Pill classes ──────────────────────────────────────────────
$caddieStatusCls = [
    'Available'   => 'pill-green',
    'Unavailable' => 'pill-red',
    'On Leave'    => 'pill-amber',
];
$bookingStatusCls = [
    'Confirmed' => 'pill-green',
    'Completed' => 'pill-sand',
    'Cancelled' => 'pill-red',
    'No-show'   => 'pill-amber',
];

// ── URL builder helper ────────────────────────────────────────
function caddieUrl(array $override = []): string {
    $base = array_merge(['tab' => $GLOBALS['tab'] ?? 'all'], $override);
    return 'caddies.php?' . http_build_query($base);
}

// ── Star rating helper ────────────────────────────────────────
function renderStars(float $rating): string {
    $full  = floor($rating);
    $html  = '<span class="caddie-stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full) {
            $html .= '<svg viewBox="0 0 24 24" fill="currentColor" width="12" height="12" style="color:#f59e0b"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        } else {
            $html .= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="12" height="12" style="color:#d1d5db"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        }
    }
    return $html . '</span>';
}

// ── Tee label ─────────────────────────────────────────────────
function teeLabel(int $tee): string { return $tee === 0 ? 'Hole 1' : 'Hole 10'; }

// ── Aggregates ────────────────────────────────────────────────
$totalCaddies   = $total ?? count($caddies ?? []);
$availableCount = $statusCounts['Available'] ?? 0;
$todayCount     = count($todayBookings ?? []);
$totalRevenue   = (float)($revenue ?? 0);
$confirmedCount = $bookingCounts['Confirmed'] ?? 0;
$allBookingCount = array_sum($bookingCounts ?? []);
?>

<?php /* ── buildUrl JS (used on this page) ── */ ?>
<script>
function caddieGo(params) {
  const base = new URLSearchParams(window.location.search);
  Object.entries(params).forEach(([k,v]) => {
    if (v === null || v === '') base.delete(k);
    else base.set(k, v);
  });
  base.set('tab', '<?= e($tab) ?>');
  window.location.href = 'caddies.php?' + base.toString();
}
function switchTab(t) {
  window.location.href = 'caddies.php?tab=' + encodeURIComponent(t);
}
</script>

<?php if ($error === 'conflict'): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;color:#dc2626;display:flex;align-items:center;gap:10px;font-size:.87rem;font-weight:500">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  Booking conflict — that caddie is already assigned to this time slot. Pick a different caddie or time.
  <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#dc2626;font-size:1rem;line-height:1">✕</button>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════════ -->
<div class="page-header">
  <div class="page-header-left">
    <h1>Caddie Booking</h1>
    <p>Manage caddie roster and tee-time assignments</p>
  </div>
  <div class="page-header-actions">
    <?php if ($tab === 'bookings'): ?>
    <button class="btn btn-primary" onclick="showModal('addBookingModal')" style="display:inline-flex;align-items:center;gap:6px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Booking
    </button>
    <?php else: ?>
    <button class="btn btn-primary" onclick="showModal('addCaddieModal')" style="display:inline-flex;align-items:center;gap:6px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Caddie
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- ══════════════════════════════════════════════
     STAT CARDS
══════════════════════════════════════════════ -->
<div class="stat-grid" style="margin-bottom:20px">
  <div class="stat-card <?= $tab !== 'bookings' ? 'highlight' : '' ?>">
    <p class="stat-label">Total Caddies</p>
    <p class="stat-value"><?= $totalCaddies ?></p>
    <p class="stat-sub"><?= $statusCounts['On Leave'] ?? 0 ?> on leave</p>
  </div>
  <div class="stat-card">
    <p class="stat-label">Available Now</p>
    <p class="stat-value" style="color:var(--forest)"><?= $availableCount ?></p>
    <p class="stat-sub">of <?= $totalCaddies ?> total</p>
  </div>
  <div class="stat-card <?= $tab === 'bookings' ? 'highlight' : '' ?>">
    <p class="stat-label">Today's Bookings</p>
    <p class="stat-value"><?= $todayCount ?></p>
    <p class="stat-sub"><?= $confirmedCount ?> confirmed</p>
  </div>
  <div class="stat-card">
    <p class="stat-label">Total Revenue</p>
    <p class="stat-value"><?= currencySymbol() ?><?= number_format($totalRevenue) ?></p>
    <p class="stat-sub"><?= $allBookingCount ?> bookings total</p>
  </div>
</div>

<!-- ══════════════════════════════════════════════
     TABS  (use <button> + onclick to match app pattern)
══════════════════════════════════════════════ -->
<div class="segmented" style="margin-bottom:20px">
  <button class="<?= $tab === 'all' ? 'active' : '' ?>" onclick="switchTab('all')">
    All Caddies
    <?php if ($totalCaddies > 0): ?><span class="badge" style="background:rgba(0,0,0,.08);color:inherit"><?= $totalCaddies ?></span><?php endif; ?>
  </button>
  <button class="<?= $tab === 'bookings' ? 'active' : '' ?>" onclick="switchTab('bookings')">
    Bookings
    <?php if ($confirmedCount > 0): ?><span class="badge"><?= $confirmedCount ?></span><?php endif; ?>
  </button>
  <button class="<?= $tab === 'available' ? 'active' : '' ?>" onclick="switchTab('available')">
    Available
    <span class="badge" style="background:rgba(0,0,0,.08);color:inherit"><?= $availableCount ?></span>
  </button>
  <button class="<?= ($tab === 'unavailable' || $tab === 'on_leave') ? 'active' : '' ?>" onclick="switchTab('unavailable')">
    Off-duty
    <span class="badge" style="background:rgba(0,0,0,.08);color:inherit"><?= ($statusCounts['Unavailable'] ?? 0) + ($statusCounts['On Leave'] ?? 0) ?></span>
  </button>
</div>


<?php if ($tab === 'bookings'): ?>
<!-- ══════════════════════════════════════════════════════════════
     BOOKINGS TAB
══════════════════════════════════════════════════════════════ -->
<div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap">

  <!-- Left: booking table -->
  <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:12px">

    <!-- Filters row -->
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <div class="segmented">
        <?php foreach (['All','Confirmed','Completed','Cancelled','No-show'] as $bs): ?>
        <button class="<?= ($status ?? 'All') === $bs ? 'active' : '' ?>"
                onclick="caddieGo({status:'<?= $bs ?>',tab:'bookings'})">
          <?= $bs ?><?= $bs !== 'All' && isset($counts[$bs]) && $counts[$bs] > 0 ? ' (' . $counts[$bs] . ')' : '' ?>
        </button>
        <?php endforeach; ?>
      </div>

      <div style="margin-left:auto;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <select class="input" style="width:auto;font-size:.82rem;height:36px"
                onchange="caddieGo({caddie_id:this.value,tab:'bookings'})">
          <option value="0" <?= ($caddieId ?? 0) == 0 ? 'selected' : '' ?>>All caddies</option>
          <?php foreach ($allCaddies ?? [] as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ($caddieId ?? 0) == (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="date" class="input" style="width:auto;font-size:.82rem;height:36px" id="bkFrom"
               value="<?= e($dateFrom ?? '') ?>" onchange="applyDateFilter()"
               title="From date">
        <span style="font-size:.82rem;color:var(--muted)">to</span>
        <input type="date" class="input" style="width:auto;font-size:.82rem;height:36px" id="bkTo"
               value="<?= e($dateTo ?? '') ?>" onchange="applyDateFilter()"
               title="To date">
      </div>
    </div>

    <!-- Table card -->
    <div class="card" style="padding:0;overflow:hidden">
      <?php if (empty($bookings)): ?>
      <div style="padding:56px 24px;text-align:center;color:var(--muted)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40" style="margin:0 auto 12px;display:block;opacity:.3"><path d="M8 7V3m8 4V3M3 11h18M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
        <p style="font-size:.9rem;font-weight:500;margin-bottom:4px">No bookings found</p>
        <p style="font-size:.82rem">Try adjusting your filters or add a new booking.</p>
      </div>
      <?php else: ?>
      <div style="overflow-x:auto">
        <table class="table">
          <thead>
            <tr>
              <th>Caddie</th>
              <th>Golfer</th>
              <th>Date</th>
              <th>Time · Tee</th>
              <th>Holes</th>
              <th>Fee</th>
              <th>Status</th>
              <th style="width:80px"></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($bookings as $bk): ?>
          <tr id="bkrow-<?= (int)$bk['id'] ?>">
            <td style="font-weight:600;white-space:nowrap"><?= e($bk['caddie_name']) ?></td>
            <td style="white-space:nowrap"><?= e($bk['golfer_name']) ?></td>
            <td style="white-space:nowrap"><?= date('M j, Y', strtotime($bk['booking_date'])) ?></td>
            <td style="white-space:nowrap"><?= SlotFormatter::format((int)$bk['tee_slot']) ?> · <?= teeLabel((int)$bk['tee']) ?></td>
            <td><?= (int)$bk['holes'] ?>h</td>
            <td style="font-weight:600"><?= currencySymbol() ?><?= number_format((float)$bk['fee'], 0) ?></td>
            <td><span class="pill <?= $bookingStatusCls[$bk['status']] ?? 'pill-gray' ?>"><?= e($bk['status']) ?></span></td>
            <td>
              <div style="display:flex;gap:2px">
                <button class="btn btn-ghost btn-sm" title="Edit" style="padding:4px 6px"
                        onclick="openEditBookingModal(<?= htmlspecialchars(json_encode($bk), ENT_QUOTES) ?>)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <?php if ($bk['status'] === 'Confirmed'): ?>
                <form method="post" style="display:inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="complete_booking">
                  <input type="hidden" name="id" value="<?= (int)$bk['id'] ?>">
                  <button type="submit" class="btn btn-ghost btn-sm" title="Mark complete" style="padding:4px 6px;color:var(--forest)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><path d="M20 6L9 17l-5-5"/></svg>
                  </button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('Cancel this booking?')">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="cancel_booking">
                  <input type="hidden" name="id" value="<?= (int)$bk['id'] ?>">
                  <button type="submit" class="btn btn-ghost btn-sm" title="Cancel" style="padding:4px 6px;color:#ef4444">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  </button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div><!-- /left col -->

  <!-- Right: schedule sidebar -->
  <div style="width:264px;flex-shrink:0;display:flex;flex-direction:column;gap:14px">

    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <p class="card-title" style="margin:0;font-size:.88rem">Today's Schedule</p>
        <span class="pill pill-green" style="font-size:.72rem"><?= $todayCount ?> booked</span>
      </div>
      <?php if (empty($todayBookings)): ?>
      <p style="font-size:.82rem;color:var(--muted);text-align:center;padding:10px 0">No bookings today</p>
      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:7px">
        <?php foreach ($todayBookings as $tb): ?>
        <div style="border-radius:8px;padding:9px 11px;background:var(--surface-2,#f8fafc)">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px">
            <span style="font-weight:600;font-size:.81rem"><?= e($tb['caddie_name']) ?></span>
            <span style="font-size:.74rem;color:var(--muted)"><?= SlotFormatter::format((int)$tb['tee_slot']) ?></span>
          </div>
          <span style="font-size:.77rem;color:var(--muted)"><?= e($tb['golfer_name']) ?> · <?= (int)$tb['holes'] ?>h</span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <p class="card-title" style="margin-bottom:12px;font-size:.88rem">Upcoming</p>
      <?php $upcoming5 = array_slice($upcomingBookings ?? [], 0, 5); ?>
      <?php if (empty($upcoming5)): ?>
      <p style="font-size:.82rem;color:var(--muted);text-align:center;padding:10px 0">None scheduled</p>
      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:7px">
        <?php foreach ($upcoming5 as $ub): ?>
        <div style="border-radius:8px;padding:9px 11px;background:var(--surface-2,#f8fafc)">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px">
            <span style="font-weight:600;font-size:.81rem"><?= e($ub['caddie_name']) ?></span>
            <span style="font-size:.74rem;color:var(--forest);font-weight:600"><?= date('M j', strtotime($ub['booking_date'])) ?></span>
          </div>
          <span style="font-size:.77rem;color:var(--muted)"><?= e($ub['golfer_name']) ?> · <?= SlotFormatter::format((int)$ub['tee_slot']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /sidebar -->
</div>


<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════
     CADDIE ROSTER TAB  (all / available / unavailable)
══════════════════════════════════════════════════════════════ -->

<!-- Filter row -->
<div class="filters" style="margin-bottom:16px">
  <div class="filter-search" style="max-width:280px">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM21 21l-4.3-4.3"/></svg>
    <input class="input" type="text" placeholder="Search name, phone, specialty…"
           value="<?= e($query ?? '') ?>"
           onkeyup="filterCaddieRows(this.value)"
           id="caddieSearch"/>
  </div>
</div>

<div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap">

  <!-- Caddie table -->
  <div class="card" style="flex:1;min-width:0;padding:0;overflow:hidden">
    <?php if (empty($caddies)): ?>
    <div style="padding:56px 24px;text-align:center;color:var(--muted)">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="44" height="44" style="margin:0 auto 14px;display:block;opacity:.25"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
      <p style="font-size:.92rem;font-weight:600;margin-bottom:4px">No caddies found</p>
      <p style="font-size:.82rem;margin-bottom:16px">Add your first caddie to get started.</p>
      <button class="btn btn-primary btn-sm" onclick="showModal('addCaddieModal')">Add Caddie</button>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
      <table class="table" id="caddieTable">
        <thead>
          <tr>
            <th>Name</th>
            <th>Specialty</th>
            <th>Exp.</th>
            <th>Rate</th>
            <th>Rating</th>
            <th>Status</th>
            <th style="width:90px"></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($caddies as $c): ?>
        <?php $isSelected = isset($selected) && $selected && (int)$selected['id'] === (int)$c['id']; ?>
        <tr class="caddie-row <?= $isSelected ? 'row-selected' : '' ?>"
            style="cursor:pointer"
            id="cr-<?= (int)$c['id'] ?>"
            onclick="selectCaddie(<?= (int)$c['id'] ?>)">
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div class="avatar" style="font-size:.7rem;flex-shrink:0;width:34px;height:34px">
                <?= strtoupper(substr($c['name'], 0, 2)) ?>
              </div>
              <div>
                <p style="font-weight:600;margin:0;font-size:.86rem;line-height:1.3"><?= e($c['name']) ?></p>
                <?php if ($c['phone']): ?>
                <p style="font-size:.76rem;color:var(--muted);margin:0"><?= e($c['phone']) ?></p>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td style="font-size:.83rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            <?= e($c['specialty'] ?: '—') ?>
          </td>
          <td style="white-space:nowrap;font-size:.83rem">
            <?= (int)$c['experience_years'] ?> yr<?= (int)$c['experience_years'] !== 1 ? 's' : '' ?>
          </td>
          <td style="white-space:nowrap;font-weight:600;font-size:.83rem">
            <?= currencySymbol() ?><?= number_format((float)$c['rate_per_round'], 0) ?>
          </td>
          <td><?= renderStars((float)$c['rating']) ?></td>
          <td><span class="pill <?= $caddieStatusCls[$c['status']] ?? 'pill-gray' ?>"><?= e($c['status']) ?></span></td>
          <td onclick="event.stopPropagation()">
            <div style="display:flex;gap:3px">
              <button class="btn btn-ghost btn-sm" title="Edit" style="padding:5px 7px"
                      onclick="openEditCaddieModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
              <form method="post" style="display:inline"
                    onsubmit="return confirm('Delete <?= e(addslashes($c['name'])) ?>? Their bookings will also be removed.')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete_caddie">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button type="submit" class="btn btn-ghost btn-sm" title="Delete" style="padding:5px 7px;color:#ef4444">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6M10 11v6M14 11v6M9 6V4h6v2"/></svg>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Detail panel -->
  <?php if (!empty($selected)): ?>
  <div class="card" style="width:288px;flex-shrink:0" id="caddiePanel">

    <!-- Header -->
    <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border)">
      <div class="avatar" style="width:44px;height:44px;font-size:.8rem;flex-shrink:0"><?= strtoupper(substr($selected['name'], 0, 2)) ?></div>
      <div style="flex:1;min-width:0">
        <p style="font-weight:700;margin:0 0 4px;font-size:.95rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($selected['name']) ?></p>
        <span class="pill <?= $caddieStatusCls[$selected['status']] ?? 'pill-gray' ?>"><?= e($selected['status']) ?></span>
      </div>
      <a href="caddies.php?tab=<?= urlencode($tab) ?>" class="btn btn-ghost btn-sm" style="padding:4px 8px;font-size:.8rem;flex-shrink:0" title="Close">✕</a>
    </div>

    <!-- Profile fields -->
    <div style="display:flex;flex-direction:column;gap:9px;font-size:.84rem;margin-bottom:16px">
      <?php if ($selected['phone']): ?>
      <div style="display:flex;justify-content:space-between;gap:8px">
        <span style="color:var(--muted);flex-shrink:0">Phone</span>
        <span style="font-weight:500"><?= e($selected['phone']) ?></span>
      </div>
      <?php endif; ?>
      <div style="display:flex;justify-content:space-between;gap:8px">
        <span style="color:var(--muted);flex-shrink:0">Experience</span>
        <span style="font-weight:500"><?= (int)$selected['experience_years'] ?> years</span>
      </div>
      <?php if ($selected['specialty']): ?>
      <div style="display:flex;justify-content:space-between;gap:8px">
        <span style="color:var(--muted);flex-shrink:0">Specialty</span>
        <span style="text-align:right"><?= e($selected['specialty']) ?></span>
      </div>
      <?php endif; ?>
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
        <span style="color:var(--muted);flex-shrink:0">Rating</span>
        <?= renderStars((float)$selected['rating']) ?>
      </div>
      <div style="display:flex;justify-content:space-between;gap:8px;padding-top:8px;border-top:1px solid var(--border)">
        <span style="color:var(--muted);flex-shrink:0">Rate / round</span>
        <span style="font-weight:700;font-size:.9rem"><?= currencySymbol() ?><?= number_format((float)$selected['rate_per_round'], 2) ?></span>
      </div>
      <?php if ($selected['notes']): ?>
      <div style="padding-top:8px;border-top:1px solid var(--border)">
        <p style="color:var(--muted);margin:0 0 4px;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em">Notes</p>
        <p style="font-size:.83rem;line-height:1.5;margin:0"><?= nl2br(e($selected['notes'])) ?></p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Recent bookings -->
    <?php if (!empty($selectedBookings)): ?>
    <div style="margin-bottom:14px">
      <p style="font-size:.78rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin:0 0 8px">Recent Bookings</p>
      <div style="display:flex;flex-direction:column;gap:6px">
        <?php foreach (array_slice($selectedBookings, 0, 5) as $sb): ?>
        <div style="border-radius:8px;padding:9px 11px;background:var(--surface-2,#f8fafc)">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px">
            <span style="font-weight:600;font-size:.8rem"><?= e($sb['golfer_name']) ?></span>
            <span class="pill <?= $bookingStatusCls[$sb['status']] ?? 'pill-gray' ?>" style="font-size:.67rem;padding:1px 6px"><?= e($sb['status']) ?></span>
          </div>
          <span style="font-size:.76rem;color:var(--muted)"><?= date('M j, Y', strtotime($sb['booking_date'])) ?> · <?= SlotFormatter::format((int)$sb['tee_slot']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Action buttons -->
    <div style="display:flex;gap:8px">
      <button class="btn btn-secondary btn-sm" style="flex:1"
              onclick="openEditCaddieModal(<?= htmlspecialchars(json_encode($selected), ENT_QUOTES) ?>)">
        Edit
      </button>
      <button class="btn btn-primary btn-sm" style="flex:1"
              onclick="prefillBookingFromCaddie(<?= htmlspecialchars(json_encode($selected), ENT_QUOTES) ?>)">
        Book
      </button>
    </div>
  </div>
  <?php endif; ?>

</div>
<?php endif; ?>


<!-- ══════════════════════════════════════════════════════════
     ADD CADDIE MODAL
══════════════════════════════════════════════════════════════ -->
<div class="modal-backdrop hidden" id="addCaddieModal">
  <div class="modal" style="max-width:500px;width:100%">
    <div class="modal-header">
      <h2 style="font-size:1rem">Add Caddie</h2>
      <button class="modal-close" onclick="closeModal('addCaddieModal')">✕</button>
    </div>
    <form method="post" class="modal-body">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_caddie">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group" style="grid-column:1/-1">
          <label class="label">Full Name *</label>
          <input class="input" name="name" required placeholder="e.g. Carlos Mendez" autofocus>
        </div>
        <div class="form-group">
          <label class="label">Phone</label>
          <input class="input" name="phone" placeholder="(555) 000-0000">
        </div>
        <div class="form-group">
          <label class="label">Status</label>
          <select class="input" name="status">
            <option value="Available">Available</option>
            <option value="Unavailable">Unavailable</option>
            <option value="On Leave">On Leave</option>
          </select>
        </div>
        <div class="form-group">
          <label class="label">Experience (years)</label>
          <input class="input" name="experience_years" type="number" min="0" max="50" value="1">
        </div>
        <div class="form-group">
          <label class="label">Rate / Round (<?= currencySymbol() ?>)</label>
          <input class="input" name="rate_per_round" type="number" step="0.01" min="0" value="50">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="label">Specialty / Skills</label>
          <input class="input" name="specialty" placeholder="e.g. Course knowledge, Club selection">
        </div>
        <div class="form-group">
          <label class="label">Rating (0–5)</label>
          <input class="input" name="rating" type="number" step="0.1" min="0" max="5" value="4.0">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="label">Notes</label>
          <textarea class="input" name="notes" rows="2" placeholder="Internal notes…" style="resize:vertical"></textarea>
        </div>
      </div>
      <div class="modal-footer" style="margin-top:16px">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addCaddieModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Caddie</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     EDIT CADDIE MODAL
══════════════════════════════════════════════════════════════ -->
<div class="modal-backdrop hidden" id="editCaddieModal">
  <div class="modal" style="max-width:500px;width:100%">
    <div class="modal-header">
      <h2 style="font-size:1rem">Edit Caddie</h2>
      <button class="modal-close" onclick="closeModal('editCaddieModal')">✕</button>
    </div>
    <form method="post" class="modal-body" id="editCaddieForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="edit_caddie">
      <input type="hidden" name="id" id="ecId">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group" style="grid-column:1/-1">
          <label class="label">Full Name *</label>
          <input class="input" name="name" id="ecName" required>
        </div>
        <div class="form-group">
          <label class="label">Phone</label>
          <input class="input" name="phone" id="ecPhone">
        </div>
        <div class="form-group">
          <label class="label">Status</label>
          <select class="input" name="status" id="ecStatus">
            <option value="Available">Available</option>
            <option value="Unavailable">Unavailable</option>
            <option value="On Leave">On Leave</option>
          </select>
        </div>
        <div class="form-group">
          <label class="label">Experience (years)</label>
          <input class="input" name="experience_years" id="ecExp" type="number" min="0" max="50">
        </div>
        <div class="form-group">
          <label class="label">Rate / Round (<?= currencySymbol() ?>)</label>
          <input class="input" name="rate_per_round" id="ecRate" type="number" step="0.01" min="0">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="label">Specialty / Skills</label>
          <input class="input" name="specialty" id="ecSpecialty">
        </div>
        <div class="form-group">
          <label class="label">Rating (0–5)</label>
          <input class="input" name="rating" id="ecRating" type="number" step="0.1" min="0" max="5">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="label">Notes</label>
          <textarea class="input" name="notes" id="ecNotes" rows="2" style="resize:vertical"></textarea>
        </div>
      </div>
      <div class="modal-footer" style="margin-top:16px">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editCaddieModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     ADD BOOKING MODAL
══════════════════════════════════════════════════════════════ -->
<div class="modal-backdrop hidden" id="addBookingModal">
  <div class="modal" style="max-width:520px;width:100%">
    <div class="modal-header">
      <h2 style="font-size:1rem">New Caddie Booking</h2>
      <button class="modal-close" onclick="closeModal('addBookingModal')">✕</button>
    </div>
    <form method="post" class="modal-body">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_booking">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group" style="grid-column:1/-1">
          <label class="label">Caddie *</label>
          <select class="input" name="caddie_id" id="abCaddie" required onchange="syncFee(this,'abFee')">
            <option value="">— Select caddie —</option>
            <?php foreach ($caddies ?? ($allCaddies ?? []) as $c): ?>
            <option value="<?= (int)$c['id'] ?>" data-rate="<?= number_format((float)$c['rate_per_round'], 2) ?>">
              <?= e($c['name']) ?> — <?= currencySymbol() ?><?= number_format((float)$c['rate_per_round'], 0) ?>/round
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="label">Golfer Name *</label>
          <input class="input" name="golfer_name" required placeholder="Full name" list="memberNameList">
        </div>
        <div class="form-group">
          <label class="label">Booking Date *</label>
          <input class="input" name="booking_date" type="date" required value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label class="label">Tee Time *</label>
          <select class="input" name="tee_slot" required>
            <?php for ($m = 420; $m <= 1080; $m += 10): ?>
            <option value="<?= $m ?>"><?= SlotFormatter::format($m) ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="label">Tee</label>
          <select class="input" name="tee">
            <option value="0">Hole 1</option>
            <option value="1">Hole 10</option>
          </select>
        </div>
        <div class="form-group">
          <label class="label">Holes</label>
          <select class="input" name="holes">
            <option value="18">18 holes</option>
            <option value="9">9 holes</option>
          </select>
        </div>
        <div class="form-group">
          <label class="label">Party Size</label>
          <select class="input" name="party">
            <?php for ($p = 1; $p <= 4; $p++): ?>
            <option value="<?= $p ?>"><?= $p ?> player<?= $p > 1 ? 's' : '' ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="label">Fee (<?= currencySymbol() ?>)</label>
          <input class="input" name="fee" id="abFee" type="number" step="0.01" min="0" value="0">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="label">Notes</label>
          <textarea class="input" name="notes" rows="2" placeholder="Special requests…" style="resize:vertical"></textarea>
        </div>
      </div>
      <div class="modal-footer" style="margin-top:16px">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addBookingModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Confirm Booking</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     EDIT BOOKING MODAL
══════════════════════════════════════════════════════════════ -->
<div class="modal-backdrop hidden" id="editBookingModal">
  <div class="modal" style="max-width:520px;width:100%">
    <div class="modal-header">
      <h2 style="font-size:1rem">Edit Booking</h2>
      <button class="modal-close" onclick="closeModal('editBookingModal')">✕</button>
    </div>
    <form method="post" class="modal-body" id="editBookingForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="edit_booking">
      <input type="hidden" name="id" id="ebId">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group" style="grid-column:1/-1">
          <label class="label">Caddie *</label>
          <select class="input" name="caddie_id" id="ebCaddie" required>
            <?php foreach ($allCaddies ?? $caddies ?? [] as $c): ?>
            <option value="<?= (int)$c['id'] ?>" data-rate="<?= number_format((float)$c['rate_per_round'], 2) ?>">
              <?= e($c['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="label">Golfer Name *</label>
          <input class="input" name="golfer_name" id="ebGolfer" required list="memberNameList">
        </div>
        <div class="form-group">
          <label class="label">Date *</label>
          <input class="input" name="booking_date" id="ebDate" type="date" required>
        </div>
        <div class="form-group">
          <label class="label">Tee Time *</label>
          <select class="input" name="tee_slot" id="ebSlot" required>
            <?php for ($m = 420; $m <= 1080; $m += 10): ?>
            <option value="<?= $m ?>"><?= SlotFormatter::format($m) ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="label">Tee</label>
          <select class="input" name="tee" id="ebTee">
            <option value="0">Hole 1</option>
            <option value="1">Hole 10</option>
          </select>
        </div>
        <div class="form-group">
          <label class="label">Holes</label>
          <select class="input" name="holes" id="ebHoles">
            <option value="18">18 holes</option>
            <option value="9">9 holes</option>
          </select>
        </div>
        <div class="form-group">
          <label class="label">Party</label>
          <select class="input" name="party" id="ebParty">
            <?php for ($p = 1; $p <= 4; $p++): ?>
            <option value="<?= $p ?>"><?= $p ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="label">Fee (<?= currencySymbol() ?>)</label>
          <input class="input" name="fee" id="ebFee" type="number" step="0.01" min="0">
        </div>
        <div class="form-group">
          <label class="label">Status</label>
          <select class="input" name="status" id="ebStatus">
            <option value="Confirmed">Confirmed</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
            <option value="No-show">No-show</option>
          </select>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="label">Notes</label>
          <textarea class="input" name="notes" id="ebNotes" rows="2" style="resize:vertical"></textarea>
        </div>
      </div>
      <div class="modal-footer" style="margin-top:16px">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editBookingModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Member name autocomplete -->
<datalist id="memberNameList">
  <?php foreach ($members ?? [] as $m): ?>
  <option value="<?= e($m['name']) ?>">
  <?php endforeach; ?>
</datalist>

<script>
// ── Client-side caddie search ──────────────────────────────
function filterCaddieRows(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#caddieTable tbody tr.caddie-row').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ── Navigate to caddie detail ──────────────────────────────
function selectCaddie(id) {
  window.location.href = 'caddies.php?tab=<?= urlencode($tab) ?>&selected=' + id;
}

// ── Sync fee when caddie selected ──────────────────────────
function syncFee(selectEl, feeId) {
  const opt  = selectEl.options[selectEl.selectedIndex];
  const rate = parseFloat(opt.getAttribute('data-rate') || '0');
  const el   = document.getElementById(feeId);
  if (el && !isNaN(rate)) el.value = rate.toFixed(2);
}

// ── Date range filter ──────────────────────────────────────
function applyDateFilter() {
  const from   = document.getElementById('bkFrom')?.value || '';
  const to     = document.getElementById('bkTo')?.value   || '';
  const params = new URLSearchParams(window.location.search);
  params.set('tab', 'bookings');
  from ? params.set('date_from', from) : params.delete('date_from');
  to   ? params.set('date_to',   to)   : params.delete('date_to');
  window.location.href = 'caddies.php?' + params.toString();
}

// ── Open edit caddie modal ─────────────────────────────────
function openEditCaddieModal(d) {
  document.getElementById('ecId').value       = d.id;
  document.getElementById('ecName').value     = d.name;
  document.getElementById('ecPhone').value    = d.phone   || '';
  document.getElementById('ecStatus').value   = d.status;
  document.getElementById('ecExp').value      = d.experience_years;
  document.getElementById('ecRate').value     = parseFloat(d.rate_per_round).toFixed(2);
  document.getElementById('ecSpecialty').value = d.specialty || '';
  document.getElementById('ecRating').value   = d.rating;
  document.getElementById('ecNotes').value    = d.notes   || '';
  showModal('editCaddieModal');
}

// ── Pre-fill booking from detail panel "Book" button ───────
function prefillBookingFromCaddie(c) {
  const sel = document.getElementById('abCaddie');
  if (sel) {
    for (const opt of sel.options) {
      if (parseInt(opt.value) === parseInt(c.id)) {
        sel.value = opt.value;
        const fee = document.getElementById('abFee');
        if (fee) fee.value = parseFloat(c.rate_per_round).toFixed(2);
        break;
      }
    }
  }
  // switch to bookings tab & open modal
  window.history.replaceState(null,'','caddies.php?tab=bookings');
  showModal('addBookingModal');
}

// ── Open edit booking modal ────────────────────────────────
function openEditBookingModal(bk) {
  document.getElementById('ebId').value     = bk.id;
  document.getElementById('ebGolfer').value = bk.golfer_name;
  document.getElementById('ebDate').value   = bk.booking_date;
  document.getElementById('ebSlot').value   = bk.tee_slot;
  document.getElementById('ebTee').value    = bk.tee;
  document.getElementById('ebHoles').value  = bk.holes;
  document.getElementById('ebParty').value  = bk.party;
  document.getElementById('ebFee').value    = parseFloat(bk.fee || 0).toFixed(2);
  document.getElementById('ebStatus').value = bk.status;
  document.getElementById('ebNotes').value  = bk.notes || '';
  const cs = document.getElementById('ebCaddie');
  if (cs) cs.value = bk.caddie_id;
  showModal('editBookingModal');
}

// ── Scroll to selected row on load ─────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.querySelector('.row-selected')?.scrollIntoView({ behavior:'smooth', block:'center' });
});
</script>
