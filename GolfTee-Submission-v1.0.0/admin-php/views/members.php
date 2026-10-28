<?php
declare(strict_types=1);

use App\Domain\SlotFormatter;

$tierCls   = ['Gold' => 'pill-gold', 'Silver' => 'pill-silver', 'Standard' => 'pill-sand'];
$statusCls = ['Active' => 'pill-green', 'Suspended' => 'pill-red', 'Pending' => 'pill-amber'];

// Helper to render sortable column headers with SVG icons (top-to-bottom and bottom-to-top)
$renderSortTh = function(string $col, string $label, string $defaultDir = 'asc') use ($sort, $dir) {
    $isActive = ($sort === $col);
    $currentDir = $isActive ? strtolower($dir) : '';

    if ($isActive) {
        $nextDir = ($currentDir === 'asc') ? 'desc' : 'asc';
    } else {
        $nextDir = $defaultDir;
    }

    $params = array_merge($_GET, ['sort' => $col, 'dir' => $nextDir]);
    $url = '?' . http_build_query($params);

    // SVG arrows (pure vector, no text icons)
    $svgAsc = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13" stroke-linecap="round" stroke-linejoin="round" style="color:var(--forest);display:inline-block;vertical-align:middle"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>';
    $svgDesc = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13" stroke-linecap="round" stroke-linejoin="round" style="color:var(--forest);display:inline-block;vertical-align:middle"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>';
    $svgNeutral = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.35;display:inline-block;vertical-align:middle"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>';

    $icon = $svgNeutral;
    if ($isActive) {
        $icon = ($currentDir === 'desc') ? $svgDesc : $svgAsc;
    }

    $sortTitle = $isActive
        ? ($currentDir === 'desc' ? 'Sorted Top-to-Bottom. Click to reverse.' : 'Sorted Bottom-to-Top. Click to reverse.')
        : 'Sort by ' . htmlspecialchars($label) . ' (' . ($defaultDir === 'desc' ? 'Top-to-Bottom' : 'Ascending') . ')';

    return '<th><a href="' . htmlspecialchars($url) . '" class="sort-header ' . ($isActive ? 'active' : '') . '" title="' . $sortTitle . '">'
        . htmlspecialchars($label) . ' ' . $icon . '</a></th>';
};
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Members</h1>
    <p><?= $total ?> total members · <?= $counts['Pending'] ?? 0 ?> pending approval</p>
  </div>
  <div class="page-header-actions">
    <a href="members.php?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-secondary" style="display:inline-flex;align-items:center;gap:6px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
        <polyline points="7 10 12 15 17 10"/>
        <line x1="12" y1="15" x2="12" y2="3"/>
      </svg>
      Export CSV
    </a>
    <button class="btn btn-primary" onclick="showModal('addMemberModal')" style="display:inline-flex;align-items:center;gap:6px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/>
        <line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Add Member
    </button>
  </div>
</div>

<?php if (!empty($flashError)): ?>
<div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:14px 18px;border-radius:12px;margin-bottom:16px;display:flex;align-items:center;gap:12px;font-size:13.5px;font-weight:600">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="20" height="20" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <span><?= htmlspecialchars($flashError) ?></span>
</div>
<?php endif; ?>

<?php if (!empty($flashSuccess)): ?>
<div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:14px 18px;border-radius:12px;margin-bottom:16px;display:flex;align-items:center;gap:12px;font-size:13.5px;font-weight:600">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="20" height="20" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
  <span><?= htmlspecialchars($flashSuccess) ?></span>
</div>
<?php elseif (($_GET['msg'] ?? '') === 'invite_sent'): ?>
<div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:12px 16px;border-radius:12px;margin-bottom:16px;display:flex;align-items:center;gap:10px;font-size:13px;font-weight:600">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18"><polyline points="20 6 9 17 4 12"/></svg>
  <span>Official invitation email with secure password setup link has been dispatched to the member.</span>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="filters">
  <div class="filter-search" style="max-width:260px">
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
  <div class="filter-select-wrap">
    <span class="select-icon-left">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="1" x2="12" y2="23"/>
        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
      </svg>
    </span>
    <select onchange="setFilter('spend', this.value)" title="Filter by spend">
      <option value="All" <?= ($spend ?? 'All') === 'All' ? 'selected' : '' ?>>All Spend</option>
      <option value="5000+" <?= ($spend ?? 'All') === '5000+' ? 'selected' : '' ?>>Top Spend (<?= currencySymbol() ?>5k+)</option>
      <option value="2000-5000" <?= ($spend ?? 'All') === '2000-5000' ? 'selected' : '' ?>><?= currencySymbol() ?>2,000 – <?= currencySymbol() ?>5,000</option>
      <option value="1000-2000" <?= ($spend ?? 'All') === '1000-2000' ? 'selected' : '' ?>><?= currencySymbol() ?>1,000 – <?= currencySymbol() ?>2,000</option>
      <option value="under1000" <?= ($spend ?? 'All') === 'under1000' ? 'selected' : '' ?>>Under <?= currencySymbol() ?>1,000</option>
    </select>
    <span class="select-icon-right">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 12 15 18 9"/>
      </svg>
    </span>
  </div>
  <div class="filter-select-wrap">
    <span class="select-icon-left">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 6h18M6 12h12m-9 6h6"/>
      </svg>
    </span>
    <select onchange="applySort(this.value)" title="Sort members order">
      <option value="spend:desc" <?= $sort === 'spend' && $dir === 'desc' ? 'selected' : '' ?>>Spend: High to Low (Top-to-Bottom)</option>
      <option value="spend:asc" <?= $sort === 'spend' && $dir === 'asc' ? 'selected' : '' ?>>Spend: Low to High</option>
      <option value="tier:asc" <?= $sort === 'tier' && $dir === 'asc' ? 'selected' : '' ?>>Tier: Gold → Standard (Top-to-Bottom)</option>
      <option value="tier:desc" <?= $sort === 'tier' && $dir === 'desc' ? 'selected' : '' ?>>Tier: Standard → Gold</option>
      <option value="status:asc" <?= $sort === 'status' && $dir === 'asc' ? 'selected' : '' ?>>Status: Active → Suspended (Top-to-Bottom)</option>
      <option value="status:desc" <?= $sort === 'status' && $dir === 'desc' ? 'selected' : '' ?>>Status: Suspended → Active</option>
      <option value="name:asc" <?= $sort === 'name' && $dir === 'asc' ? 'selected' : '' ?>>Name: A → Z</option>
      <option value="name:desc" <?= $sort === 'name' && $dir === 'desc' ? 'selected' : '' ?>>Name: Z → A</option>
      <option value="rounds:desc" <?= $sort === 'rounds' && $dir === 'desc' ? 'selected' : '' ?>>Rounds: High to Low</option>
      <option value="hcp:asc" <?= $sort === 'hcp' && $dir === 'asc' ? 'selected' : '' ?>>HCP: Low to High</option>
    </select>
    <span class="select-icon-right">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 12 15 18 9"/>
      </svg>
    </span>
  </div>
  <?php if ($query !== '' || $tier !== 'All' || $status !== 'All' || ($spend ?? 'All') !== 'All' || $sort !== 'name' || $dir !== 'asc'): ?>
  <a href="members.php" class="btn btn-ghost btn-sm" style="height:38px;padding:0 12px;font-size:0.75rem;color:var(--text-muted);display:inline-flex;align-items:center;gap:6px" title="Reset all filters and sorting">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
      <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
      <path d="M3 3v5h5"/>
    </svg>
    Reset
  </a>
  <?php endif; ?>
