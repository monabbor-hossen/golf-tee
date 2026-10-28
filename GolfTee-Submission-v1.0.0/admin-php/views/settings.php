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

<div class="section-card" style="margin-top:20px">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:6px">
    <div>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <h2>Course location & live conditions</h2>
        <span class="pill pill-green" id="weatherSourceBadge" style="font-size:0.7rem;text-transform:none;letter-spacing:0">
          <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#16a34a;margin-right:4px"></span>
          Live Free API
        </span>
        <span class="pill" id="weatherAutoRefreshBadge" style="font-size:0.7rem;text-transform:none;letter-spacing:0;background:rgba(22,101,52,0.1);color:#15803d;border:1px solid rgba(22,101,52,0.25);display:inline-flex;align-items:center;gap:4px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          Auto-refreshes every 1 hr
        </span>
      </div>
      <p class="section-desc">Set your golf club location to fetch real-time weather, live wind speed & direction, and publish daily green speeds to members. Auto-updates every hour.</p>
    </div>
    <button type="button" class="btn btn-ghost btn-sm" id="btnRefreshWeather" style="border:1px solid var(--border);display:flex;align-items:center;gap:6px">
      <svg id="refreshSpinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-1.19"/>
      </svg>
      <span>Refresh live feed</span>
    </button>
  </div>

  <div class="form-grid-2">
    <div class="field-group col-span-2">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
        <label class="field-label" style="margin:0">Location / City (or Live GPS)</label>
        <button type="button" class="btn btn-primary btn-sm" id="btnDetectGps" style="padding:4px 12px;font-size:0.75rem;display:flex;align-items:center;gap:6px;border-radius:20px;background:var(--forest);box-shadow:0 2px 8px rgba(30,86,49,0.25)">
          <svg id="gpsIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="2" x2="12" y2="6"/>
            <line x1="12" y1="18" x2="12" y2="22"/>
            <line x1="2" y1="12" x2="6" y2="12"/>
            <line x1="18" y1="12" x2="22" y2="12"/>
            <circle cx="12" cy="12" r="3" fill="currentColor"/>
          </svg>
          <span id="btnDetectGpsText">Detect Live GPS</span>
        </button>
      </div>

      <div style="position:relative">
        <input name="course_location" id="inputCourseLocation" class="input" style="padding-left:36px;padding-right:120px" value="<?= htmlspecialchars($settings['course_location'] ?? 'Pine Valley, NJ') ?>" placeholder="e.g. Pine Valley, NJ or click Detect Live GPS" autocomplete="off"/>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;pointer-events:none">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
          <circle cx="12" cy="10" r="3"/>
        </svg>
        <span id="gpsLiveIndicator" style="display:<?= (!empty($settings['course_lat']) && !empty($settings['course_lng'])) ? 'inline-flex' : 'none' ?>;position:absolute;right:10px;top:50%;transform:translateY(-50%);align-items:center;gap:4px;font-size:0.72rem;background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:10px;font-weight:700">
          <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#16a34a;animation:pulse 1.5s infinite"></span>
          <span>GPS Active</span>
        </span>
      </div>

      <input type="hidden" name="course_lat" id="inputCourseLat" value="<?= htmlspecialchars($settings['course_lat'] ?? '') ?>"/>
      <input type="hidden" name="course_lng" id="inputCourseLng" value="<?= htmlspecialchars($settings['course_lng'] ?? '') ?>"/>

      <div id="gpsStatusMessage" style="font-size:0.75rem;color:#15803d;margin-top:4px;display:<?= (!empty($settings['course_lat']) && !empty($settings['course_lng'])) ? 'flex' : 'none' ?>;align-items:center;gap:4px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
        <span id="gpsStatusText">
          <?= (!empty($settings['course_lat']) && !empty($settings['course_lng'])) ? 'GPS Coordinates: ' . htmlspecialchars($settings['course_lat']) . '°, ' . htmlspecialchars($settings['course_lng']) . '°' : '' ?>
        </span>
      </div>

      <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:8px">
        <span style="font-size:0.75rem;color:#6b7280;font-weight:600">Quick presets:</span>
        <?php
        $quickLocations = [
            'Pine Valley, NJ',
            'Pebble Beach, CA',
            'Augusta, GA',
            'Scottsdale, AZ',
            'St Andrews, Scotland',
            'Orlando, FL',
            'Torrey Pines, CA',
        ];
        foreach ($quickLocations as $ql):
        ?>
        <button type="button" class="btn btn-ghost btn-sm preset-loc-btn" data-loc="<?= htmlspecialchars($ql) ?>" style="font-size:0.72rem;padding:2px 8px;border-radius:12px;background:var(--sand-light, #f4f3ef);border:1px solid var(--border)">
          <?= htmlspecialchars($ql) ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="field-group">
      <label class="field-label">Green speed (Stimpmeter)</label>
      <div style="display:flex;gap:8px">
        <input name="green_speed" id="inputGreenSpeed" class="input" style="width:90px" value="<?= htmlspecialchars($settings['green_speed'] ?? '10.5') ?>" step="0.1" placeholder="10.5"/>
        <select class="input select-field" id="selectGreenPreset" onchange="if(this.value){document.getElementById('inputGreenSpeed').value=this.value; document.getElementById('inputSpeedDesc').value=this.options[this.selectedIndex].text.split(' - ')[1] || 'Green speed'; updateLivePreview();}">
          <option value="">Preset speed...</option>
          <option value="12.0" <?= ($settings['green_speed'] ?? '') === '12.0' ? 'selected' : '' ?>>12.0 - Major Tournament Fast</option>
          <option value="11.5" <?= ($settings['green_speed'] ?? '') === '11.5' ? 'selected' : '' ?>>11.5 - Tournament Fast</option>
          <option value="10.5" <?= ($settings['green_speed'] ?? '10.5') === '10.5' ? 'selected' : '' ?>>10.5 - Club Championship / Fast</option>
          <option value="9.5" <?= ($settings['green_speed'] ?? '') === '9.5' ? 'selected' : '' ?>>9.5 - Medium-Fast</option>
          <option value="8.5" <?= ($settings['green_speed'] ?? '') === '8.5' ? 'selected' : '' ?>>8.5 - Standard Member Pace</option>
        </select>
      </div>
      <input type="hidden" name="speed_desc" id="inputSpeedDesc" value="<?= htmlspecialchars($settings['speed_desc'] ?? 'Green speed') ?>"/>
    </div>

    <div class="field-group">
      <label class="field-label">Cart policy</label>
      <select name="carts" id="selectCarts" class="input select-field" onchange="document.getElementById('inputCartsDesc').value=this.options[this.selectedIndex].getAttribute('data-desc'); updateLivePreview();">
        <option value="Open" data-desc="Carts on fairways" <?= ($settings['carts'] ?? 'Open') === 'Open' ? 'selected' : '' ?>>Open — Carts on fairways (90° rule)</option>
        <option value="90° Rule" data-desc="90 degree cart rule" <?= ($settings['carts'] ?? '') === '90° Rule' ? 'selected' : '' ?>>90° Rule — Cross fairway at ball only</option>
        <option value="Path Only" data-desc="Cart paths only today" <?= ($settings['carts'] ?? '') === 'Path Only' ? 'selected' : '' ?>>Path Only — Wet turf / Cart paths only</option>
        <option value="Walking Only" data-desc="Walking only / No carts" <?= ($settings['carts'] ?? '') === 'Walking Only' ? 'selected' : '' ?>>Walking Only — Carts suspended</option>
      </select>
      <input type="hidden" name="carts_desc" id="inputCartsDesc" value="<?= htmlspecialchars($settings['carts_desc'] ?? 'Carts on fairways') ?>"/>
    </div>

    <div class="field-group">
      <label class="field-label">Weather unit</label>
      <select name="weather_unit" id="selectWeatherUnit" class="input select-field" onchange="fetchWeatherForLocation();">
        <option value="F" <?= ($settings['weather_unit'] ?? 'F') === 'F' ? 'selected' : '' ?>>Fahrenheit (°F · mph)</option>
        <option value="C" <?= ($settings['weather_unit'] ?? '') === 'C' ? 'selected' : '' ?>>Celsius (°C · km/h)</option>
      </select>
    </div>
  </div>

  <!-- Live Conditions Member Preview Card -->
  <div style="margin-top:20px;padding:16px 20px;border-radius:14px;background:linear-gradient(135deg, #0f2e1a 0%, #153e24 100%);color:#fff;border:1px solid rgba(255,255,255,0.08);box-shadow:0 8px 24px rgba(0,0,0,0.12)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;border-bottom:1px solid rgba(255,255,255,0.1);padding-bottom:10px">
      <div style="display:flex;align-items:center;gap:8px">
        <span style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.08em;color:#86efac;font-weight:700">Live Member Feed Preview</span>
        <span style="font-size:0.75rem;color:rgba(255,255,255,0.6)" id="previewLocationName"><?= htmlspecialchars($conditions['location'] ?? 'Pine Valley, NJ') ?></span>
      </div>
      <span style="font-size:0.72rem;color:rgba(255,255,255,0.6)" id="previewUpdatedAt"><?= htmlspecialchars($conditions['updated_at'] ?? 'Updated just now') ?></span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));gap:12px">
      <!-- 1. Weather / Sunny -->
      <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:12px;text-align:center">
        <div style="font-size:1.5rem;margin-bottom:4px" id="previewWeatherIcon">☀️</div>
        <div style="font-size:1.25rem;font-weight:800;letter-spacing:-0.02em" id="previewWeather"><?= htmlspecialchars($conditions['weather'] ?? '70°') ?></div>
        <div style="font-size:0.75rem;color:#86efac;font-weight:600" id="previewWeatherDesc"><?= htmlspecialchars($conditions['weather_desc'] ?? 'Sunny') ?></div>
      </div>

      <!-- 2. Live Wind -->
      <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:12px;text-align:center">
        <div style="font-size:1.5rem;margin-bottom:4px">💨</div>
        <div style="font-size:1.25rem;font-weight:800;letter-spacing:-0.02em" id="previewWind"><?= htmlspecialchars($conditions['wind'] ?? '6 mph') ?></div>
        <div style="font-size:0.75rem;color:#86efac;font-weight:600" id="previewWindDesc"><?= htmlspecialchars($conditions['wind_desc'] ?? 'NW wind') ?></div>
      </div>

      <!-- 3. Green Speed -->
      <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:12px;text-align:center">
        <div style="font-size:1.5rem;margin-bottom:4px">⚡</div>
        <div style="font-size:1.25rem;font-weight:800;letter-spacing:-0.02em" id="previewGreenSpeed"><?= htmlspecialchars($conditions['green_speed'] ?? '10.5') ?></div>
        <div style="font-size:0.75rem;color:#86efac;font-weight:600" id="previewSpeedDesc"><?= htmlspecialchars($conditions['speed_desc'] ?? 'Green speed') ?></div>
      </div>

      <!-- 4. Carts -->
      <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:12px;text-align:center">
        <div style="font-size:1.5rem;margin-bottom:4px">🛺</div>
        <div style="font-size:1.25rem;font-weight:800;letter-spacing:-0.02em" id="previewCarts"><?= htmlspecialchars($conditions['carts'] ?? 'Open') ?></div>
        <div style="font-size:0.75rem;color:#86efac;font-weight:600" id="previewCartsDesc"><?= htmlspecialchars($conditions['carts_desc'] ?? 'Carts on fairways') ?></div>
      </div>
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

