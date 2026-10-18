<?php
declare(strict_types=1);

use App\Domain\SlotFormatter;

$tierCls   = ['Gold' => 'pill-gold', 'Silver' => 'pill-silver', 'Standard' => 'pill-sand'];
$statusCls = ['Active' => 'pill-green', 'Suspended' => 'pill-red', 'Pending' => 'pill-amber'];
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Members</h1>
    <p><?= $total ?> total members · <?= $counts['Pending'] ?? 0 ?> pending approval</p>
  </div>
  <div class="page-header-actions">
    <a href="members.php?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-secondary">⬇ Export CSV</a>
    <button class="btn btn-primary" onclick="showModal('addMemberModal')">+ Add Member</button>
  </div>
</div>

<!-- Filters -->
<div class="filters">
  <div class="filter-search" style="max-width:280px">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM21 21l-4.3-4.3"/></svg>
    <input class="input" type="text" name="q" placeholder="Search members…"
           value="<?= htmlspecialchars($query) ?>" onkeyup="applyFilter()" id="searchQ"/>
  </div>
  <div class="segmented" id="statusSeg">
    <?php foreach (['All', 'Active', 'Pending', 'Suspended'] as $s): ?>
    <button class="<?= $status === $s ? 'active' : '' ?>" onclick="setFilter('status','<?= $s ?>')">
      <?= $s ?><?= $s !== 'All' && isset($counts[$s]) ? ' (' . $counts[$s] . ')' : ($s === 'All' ? ' (' . $total . ')' : '') ?>
    </button>
    <?php endforeach; ?>
  </div>
  <div class="segmented">
    <?php foreach (['All', 'Gold', 'Silver', 'Standard'] as $t): ?>
    <button class="<?= $tier === $t ? 'active' : '' ?>" onclick="setFilter('tier','<?= $t ?>')"><?= $t ?></button>
    <?php endforeach; ?>
  </div>
</div>