</div>

<!-- Table + Detail Panel -->
<div class="panel-layout">
  <div class="panel-main">
    <div class="card" style="padding:0;overflow:hidden">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <?= $renderSortTh('name', 'Name', 'asc') ?>
              <?= $renderSortTh('tier', 'Tier', 'asc') ?>
              <?= $renderSortTh('status', 'Status', 'asc') ?>
              <?= $renderSortTh('hcp', 'HCP', 'asc') ?>
              <?= $renderSortTh('rounds', 'Rounds', 'desc') ?>
              <?= $renderSortTh('spend', 'Spend', 'desc') ?>
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
            <td><?= currencySymbol() ?><?= number_format((float)$m['spend']) ?></td>
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
      <div class="info-row"><span class="info-label">Total spend</span><span class="info-val"><?= currencySymbol() ?><?= number_format((float)$selectedMember['spend']) ?></span></div>
      <div class="info-row"><span class="info-label">Last round</span><span class="info-val"><?= htmlspecialchars($selectedMember['last_play'] ?: '—') ?></span></div>
      <?php if (!empty($selectedMember['next_tee'])): ?>
      <div class="info-row"><span class="info-label">Next tee time</span><span class="info-val" style="font-size:.75rem"><?= htmlspecialchars($selectedMember['next_tee']) ?></span></div>
      <?php endif; ?>

      <!-- Account & App Access Security -->
      <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin-top:14px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
          <span style="font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em">App Access & Security</span>
          <?php if (!empty($selectedMember['password_hash'])): ?>
            <span class="pill pill-green" style="font-size:11px">Password Active</span>
          <?php elseif (!empty($selectedMember['setup_token'])): ?>
            <span class="pill pill-amber" style="font-size:11px">Password Pending</span>
          <?php else: ?>
            <span class="pill pill-gray" style="font-size:11px">Not Activated</span>
          <?php endif; ?>
        </div>
        <p style="font-size:.75rem;color:#4b5563;line-height:1.4;margin:0 0 10px 0">
          <?php if (!empty($selectedMember['password_hash'])): ?>
            Member has set their password. Can sign in with email/password or authorized Google account.
          <?php elseif (!empty($selectedMember['setup_token'])): ?>
            Invitation sent. Waiting for member to click email link and enter a password.
          <?php else: ?>
            Account created without password. Send an invitation link below.
          <?php endif; ?>
        </p>

        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <form method="post" style="margin:0">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="resend_invite">
            <input type="hidden" name="id" value="<?= $selectedMember['id'] ?>">
            <button type="submit" class="btn btn-secondary btn-sm" style="font-size:.75rem;padding:5px 10px;display:inline-flex;align-items:center;gap:4px">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              <?= !empty($selectedMember['password_hash']) ? 'Send Reset Link' : 'Resend Invite' ?>
            </button>
          </form>

          <?php if (!empty($selectedMember['setup_token'])): ?>
            <a href="set-password.php?token=<?= urlencode($selectedMember['setup_token']) ?>" target="_blank" class="btn btn-ghost btn-sm" style="font-size:.75rem;padding:5px 10px;color:var(--forest);text-decoration:none">
              Open Setup Link ↗
            </a>
          <?php endif; ?>
        </div>
      </div>

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
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:10px 12px;font-size:12px;color:#166534;margin-bottom:14px;line-height:1.4">
          🔒 <strong>Invite-Only Access:</strong> Regular users cannot self-register. Creating this member will automatically email them an invitation link to set their password. They can also use "Continue with Google" once their email is saved here.
        </div>
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
function applySort(val) {
  if (!val) return;
  const parts = val.split(':');
  const sort = parts[0];
  const dir = parts[1] || 'asc';
  const url = new URL(window.location.href);
  url.searchParams.set('sort', sort);
  url.searchParams.set('dir', dir);
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
