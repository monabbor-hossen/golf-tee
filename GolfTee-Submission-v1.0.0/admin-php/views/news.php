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
    <button class="btn btn-primary" onclick="openCreateNewsModal()" style="display:inline-flex;align-items:center;gap:6px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/>
        <line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Publish News
    </button>
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
        <div style="width:48px;height:48px;border-radius:50%;background:#f3f4f6;color:var(--forest);display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 5L6 9H2v6h4l5 4V5z"/>
            <path d="M15.54 8.46a5 5 0 0 1 0 7.07"/>
            <path d="M19.07 4.93a10 10 0 0 1 0 14.14"/>
          </svg>
        </div>
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
               style="width:84px;height:84px;border-radius:12px;object-fit:cover;flex-shrink:0;background:var(--offwhite)"
               onerror="this.style.display='none'"/>
          <?php else: ?>
          <div style="width:84px;height:84px;border-radius:12px;background:var(--offwhite);color:var(--forest);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="28" height="28" stroke-linecap="round" stroke-linejoin="round">
              <path d="M11 5L6 9H2v6h4l5 4V5z"/>
              <path d="M15.54 8.46a5 5 0 0 1 0 7.07"/>
              <path d="M19.07 4.93a10 10 0 0 1 0 14.14"/>
            </svg>
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

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:8px;border-top:1px solid #f9fafb;flex-wrap:wrap;gap:8px">
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
                <a href="news.php?category=<?= urlencode($category) ?>&id=<?= $art['id'] ?>" class="btn btn-secondary btn-sm" style="font-size:.75rem;display:inline-flex;align-items:center;gap:4px">
                  Preview
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"/>
                  </svg>
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
          <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger)" aria-label="Delete">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="3 6 5 6 21 6"/>
              <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
              <path d="M10 11v6"/>
              <path d="M14 11v6"/>
              <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
            </svg>
          </button>
        </form>
        <a href="news.php?category=<?= urlencode($category) ?>" class="btn btn-ghost btn-sm hide-desktop" style="color:#9ca3af;padding:4px 8px;display:inline-flex;align-items:center" title="Close details">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </a>
      </div>
    </div>

    <div class="panel-body" style="padding:16px">
      <!-- Member App Live Card Preview -->
      <p style="font-size:.65rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;display:flex;align-items:center;gap:6px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" stroke-linecap="round" stroke-linejoin="round">
          <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
          <line x1="12" y1="18" x2="12.01" y2="18"/>
        </svg>
        Live Mobile Feed Preview
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
      <button class="close-btn" onclick="closeModal('createNewsModal')" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <form method="post" enctype="multipart/form-data">
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
          <textarea name="content" class="input" rows="3" placeholder="Detailed announcement text..."></textarea>
        </div>

        <!-- Image Upload & Live Preview Section -->
        <div class="field-group">
          <label class="field-label" style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-weight:700">Cover Image</span>
            <span style="font-size:.72rem;color:#9ca3af;font-weight:400">JPG, PNG, WebP or GIF up to 8MB</span>
          </label>

          <!-- Live Image Preview Area / Dropzone -->
          <div id="createImgPreviewWrapper" style="margin-bottom:10px">
            <div id="createImgEmptyBox"
                 onclick="document.getElementById('createImageFileInput').click()"
                 ondragover="handleDragOver(event, this)"
                 ondragleave="handleDragLeave(event, this)"
                 ondrop="handleImageDrop(event, 'create', this)"
                 style="border:2px dashed #cbd5e1;border-radius:12px;padding:22px 16px;text-align:center;background:#f8fafc;cursor:pointer;transition:all .15s ease">
              <div style="width:42px;height:42px;border-radius:50%;background:#e2e8f0;display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px;color:var(--forest)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                  <circle cx="8.5" cy="8.5" r="1.5"/>
                  <polyline points="21 15 16 10 5 21"/>
                </svg>
              </div>
              <p style="font-weight:700;font-size:.85rem;color:#1e293b;margin:0">Click to upload image or drag & drop</p>
              <p style="font-size:.75rem;color:#64748b;margin:4px 0 0">SVG, PNG, JPG, or WebP (max 8MB)</p>
            </div>

            <div id="createImgDisplayBox" class="hidden" style="position:relative;border-radius:12px;overflow:hidden;border:1.5px solid var(--border);background:#0f172a;box-shadow:0 2px 8px rgba(0,0,0,.08)">
              <img id="createImgElement" src="" alt="Preview" style="width:100%;height:160px;object-fit:cover;display:block" onerror="handleImageError('create')"/>
              <div style="position:absolute;top:8px;left:8px;background:rgba(0,0,0,.65);backdrop-filter:blur(4px);color:#fff;border-radius:6px;padding:3px 8px;font-size:.7rem;font-weight:700;letter-spacing:.03em;display:flex;align-items:center;gap:5px">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                <span>Image Preview</span>
              </div>
              <div style="position:absolute;top:8px;right:8px;display:flex;gap:6px">
                <button type="button" onclick="document.getElementById('createImageFileInput').click()"
                        style="background:rgba(255,255,255,.95);color:#0f172a;border:none;border-radius:6px;padding:5px 9px;font-size:.75rem;font-weight:700;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.25);display:inline-flex;align-items:center;gap:4px">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"/>
                    <polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                  </svg>
                  Change
                </button>
                <button type="button" onclick="clearNewsImage('create')"
                        style="background:rgba(220,38,38,.9);color:#fff;border:none;border-radius:6px;padding:5px 9px;font-size:.75rem;font-weight:700;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.25);display:inline-flex;align-items:center;gap:4px">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                  </svg>
                  Remove
                </button>
              </div>
              <div id="createImgMetaBar" style="padding:6px 12px;background:#1e293b;color:#cbd5e1;font-size:.72rem;display:flex;justify-content:space-between;align-items:center">
                <span id="createImgFileName">Image selected</span>
                <span style="color:#94a3b8">Will be displayed on news cards</span>
              </div>
            </div>
          </div>

          <!-- Hidden File Input -->
          <input type="file" name="image_file" id="createImageFileInput" accept="image/*" style="display:none"
                 onchange="handleImageFileSelect(this, 'create')"/>

          <!-- Action Bar: Upload Button -->
          <div style="display:flex;align-items:center;gap:8px">
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('createImageFileInput').click()" style="display:inline-flex;align-items:center;gap:6px">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
              </svg>
              Upload Image
            </button>
            <span style="font-size:.75rem;color:#9ca3af">or enter URL below</span>
          </div>

          <!-- URL fallback input -->
          <input name="image_url" id="createImageUrlInput" class="input" style="height:32px;font-size:.8rem;margin-top:8px"
                 placeholder="Or paste external image URL (https://...)"
                 oninput="handleImageUrlInput(this.value, 'create')"/>
        </div>

        <div class="modal-grid-2">
          <div class="field-group">
            <label class="field-label">Publish Date</label>
            <input type="date" name="published_at" class="input" value="<?= date('Y-m-d') ?>" required/>
          </div>
          <div class="field-group" style="justify-content:center">
            <label style="display:flex;align-items:center;gap:10px;font-size:.875rem;font-weight:600;cursor:pointer;margin-top:20px">
              <input type="checkbox" name="is_published" value="1" checked style="width:16px;height:16px;accent-color:var(--forest)"/>
              Publish immediately
            </label>
          </div>
        </div>
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
        <p class="modal-desc">Update announcement content, image, or publication status.</p>
      </div>
      <button class="close-btn" onclick="closeModal('editNewsModal')" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <form method="post" enctype="multipart/form-data">
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
          <textarea name="content" id="editNewsContent" class="input" rows="3"></textarea>
        </div>

        <!-- Image Upload & Live Preview Section (Edit) -->
        <div class="field-group">
          <label class="field-label" style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-weight:700">Cover Image</span>
            <span style="font-size:.72rem;color:#9ca3af;font-weight:400">JPG, PNG, WebP or GIF up to 8MB</span>
          </label>

          <!-- Live Image Preview Area / Dropzone -->
          <div id="editImgPreviewWrapper" style="margin-bottom:10px">
            <div id="editImgEmptyBox"
                 onclick="document.getElementById('editImageFileInput').click()"
                 ondragover="handleDragOver(event, this)"
                 ondragleave="handleDragLeave(event, this)"
                 ondrop="handleImageDrop(event, 'edit', this)"
                 style="border:2px dashed #cbd5e1;border-radius:12px;padding:22px 16px;text-align:center;background:#f8fafc;cursor:pointer;transition:all .15s ease">
              <div style="width:42px;height:42px;border-radius:50%;background:#e2e8f0;display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px;color:var(--forest)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                  <circle cx="8.5" cy="8.5" r="1.5"/>
                  <polyline points="21 15 16 10 5 21"/>
                </svg>
              </div>
              <p style="font-weight:700;font-size:.85rem;color:#1e293b;margin:0">Click to upload image or drag & drop</p>
              <p style="font-size:.75rem;color:#64748b;margin:4px 0 0">SVG, PNG, JPG, or WebP (max 8MB)</p>
            </div>

            <div id="editImgDisplayBox" class="hidden" style="position:relative;border-radius:12px;overflow:hidden;border:1.5px solid var(--border);background:#0f172a;box-shadow:0 2px 8px rgba(0,0,0,.08)">
              <img id="editImgElement" src="" alt="Preview" style="width:100%;height:160px;object-fit:cover;display:block" onerror="handleImageError('edit')"/>
              <div style="position:absolute;top:8px;left:8px;background:rgba(0,0,0,.65);backdrop-filter:blur(4px);color:#fff;border-radius:6px;padding:3px 8px;font-size:.7rem;font-weight:700;letter-spacing:.03em;display:flex;align-items:center;gap:5px">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                <span>Image Preview</span>
              </div>
              <div style="position:absolute;top:8px;right:8px;display:flex;gap:6px">
                <button type="button" onclick="document.getElementById('editImageFileInput').click()"
                        style="background:rgba(255,255,255,.95);color:#0f172a;border:none;border-radius:6px;padding:5px 9px;font-size:.75rem;font-weight:700;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.25);display:inline-flex;align-items:center;gap:4px">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"/>
                    <polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                  </svg>
                  Change
                </button>
                <button type="button" onclick="clearNewsImage('edit')"
                        style="background:rgba(220,38,38,.9);color:#fff;border:none;border-radius:6px;padding:5px 9px;font-size:.75rem;font-weight:700;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.25);display:inline-flex;align-items:center;gap:4px">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                  </svg>
                  Remove
                </button>
              </div>
              <div id="editImgMetaBar" style="padding:6px 12px;background:#1e293b;color:#cbd5e1;font-size:.72rem;display:flex;justify-content:space-between;align-items:center">
                <span id="editImgFileName">Image preview</span>
                <span style="color:#94a3b8">Will be displayed on news cards</span>
              </div>
            </div>
          </div>

          <!-- Hidden File Input -->
          <input type="file" name="image_file" id="editImageFileInput" accept="image/*" style="display:none"
                 onchange="handleImageFileSelect(this, 'edit')"/>

          <!-- Action Bar: Upload Button -->
          <div style="display:flex;align-items:center;gap:8px">
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('editImageFileInput').click()" style="display:inline-flex;align-items:center;gap:6px">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
              </svg>
              Upload Image
            </button>
            <span style="font-size:.75rem;color:#9ca3af">or enter URL below</span>
          </div>

          <!-- URL fallback input -->
          <input name="image_url" id="editNewsImage" class="input" style="height:32px;font-size:.8rem;margin-top:8px"
                 placeholder="Or paste external image URL (https://...)"
                 oninput="handleImageUrlInput(this.value, 'edit')"/>
        </div>

        <div class="modal-grid-2">
          <div class="field-group">
            <label class="field-label">Publish Date</label>
            <input type="date" name="published_at" id="editNewsDate" class="input" required/>
          </div>
          <div class="field-group" style="justify-content:center">
            <label style="display:flex;align-items:center;gap:10px;font-size:.875rem;font-weight:600;cursor:pointer;margin-top:20px">
              <input type="checkbox" name="is_published" id="editNewsPublished" value="1" style="width:16px;height:16px;accent-color:var(--forest)"/>
              Published (visible to members)
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('editNewsModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function showImagePreview(prefix, src, metaText) {
  const emptyBox = document.getElementById(prefix + 'ImgEmptyBox');
  const displayBox = document.getElementById(prefix + 'ImgDisplayBox');
  const imgElem = document.getElementById(prefix + 'ImgElement');
  const metaSpan = document.getElementById(prefix + 'ImgFileName');

  if (src && src.trim()) {
    imgElem.src = src;
    if (metaSpan) {
      metaSpan.textContent = metaText || (src.startsWith('data:') ? 'Local file selected' : src.split('/').pop().split('?')[0]);
    }
    emptyBox.classList.add('hidden');
    displayBox.classList.remove('hidden');
  } else {
    imgElem.src = '';
    if (metaSpan) metaSpan.textContent = '';
    displayBox.classList.add('hidden');
    emptyBox.classList.remove('hidden');
  }
}

