<?php
declare(strict_types=1);

use App\Domain\SlotFormatter;

if ($saved): ?>
<div style="background:rgba(30,86,49,.1);color:var(--forest);padding:10px 16px;border-radius:10px;font-weight:600;font-size:.875rem;margin-bottom:16px;display:flex;align-items:center;gap:8px">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="20 6 9 17 4 12"/>
  </svg>
  <span>Settings saved successfully</span>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div style="background:rgba(220,38,38,.1);color:var(--danger);padding:10px 16px;border-radius:10px;font-weight:600;font-size:.875rem;margin-bottom:16px;display:flex;align-items:center;gap:8px">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
    <line x1="12" y1="9" x2="12" y2="13"/>
    <line x1="12" y1="17" x2="12.01" y2="17"/>
  </svg>
  <span><?= htmlspecialchars($error) ?></span>
</div>
<?php endif; ?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Settings</h1>
    <p>Manage course details, booking rules, pricing and team access</p>
  </div>
</div>

<div class="tab-bar">
  <?php foreach ($tabs as $t): ?>
  <a href="settings.php?tab=<?= urlencode($t) ?>" class="tab-btn <?= $tab === $t ? 'active' : '' ?>"><?= $t ?></a>
  <?php endforeach; ?>
</div>

<form method="post">
  <?= csrfField() ?>
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">

<?php if ($tab === 'Course'): ?>
<div class="section-card">
  <h2>Course information</h2>
  <p class="section-desc">Basic details about your golf course</p>
  <div class="form-grid-2">
    <div class="field-group col-span-2">
      <label class="field-label">Course name</label>
      <input name="course_name" class="input" value="<?= htmlspecialchars($settings['course_name'] ?? '') ?>"/>
    </div>
    <div class="field-group">
      <label class="field-label">Phone</label>
      <input name="phone" class="input" value="<?= htmlspecialchars($settings['phone'] ?? '') ?>"/>
    </div>
    <div class="field-group">
      <label class="field-label">Timezone</label>
      <select name="timezone" class="input select-field">
        <?php
        $currentTimezone = $settings['timezone'] ?? 'America/New_York';
        $tzGroups = $timezones ?? \App\Domain\TimezoneHelper::getAllGrouped();
        foreach ($tzGroups as $region => $groupList):
        ?>
        <optgroup label="<?= htmlspecialchars($region) ?>">
          <?php foreach ($groupList as $tzValue => $tzLabel): ?>
          <option value="<?= htmlspecialchars($tzValue) ?>" <?= $currentTimezone === $tzValue ? 'selected' : '' ?>>
            <?= htmlspecialchars($tzLabel) ?>
          </option>
          <?php endforeach; ?>
        </optgroup>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field-group col-span-2" style="grid-column:1/-1">
      <label class="field-label">Address</label>
      <input name="address" class="input" value="<?= htmlspecialchars($settings['address'] ?? '') ?>"/>
    </div>
    <div class="field-group">
      <label class="field-label">Currency</label>
      <select name="currency" class="input select-field">
        <?php
        $currList = $currencies ?? \App\Domain\CurrencyHelper::getAll();
        $selectedCurrency = strtoupper(trim($settings['currency'] ?? 'USD'));
        foreach ($currList as $code => $info):
        ?>
        <option value="<?= htmlspecialchars($code) ?>" <?= $selectedCurrency === $code ? 'selected' : '' ?>>
          <?= htmlspecialchars($code) ?> — <?= htmlspecialchars($info['name']) ?> (<?= htmlspecialchars($info['symbol']) ?>)
        </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<?php elseif ($tab === 'Tee Sheet'): ?>
<div class="section-card">
  <h2>Tee sheet schedule</h2>
  <p class="section-desc">Operating hours and booking slot configuration</p>
  <div class="form-grid-3" style="margin-bottom:16px">
    <div class="field-group">
      <label class="field-label">First tee time</label>
      <input type="time" name="first_tee" class="input" value="<?= $settings['first_tee'] ?? '07:00' ?>"/>
    </div>
    <div class="field-group">
      <label class="field-label">Last tee time</label>
      <input type="time" name="last_tee" class="input" value="<?= $settings['last_tee'] ?? '17:50' ?>"/>
    </div>
    <div class="field-group">
      <label class="field-label">Interval (minutes)</label>
      <select name="interval" class="input select-field">
        <?php foreach ([8, 10, 12, 15] as $i): ?>
        <option <?= ($settings['interval'] ?? '10') == $i ? 'selected' : '' ?>><?= $i ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field-group">
      <label class="field-label">Max party size</label>
      <select name="max_party" class="input select-field">
        <?php foreach ([2, 3, 4, 5] as $n): ?>
        <option <?= ($settings['max_party'] ?? '4') == $n ? 'selected' : '' ?>><?= $n ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field-group">
      <label class="field-label">Advance booking (days)</label>
      <input type="number" name="advance_days" min="1" max="365" class="input" value="<?= $settings['advance_days'] ?? '14' ?>"/>
    </div>
    <div class="field-group">
      <label class="field-label">Cancel cutoff (hours)</label>
      <input type="number" name="cancel_hours" min="0" class="input" value="<?= $settings['cancel_hours'] ?? '24' ?>"/>
    </div>
  </div>
  <p style="font-size:.875rem;font-weight:700;margin-bottom:10px">Open days</p>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php foreach ($days as $d): ?>
    <label style="display:flex;align-items:center;gap:6px;font-size:.875rem;cursor:pointer">
      <input type="checkbox" name="open_days[]" value="<?= $d ?>" <?= in_array($d, $openDays) ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:var(--forest)"/>
      <?= $d ?>
    </label>
    <?php endforeach; ?>
  </div>
