<?php
declare(strict_types=1);

use App\Domain\SlotFormatter;

$totalPlayers = array_sum(array_column($bookingMap, 'party'));
$booked = count(array_filter($bookingMap, fn($b) => ($b['status'] ?? '') !== 'No-show'));
$checkedIn = count(array_filter($bookingMap, fn($b) => ($b['status'] ?? '') === 'Checked in'));
$blocked = count($blockMap);
$totalSlots = count($slots) * 2;
$util = $totalSlots > 0 ? (int)round(($booked / max(1, $totalSlots - $blocked)) * 100) : 0;
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Master Grid</h1>
    <p>Tee sheet — <?= htmlspecialchars($dateLabel) ?>, <?= date('M j, Y', strtotime($dateStr)) ?></p>
  </div>
  <div class="page-header-actions">
    <div class="grid-date-nav">
      <a href="mastergrid.php?offset=<?= $offset - 1 ?>" class="btn btn-ghost btn-sm">‹ Prev</a>
      <a href="mastergrid.php?offset=0" class="btn btn-ghost btn-sm" style="<?= $offset === 0 ? 'color:var(--forest);font-weight:700;' : ''; ?>">Today</a>
      <a href="mastergrid.php?offset=<?= $offset + 1 ?>" class="btn btn-ghost btn-sm">Next ›</a>
    </div>
    <button class="btn btn-primary" onclick="showModal('quickBookModal')">+ New Booking</button>
  </div>
</div>

<!-- Day stats bar -->
<div class="stat-grid">
  <div class="stat-card"><p class="stat-label">Groups booked</p><p class="stat-value"><?= $booked ?></p></div>
  <div class="stat-card"><p class="stat-label">Players</p><p class="stat-value"><?= $totalPlayers ?></p></div>
  <div class="stat-card"><p class="stat-label">Checked in</p><p class="stat-value green" style="color:var(--forest)"><?= $checkedIn ?></p></div>
  <div class="stat-card"><p class="stat-label">Utilization</p><p class="stat-value"><?= $util ?>%</p><div class="stat-bar"><div class="stat-bar-fill" style="width:<?= $util ?>%"></div></div></div>
</div>

<!-- Tee Grid -->
<div class="card" style="padding:0;overflow:hidden">
  <div class="tee-grid">
    <table class="tee-table">
      <thead>
        <tr>
          <th style="width:80px">Time</th>
          <?php foreach ($tees as $ti => $tname): ?>
          <th><?= $tname ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($slots as $slot):
        $isPast = $offset < 0 || ($offset === 0 && $slot < $nowMin);
        $isNow  = $offset === 0 && $slot >= $nowMin - 10 && $slot < $nowMin + 10;
        $isHighlight = $focusKey && str_contains($focusKey, "-{$slot}");
      ?>
      <tr <?= $isHighlight ? 'class="selected"' : '' ?>>
        <td class="slot-time <?= $isNow ? 'slot-now' : '' ?>">
          <?= SlotFormatter::format($slot) ?>
          <?php if ($isNow): ?><span style="font-size:.6rem;background:var(--forest);color:#fff;border-radius:4px;padding:1px 4px;display:block;margin-top:2px">NOW</span><?php endif; ?>
        </td>
        <?php foreach ($tees as $ti => $tname):
          $key = "{$ti}-{$slot}";
          $booking = $bookingMap[$key] ?? null;
          $block   = $blockMap[$key] ?? null;
        ?>
        <td class="tee-cell" id="cell-<?= $key ?>">
          <?php if ($block): ?>
          <div class="block-chip" style="display:inline-flex;align-items:center;gap:5px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
            </svg>
            <span><?= htmlspecialchars($block['reason']) ?></span>
            <?php if (!$isPast): ?>
            <form method="post" style="display:inline;margin-left:6px">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="unblock">
              <input type="hidden" name="block_id" value="<?= $block['id'] ?>">
              <button type="submit" style="color:#9ca3af;background:none;border:none;cursor:pointer;padding:0;display:inline-flex;align-items:center" aria-label="Unblock">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="10" height="10" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/>
                  <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </form>
            <?php endif; ?>
          </div>
          <?php elseif ($booking): ?>
            <?php
            $cls = $booking['status'] === 'Checked in' ? 'checked-in' :
                   ($booking['status'] === 'No-show' ? 'no-show' :
                   ($isPast && $booking['status'] === 'Booked' ? 'late' : ''));
            ?>
            <div class="booking-chip <?= $cls ?>">
              <div class="booking-chip-header">
                <div style="flex:1;min-width:0">
                  <p class="booking-name"><?= htmlspecialchars($booking['name']) ?></p>
                  <p class="booking-meta"><?= $booking['party'] ?> players · <?= $booking['holes'] ?>h</p>
                </div>
                <span class="pill <?= $booking['status']==='Checked in'?'pill-green':($booking['status']==='No-show'?'pill-red':'pill-gray') ?>"
                      style="font-size:.65rem"><?= $booking['status'] ?></span>
              </div>
              <?php if (!$isPast || $booking['status'] === 'Booked'): ?>
              <div class="booking-chip-actions">
                <?php if ($booking['status'] === 'Booked'): ?>
                <form method="post" style="margin:0">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="checkin">
                  <input type="hidden" name="bid" value="<?= $booking['id'] ?>">
                  <button type="submit" class="btn btn-primary btn-xs">Check in</button>
                </form>
                <form method="post" style="margin:0">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="noshow">
                  <input type="hidden" name="bid" value="<?= $booking['id'] ?>">
                  <button type="submit" class="btn btn-ghost btn-xs" style="color:#9ca3af">No-show</button>
                </form>
                <?php endif; ?>
                <form method="post" style="margin:0" onsubmit="return confirm('Cancel this booking?')">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="cancel">
                  <input type="hidden" name="bid" value="<?= $booking['id'] ?>">
                  <button type="submit" class="btn btn-ghost btn-xs" style="color:var(--danger);padding:2px 4px;display:inline-flex;align-items:center" aria-label="Cancel booking">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="18" y1="6" x2="6" y2="18"/>
                      <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                  </button>
                </form>
              </div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <?php if (!$isPast): ?>
            <button class="add-chip" onclick="openQuickBook(<?= $slot ?>, <?= $ti ?>)">
              + Add booking
            </button>
            <button class="add-chip" onclick="openBlockSlot(<?= $slot ?>, <?= $ti ?>)" style="margin-top:4px;color:#9ca3af;display:inline-flex;align-items:center;gap:4px">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
              </svg>
              Block
            </button>
            <?php else: ?>
            <span style="color:#e5e7eb;font-size:.75rem;padding:4px 8px">—</span>
            <?php endif; ?>
          <?php endif; ?>
        </td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Quick Book Modal -->
