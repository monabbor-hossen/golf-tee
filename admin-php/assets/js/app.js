/* ── App JavaScript ────────────────────────────────── */
const APP_URL = document.documentElement.dataset.appUrl || '';

// Prevent form resubmission dialog on browser refresh
if (window.history.replaceState) {
  window.history.replaceState(null, null, window.location.href);
}

// ── Toast ──────────────────────────────────────────── //
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  if (!t) return;
  t.innerHTML = `<span class="toast-check" style="display:inline-flex;align-items:center"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span> ${msg}`;
  t.classList.remove('hidden');
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.add('hidden'), 2800);
}

// ── Dropdown helpers ──────────────────────────────── //
function toggleDropdown(id) {
  const el = document.getElementById(id);
  if (!el) return;
  const wasHidden = el.classList.contains('hidden');
  // close all first
  document.querySelectorAll('.notif-dropdown, .profile-dropdown, .search-dropdown').forEach(d => d.classList.add('hidden'));
  if (wasHidden) el.classList.remove('hidden');
}
function toggleNotifs()  { toggleDropdown('notifDropdown'); }
function toggleProfile() { toggleDropdown('profileDropdown'); }

// Close dropdowns when clicking outside
document.addEventListener('click', e => {
  ['notifWrap','profileWrap','searchWrap'].forEach(id => {
    const w = document.getElementById(id);
    if (w && !w.contains(e.target)) {
      w.querySelectorAll('.notif-dropdown,.profile-dropdown,.search-dropdown').forEach(d => d.classList.add('hidden'));
    }
  });
});

function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

// ── Mark all notifications read ───────────────────── //
async function markAllRead() {
  await fetch('api/notifications.php?action=mark_all_read', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': getCsrfToken() }
  });
  document.querySelectorAll('.notif-item').forEach(el => el.classList.remove('unread'));
  const badge = document.querySelector('.notif-badge');
  if (badge) badge.remove();
}

// ── Click notification ────────────────────────────── //
function handleNotifClick(el) {
  if (!el) return;
  const id = el.dataset.id;
  const page = el.dataset.page || '';
  const focusJson = el.dataset.focus || '';
  clickNotif(id, page, focusJson);
}

async function clickNotif(id, page, focusJson) {
  try {
    if (id) {
      await fetch(`api/notifications.php?action=mark_read&id=${encodeURIComponent(id)}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrfToken() }
      });
    }
  } catch (err) {
    console.error('Failed to mark notification as read:', err);
  }
  const pageMap = {
    'Dashboard':   'index.php',
    'Master Grid': 'mastergrid.php',
    'Waitlist':    'waitlist.php',
    'Events':      'events.php',
    'Users':       'members.php',
    'Members':     'members.php',
    'Payments':    'payments.php',
    'Reports':     'reports.php',
    'Settings':    'settings.php',
  };
  let url = pageMap[page] || 'index.php';
  if (focusJson) {
    try {
      const focus = typeof focusJson === 'string' ? JSON.parse(focusJson) : focusJson;
      const params = new URLSearchParams(focus).toString();
      if (params) url += '?' + params;
    } catch(e) {}
  }
  window.location.href = url;
}

// ── Mobile Drawer ─────────────────────────────────── //
function openDrawer() {
  document.getElementById('sidebarDrawer').classList.add('open');
  document.getElementById('drawerOverlay').classList.add('open');
}
function closeDrawer() {
  document.getElementById('sidebarDrawer').classList.remove('open');
  document.getElementById('drawerOverlay').classList.remove('open');
}

// ── Global Search ─────────────────────────────────── //
let searchTimer;
async function handleGlobalSearch(q) {
  clearTimeout(searchTimer);
  const res = document.getElementById('searchResults');
  if (!q.trim()) { res.classList.add('hidden'); return; }
  searchTimer = setTimeout(async () => {
    const data = await fetch(`api/search.php?q=${encodeURIComponent(q)}`).then(r => r.json());
    if (!data.results || data.results.length === 0) {
      res.innerHTML = `<p style="padding:20px;text-align:center;color:#9ca3af;font-size:.875rem">No results for "${q}"</p>`;
    } else {
      let html = '', lastGroup = '';
      data.results.forEach(r => {
        if (r.group !== lastGroup) {
          html += `<p class="search-group-label">${r.group}</p>`;
          lastGroup = r.group;
        }
        html += `<div class="search-result" onclick="window.location.href='${r.url}'">
          <span class="search-result-icon">${r.icon}</span>
          <div class="search-result-info">
            <span class="search-result-title">${r.title}</span>
            <span class="search-result-sub">${r.sub}</span>
          </div>
        </div>`;
      });
      res.innerHTML = html;
    }
    res.classList.remove('hidden');
  }, 200);
}

// ── Toggle helpers ────────────────────────────────── //
function showModal(id)  { document.getElementById(id)?.classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id)?.classList.add('hidden'); }

// Close modal on backdrop click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-backdrop')) {
    e.target.classList.add('hidden');
  }
});

// ── Confirm dialog ────────────────────────────────── //
function confirmAction(msg, onConfirm) {
  if (window.confirm(msg)) onConfirm();
}

// ── Fetch helpers ──────────────────────────────────── //
async function apiPost(url, data) {
  const r = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken()
    },
    body: JSON.stringify(data)
  });
  return r.json();
}
async function apiGet(url) {
  return fetch(url).then(r => r.json());
}

// ── Weather 1-Hour Auto-Refresh ─────────────────────── //
const HOURLY_WEATHER_MS = 60 * 60 * 1000; // 1 hour (3600000ms)

function getWeatherEmoji(desc) {
  if (!desc) return '⛅';
  const d = desc.toLowerCase();
  if (d.includes('thunder') || d.includes('storm')) return '⚡';
  if (d.includes('rain') || d.includes('shower') || d.includes('drizzle')) return '🌧️';
  if (d.includes('snow') || d.includes('flurr') || d.includes('ice')) return '❄️';
  if (d.includes('overcast') || d.includes('fog') || d.includes('haze') || d.includes('mist')) return '☁️';
  if (d.includes('cloud') || d.includes('partly')) return '⛅';
  return '☀️';
}

async function refreshAdminWeather(force = false) {
  try {
    const url = `api/mobile.php?action=weather${force ? '&refresh=1' : ''}`;
    const res = await fetch(url).then(r => r.json());
    if (res && res.status === 'ok' && res.conditions) {
      const c = res.conditions;
      const iconEl = document.getElementById('topbarWeatherIcon');
      const tempEl = document.getElementById('topbarWeatherTemp');
      const descEl = document.getElementById('topbarWeatherDesc');
      if (tempEl) tempEl.textContent = c.weather || '--°';
      if (descEl) descEl.textContent = c.weather_desc ? `${c.weather_desc} · ${c.wind}` : '';
      if (iconEl) iconEl.textContent = getWeatherEmoji(c.weather_desc);
    }
  } catch (e) {
    console.warn('Weather auto-refresh error:', e);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const topbar = document.getElementById('topbarWeather');
  if (topbar) {
    refreshAdminWeather(false);
  }
  // Auto-refresh weather every 1 hour across all admin views
  setInterval(() => {
    refreshAdminWeather(true);
  }, HOURLY_WEATHER_MS);
});

