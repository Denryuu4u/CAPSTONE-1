<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(); // enforced only when DEV_MODE is false
require_once __DIR__ . '/../includes/legal.php';
require_agreements(); // clients must accept the latest Terms & Privacy first

$active_page = 'request_quote';

// Design gallery (managed in admin Settings → Design Gallery).
require_once __DIR__ . '/../includes/db.php';
$galleryImages = [];
try {
    $galleryImages = db()->query("SELECT file_path, label FROM gallery_images ORDER BY sort_order, id")->fetchAll();
} catch (Throwable $e) { $galleryImages = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Request a Quote – Vast Solutions</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
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
  </div>

  <div class="page-content">
    <h1 class="page-title">Request a Quote</h1>

    <form action="submit_quote.php" method="POST" enctype="multipart/form-data">
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
            <select id="material_type" name="material_type" class="form-control">
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
            <input type="text" id="dimensions" name="dimensions" class="form-control" placeholder="e.g. 2400 × 720 × 600 mm (W × H × D)"/>
          </div>

          <div class="form-group">
            <label class="form-label" for="target_completion">Target Completion Date <span style="font-weight:400;color:#9ca3af;">(Optional)</span></label>
            <input type="date" id="target_completion" name="target_completion" class="form-control"/>
          </div>

          <div class="form-group">
            <label class="form-label" for="budget">Estimated Budget</label>
            <input type="text" id="budget" name="budget" class="form-control" placeholder="₱0.00"/>
          </div>

          <div class="form-group">
            <label class="form-label" for="notes">Additional Notes</label>
            <textarea id="notes" name="notes" class="form-control" placeholder="Describe your requirements, preferred materials, timeline..."></textarea>
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
  }
  function removeFile(i) { selectedFiles.splice(i, 1); renderList(); }

  // ── Reference Design Modal ──
  // Built from the DB-driven reference grid (admin Settings → Design Gallery).
  const refImages = Array.from(document.querySelectorAll('#referenceGrid .reference-item')).map(item => ({
    src:   item.dataset.src,
    label: item.dataset.label,
    value: item.dataset.value
  }));

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
  });

  // Remove confirmed selection
  document.getElementById('clearRefBtn').addEventListener('click', () => {
    confirmedRef = pendingRef = null;
    document.getElementById('referenceDesignInput').value = '';
    document.getElementById('refPreview').style.display   = 'none';
    document.querySelectorAll('.reference-item').forEach(i => i.classList.remove('selected'));
    document.getElementById('confirmRefBtn').disabled = true;
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
</script>
</body>
</html>