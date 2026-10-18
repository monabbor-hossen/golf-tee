<?php
declare(strict_types=1);

use App\Domain\SlotFormatter;

$formats = ['Scramble', 'Stroke play', 'Best ball', 'Match play', 'Stableford', 'Clinic'];
$slots   = range(420, 1070, 30);
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Events</h1>
    <p>Tournaments, clinics and leagues</p>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-primary" onclick="showModal('createEventModal')">+ Create event</button>
  </div>
</div>

<div class="tab-bar">
  <a href="events.php?tab=upcoming" class="tab-btn <?= $tab === 'upcoming' ? 'active' : '' ?>">Upcoming</a>
  <a href="events.php?tab=past"     class="tab-btn <?= $tab === 'past' ? 'active' : '' ?>">Past</a>
</div>

<div class="panel-layout">
  <!-- Event list -->
  <div class="panel-main" style="display:flex;flex-direction:column;gap:10px">
    <?php foreach ($events as $e):
      $regPct = $e['capacity'] > 0 ? round($e['reg_count'] / $e['capacity'] * 100) : 0;
      $isSel  = $selectedId == $e['id'];
    ?>
    <a href="events.php?tab=<?= $tab ?>&id=<?= $e['id'] ?>" class="card" style="text-decoration:none;border-color:<?= $isSel ? 'var(--forest)' : 'var(--border)' ?>;transition:border-color .15s">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
        <div>
          <p style="font-weight:700"><?= htmlspecialchars($e['name']) ?></p>
          <p style="font-size:.8rem;color:#6b7280;margin-top:2px">
            <?= date('D, M j', strtotime($e['event_date'])) ?> · <?= SlotFormatter::format((int)$e['start_slot']) ?> · <?= $e['hours'] ?>h · <?= $e['format'] ?>
          </p>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
          <span class="pill <?= $e['is_open'] ? 'pill-green' : 'pill-gray' ?>"><?= $e['is_open'] ? 'Open' : 'Closed' ?></span>
          <span style="font-size:.75rem;color:#6b7280">$<?= number_format((float)$e['fee']) ?> entry</span>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:8px;margin-top:10px">
        <div class="event-fill" style="flex:1"><div class="event-fill-bar" style="width:<?= $regPct ?>%"></div></div>
        <span style="font-size:.75rem;font-weight:600;color:#6b7280"><?= $e['reg_count'] ?>/<?= $e['capacity'] ?></span>
      </div>
    </a>
    <?php endforeach; ?>
    <?php if (empty($events)): ?>
    <div class="card" style="text-align:center;color:#9ca3af;padding:40px">No events found</div>
    <?php endif; ?>
  </div>

  <!-- Event detail -->
  <?php if ($selectedEvent): ?>
  <div class="detail-panel">
    <div class="panel-header">
      <div style="min-width:0">
        <p style="font-weight:700;font-size:.9rem"><?= htmlspecialchars($selectedEvent['name']) ?></p>
        <p style="font-size:.75rem;color:#9ca3af"><?= date('D, M j', strtotime($selectedEvent['event_date'])) ?> · <?= SlotFormatter::format((int)$selectedEvent['start_slot']) ?></p>
      </div>
      <div style="display:flex;gap:6px">
        <form method="post" style="margin:0">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="toggle_open">
          <input type="hidden" name="id" value="<?= $selectedEvent['id'] ?>">
          <input type="hidden" name="is_open" value="<?= $selectedEvent['is_open'] ?>">
          <button type="submit" class="btn btn-ghost btn-sm"><?= $selectedEvent['is_open'] ? 'Close reg' : 'Open reg' ?></button>
        </form>
        <form method="post" style="margin:0" onsubmit="return confirm('Delete this event?')">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $selectedEvent['id'] ?>">
          <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger)">🗑</button>
        </form>
      </div>
    </div>
    <div class="panel-body">
      <div class="info-row"><span class="info-label">Format</span><span class="info-val"><?= htmlspecialchars($selectedEvent['format']) ?></span></div>
      <div class="info-row"><span class="info-label">Duration</span><span class="info-val"><?= $selectedEvent['hours'] ?> hours</span></div>
      <div class="info-row"><span class="info-label">Capacity</span><span class="info-val"><?= $selectedEvent['reg_count'] ?>/<?= $selectedEvent['capacity'] ?></span></div>
      <div class="info-row"><span class="info-label">Entry fee</span><span class="info-val">$<?= number_format((float)$selectedEvent['fee']) ?></span></div>
      <div class="info-row"><span class="info-label">Revenue</span><span class="info-val">$<?= number_format($selectedEvent['reg_count'] * (float)$selectedEvent['fee']) ?></span></div>

      <!-- Progress -->
      <div class="event-fill" style="margin-top:12px;margin-bottom:4px">
        <?php $pct = $selectedEvent['capacity'] > 0 ? round($selectedEvent['reg_count'] / $selectedEvent['capacity'] * 100) : 0; ?>
        <div class="event-fill-bar" style="width:<?= $pct ?>%"></div>
      </div>
      <p style="font-size:.75rem;color:#6b7280"><?= $pct ?>% full · <?= $selectedEvent['capacity'] - $selectedEvent['reg_count'] ?> spots remaining</p>

      <!-- Registrations -->
      <p style="font-size:.75rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin-top:16px;margin-bottom:8px">
        Registrations (<?= count($registrations) ?>)
      </p>
      <!-- Add registration -->
      <?php if ($selectedEvent['is_open'] && $selectedEvent['reg_count'] < $selectedEvent['capacity']): ?>
      <form method="post" style="display:flex;gap:6px;margin-bottom:10px">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="register">
        <input type="hidden" name="event_id" value="<?= $selectedEvent['id'] ?>">
        <input name="name" class="input" style="height:32px;font-size:.8rem" placeholder="Add name…" required list="event-member-names"/>
        <datalist id="event-member-names">
          <?php foreach ($allMemberNames as $m): ?>
            <option value="<?= htmlspecialchars($m['name']) ?>">
          <?php endforeach; ?>
        </datalist>
        <button type="submit" class="btn btn-primary btn-sm">+ Add</button>
      </form>
      <?php endif; ?>

      <?php foreach ($registrations as $r): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #f9fafb;font-size:.875rem">
        <span><?= htmlspecialchars($r['name']) ?></span>
        <form method="post" style="margin:0">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="unregister">
          <input type="hidden" name="reg_id" value="<?= $r['id'] ?>">
          <input type="hidden" name="event_id" value="<?= $selectedEvent['id'] ?>">
          <button type="submit" class="btn btn-ghost btn-sm" style="color:#9ca3af;height:24px;padding:0 6px">✕</button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Create Event Modal -->