<!-- Table + Detail Panel -->
<div class="panel-layout">
  <div class="panel-main">
    <div class="card" style="padding:0;overflow:hidden">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'name', 'dir' => $sort === 'name' && $dir === 'asc' ? 'desc' : 'asc'])) ?>">Name <?= $sort === 'name' ? ($dir === 'asc' ? '↑' : '↓') : '' ?></a></th>
              <th>Tier</th>
              <th>Status</th>
              <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'hcp', 'dir' => $sort === 'hcp' && $dir === 'asc' ? 'desc' : 'asc'])) ?>">HCP <?= $sort === 'hcp' ? ($dir === 'asc' ? '↑' : '↓') : '' ?></a></th>
              <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'rounds', 'dir' => $sort === 'rounds' && $dir === 'asc' ? 'desc' : 'asc'])) ?>">Rounds <?= $sort === 'rounds' ? ($dir === 'asc' ? '↑' : '↓') : '' ?></a></th>
              <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'spend', 'dir' => $sort === 'spend' && $dir === 'asc' ? 'desc' : 'asc'])) ?>">Spend <?= $sort === 'spend' ? ($dir === 'asc' ? '↑' : '↓') : '' ?></a></th>
              <th>Last round</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($members as $m): ?>
          <tr class="<?= $selectedId == $m['id'] ? 'selected' : '' ?>"
              onclick="window.location.href='members.php?<?= http_build_query(array_merge($_GET, ['memberId' => $m['id']])) ?>'">
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div class="avatar" style="width:32px;height:32px;font-size:.7rem"><?= strtoupper(substr($m['name'], 0, 2)) ?></div>
                <div>
                  <p style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($m['name']) ?></p>
                  <p style="font-size:.75rem;color:#9ca3af"><?= htmlspecialchars($m['email']) ?></p>
                </div>
              </div>
            </td>
            <td><span class="pill <?= $tierCls[$m['tier']] ?? 'pill-gray' ?>"><?= $m['tier'] ?></span></td>
            <td><span class="pill <?= $statusCls[$m['status']] ?? 'pill-gray' ?>"><?= $m['status'] ?></span></td>
            <td><?= $m['hcp'] ?></td>
            <td><?= $m['rounds'] ?></td>
            <td>$<?= number_format((float)$m['spend']) ?></td>
            <td style="color:#6b7280"><?= htmlspecialchars($m['last_play'] ?: '—') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($members)): ?>
          <tr><td colspan="7" style="text-align:center;color:#9ca3af;padding:40px">No members found</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Detail panel -->
  <?php if ($selectedMember): ?>
  <div class="detail-panel">
    <div class="panel-header">
      <div>
        <p style="font-weight:700"><?= htmlspecialchars($selectedMember['name']) ?></p>
        <p style="font-size:.75rem;color:#9ca3af"><?= htmlspecialchars($selectedMember['email']) ?></p>
      </div>
      <div style="display:flex;align-items:center;gap:4px">
        <button class="btn btn-ghost btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($selectedMember, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">Edit</button>
        <?php if ($selectedMember['status'] === 'Pending'): ?>
        <form method="post" style="margin:0">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="approve">
          <input type="hidden" name="id" value="<?= $selectedMember['id'] ?>">
          <button type="submit" class="btn btn-primary btn-sm">Approve</button>
        </form>
        <?php endif; ?>
        <a href="members.php" class="btn btn-ghost btn-sm hide-desktop" style="color:#9ca3af;padding:4px 8px;display:inline-flex;align-items:center" title="Close details">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </a>
      </div>
    </div>
    <div class="panel-body" style="padding:16px">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
        <div class="avatar-lg"><?= strtoupper(substr($selectedMember['name'], 0, 2)) ?></div>
        <div>
          <span class="pill <?= $tierCls[$selectedMember['tier']] ?? 'pill-sand' ?>"><?= $selectedMember['tier'] ?></span>
          <span class="pill <?= $statusCls[$selectedMember['status']] ?? 'pill-gray' ?>" style="margin-left:4px"><?= $selectedMember['status'] ?></span>
          <p style="font-size:.75rem;color:#6b7280;margin-top:4px">Joined <?= $selectedMember['joined'] ?></p>
        </div>
      </div>
      <div class="info-row"><span class="info-label">Phone</span><span class="info-val"><?= htmlspecialchars($selectedMember['phone'] ?: '—') ?></span></div>
      <div class="info-row"><span class="info-label">Handicap</span><span class="info-val"><?= $selectedMember['hcp'] ?></span></div>
      <div class="info-row"><span class="info-label">Rounds played</span><span class="info-val"><?= $selectedMember['rounds'] ?></span></div>
      <div class="info-row"><span class="info-label">Total spend</span><span class="info-val">$<?= number_format((float)$selectedMember['spend']) ?></span></div>
      <div class="info-row"><span class="info-label">Last round</span><span class="info-val"><?= htmlspecialchars($selectedMember['last_play'] ?: '—') ?></span></div>
      <?php if (!empty($selectedMember['next_tee'])): ?>
      <div class="info-row"><span class="info-label">Next tee time</span><span class="info-val" style="font-size:.75rem"><?= htmlspecialchars($selectedMember['next_tee']) ?></span></div>
      <?php endif; ?>

      <?php if (!empty($bookingHistory)): ?>
      <p style="font-size:.75rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin-top:16px;margin-bottom:8px">Booking history</p>
      <?php foreach ($bookingHistory as $h): ?>
      <div style="display:flex;justify-content:space-between;font-size:.8rem;padding:6px 0;border-bottom:1px solid #f9fafb">
        <span><?= date('M j', strtotime($h['tee_date'])) ?> · <?= SlotFormatter::format((int)$h['tee_slot']) ?></span>
        <span style="color:#6b7280"><?= $h['party'] ?> players</span>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
        <?php if ($selectedMember['status'] === 'Suspended'): ?>
        <form method="post" style="margin:0">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="approve">
          <input type="hidden" name="id" value="<?= $selectedMember['id'] ?>">
          <button type="submit" class="btn btn-ghost btn-sm">Reactivate</button>
        </form>
        <?php elseif ($selectedMember['status'] === 'Active'): ?>
        <form method="post" style="margin:0" onsubmit="return confirm('Suspend this member?')">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="suspend">
          <input type="hidden" name="id" value="<?= $selectedMember['id'] ?>">
          <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger)">Suspend</button>
        </form>
        <?php endif; ?>
        <form method="post" style="margin:0" onsubmit="return confirm('Delete this member?')">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $selectedMember['id'] ?>">
          <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);display:inline-flex;align-items:center;gap:6px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="3 6 5 6 21 6"/>
              <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
              <path d="M10 11v6"/>
              <path d="M14 11v6"/>
              <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
            </svg>
            Delete
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Add Member Modal -->
<div class="modal-backdrop hidden" id="addMemberModal">
  <div class="modal">
    <div class="modal-header">
      <div><p class="modal-title">Add member</p><p class="modal-desc">New members start as Pending until approved.</p></div>
      <button class="close-btn" onclick="closeModal('addMemberModal')" aria-label="Close">
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
        <div class="field-group"><label class="field-label">Full name</label><input name="name" class="input" required placeholder="Jane Doe"/></div>
        <div class="modal-grid-2">
          <div class="field-group"><label class="field-label">Email</label><input name="email" type="email" class="input" required placeholder="jane@mail.com"/></div>
          <div class="field-group"><label class="field-label">Phone</label><input name="phone" class="input" placeholder="(555) 000-0000"/></div>
        </div>
        <div class="modal-grid-2">
          <div class="field-group">
            <label class="field-label">Tier</label>
            <select name="tier" class="input select-field"><option>Gold</option><option>Silver</option><option selected>Standard</option></select>
          </div>
          <div class="field-group"><label class="field-label">Handicap</label><input name="hcp" type="number" step="0.1" class="input" placeholder="18.0"/></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addMemberModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add member</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Member Modal -->