<script>
function getWeatherIcon(desc) {
  desc = (desc || '').toLowerCase();
  if (desc.includes('sun') || desc.includes('clear')) return '☀️';
  if (desc.includes('cloud') || desc.includes('overcast')) return '⛅';
  if (desc.includes('rain') || desc.includes('shower') || desc.includes('drizzle')) return '🌧️';
  if (desc.includes('snow') || desc.includes('flurr') || desc.includes('ice')) return '❄️';
  if (desc.includes('thunder') || desc.includes('storm')) return '⚡';
  return '☀️';
}

function updateLivePreview() {
  const speed = document.getElementById('inputGreenSpeed')?.value || '10.5';
  const speedDesc = document.getElementById('inputSpeedDesc')?.value || 'Green speed';
  const carts = document.getElementById('selectCarts')?.value || 'Open';
  const cartsDesc = document.getElementById('inputCartsDesc')?.value || 'Carts on fairways';

  if (document.getElementById('previewGreenSpeed')) document.getElementById('previewGreenSpeed').textContent = speed;
  if (document.getElementById('previewSpeedDesc')) document.getElementById('previewSpeedDesc').textContent = speedDesc;
  if (document.getElementById('previewCarts')) document.getElementById('previewCarts').textContent = carts;
  if (document.getElementById('previewCartsDesc')) document.getElementById('previewCartsDesc').textContent = cartsDesc;
}