<div class="modal-backdrop hidden" id="quickBookModal">
  <div class="modal">
    <div class="modal-header">
      <div><p class="modal-title">New Booking</p><p class="modal-desc"><?= htmlspecialchars($dateLabel) ?></p></div>
      <button class="close-btn" onclick="closeModal('quickBookModal')" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="book">
      <div class="modal-body">
        <div class="field-group">
          <label class="field-label">Name</label>
          <input name="name" class="input" required placeholder="Member or guest name" list="grid-member-names"/>
          <datalist id="grid-member-names">
            <?php foreach ($activeMemberNames as $m): ?>
              <option value="<?= htmlspecialchars($m['name']) ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="modal-grid-2">
          <div class="field-group">
            <label class="field-label">Time</label>
            <select name="tee_slot" class="input select-field" id="qbSlot">
              <?php foreach ($slots as $s): ?><option value="<?= $s ?>"><?= SlotFormatter::format($s) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label">Tee</label>
            <select name="tee" class="input select-field" id="qbTee">
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
        <button type="button" class="btn btn-ghost" onclick="closeModal('quickBookModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Book tee time</button>
      </div>
    </form>
  </div>
</div>

<!-- Block Slot Modal -->
<div class="modal-backdrop hidden" id="blockSlotModal">
  <div class="modal" style="max-width:380px">
    <div class="modal-header">
      <div><p class="modal-title">Block slot</p></div>
      <button class="close-btn" onclick="closeModal('blockSlotModal')" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="block">
      <input type="hidden" name="tee_slot" id="blockSlot">
      <input type="hidden" name="tee" id="blockTee">
      <div class="modal-body">
        <div class="field-group">
          <label class="field-label">Reason</label>
          <select name="reason" class="input select-field">
            <option>Maintenance</option><option>Staff event</option><option>Course closed</option><option>Reserved</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('blockSlotModal')">Cancel</button>
        <button type="submit" class="btn btn-secondary" style="display:inline-flex;align-items:center;gap:6px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
          </svg>
          Block slot
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openQuickBook(slot, tee) {
  showModal('quickBookModal');
  document.getElementById('qbSlot').value = slot;
  document.getElementById('qbTee').value  = tee;
}
function openBlockSlot(slot, tee) {
  showModal('blockSlotModal');
  document.getElementById('blockSlot').value = slot;
  document.getElementById('blockTee').value  = tee;
}
document.addEventListener('DOMContentLoaded', () => {
  const focus = '<?= htmlspecialchars($focusKey ?? '') ?>';
  if (focus) {
    const el = document.getElementById('cell-' + focus);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
});
</script>
