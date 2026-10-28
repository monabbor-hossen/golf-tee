<?php
declare(strict_types=1);

use App\Domain\SlotFormatter;

$statusPill = ['Paid' => 'pill-green', 'Pending' => 'pill-amber', 'Refunded' => 'pill-red'];
$typeCls    = ['Green fee' => '#1E5631', 'Pro shop' => '#D4C4A8', 'Food & bev' => '#F4A261', 'Event' => '#E8C766', 'Membership' => '#6b8f71'];
$types      = ['Green fee', 'Pro shop', 'Food & bev', 'Event', 'Membership'];
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Payments</h1>
    <p>Transaction ledger and revenue overview</p>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-secondary" onclick="exportCSV()" style="display:inline-flex;align-items:center;gap:6px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
        <polyline points="7 10 12 15 17 10"/>
        <line x1="12" y1="15" x2="12" y2="3"/>
      </svg>
      Export
    </button>
    <button class="btn btn-primary" onclick="showModal('recordPayModal')" style="display:inline-flex;align-items:center;gap:6px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/>
        <line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Record payment
    </button>
  </div>
</div>

<!-- Summary stat cards -->
<div class="stat-grid" style="margin-bottom:16px">
  <div class="stat-card highlight">
    <p class="stat-label">Paid</p>
    <p class="stat-value"><?= currencySymbol() ?><?= number_format((float)($statMap['Paid']['total'] ?? 0)) ?></p>
    <p class="stat-sub"><?= $statMap['Paid']['cnt'] ?? 0 ?> transactions</p>
  </div>
  <div class="stat-card">
    <p class="stat-label">Pending</p>
    <p class="stat-value"><?= currencySymbol() ?><?= number_format((float)($statMap['Pending']['total'] ?? 0)) ?></p>
    <p class="stat-sub amber"><?= $statMap['Pending']['cnt'] ?? 0 ?> transactions</p>
  </div>
  <div class="stat-card">
    <p class="stat-label">Refunded</p>
    <p class="stat-value"><?= currencySymbol() ?><?= number_format((float)($statMap['Refunded']['total'] ?? 0)) ?></p>
    <p class="stat-sub red"><?= $statMap['Refunded']['cnt'] ?? 0 ?> transactions</p>
  </div>
  <div class="stat-card">
    <p class="stat-label">Net Revenue</p>
    <p class="stat-value"><?= currencySymbol() ?><?= number_format((float)(($statMap['Paid']['total'] ?? 0) - ($statMap['Refunded']['total'] ?? 0))) ?></p>
    <p class="stat-sub"><?= count($rows) ?> total rows</p>
  </div>
</div>

<!-- Filters -->
<div class="filters">
  <div class="filter-search" style="max-width:260px">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#9ca3af"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM21 21l-4.3-4.3"/></svg>
    <input class="input" type="text" placeholder="Search transactions…" value="<?= htmlspecialchars($query) ?>" style="padding-left:32px"
           onchange="window.location.href=buildUrl({q:this.value})" id="paySearch"/>
  </div>
  <div class="segmented">
    <?php foreach (['all' => 'All', 'today' => 'Today', 'yesterday' => 'Yesterday'] as $k => $l): ?>
    <button class="<?= $range === $k ? 'active' : '' ?>" onclick="window.location.href=buildUrl({range:'<?= $k ?>'})"> <?= $l ?></button>
    <?php endforeach; ?>
  </div>
  <div class="segmented">
    <?php foreach (['All', 'Paid', 'Pending', 'Refunded'] as $s): ?>
    <button class="<?= $status === $s ? 'active' : '' ?>" onclick="window.location.href=buildUrl({status:'<?= $s ?>'})"> <?= $s ?></button>
    <?php endforeach; ?>
  </div>
  <div class="filter-select-wrap">
    <select class="input select-field" style="width:auto;height:38px;padding-left:14px;padding-right:34px" onchange="window.location.href=buildUrl({type:this.value})">
      <option <?= $type === 'All' ? 'selected' : '' ?>>All types</option>
      <?php foreach ($types as $t): ?>
      <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
    <span class="select-icon-right">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 12 15 18 9"/>
      </svg>
    </span>
  </div>
</div>