let weatherFetchTimer = null;

function fetchWeatherForLocation(force = true) {
  const locInput = document.getElementById('inputCourseLocation');
  const loc = locInput ? locInput.value.trim() : '';
  const unit = document.getElementById('selectWeatherUnit')?.value || 'F';
  if (!loc) return;

  const spinner = document.getElementById('refreshSpinner');
  if (spinner) spinner.style.animation = 'spin 0.8s linear infinite';

  fetch(`api/mobile.php?action=weather&location=${encodeURIComponent(loc)}&unit=${encodeURIComponent(unit)}&refresh=${force ? 1 : 0}`)
    .then(r => r.json())
    .then(res => {
      if (spinner) spinner.style.animation = '';
      if (res && res.status === 'ok' && (res.conditions || res.data)) {
        const d = res.conditions || res.data;
        if (document.getElementById('previewLocationName')) document.getElementById('previewLocationName').textContent = d.location;
        if (document.getElementById('previewWeather')) document.getElementById('previewWeather').textContent = d.weather;
        if (document.getElementById('previewWeatherDesc')) document.getElementById('previewWeatherDesc').textContent = d.weather_desc;
        if (document.getElementById('previewWeatherIcon')) document.getElementById('previewWeatherIcon').textContent = getWeatherIcon(d.weather_desc);
        if (document.getElementById('previewWind')) document.getElementById('previewWind').textContent = d.wind;
        if (document.getElementById('previewWindDesc')) document.getElementById('previewWindDesc').textContent = d.wind_desc;
        if (document.getElementById('previewUpdatedAt')) document.getElementById('previewUpdatedAt').textContent = d.updated_at;
        updateLivePreview();
      }
    })
    .catch(err => {
      if (spinner) spinner.style.animation = '';
      console.warn('Weather fetch error:', err);
    });
}

