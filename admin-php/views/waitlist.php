<?php
declare(strict_types=1);

use App\Domain\SlotFormatter;
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Waitlist</h1>
    <p><?= count($rows) ?> entries</p>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-primary" onclick="showModal('addWaitModal')" style="display:inline-flex;align-items:center;gap:6px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/>
        <line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Add to waitlist
    </button>
  </div>
</div>

<div class="tab-bar">
  <a href="waitlist.php?tab=upcoming" class="tab-btn <?= $tab === 'upcoming' ? 'active' : '' ?>">Upcoming</a>
  <a href="waitlist.php?tab=past"     class="tab-btn <?= $tab === 'past' ? 'active' : '' ?>">Past</a>
</div>

<div class="card">
  <?php if (empty($rows)): ?>
    <p style="color:#9ca3af;text-align:center;padding:40px;font-size:.875rem">Waitlist is empty</p>
  <?php else: ?>
  <?php foreach ($rows as $i => $w):
    $dateLabel = ($w['day_offset'] == 0) ? 'Today' : (($w['day_offset'] == 1) ? 'Tomorrow' : date('D, M j', strtotime($w['tee_date'])));
    $isOffered = ($w['status'] === 'Offered');
  ?>
  <div class="wait-row">
    <span class="wait-index"><?= $i + 1 ?></span>
    <div style="flex:1;min-width:0">
      <p style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($w['name']) ?></p>
      <p style="font-size:.75rem;color:#6b7280"><?= $dateLabel ?> · <?= SlotFormatter::format((int)$w['tee_slot']) ?> · <?= $w['party'] ?> players · <?= $w['holes'] ?> holes</p>
    </div>
    <?php if ($isOffered): ?>
      <span class="pill pill-amber">Offered</span>
    <?php else: ?>
      <span class="pill pill-gray">Waiting</span>
    <?php endif; ?>
    <div class="wait-row-actions" style="display:flex;gap:6px">
      <?php if (!$isOffered): ?>
      <form method="post" style="margin:0">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="offer">
        <input type="hidden" name="id" value="<?= $w['id'] ?>">
        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--forest)">Send offer</button>
      </form>
      <?php endif; ?>
      <form method="post" style="margin:0">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="book_from_wait">
        <input type="hidden" name="wid" value="<?= $w['id'] ?>">
        <button type="submit" class="btn btn-primary btn-sm">Book now</button>
      </form>
      <form method="post" style="margin:0" onsubmit="return confirm('Remove from waitlist?')">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="remove">
        <input type="hidden" name="id" value="<?= $w['id'] ?>">
        <button type="submit" class="btn btn-ghost btn-sm" style="color:#9ca3af;padding:4px 8px;display:inline-flex;align-items:center" aria-label="Remove from waitlist">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Add to Waitlist Modal -->
<div class="modal-backdrop hidden" id="addWaitModal">
  <div class="modal">
    <div class="modal-header">
      <div><p class="modal-title">Add to waitlist</p></div>
      <button class="close-btn" onclick="closeModal('addWaitModal')" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="field-group">
          <label class="field-label">Name</label>
          <input name="name" class="input" required placeholder="Member or guest" list="wait-member-names"/>
          <datalist id="wait-member-names">
            <?php foreach ($memberNames as $m): ?>
              <option value="<?= htmlspecialchars($m['name']) ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="modal-grid-2">
          <div class="field-group">
            <label class="field-label">Date</label>
            <input type="date" name="tee_date" class="input" min="<?= $today ?>" value="<?= $today ?>"/>
          </div>
          <div class="field-group">
            <label class="field-label">Preferred time</label>
            <select name="tee_slot" class="input select-field">
              <?php for ($m = 420; $m <= 1070; $m += 30): ?>
              <option value="<?= $m ?>"><?= SlotFormatter::format($m) ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
        <div class="modal-grid-2">
          <div class="field-group">
            <label class="field-label">Players</label>
            <select name="party" class="input select-field">
              <option>1</option><option>2</option><option selected>2</option><option>3</option><option>4</option>
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
        <button type="button" class="btn btn-ghost" onclick="closeModal('addWaitModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add to waitlist</button>
      </div>
    </form>
  </div>
</div>
