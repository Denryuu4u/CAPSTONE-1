<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$active_page = 'request_quote';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Request a Quote – Vast Solutions</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="dashboard.css"/>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
  <div class="topbar">
    <a href="dashboard.php">Portal</a>
    <span class="sep">›</span>
    <span>Request Quote</span>
  </div>

  <div class="page-content">
    <h1 class="page-title">Request a Quote</h1>

    <form action="submit_quote.php" method="POST" enctype="multipart/form-data">
      <div class="quote-grid">

        <!-- LEFT: Upload -->
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
            <label class="form-label" for="budget">Estimated Budget</label>
            <input type="text" id="budget" name="budget" class="form-control" placeholder="$0.00"/>
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
</script>
</body>
</html>