<?php
declare(strict_types=1);

$categoryPills = [
    'Event'         => 'pill-green',
    'Course Update' => 'pill-amber',
    'Pro Shop'      => 'pill-sand',
    'General'       => 'pill-gray',
];
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Club News</h1>
    <p><?= $total ?> announcements · <?= $publishedCount ?> published to members</p>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-primary" onclick="showModal('createNewsModal')">+ Publish News</button>
  </div>
</div>

<!-- Category Filters -->
<div class="filters">
  <div class="segmented" style="width:100%">
    <?php foreach ($categories as $cat):
      $cnt = $cat === 'All' ? $total : ($counts[$cat] ?? 0);
    ?>
    <a href="news.php?category=<?= urlencode($cat) ?>" class="tab-btn <?= $category === $cat ? 'active' : '' ?>">
      <?= htmlspecialchars($cat) ?> (<?= $cnt ?>)
    </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="panel-layout">
  <!-- News Article List -->
  <div class="panel-main" style="display:flex;flex-direction:column;gap:12px">
    <?php if (empty($articles)): ?>
      <div class="card" style="text-align:center;color:#9ca3af;padding:48px 20px">
        <p style="font-size:2rem;margin-bottom:8px">📢</p>
        <p style="font-weight:700;font-size:1rem;color:#374151">No announcements found</p>
        <p style="font-size:.875rem;margin-top:4px">Click "+ Publish News" to post a new announcement for members.</p>
      </div>
    <?php else: ?>
      <?php foreach ($articles as $art):
        $isSel = $selectedId === (int)$art['id'];
        $pill = $categoryPills[$art['category']] ?? 'pill-gray';
        $statusPill = $art['is_published'] ? 'pill-green' : 'pill-gray';
        $statusText = $art['is_published'] ? 'Published' : 'Draft';
      ?>
      <div class="card" style="padding:16px;border-color:<?= $isSel ? 'var(--forest)' : 'var(--border)' ?>;transition:border-color .15s">
        <div style="display:flex;gap:14px;align-items:flex-start">
          <?php if (!empty($art['image_url'])): ?>
          <img src="<?= htmlspecialchars($art['image_url']) ?>" alt="Thumbnail"
               style="width:84px;height:84px;border-radius:12px;object-cover:cover;object-fit:cover;flex-shrink:0;background:var(--offwhite)"
               onerror="this.style.display='none'"/>
          <?php else: ?>
          <div style="width:84px;height:84px;border-radius:12px;background:var(--offwhite);color:var(--forest);display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0">
            📢
          </div>
          <?php endif; ?>

          <div style="flex:1;min-width:0">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap">
              <div style="display:flex;align-items:center;gap:6px">
                <span class="pill <?= $pill ?>"><?= htmlspecialchars($art['category']) ?></span>
                <?php if (!empty($art['badge_text'])): ?>
                <span style="font-size:.7rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">
                  <?= htmlspecialchars($art['badge_text']) ?>
                </span>
                <?php endif; ?>
              </div>
              <span class="pill <?= $statusPill ?>"><?= $statusText ?></span>
            </div>

            <a href="news.php?category=<?= urlencode($category) ?>&id=<?= $art['id'] ?>"
               style="text-decoration:none;color:#111827;font-weight:700;font-size:.95rem;line-height:1.3;display:block">
              <?= htmlspecialchars($art['title']) ?>
            </a>

            <p style="font-size:.8rem;color:#6b7280;margin-top:4px;line-height:1.4">
              <?= htmlspecialchars($art['summary']) ?>
            </p>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:8px;border-top:1px solid #f9fafb">
              <span style="font-size:.75rem;color:#9ca3af">
                Published <?= date('M j, Y', strtotime($art['published_at'])) ?>
              </span>
              <div style="display:flex;gap:6px;align-items:center">
                <form method="post" style="margin:0">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="toggle_publish">
                  <input type="hidden" name="id" value="<?= $art['id'] ?>">
                  <button type="submit" class="btn btn-ghost btn-sm" style="font-size:.75rem">
                    <?= $art['is_published'] ? 'Unpublish' : 'Publish' ?>
                  </button>
                </form>
                <button type="button" class="btn btn-ghost btn-sm" style="font-size:.75rem"
                        onclick="openEditNewsModal(<?= htmlspecialchars(json_encode($art, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">
                  Edit
                </button>
                <a href="news.php?category=<?= urlencode($category) ?>&id=<?= $art['id'] ?>" class="btn btn-secondary btn-sm" style="font-size:.75rem">
                  Preview ›
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Detail & Mobile App Preview Panel -->
  <?php if ($selectedNews): ?>
  <div class="detail-panel">
    <div class="panel-header">
      <div>
        <p style="font-weight:700;font-size:.9rem">Announcement Details</p>
        <p style="font-size:.75rem;color:#9ca3af">Member App Preview</p>
      </div>
      <div style="display:flex;align-items:center;gap:6px">
        <button type="button" class="btn btn-ghost btn-sm"
                onclick="openEditNewsModal(<?= htmlspecialchars(json_encode($selectedNews, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">
          Edit
        </button>
        <form method="post" style="margin:0" onsubmit="return confirm('Delete this announcement?')">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $selectedNews['id'] ?>">
          <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger)">🗑</button>
        </form>
        <a href="news.php?category=<?= urlencode($category) ?>" class="btn btn-ghost btn-sm hide-desktop" style="color:#9ca3af;font-size:1rem;padding:0 8px" title="Close details">✕</a>
      </div>
    </div>

    <div class="panel-body" style="padding:16px">
      <!-- Member App Live Card Preview -->
      <p style="font-size:.65rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px">
        📱 Live Mobile Feed Preview
      </p>

      <div style="background:#fff;border-radius:16px;border:1px solid var(--border);overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.04);margin-bottom:16px">
        <?php if (!empty($selectedNews['image_url'])): ?>
        <img src="<?= htmlspecialchars($selectedNews['image_url']) ?>" alt="Preview"
             style="width:100%;height:130px;object-fit:cover;display:block"/>
        <?php endif; ?>
        <div style="padding:14px">
          <p style="font-size:.68rem;font-weight:800;text-transform:uppercase;color:var(--forest);letter-spacing:.04em">
            <?= htmlspecialchars($selectedNews['badge_text'] ?: ($selectedNews['category'] . ' · ' . date('M j', strtotime($selectedNews['published_at'])))) ?>
          </p>
          <p style="font-weight:800;font-size:.95rem;line-height:1.3;margin-top:4px">
            <?= htmlspecialchars($selectedNews['title']) ?>
          </p>
          <p style="font-size:.78rem;color:#6b7280;margin-top:4px;line-height:1.4">
            <?= htmlspecialchars($selectedNews['summary']) ?>
          </p>
        </div>
      </div>

      <!-- Info Rows -->
      <div class="info-row">
        <span class="info-label">Category</span>
        <span class="info-val"><?= htmlspecialchars($selectedNews['category']) ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Status</span>
        <span class="info-val">
          <span class="pill <?= $selectedNews['is_published'] ? 'pill-green' : 'pill-gray' ?>">
            <?= $selectedNews['is_published'] ? 'Published' : 'Draft' ?>
          </span>
        </span>
      </div>
      <div class="info-row">
        <span class="info-label">Published date</span>
        <span class="info-val"><?= date('F j, Y', strtotime($selectedNews['published_at'])) ?></span>
      </div>

      <?php if (!empty($selectedNews['content'])): ?>
      <div style="margin-top:16px">
        <p style="font-size:.75rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Full Content</p>
        <p style="font-size:.85rem;line-height:1.5;color:#374151;white-space:pre-wrap"><?= htmlspecialchars($selectedNews['content']) ?></p>
      </div>
      <?php endif; ?>

      <div style="margin-top:20px;display:flex;gap:8px">
        <form method="post" style="flex:1;margin:0">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="toggle_publish">
          <input type="hidden" name="id" value="<?= $selectedNews['id'] ?>">
          <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">
            <?= $selectedNews['is_published'] ? 'Set as Draft' : 'Publish Now' ?>
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Create News Modal -->
<div class="modal-backdrop hidden" id="createNewsModal">
  <div class="modal wide">
    <div class="modal-header">
      <div>
        <p class="modal-title">Publish Club News</p>
        <p class="modal-desc">Announcements appear in the member app and website feeds.</p>
      </div>
      <button class="close-btn" onclick="closeModal('createNewsModal')">✕</button>
    </div>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="field-group">
          <label class="field-label">Headline / Title</label>
          <input name="title" class="input" required placeholder="e.g. Autumn Member Classic — registration is open"/>
        </div>
        <div class="modal-grid-2">
          <div class="field-group">
            <label class="field-label">Category</label>
            <select name="category" class="input select-field" required>
              <option value="Event">Event</option>
              <option value="Course Update">Course Update</option>
              <option value="Pro Shop">Pro Shop</option>
              <option value="General" selected>General</option>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label">Badge Tag (optional)</label>
            <input name="badge_text" class="input" placeholder="e.g. Event · Nov 9 or 15% Off"/>
          </div>
        </div>
        <div class="field-group">
          <label class="field-label">Summary / Teaser</label>
          <input name="summary" class="input" required placeholder="Short summary shown in mobile card feed"/>
        </div>
        <div class="field-group">
          <label class="field-label">Full Story / Details</label>
          <textarea name="content" class="input" rows="4" placeholder="Detailed announcement text..."></textarea>
        </div>
        <div class="modal-grid-2">
          <div class="field-group">
            <label class="field-label">Cover Image URL</label>
            <input name="image_url" class="input" placeholder="https://images.unsplash.com/photo-..."/>
          </div>
          <div class="field-group">
            <label class="field-label">Publish Date</label>
            <input type="date" name="published_at" class="input" value="<?= date('Y-m-d') ?>" required/>
          </div>
        </div>
        <label style="display:flex;align-items:center;gap:10px;font-size:.875rem;font-weight:600;cursor:pointer;margin-top:4px">
          <input type="checkbox" name="is_published" value="1" checked style="width:16px;height:16px;accent-color:var(--forest)"/>
          Publish immediately (visible to all members)
        </label>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('createNewsModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Publish announcement</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit News Modal -->
<div class="modal-backdrop hidden" id="editNewsModal">
  <div class="modal wide">
    <div class="modal-header">
      <div>
        <p class="modal-title">Edit Club News</p>
        <p class="modal-desc">Update announcement content or publication status.</p>
      </div>
      <button class="close-btn" onclick="closeModal('editNewsModal')">✕</button>
    </div>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="editNewsId">
      <div class="modal-body">
        <div class="field-group">
          <label class="field-label">Headline / Title</label>
          <input name="title" id="editNewsTitle" class="input" required/>
        </div>
        <div class="modal-grid-2">
          <div class="field-group">
            <label class="field-label">Category</label>
            <select name="category" id="editNewsCategory" class="input select-field" required>
              <option value="Event">Event</option>
              <option value="Course Update">Course Update</option>
              <option value="Pro Shop">Pro Shop</option>
              <option value="General">General</option>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label">Badge Tag (optional)</label>
            <input name="badge_text" id="editNewsBadge" class="input"/>
          </div>
        </div>
        <div class="field-group">
          <label class="field-label">Summary / Teaser</label>
          <input name="summary" id="editNewsSummary" class="input" required/>
        </div>
        <div class="field-group">
          <label class="field-label">Full Story / Details</label>
          <textarea name="content" id="editNewsContent" class="input" rows="4"></textarea>
        </div>
        <div class="modal-grid-2">
          <div class="field-group">
            <label class="field-label">Cover Image URL</label>
            <input name="image_url" id="editNewsImage" class="input"/>
          </div>
          <div class="field-group">
            <label class="field-label">Publish Date</label>
            <input type="date" name="published_at" id="editNewsDate" class="input" required/>
          </div>
        </div>
        <label style="display:flex;align-items:center;gap:10px;font-size:.875rem;font-weight:600;cursor:pointer;margin-top:4px">
          <input type="checkbox" name="is_published" id="editNewsPublished" value="1" style="width:16px;height:16px;accent-color:var(--forest)"/>
          Published (visible to members)
        </label>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('editNewsModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditNewsModal(art) {
  if (!art) return;
  document.getElementById('editNewsId').value = art.id || '';
  document.getElementById('editNewsTitle').value = art.title || '';
  document.getElementById('editNewsCategory').value = art.category || 'General';
  document.getElementById('editNewsBadge').value = art.badge_text || '';
  document.getElementById('editNewsSummary').value = art.summary || '';
  document.getElementById('editNewsContent').value = art.content || '';
  document.getElementById('editNewsImage').value = art.image_url || '';
  document.getElementById('editNewsDate').value = art.published_at || '';
  document.getElementById('editNewsPublished').checked = Boolean(Number(art.is_published));
  showModal('editNewsModal');
}
</script>