function fetchWeatherByCoords(lat, lng, force = true) {
  const unit = document.getElementById('selectWeatherUnit')?.value || 'F';
  const spinner = document.getElementById('refreshSpinner');
  if (spinner) spinner.style.animation = 'spin 0.8s linear infinite';

  fetch(`api/mobile.php?action=weather&lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}&unit=${encodeURIComponent(unit)}&refresh=${force ? 1 : 0}`)
    .then(r => r.json())
    .then(res => {
      if (spinner) spinner.style.animation = '';
      if (res && res.status === 'ok' && res.conditions) {
        const d = res.conditions;
        if (d.location && document.getElementById('inputCourseLocation')) {
          document.getElementById('inputCourseLocation').value = d.location;
        }
        if (document.getElementById('previewLocationName')) {
          document.getElementById('previewLocationName').textContent = (d.location || 'Live GPS') + (d.gps_formatted ? ' · ' + d.gps_formatted : '');
        }
        if (document.getElementById('previewWeather')) document.getElementById('previewWeather').textContent = d.weather;
        if (document.getElementById('previewWeatherDesc')) document.getElementById('previewWeatherDesc').textContent = d.weather_desc;
        if (document.getElementById('previewWeatherIcon')) document.getElementById('previewWeatherIcon').textContent = getWeatherIcon(d.weather_desc);
        if (document.getElementById('previewWind')) document.getElementById('previewWind').textContent = d.wind;
        if (document.getElementById('previewWindDesc')) document.getElementById('previewWindDesc').textContent = d.wind_desc;
        if (document.getElementById('previewUpdatedAt')) document.getElementById('previewUpdatedAt').textContent = d.updated_at;
        updateLivePreview();
      }
    })
    .catch(err => {
      if (spinner) spinner.style.animation = '';
      console.warn('Weather fetch by coords error:', err);
    });
}