<div class="modal-backdrop hidden" id="editMemberModal">
  <div class="modal">
    <div class="modal-header">
      <div><p class="modal-title">Edit member</p><p class="modal-desc">Update profile and membership details.</p></div>
      <button class="close-btn" onclick="closeModal('editMemberModal')" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <form method="post" id="editMemberForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      <div class="modal-body">
        <div class="field-group"><label class="field-label">Full name</label><input name="name" id="editName" class="input" required/></div>
        <div class="modal-grid-2">
          <div class="field-group"><label class="field-label">Email</label><input name="email" id="editEmail" type="email" class="input" required/></div>
          <div class="field-group"><label class="field-label">Phone</label><input name="phone" id="editPhone" class="input"/></div>
        </div>
        <div class="modal-grid-3">
          <div class="field-group">
            <label class="field-label">Tier</label>
            <select name="tier" id="editTier" class="input select-field"><option>Gold</option><option>Silver</option><option>Standard</option></select>
          </div>
          <div class="field-group"><label class="field-label">HCP</label><input name="hcp" id="editHcp" type="number" step="0.1" class="input"/></div>
          <div class="field-group">
            <label class="field-label">Status</label>
            <select name="status" id="editStatus" class="input select-field"><option>Active</option><option>Pending</option><option>Suspended</option></select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('editMemberModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(m) {
  document.getElementById('editId').value = m.id;
  document.getElementById('editName').value = m.name;
  document.getElementById('editEmail').value = m.email;
  document.getElementById('editPhone').value = m.phone;
  document.getElementById('editTier').value = m.tier;
  document.getElementById('editHcp').value = m.hcp;
  document.getElementById('editStatus').value = m.status;
  showModal('editMemberModal');
}
function setFilter(key, val) {
  const url = new URL(window.location.href);
  url.searchParams.set(key, val);
  window.location.href = url.toString();
}
let filterTimer;
function applyFilter() {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => {
    const url = new URL(window.location.href);
    url.searchParams.set('q', document.getElementById('searchQ').value);
    window.location.href = url.toString();
  }, 500);
}
</script>
