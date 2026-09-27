<?php
require_once __DIR__ . '/../includes/auth.php';
require_client(); // customers only — staff are sent to their own dashboard
require_once __DIR__ . '/../includes/legal.php';
require_agreements(); // clients must accept the latest Terms & Privacy first
require_once __DIR__ . '/../includes/helpers.php'; // company_name() for branding

$active_page = 'request_quote';

// Design gallery (managed in admin Settings → Design Gallery).
require_once __DIR__ . '/../includes/db.php';
$galleryImages = [];
try {
    $galleryImages = db()->query("SELECT file_path, label FROM gallery_images ORDER BY sort_order, id")->fetchAll();
} catch (Throwable $e) { $galleryImages = []; }

// A quote request needs the client's contact number + an installation address so
// we can prepare and deliver the quotation. The address is picked from their saved
// addresses (default preselected); anything missing is collected in a pop-up on
// this page, so nothing they've already typed is lost.
$__uid = (int) (current_user()['id'] ?? 0);
$profilePhone = $meName = $meEmail = '';
$addresses = [];
if ($__uid) {
    try {
        $st = db()->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
        $st->execute([$__uid]);
        $pr = $st->fetch() ?: [];
        $profilePhone = trim((string) ($pr['phone'] ?? ''));
        $meName       = (string) ($pr['full_name'] ?? '');
        $meEmail      = (string) ($pr['email'] ?? '');
    } catch (Throwable $e) { /* leave blank → will prompt */ }
    $addresses = client_addresses($__uid);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Request a Quote – <?= function_exists('company_name') ? htmlspecialchars(company_name()) : 'Vast Solutions' ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="dashboard.css"/>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    /* ── Reference trigger row ── */
    .btn-browse-ref {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.5rem 1.1rem;
      background: var(--teal, #2da89a);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 0.85rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
      white-space: nowrap;
    }
    .btn-browse-ref:hover { background: #248a7e; }
    .ref-trigger-row {
      display: flex;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .ref-preview-box {
      display: none;
      align-items: center;
      gap: 0.6rem;
    }
    .ref-preview-box img {
      width: 60px;
      height: 45px;
      object-fit: cover;
      border-radius: 6px;
      border: 2px solid var(--teal, #2da89a);
    }
    .ref-preview-box .ref-remove-btn {
      font-size: 0.75rem;
      color: #999;
      background: none;
      border: none;
      cursor: pointer;
      padding: 0;
      display: block;
    }
    .ref-preview-box .ref-remove-btn:hover { color: #c0392b; }

    /* ── Reference modal grid ── */
    .reference-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
      gap: 0.8rem;
    }
    .reference-item {
      cursor: pointer;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      overflow: hidden;
      transition: border-color 0.2s, box-shadow 0.2s;
      text-align: center;
      background: #fff;
      user-select: none;
    }
    .ref-img-wrap { position: relative; overflow: hidden; }
    .reference-item img {
      width: 100%;
      height: 110px;
      object-fit: cover;
      display: block;
      transition: transform 0.2s;
    }
    .reference-item:hover img { transform: scale(1.04); }
    .ref-zoom-btn {
      position: absolute;
      top: 6px;
      right: 6px;
      background: rgba(0,0,0,0.55);
      border: none;
      border-radius: 5px;
      color: #fff;
      width: 28px;
      height: 28px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      cursor: pointer;
      opacity: 0;
      transition: opacity 0.2s;
      z-index: 2;
    }
    .ref-img-wrap:hover .ref-zoom-btn { opacity: 1; }
    .ref-label {
      display: block;
      font-size: 0.75rem;
      padding: 0.35rem 0.2rem 0.1rem;
      color: #666;
      font-weight: 600;
    }
    .ref-dim {
      display: block;
      font-size: 0.66rem;
      color: #9ca3af;
      padding: 0 0.2rem 0.4rem;
    }
    .reference-item.selected .ref-dim { color: var(--teal, #2da89a); }
    .reference-item.selected {
      border-color: var(--teal, #2da89a);
      box-shadow: 0 0 0 2px var(--teal, #2da89a);
      background: #e8f7f5;
    }
    .reference-item.selected .ref-label {
      color: var(--teal, #2da89a);
      font-weight: 600;
    }

    /* ── Lightbox overlay ── */
    #lightboxOverlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.92);
      z-index: 1200;
      align-items: center;
      justify-content: center;
      flex-direction: column;
    }
    #lightboxOverlay.active { display: flex; }
    #lightboxOverlay img {
      max-width: 88vw;
      max-height: 80vh;
      object-fit: contain;
      border-radius: 6px;
    }
    #lightboxOverlay .lb-label {
      color: #ccc;
      font-size: 0.85rem;
      margin-top: 0.7rem;
    }
    .lb-close-btn {
      position: absolute;
      top: 16px;
      right: 20px;
      background: none;
      border: none;
      color: #fff;
      font-size: 1.6rem;
      cursor: pointer;
      line-height: 1;
    }
    .lb-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(255,255,255,0.12);
      border: none;
      color: #fff;
      font-size: 1.4rem;
      width: 46px;
      height: 46px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background 0.2s;
    }
    .lb-nav:hover { background: rgba(255,255,255,0.28); }
    .lb-prev { left: 16px; }
    .lb-next { right: 16px; }

    /* ── Installation address picker + details pop-up ── */
    .addr-preview {
      margin-top: .45rem; font-size: .78rem; color: #0a7a60; line-height: 1.45;
      background: #f0fdf9; border: 1px solid #ccfbef; border-radius: 8px; padding: .5rem .7rem;
      word-break: break-word;
    }
    .addr-hint { margin-top: .35rem; font-size: .74rem; color: #9ca3af; }
    .addr-hint a { color: #0D9676; font-weight: 600; text-decoration: none; }
    .dm-intro { font-size: .85rem; color: #4b5563; line-height: 1.5; margin-bottom: .9rem; }
    .dm-default { display: flex; align-items: center; gap: .45rem; font-size: .8rem; color: #374151; cursor: pointer; margin: -.2rem 0 .8rem; }
    .dm-note { font-size: .76rem; color: #0a7a60; background: #f0fdf9; border-radius: 8px; padding: .5rem .7rem; }
    .dm-save { background: #0D9676; color: #fff; font-weight: 700; }
    .dm-save:hover { background: #0a7a60; color: #fff; }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
  <div class="topbar">
    <a href="dashboard.php">Portal</a>
    <span class="sep">›</span>
    <span>Request Quote</span>
    <?php include __DIR__ . '/../includes/notif_bell.php'; ?>
    <div class="topbar-user">
      <span class="topbar-user-avatar"><?= strtoupper(mb_substr($_SESSION['full_name'] ?? 'C', 0, 1)) ?></span>
      <span class="topbar-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Client') ?></span>
    </div>
  </div>

  <div class="page-content">
    <h1 class="page-title">Request a Quote</h1>
    <?php
      $__err = $_GET['error'] ?? '';
      $__errMsg = [
        'profile'  => 'Please add your phone number and choose an installation address before submitting a request.',
        'pastdate' => 'The target completion date cannot be in the past. Please choose today or a later date.',
        'name'     => 'Please enter a project name.',
        'material' => 'Please select a material type.',
        'budget'   => 'Please enter a valid estimated budget (a positive amount).',
        'server'   => 'Something went wrong submitting your request. Please try again.',
      ][$__err] ?? '';
      if ($__errMsg):
    ?>
    <div style="background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;font-size:.85rem;padding:.7rem .9rem;border-radius:8px;margin-bottom:1rem;">
      <?= htmlspecialchars($__errMsg) ?>
    </div>
    <?php endif; ?>

    <form action="submit_quote.php" method="POST" enctype="multipart/form-data" id="quoteForm">
      <div class="quote-grid">

        <!-- LEFT COLUMN: Upload + Reference -->
        <div class="quote-col">

        <!-- Upload -->
        <div class="section-card" style="padding: 1.4rem 1.6rem;">
          <div class="section-card-title">Upload Design Files</div>

          <div class="upload-zone" id="dropZone">
            <div class="upload-icon">
              <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <div class="upload-title">Drag &amp; drop files here</div>
            <div class="upload-hint">or <span>click to browse</span> &bull; PDF, DWG, SKP, JPG</div>
            <input type="file" name="design_files[]" id="fileInput" multiple accept=".pdf,.dwg,.skp,.jpg,.jpeg,.png" style="display:none"/>
          </div>

          <div class="file-list" id="fileList"></div>
        </div>

        <!-- REFERENCE DESIGN TRIGGER (below Upload) -->
        <div class="section-card" style="padding: 1rem 1.6rem; margin-top: 1.2rem;">
          <div class="ref-trigger-row">
            <div>
              <div style="font-weight: 700; font-size: 0.9rem;">Reference Design <span style="font-weight: 400; color: #888; font-size: 0.8rem;">(Optional)</span></div>
              <div style="font-size: 0.8rem; color: #888; margin-top: 0.15rem;">No design file? Browse our catalog for a reference style.</div>
            </div>
            <button type="button" class="btn-browse-ref" id="openRefModal">
              <i class="bi bi-images"></i> Browse Designs
            </button>
            <div class="ref-preview-box" id="refPreview">
              <img id="refPreviewImg" src="" alt="Selected reference"/>
              <div>
                <div id="refPreviewName" style="font-size: 0.85rem; font-weight: 600;"></div>
                <button type="button" class="ref-remove-btn" id="clearRefBtn">Remove</button>
              </div>
            </div>
          </div>
          <input type="hidden" name="reference_design" id="referenceDesignInput" value=""/>
        </div>

        </div><!-- /quote-col -->

        <!-- RIGHT: Project Details -->
        <div class="section-card" style="padding: 1.4rem 1.6rem;">
          <div class="section-card-title">Project Details</div>

          <div class="form-group">
            <label class="form-label" for="project_name">Project Name</label>
            <input type="text" id="project_name" name="project_name" class="form-control" placeholder="e.g. Kitchen Cabinets - Unit 4B" required/>
          </div>

          <div class="form-group">
            <label class="form-label" for="category">Category</label>
            <select id="category" name="category" class="form-control" required>
              <option value="" disabled selected>Select category</option>
              <option>Wardrobe</option>
              <option>Kitchen Cabinets</option>
              <option>Bathroom Vanity</option>
              <option>Entertainment Unit</option>
              <option>Office Built-ins</option>
              <option>Custom Furniture</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="material_type">Material Type</label>
            <select id="material_type" name="material_type" class="form-control" required>
              <option value="" disabled selected>Select material</option>
              <option value="Plywood">Plywood</option>
              <option value="MDF">MDF</option>
              <option value="Particle Board">Particle Board</option>
              <option value="Aluminum">Aluminum</option>
              <option value="Steel">Steel</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="dimensions">Dimensions <span style="font-weight:400;color:#9ca3af;">(Optional)</span></label>
            <input type="text" id="dimensions" name="dimensions" class="form-control" placeholder="e.g. 2400 × 720 × 600 mm (W × H × D)" maxlength="150"/>
          </div>

          <div class="form-group">
            <label class="form-label" for="target_completion">Target Completion Date <span style="font-weight:400;color:#9ca3af;">(Optional)</span></label>
            <input type="date" id="target_completion" name="target_completion" class="form-control" min="<?= date('Y-m-d') ?>"/>
          </div>

          <div class="form-group">
            <label class="form-label" for="budget">Estimated Budget</label>
            <input type="text" id="budget" name="budget" class="form-control" placeholder="₱0.00"
                   inputmode="decimal" required title="Enter an estimated amount, e.g. 50000"/>
          </div>

          <div class="form-group">
            <label class="form-label" for="address_id">Installation Address</label>
            <select id="address_id" name="address_id" class="form-control">
              <?php if (!$addresses): ?>
              <option value="" selected disabled>No saved address yet — add one</option>
              <?php endif; ?>
              <?php foreach ($addresses as $a): ?>
              <option value="<?= (int) $a['id'] ?>" data-address="<?= htmlspecialchars($a['address'], ENT_QUOTES) ?>"<?= (int) $a['is_default'] === 1 ? ' selected' : '' ?>>
                <?= htmlspecialchars(($a['label'] ?: 'Address') . ' — ' . $a['address']) ?>
              </option>
              <?php endforeach; ?>
              <option value="__new">+ Add a new address…</option>
            </select>
            <div class="addr-preview" id="addrPreview" style="display:none;"></div>
            <div class="addr-hint">Manage your saved addresses in <a href="settings.php">Settings</a>.</div>
          </div>

          <div class="form-group">
            <label class="form-label" for="notes">Additional Notes</label>
            <textarea id="notes" name="notes" class="form-control" placeholder="Describe your requirements, preferred materials, timeline..." maxlength="1000"></textarea>
          </div>

          <button type="submit" class="btn-submit">Submit Quotation Request</button>
        </div>

      </div>

    </form>
  </div>
</div>

<!-- ── Reference Design Modal ── -->
<div class="modal fade" id="referenceModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select a Reference Design</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p style="font-size: 0.85rem; color: #888; margin-bottom: 1rem;">
          Click a thumbnail to select it as your reference.
          Use the <i class="bi bi-arrows-fullscreen"></i> icon to view the image full size.
        </p>
        <div class="reference-grid" id="referenceGrid">
          <?php if (empty($galleryImages)): ?>
            <p style="color:#888;font-size:.85rem;">No reference designs available yet.</p>
          <?php else: foreach ($galleryImages as $idx => $g):
            $label = $g['label'] ?: ('Design ' . ($idx + 1));
            $src   = '../' . $g['file_path']; ?>
          <div class="reference-item"
               data-value="<?= htmlspecialchars(basename($g['file_path']), ENT_QUOTES) ?>"
               data-label="<?= htmlspecialchars($label, ENT_QUOTES) ?>"
               data-src="<?= htmlspecialchars($src, ENT_QUOTES) ?>"
               data-index="<?= $idx ?>">
            <div class="ref-img-wrap">
              <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($label) ?>"/>
              <button type="button" class="ref-zoom-btn" data-index="<?= $idx ?>" title="View larger">
                <i class="bi bi-arrows-fullscreen"></i>
              </button>
            </div>
            <span class="ref-label"><?= htmlspecialchars($label) ?></span>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmRefBtn" class="btn btn-primary" disabled>Use Selected Design</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Contact details / new address (collected here so the form isn't lost) ── -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border:none;border-radius:14px;">
      <div class="modal-header">
        <h5 class="modal-title" id="dmTitle" style="font-size:1rem;font-weight:700;">Complete your details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="dm-intro" id="dmIntro"></p>
        <div class="form-group" id="dmPhoneWrap">
          <label class="form-label" for="dmPhone">Contact Number</label>
          <input type="tel" id="dmPhone" class="form-control" maxlength="30" placeholder="e.g. 0917 123 4567"/>
        </div>
        <div id="dmAddrWrap">
          <div class="form-group">
            <label class="form-label" for="dmLabel">Address Label <span style="font-weight:400;color:#9ca3af;">(Optional)</span></label>
            <input type="text" id="dmLabel" class="form-control" maxlength="60" placeholder="e.g. Home, Condo Unit, Office"/>
          </div>
          <div class="form-group">
            <label class="form-label" for="dmAddress">Installation Address</label>
            <textarea id="dmAddress" class="form-control" rows="2" maxlength="255" placeholder="House/unit no., street, barangay, city, province"></textarea>
          </div>
          <label class="dm-default" id="dmDefaultWrap"><input type="checkbox" id="dmDefault"/> Make this my default address</label>
        </div>
        <div class="dm-note"><i class="bi bi-shield-check"></i> Your project details stay as they are — nothing you've entered is lost.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn dm-save" id="dmSaveBtn">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Lightbox Overlay ── -->
<div id="lightboxOverlay" role="dialog" aria-modal="true">
  <button class="lb-close-btn" id="lbClose" title="Close">&times;</button>
  <button class="lb-nav lb-prev" id="lbPrev"><i class="bi bi-chevron-left"></i></button>
  <img id="lightboxImg" src="" alt=""/>
  <div class="lb-label" id="lightboxLabel"></div>
  <button class="lb-nav lb-next" id="lbNext"><i class="bi bi-chevron-right"></i></button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const dropZone  = document.getElementById('dropZone');
  const fileInput = document.getElementById('fileInput');
  const fileList  = document.getElementById('fileList');
  let selectedFiles = [];

  dropZone.addEventListener('click', () => fileInput.click());
  dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
  dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
  dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    addFiles([...e.dataTransfer.files]);
  });
  fileInput.addEventListener('change', () => addFiles([...fileInput.files]));

  function addFiles(files) {
    files.forEach(f => { if (!selectedFiles.find(x => x.name === f.name)) selectedFiles.push(f); });
    renderList();
  }
  function renderList() {
    fileList.innerHTML = '';
    selectedFiles.forEach((f, i) => {
      const item = document.createElement('div');
      item.className = 'file-item';
      item.innerHTML = `<span>📄 ${f.name}</span><button type="button" onclick="removeFile(${i})">✕</button>`;
      fileList.appendChild(item);
    });
    // The form posts fileInput's files, so mirror the list into it — otherwise
    // dropped files were never uploaded and removed ones still were.
    try {
      const dt = new DataTransfer();
      selectedFiles.forEach(f => dt.items.add(f));
      fileInput.files = dt.files;
    } catch (e) { /* very old browsers: picker-only uploads still work */ }
  }
  function removeFile(i) { selectedFiles.splice(i, 1); renderList(); }

  // ── Reference Design Modal ──
  // Built from the DB-driven reference grid (admin Settings → Design Gallery).
  const refImages = Array.from(document.querySelectorAll('#referenceGrid .reference-item')).map(item => ({
    src:   item.dataset.src,
    label: item.dataset.label,
    value: item.dataset.value
  }));

  const TOTAL_REFS = refImages.length;
  let confirmedRef = null;
  let pendingRef   = null;
  let lbIndex      = 0;

  const refModal = new bootstrap.Modal(document.getElementById('referenceModal'));

  document.getElementById('openRefModal').addEventListener('click', () => refModal.show());

  // Restore visual state when modal re-opens
  document.getElementById('referenceModal').addEventListener('show.bs.modal', () => {
    pendingRef = confirmedRef;
    document.querySelectorAll('.reference-item').forEach(item => {
      const isSelected = confirmedRef && item.dataset.value === confirmedRef.value;
      item.classList.toggle('selected', isSelected);
    });
    document.getElementById('confirmRefBtn').disabled = !confirmedRef;
  });

  // Select a thumbnail
  document.querySelectorAll('.reference-item').forEach(item => {
    item.addEventListener('click', e => {
      if (e.target.closest('.ref-zoom-btn')) return;
      document.querySelectorAll('.reference-item').forEach(i => i.classList.remove('selected'));
      item.classList.add('selected');
      pendingRef = { value: item.dataset.value, label: item.dataset.label, src: item.dataset.src, dim: item.dataset.dim };
      document.getElementById('confirmRefBtn').disabled = false;
    });
  });

  // Confirm selection
  document.getElementById('confirmRefBtn').addEventListener('click', () => {
    confirmedRef = pendingRef;
    document.getElementById('referenceDesignInput').value = confirmedRef.value;
    document.getElementById('refPreviewImg').src          = confirmedRef.src;
    document.getElementById('refPreviewName').textContent =
      confirmedRef.label + (confirmedRef.dim ? ' · ' + confirmedRef.dim : '');
    document.getElementById('refPreview').style.display   = 'flex';
    refModal.hide();
    saveDraft();
  });

  // Remove confirmed selection
  document.getElementById('clearRefBtn').addEventListener('click', () => {
    confirmedRef = pendingRef = null;
    document.getElementById('referenceDesignInput').value = '';
    document.getElementById('refPreview').style.display   = 'none';
    document.querySelectorAll('.reference-item').forEach(i => i.classList.remove('selected'));
    document.getElementById('confirmRefBtn').disabled = true;
    saveDraft();
  });

  // ── Lightbox ──
  const lbOverlay = document.getElementById('lightboxOverlay');
  const lbImg     = document.getElementById('lightboxImg');
  const lbLbl     = document.getElementById('lightboxLabel');

  function openLightbox(index) {
    lbIndex = (index + TOTAL_REFS) % TOTAL_REFS;
    lbImg.src          = refImages[lbIndex].src;
    lbLbl.textContent  = refImages[lbIndex].label;
    lbOverlay.classList.add('active');
  }
  function closeLightbox() { lbOverlay.classList.remove('active'); lbImg.src = ''; }

  document.querySelectorAll('.ref-zoom-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      openLightbox(parseInt(btn.dataset.index));
    });
  });

  document.getElementById('lbClose').addEventListener('click', closeLightbox);
  document.getElementById('lbPrev').addEventListener('click', () => openLightbox(lbIndex - 1));
  document.getElementById('lbNext').addEventListener('click', () => openLightbox(lbIndex + 1));

  // Close lightbox on overlay background click
  lbOverlay.addEventListener('click', e => { if (e.target === lbOverlay) closeLightbox(); });

  // Keyboard nav
  document.addEventListener('keydown', e => {
    if (!lbOverlay.classList.contains('active')) return;
    if (e.key === 'ArrowLeft')  openLightbox(lbIndex - 1);
    if (e.key === 'ArrowRight') openLightbox(lbIndex + 1);
    if (e.key === 'Escape')     closeLightbox();
  });

  // ── Estimated budget must be a valid positive amount ──
  // (HTML5 `required` on a text field still allows non-numeric input.) Added
  // before the profile gate so an invalid budget is reported first.
  document.getElementById('quoteForm').addEventListener('submit', function (e) {
    const el = document.getElementById('budget');
    const val = parseFloat(el.value.replace(/[^0-9.]/g, ''));
    if (el.value.trim() === '' || isNaN(val) || val <= 0) {
      e.preventDefault();
      e.stopImmediatePropagation(); // don't also trigger the profile prompt below
      vsAlert('Please enter a valid estimated budget (a positive amount).', { title: 'Invalid budget' });
      el.focus();
    }
  });

  // ── Installation address picker ──
  const quoteForm  = document.getElementById('quoteForm');
  const addrSelect = document.getElementById('address_id');
  const ME = <?= json_encode(['name' => $meName, 'email' => $meEmail], JSON_UNESCAPED_UNICODE) ?>;
  let HAS_PHONE = <?= $profilePhone !== '' ? 'true' : 'false' ?>;
  let lastAddr  = addrSelect.value;

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }
  function savedAddressCount() {
    return [...addrSelect.options].filter(o => o.value && o.value !== '__new').length;
  }
  function updateAddrPreview() {
    const o = addrSelect.selectedOptions[0];
    const p = document.getElementById('addrPreview');
    const text = o ? (o.dataset.address || '') : '';
    p.textContent = text ? '📍 ' + text : '';
    p.style.display = text ? '' : 'none';
  }
  function renderAddrOptions(list, selectId) {
    let html = list.length ? '' : '<option value="" selected disabled>No saved address yet — add one</option>';
    list.forEach(a => {
      const sel = selectId ? Number(a.id) === Number(selectId) : String(a.is_default) === '1';
      html += '<option value="' + a.id + '" data-address="' + esc(a.address) + '"' + (sel ? ' selected' : '') + '>' +
              esc((a.label || 'Address') + ' — ' + a.address) + '</option>';
    });
    html += '<option value="__new">+ Add a new address…</option>';
    addrSelect.innerHTML = html;
    lastAddr = addrSelect.value;
    updateAddrPreview();
  }
  addrSelect.addEventListener('change', function () {
    if (addrSelect.value === '__new') {
      addrSelect.value = lastAddr;          // keep the previous choice until one is saved
      updateAddrPreview();
      openDetails('address', false, true);
      return;
    }
    lastAddr = addrSelect.value;
    updateAddrPreview();
  });
  updateAddrPreview();

  // ── Contact / address pop-up — collected in place, so the form is never lost ──
  const dmEl = document.getElementById('detailsModal');
  const dm   = new bootstrap.Modal(dmEl);
  let dmMode = 'address', dmNeedPhone = false, dmNeedAddr = false;

  function openDetails(mode, needPhone, needAddr) {
    dmMode = mode; dmNeedPhone = needPhone; dmNeedAddr = needAddr;
    const missing = [needPhone ? 'contact number' : '', needAddr ? 'installation address' : ''].filter(Boolean).join(' and ');
    document.getElementById('dmTitle').textContent = mode === 'address' ? 'Add a new address' : 'Complete your details';
    document.getElementById('dmIntro').textContent = mode === 'address'
      ? 'Save another installation address to your account. It will be selected for this request.'
      : 'Please add your ' + missing + ' so we can prepare and deliver your quotation. Then we\'ll submit your request.';
    document.getElementById('dmPhoneWrap').style.display = needPhone ? '' : 'none';
    document.getElementById('dmAddrWrap').style.display  = needAddr ? '' : 'none';
    // "Make default" only matters once there's already an address (the first is always default).
    document.getElementById('dmDefaultWrap').style.display = savedAddressCount() ? '' : 'none';
    ['dmPhone', 'dmLabel', 'dmAddress'].forEach(id => { document.getElementById(id).value = ''; });
    document.getElementById('dmDefault').checked = false;
    document.getElementById('dmSaveBtn').textContent = mode === 'complete' ? 'Save & submit request' : 'Save address';
    dm.show();
  }
  dmEl.addEventListener('shown.bs.modal', function () {
    const first = document.getElementById(dmNeedPhone ? 'dmPhone' : 'dmAddress');
    if (first) first.focus();
  });

  function postForm(url, data) {
    return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(data) })
      .then(async r => { const d = await r.json().catch(() => ({ ok: false })); if (!r.ok || !d.ok) throw new Error(d.error || 'Could not save.'); return d; });
  }

  document.getElementById('dmSaveBtn').addEventListener('click', function () {
    const btn = this;
    const phone   = document.getElementById('dmPhone').value.trim();
    const address = document.getElementById('dmAddress').value.trim();
    if (dmNeedPhone && phone.replace(/\D/g, '').length < 7) { vsAlert('Please enter a valid contact number.', { title: 'Contact number' }); return; }
    if (dmNeedAddr && address.length < 5) { vsAlert('Please enter the full installation address.', { title: 'Installation address' }); return; }

    btn.disabled = true;
    let chain = Promise.resolve();
    if (dmNeedPhone) {
      chain = chain.then(() => postForm('save_profile.php', { full_name: ME.name, email: ME.email, phone: phone }))
                   .then(() => { HAS_PHONE = true; });
    }
    if (dmNeedAddr) {
      chain = chain.then(() => postForm('save_address.php', {
                      action: 'add', label: document.getElementById('dmLabel').value.trim(), address: address,
                      make_default: document.getElementById('dmDefault').checked ? '1' : '',
                    }))
                   .then(d => renderAddrOptions(d.addresses || [], d.id));
    }
    chain.then(() => {
      dm.hide();
      saveDraft();
      if (dmMode === 'complete') {
        vsToast('Details saved — submitting your request…');
        if (quoteForm.requestSubmit) quoteForm.requestSubmit(); else quoteForm.submit();
      } else {
        vsToast('Address saved and selected.');
      }
    })
    .catch(err => vsToast(err.message, { type: 'error' }))
    .finally(() => { btn.disabled = false; });
  });

  // ── Require a contact number + installation address before submitting ──
  quoteForm.addEventListener('submit', function (e) {
    const needPhone = !HAS_PHONE;
    const needAddr  = !addrSelect.value || addrSelect.value === '__new';
    if (needPhone || needAddr) {
      e.preventDefault();
      openDetails('complete', needPhone, needAddr);
    }
  });

  // ── Draft: keep what's typed across a refresh, a trip to Settings, or a server error ──
  // (Cleared on My Projects once the request is submitted. Files can't be kept by
  // browsers, so only the typed fields, address and reference design are saved.)
  const DRAFT_KEY    = 'vsQuoteDraft';
  const DRAFT_FIELDS = ['project_name', 'category', 'material_type', 'dimensions', 'target_completion', 'budget', 'notes'];
  function saveDraft() {
    try {
      const d = {};
      DRAFT_FIELDS.forEach(id => { d[id] = document.getElementById(id).value; });
      d.address_id = (addrSelect.value && addrSelect.value !== '__new') ? addrSelect.value : '';
      d.reference  = confirmedRef;
      sessionStorage.setItem(DRAFT_KEY, JSON.stringify(d));
    } catch (e) { /* storage unavailable — fine */ }
  }
  quoteForm.addEventListener('input', saveDraft);
  quoteForm.addEventListener('change', saveDraft);

  (function restoreDraft() {
    let d = null;
    try { d = JSON.parse(sessionStorage.getItem(DRAFT_KEY) || 'null'); } catch (e) {}
    if (!d) return;
    DRAFT_FIELDS.forEach(id => {
      const el = document.getElementById(id);
      if (!el || d[id] == null || d[id] === '') return;
      if (el.tagName === 'SELECT' && ![...el.options].some(o => o.value === d[id])) return;
      el.value = d[id];
    });
    if (d.address_id && [...addrSelect.options].some(o => o.value === String(d.address_id))) {
      addrSelect.value = String(d.address_id);
      lastAddr = addrSelect.value;
      updateAddrPreview();
    }
    if (d.reference && d.reference.value && refImages.some(r => r.value === d.reference.value)) {
      confirmedRef = d.reference;
      document.getElementById('referenceDesignInput').value = confirmedRef.value;
      document.getElementById('refPreviewImg').src          = confirmedRef.src;
      document.getElementById('refPreviewName').textContent = confirmedRef.label + (confirmedRef.dim ? ' · ' + confirmedRef.dim : '');
      document.getElementById('refPreview').style.display   = 'flex';
    }
  })();
</script>
</body>
</html>