function handleImageFileSelect(input, prefix) {
  if (input.files && input.files[0]) {
    const file = input.files[0];
    const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
    const meta = file.name + ' (' + sizeMb + ' MB)';
    const reader = new FileReader();
    reader.onload = function(e) {
      showImagePreview(prefix, e.target.result, meta);
    };
    reader.readAsDataURL(file);
    // Clear URL text input to avoid confusion
    const urlInput = document.getElementById(prefix === 'create' ? 'createImageUrlInput' : 'editNewsImage');
    if (urlInput) urlInput.value = '';
  }
}

function handleDragOver(e, el) {
  e.preventDefault();
  e.stopPropagation();
  el.style.borderColor = 'var(--forest)';
  el.style.background = 'rgba(30,86,49,0.06)';
}

function handleDragLeave(e, el) {
  e.preventDefault();
  e.stopPropagation();
  el.style.borderColor = '#cbd5e1';
  el.style.background = '#f8fafc';
}

function handleImageDrop(e, prefix, el) {
  e.preventDefault();
  e.stopPropagation();
  handleDragLeave(e, el);

  if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
    const fileInput = document.getElementById(prefix + 'ImageFileInput');
    if (fileInput) {
      fileInput.files = e.dataTransfer.files;
      handleImageFileSelect(fileInput, prefix);
    }
  }
}