<div class="panel-layout">
  <div class="panel-main">
    <div class="card" style="padding:0;overflow:hidden">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th><th>Date</th><th>Customer</th><th>Description</th><th>Category</th><th>Method</th><th>Amount</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
          <tr class="<?= $selectedId === $r['id'] ? 'selected' : '' ?>" onclick="selectTxn('<?= $r['id'] ?>')">
            <td style="font-family:monospace;font-size:.8rem"><?= htmlspecialchars($r['id']) ?></td>
            <td style="color:#6b7280;white-space:nowrap"><?= date('M j', strtotime($r['txn_date'])) ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($r['name']) ?></td>
            <td style="color:#6b7280;font-size:.8rem;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($r['description']) ?></td>
            <td>
              <span style="display:inline-flex;align-items:center;gap:5px;font-size:.8rem">
                <i style="width:8px;height:8px;border-radius:2px;background:<?= $typeCls[$r['type']] ?? '#9ca3af' ?>;display:inline-block"></i>
                <?= htmlspecialchars($r['type']) ?>
              </span>
            </td>
            <td style="font-size:.8rem;color:#6b7280"><?= htmlspecialchars($r['method']) ?></td>
            <td style="font-weight:700"><?= currencySymbol() ?><?= number_format((float)$r['amount']) ?></td>
            <td><span class="pill <?= $statusPill[$r['status']] ?? 'pill-gray' ?>"><?= $r['status'] ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($rows)): ?>
          <tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:40px">No transactions found</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Detail Panel -->
  <?php if ($selectedTxn): ?>
  <div class="detail-panel">
    <div class="panel-header">
      <div>
        <p style="font-weight:700;font-family:monospace;font-size:.9rem"><?= htmlspecialchars($selectedTxn['id']) ?></p>
        <p style="font-size:.75rem;color:#9ca3af"><?= htmlspecialchars($selectedTxn['type']) ?></p>
      </div>
      <div style="display:flex;align-items:center;gap:6px">
        <span class="pill <?= $statusPill[$selectedTxn['status']] ?? 'pill-gray' ?>"><?= $selectedTxn['status'] ?></span>
        <a href="payments.php" class="btn btn-ghost btn-sm hide-desktop" style="color:#9ca3af;padding:4px 8px;display:inline-flex;align-items:center" title="Close details">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </a>
      </div>
    </div>
    <div class="panel-body">
      <div class="info-row"><span class="info-label">Customer</span><span class="info-val"><?= htmlspecialchars($selectedTxn['name']) ?></span></div>
      <div class="info-row"><span class="info-label">Amount</span><span class="info-val" style="font-size:1.1rem;font-weight:900;color:<?= $selectedTxn['status'] === 'Refunded' ? 'var(--danger)' : '' ?>">
        <?= $selectedTxn['status'] === 'Refunded' ? '−' : '' ?><?= currencySymbol() ?><?= number_format((float)$selectedTxn['amount']) ?></span></div>
      <div class="info-row"><span class="info-label">Date</span><span class="info-val"><?= date('M j, Y', strtotime($selectedTxn['txn_date'])) ?></span></div>
      <div class="info-row"><span class="info-label">Time</span><span class="info-val"><?= SlotFormatter::format((int)$selectedTxn['txn_time']) ?></span></div>
      <div class="info-row"><span class="info-label">Method</span><span class="info-val"><?= htmlspecialchars($selectedTxn['method']) ?></span></div>
      <div class="info-row"><span class="info-label">Category</span><span class="info-val"><?= htmlspecialchars($selectedTxn['type']) ?></span></div>
      <p style="font-size:.75rem;color:#9ca3af;margin-top:12px">Description</p>
      <p style="font-size:.8rem;margin-top:4px"><?= htmlspecialchars($selectedTxn['description']) ?></p>

      <?php if ($selectedTxn['status'] === 'Paid'): ?>
      <form method="post" action="api/payments.php" style="margin-top:16px" onsubmit="return confirm('Issue refund for <?= currencySymbol() ?><?= number_format((float)$selectedTxn['amount']) ?>?')">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="refund">
        <input type="hidden" name="id" value="<?= htmlspecialchars($selectedTxn['id']) ?>">
        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);width:100%;justify-content:center;display:inline-flex;align-items:center;gap:6px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="1 4 1 10 7 10"/>
            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
          </svg>
          Issue refund
        </button>
      </form>
      <?php elseif ($selectedTxn['status'] === 'Pending'): ?>
      <form method="post" action="api/payments.php" style="margin-top:16px">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="mark_paid">
        <input type="hidden" name="id" value="<?= htmlspecialchars($selectedTxn['id']) ?>">
        <button type="submit" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;display:inline-flex;align-items:center;gap:6px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          Mark as paid
        </button>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Record Payment Modal -->
<div class="modal-backdrop hidden" id="recordPayModal">
  <div class="modal">
    <div class="modal-header">
      <div><p class="modal-title">Record a payment</p><p class="modal-desc">Log a counter sale, membership fee or event entry.</p></div>
      <button class="close-btn" onclick="closeModal('recordPayModal')" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <form method="post" action="api/payments.php">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="field-group">
          <label class="field-label">Customer</label>
          <input name="name" class="input" required placeholder="Member or guest" list="pay-member-names"/>
          <datalist id="pay-member-names">
            <?php foreach ($allMemberNames as $m): ?>
              <option value="<?= htmlspecialchars($m['name']) ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="modal-grid-2">
          <div class="field-group">
            <label class="field-label">Category</label>
            <select name="type" class="input select-field">
              <?php foreach ($types as $t): ?><option><?= $t ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label">Method</label>
            <select name="method" class="input select-field">
              <?php foreach (['Visa', 'Mastercard', 'Apple Pay', 'Cash', 'Member account'] as $m): ?><option><?= $m ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="field-group"><label class="field-label">Description</label><input name="description" class="input" placeholder="e.g. Golf glove, size M"/></div>
        <div class="field-group">
          <label class="field-label">Amount</label>
          <div class="price-prefix"><span><?= currencySymbol() ?></span><input type="number" name="amount" min="0" step="0.01" class="input" placeholder="0.00" style="padding-left:22px"/></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('recordPayModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Record payment</button>
      </div>
    </form>
  </div>
</div>

<script>
function buildUrl(changes) {
  const url = new URL(window.location.href);
  Object.entries(changes).forEach(([k,v]) => url.searchParams.set(k, v));
  return url.toString();
}
function selectTxn(id) {
  window.location.href = buildUrl({txnId: id});
}
function exportCSV() {
  window.location.href = 'api/payments.php?action=export&' + new URL(window.location.href).searchParams.toString();
}
</script>