document.addEventListener('DOMContentLoaded', function() {
  const btnGps = document.getElementById('btnDetectGps');
  if (btnGps) {
    btnGps.addEventListener('click', function(e) {
      e.preventDefault();
      const btnText = document.getElementById('btnDetectGpsText');
      const icon = document.getElementById('gpsIcon');
      const statusMsg = document.getElementById('gpsStatusMessage');
      const statusText = document.getElementById('gpsStatusText');

      if (btnText) btnText.textContent = 'Acquiring GPS...';
      if (icon) icon.style.animation = 'spin 1s linear infinite';

      function onPosition(lat, lng, acc) {
        if (icon) icon.style.animation = '';
        if (btnText) btnText.textContent = 'GPS Locked ✓';
        setTimeout(() => { if (btnText) btnText.textContent = 'Detect Live GPS'; }, 3000);

        document.getElementById('inputCourseLat').value = lat;
        document.getElementById('inputCourseLng').value = lng;

        if (statusMsg && statusText) {
          statusMsg.style.display = 'flex';
          statusMsg.style.color = '#15803d';
          statusText.textContent = `GPS Fix: ${lat.toFixed(4)}°, ${lng.toFixed(4)}° (Accuracy ±${acc}m)`;
        }

        const indicator = document.getElementById('gpsLiveIndicator');
        if (indicator) indicator.style.display = 'inline-flex';

        fetchWeatherByCoords(lat, lng);
      }

      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          function(pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            const acc = Math.round(pos.coords.accuracy || 10);
            onPosition(lat, lng, acc);
          },
          function(err) {
            console.warn('Browser GPS prompt error, falling back to network IP GPS:', err);
            // Fallback to IP geolocation
            fetch('https://ipwho.is/')
              .then(r => r.json())
              .then(data => {
                if (data && data.latitude && data.longitude) {
                  onPosition(data.latitude, data.longitude, 1000);
                } else {
                  throw new Error('IP GPS unavailable');
                }
              })
              .catch(() => {
                if (icon) icon.style.animation = '';
                if (btnText) btnText.textContent = 'Detect Live GPS';
                if (statusMsg && statusText) {
                  statusMsg.style.display = 'flex';
                  statusMsg.style.color = '#b91c1c';
                  statusText.textContent = 'GPS permission denied or unavailable. You can enter your city manually.';
                }
              });
          },
          { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
        );
      } else {
        // Fallback for browsers without geolocation
        fetch('https://ipwho.is/')
          .then(r => r.json())
          .then(data => {
            if (data && data.latitude && data.longitude) {
              onPosition(data.latitude, data.longitude, 1000);
            }
          })
          .catch(() => {
            if (icon) icon.style.animation = '';
            if (btnText) btnText.textContent = 'Detect Live GPS';
          });
      }
    });
  }

  document.querySelectorAll('.preset-loc-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const loc = this.getAttribute('data-loc');
      const input = document.getElementById('inputCourseLocation');
      if (input) {
        input.value = loc;
        document.getElementById('inputCourseLat').value = '';
        document.getElementById('inputCourseLng').value = '';
        const ind = document.getElementById('gpsLiveIndicator');
        if (ind) ind.style.display = 'none';
        const msg = document.getElementById('gpsStatusMessage');
        if (msg) msg.style.display = 'none';
        fetchWeatherForLocation(true);
      }
    });
  });

  const locInput = document.getElementById('inputCourseLocation');
  if (locInput) {
    locInput.addEventListener('input', function() {
      clearTimeout(weatherFetchTimer);
      weatherFetchTimer = setTimeout(() => {
        document.getElementById('inputCourseLat').value = '';
        document.getElementById('inputCourseLng').value = '';
        const ind = document.getElementById('gpsLiveIndicator');
        if (ind) ind.style.display = 'none';
        const msg = document.getElementById('gpsStatusMessage');
        if (msg) msg.style.display = 'none';
        fetchWeatherForLocation(true);
      }, 700);
    });
  }

  const btnRefresh = document.getElementById('btnRefreshWeather');
  if (btnRefresh) {
    btnRefresh.addEventListener('click', function(e) {
      e.preventDefault();
      const lat = document.getElementById('inputCourseLat')?.value;
      const lng = document.getElementById('inputCourseLng')?.value;
      if (lat && lng) {
        fetchWeatherByCoords(lat, lng);
      } else {
        fetchWeatherForLocation(true);
      }
    });
  }

  const speedInput = document.getElementById('inputGreenSpeed');
  if (speedInput) {
    speedInput.addEventListener('input', updateLivePreview);
  }

  // Auto-refresh weather every 1 hour (3600 seconds)
  const HOURLY_WEATHER_INTERVAL = 60 * 60 * 1000;
  setInterval(function() {
    const lat = document.getElementById('inputCourseLat')?.value;
    const lng = document.getElementById('inputCourseLng')?.value;
    if (lat && lng) {
      fetchWeatherByCoords(lat, lng, true);
    } else {
      fetchWeatherForLocation(true);
    }
    if (typeof showToast === 'function') {
      showToast('Live weather auto-refreshed (1-hr interval)', 'success');
    }
  }, HOURLY_WEATHER_INTERVAL);
});
</script>
<style>
@keyframes spin { 100% { transform: rotate(360deg); } }
@keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(0.85); } }
</style>