function handleImageUrlInput(val, prefix) {
  const trimmed = (val || '').trim();
  if (trimmed) {
    // Clear any file input
    const fileInput = document.getElementById(prefix + 'ImageFileInput');
    if (fileInput) fileInput.value = '';
    showImagePreview(prefix, trimmed, 'External image link');
  } else {
    showImagePreview(prefix, '');
  }
}

function handleImageError(prefix) {
  const metaSpan = document.getElementById(prefix + 'ImgFileName');
  if (metaSpan) {
    metaSpan.textContent = 'Unable to load image preview from this URL';
  }
}


function clearNewsImage(prefix) {
  const urlInput = document.getElementById(prefix === 'create' ? 'createImageUrlInput' : 'editNewsImage');
  if (urlInput) urlInput.value = '';
  const fileInput = document.getElementById(prefix + 'ImageFileInput');
  if (fileInput) fileInput.value = '';
  showImagePreview(prefix, '');
}

function openCreateNewsModal() {
  const modal = document.getElementById('createNewsModal');
  if (!modal) return;
  const form = modal.querySelector('form');
  if (form) form.reset();
  clearNewsImage('create');
  const dateInput = form.querySelector('input[name="published_at"]');
  if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];
  const pubCheck = form.querySelector('input[name="is_published"]');
  if (pubCheck) pubCheck.checked = true;
  showModal('createNewsModal');
}

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

  const fileInput = document.getElementById('editImageFileInput');
  if (fileInput) fileInput.value = '';

  showImagePreview('edit', art.image_url || '', art.image_url ? 'Current cover image' : '');
  showModal('editNewsModal');
}
</script>