</div>

<?php elseif ($tab === 'Pricing'): ?>
<div class="section-card">
  <h2>Green fee pricing</h2>
  <p class="section-desc">Set green fees by round type and time. These prices apply automatically when bookings are made.</p>
  <div class="price-grid">
    <div>
      <p class="field-label" style="margin-bottom:8px">Weekday</p>
      <div class="price-cell">
        <label class="field-label" style="font-size:.75rem">18 holes</label>
        <div class="price-prefix"><span><?= currencySymbol() ?></span><input type="number" name="price_weekday_18" class="input" value="<?= $settings['price_weekday_18'] ?? '85' ?>" style="padding-left:22px"/></div>
      </div>
      <div class="price-cell" style="margin-top:10px">
        <label class="field-label" style="font-size:.75rem">9 holes</label>
        <div class="price-prefix"><span><?= currencySymbol() ?></span><input type="number" name="price_weekday_9" class="input" value="<?= $settings['price_weekday_9'] ?? '48' ?>" style="padding-left:22px"/></div>
      </div>
    </div>
    <div>
      <p class="field-label" style="margin-bottom:8px">Weekend</p>
      <div class="price-cell">
        <label class="field-label" style="font-size:.75rem">18 holes</label>
        <div class="price-prefix"><span><?= currencySymbol() ?></span><input type="number" name="price_weekend_18" class="input" value="<?= $settings['price_weekend_18'] ?? '110' ?>" style="padding-left:22px"/></div>
      </div>
      <div class="price-cell" style="margin-top:10px">
        <label class="field-label" style="font-size:.75rem">9 holes</label>
        <div class="price-prefix"><span><?= currencySymbol() ?></span><input type="number" name="price_weekend_9" class="input" value="<?= $settings['price_weekend_9'] ?? '62' ?>" style="padding-left:22px"/></div>
      </div>
    </div>
    <div>
      <p class="field-label" style="margin-bottom:8px">Twilight</p>
      <div class="price-cell">
        <label class="field-label" style="font-size:.75rem">18 holes</label>
        <div class="price-prefix"><span><?= currencySymbol() ?></span><input type="number" name="price_twilight_18" class="input" value="<?= $settings['price_twilight_18'] ?? '55' ?>" style="padding-left:22px"/></div>
      </div>
      <div class="price-cell" style="margin-top:10px">
        <label class="field-label" style="font-size:.75rem">9 holes</label>
        <div class="price-prefix"><span><?= currencySymbol() ?></span><input type="number" name="price_twilight_9" class="input" value="<?= $settings['price_twilight_9'] ?? '35' ?>" style="padding-left:22px"/></div>
      </div>
    </div>
  </div>
  <div style="margin-top:20px">
    <label class="field-label">Twilight start time</label>
    <select name="twilight_start" class="input select-field" style="width:200px;margin-top:6px">
      <?php foreach ($slots as $s): ?>
      <option value="<?= $s ?>" <?= ($settings['twilight_start'] ?? '900') == $s ? 'selected' : '' ?>><?= SlotFormatter::format($s) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<?php elseif ($tab === 'Feature flags'): ?>
<div class="section-card">
  <h2>Feature flags</h2>
  <p class="section-desc">Enable or disable booking features</p>
  <?php
  $flagDefs = [
    'flag_waitlist'   => ['Waitlist',             'Allow members to join a waitlist when tee times are full'],
    'flag_nine'       => ['9-hole bookings',       'Allow 9-hole rounds alongside 18-hole rounds'],
    'flag_guest'      => ['Guest bookings',        'Allow non-members to book tee times'],
    'flag_deposit'    => ['Require deposit',       'Charge a deposit when booking to reduce no-shows'],
    'flag_twilight'   => ['Twilight pricing',      'Apply discounted twilight rates after the start time above'],
    'flag_dynamic'    => ['Dynamic pricing',       'Automatically adjust prices based on demand (experimental)'],
    'flag_auto_offer' => ['Auto-offer waitlist',   'Automatically notify waitlist when a slot opens up'],
  ];
  foreach ($flagDefs as $k => [$label, $desc]):
    $on = ($settings[$k] ?? '0') === '1';
  ?>
  <div class="flag-row">
    <div>
      <p class="flag-label"><?= $label ?></p>
      <p class="flag-desc"><?= $desc ?></p>
    </div>
    <label class="toggle">
      <input type="checkbox" name="<?= $k ?>" value="1" <?= $on ? 'checked' : '' ?>>
      <div class="toggle-track"></div>
      <div class="toggle-thumb"></div>
    </label>
  </div>
  <?php endforeach; ?>