<div class="modal-backdrop hidden" id="createEventModal">
  <div class="modal wide">
    <div class="modal-header">
      <div><p class="modal-title">Create event</p><p class="modal-desc">Tournaments, clinics and leagues members can register for.</p></div>
      <button class="close-btn" onclick="closeModal('createEventModal')">✕</button>
    </div>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="field-group"><label class="field-label">Event name</label><input name="name" class="input" required placeholder="e.g. Spring Member-Guest"/></div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
          <div class="field-group">
            <label class="field-label">Date</label>
            <input type="date" name="event_date" class="input" min="<?= $today ?>" required/>
          </div>
          <div class="field-group">
            <label class="field-label">Start time</label>
            <select name="start_slot" class="input select-field">
              <?php foreach ($slots as $s): ?><option value="<?= $s ?>"><?= SlotFormatter::format($s) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label">Duration</label>
            <select name="hours" class="input select-field">
              <?php foreach ([1, 2, 3, 4, 5, 6, 8] as $h): ?><option value="<?= $h ?>"><?= $h ?> hours</option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
          <div class="field-group">
            <label class="field-label">Format</label>
            <select name="format" class="input select-field">
              <?php foreach ($formats as $f): ?><option><?= $f ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field-group"><label class="field-label">Capacity</label><input type="number" name="capacity" class="input" value="48" min="1"/></div>
          <div class="field-group">
            <label class="field-label">Entry fee</label>
            <div class="price-prefix"><span>$</span><input type="number" name="fee" min="0" class="input" value="75" style="padding-left:22px"/></div>
          </div>
        </div>
        <label style="display:flex;align-items:center;gap:10px;font-size:.875rem;font-weight:600">
          <input type="checkbox" name="is_blocked" value="1" checked style="width:16px;height:16px;accent-color:var(--forest)"/>
          Block the tee sheet during event hours
        </label>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('createEventModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create event</button>
      </div>
    </form>
  </div>
</div>