</div>

<?php elseif ($tab === 'Notifications'): ?>
<div class="section-card">
  <h2>Notification preferences</h2>
  <p class="section-desc">Choose which events trigger in-app notifications</p>
  <?php
  $notifDefs = [
    'flag_n_book'   => ['New booking',        'When a tee time is reserved'],
    'flag_n_cancel' => ['Cancellation',       'When a booking is cancelled'],
    'flag_n_wait'   => ['Waitlist activity',  'When someone joins or is offered a spot'],
    'flag_n_daily'  => ['Daily summary',      'Morning digest of upcoming tee times'],
    'flag_n_sms'    => ['SMS alerts',         'Send text messages for critical updates (requires integration)'],
  ];
  foreach ($notifDefs as $k => [$label, $desc]):
    $on = ($settings[$k] ?? '0') === '1';
  ?>
  <div class="flag-row">
    <div>
      <p class="flag-label"><?= $label ?></p>
      <p class="flag-desc"><?= $desc ?></p>
    </div>
    <label class="toggle">
      <input type="checkbox" name="<?= $k ?>" value="1" <?= $on ? 'checked' : '' ?>>
      <div class="toggle-track"></div>
      <div class="toggle-thumb"></div>
    </label>
  </div>
  <?php endforeach; ?>
</div>

<?php elseif ($tab === 'Staff'): ?>
<?php /* Staff tab has its own form */ ?>
</form>
<div class="section-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
    <h2>Staff members</h2>
    <button class="btn btn-primary btn-sm" onclick="showModal('inviteStaffModal')">+ Invite staff</button>
  </div>
  <p class="section-desc">Manage team access levels</p>
  <div class="table-wrap">
    <table style="width:100%;border-collapse:collapse;font-size:.875rem">
      <thead>
        <tr>
          <th style="padding:8px 0;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;color:#9ca3af;letter-spacing:.05em;border-bottom:1px solid var(--border)">Name</th>
          <th style="padding:8px 0;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;color:#9ca3af;letter-spacing:.05em;border-bottom:1px solid var(--border)">Email</th>
          <th style="padding:8px 0;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;color:#9ca3af;letter-spacing:.05em;border-bottom:1px solid var(--border)">Role</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($staff as $s): ?>
      <tr>
        <td style="padding:12px 0;border-bottom:1px solid #f9fafb">
          <div style="display:flex;align-items:center;gap:8px">
            <div class="avatar" style="width:28px;height:28px;font-size:.65rem"><?= strtoupper(substr($s['name'], 0, 2)) ?></div>
            <span style="font-weight:600"><?= htmlspecialchars($s['name']) ?></span>
          </div>
        </td>
        <td style="padding:12px 0;border-bottom:1px solid #f9fafb;color:#6b7280"><?= htmlspecialchars($s['email']) ?></td>
        <td style="padding:12px 0;border-bottom:1px solid #f9fafb">
          <span class="pill <?= $s['role'] === 'Owner' ? 'pill-gold' : ($s['role'] === 'Manager' ? 'pill-green' : 'pill-gray') ?>"><?= $s['role'] ?></span>
        </td>
        <td style="padding:12px 0;border-bottom:1px solid #f9fafb;text-align:right">
          <?php if ($s['id'] != \App\Core\Session::getStaffId() && $s['role'] !== 'Owner'): ?>
          <form method="post" style="display:inline" onsubmit="return confirm('Remove this staff member?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="remove_staff">
            <input type="hidden" name="staff_id" value="<?= $s['id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger)">Remove</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<!-- Invite Staff Modal -->
<div class="modal-backdrop hidden" id="inviteStaffModal">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <div><p class="modal-title">Invite staff member</p><p class="modal-desc">They'll get an email to set up their account.</p></div>
      <button class="close-btn" onclick="closeModal('inviteStaffModal')" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="invite">
      <div class="modal-body">
        <div class="field-group"><label class="field-label">Name</label><input name="name" class="input" required/></div>
        <div class="field-group"><label class="field-label">Email</label><input name="email" type="email" class="input" required placeholder="name@pinevalley.com"/></div>
        <div class="field-group">
          <label class="field-label">Role</label>
          <select name="role" class="input select-field">
            <option>Manager</option><option>Front Desk</option><option>Read only</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('inviteStaffModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Send invite</button>
      </div>
    </form>
  </div>
</div>
<?php
$tab = ''; // skip save button for staff tab
endif;

if ($tab !== ''): ?>
  <div style="display:flex;justify-content:flex-end;margin-top:16px">
    <button type="submit" class="btn btn-primary">Save changes</button>
  </div>
</form>
<?php endif; ?>
