<?php
// Public landing page — pull the "Contact Us" details from settings (no auth needed).
$contact = ['web_email' => 'support@vastsolutions.com', 'web_phone' => '+63 900 000 0000', 'web_location' => 'Calamba, Laguna, Philippines'];
try {
    require_once __DIR__ . '/includes/db.php';
    $row = db()->query("SELECT web_email, web_phone, web_location FROM company_settings WHERE id = 1")->fetch();
    if ($row) {
        foreach ($contact as $k => $v) {
            if (!empty($row[$k])) $contact[$k] = $row[$k];
        }
    }
} catch (Throwable $e) { /* fall back to defaults */ }
$ce = fn($k) => htmlspecialchars($contact[$k], ENT_QUOTES);

// Design gallery (managed in admin Settings → Design Gallery).
$galleryImages = [];
try {
    if (function_exists('db')) {
        $galleryImages = db()->query("SELECT file_path, label FROM gallery_images ORDER BY sort_order, id")->fetchAll();
    }
} catch (Throwable $e) { $galleryImages = []; }
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Vast Solutions – Every Inch, Endless Possibilities</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style/index.css" />
</head>

<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <div class="container-fluid d-flex align-items-center gap-3">
      <a class="navbar-brand" href="index.php">
        <img src="style/assets/logo.jpg" alt="Vast Solutions Logo" style="width:28px; height:28px; object-fit:contain; margin-right:10px;">
        Vast Solutions
      </a>
      <button class="nav-toggle" id="navToggle" type="button" aria-label="Toggle menu"
              onclick="document.getElementById('navMenu').classList.toggle('open')">
        <i class="bi bi-list"></i>
      </button>
      <div class="nav-menu ms-auto" id="navMenu">
        <a class="nav-link" href="#designs">Browse Designs</a>
        <a class="nav-link" href="#contact">Contact</a>
        <a class="btn-login" href="login.php">Login</a>
        <a class="btn-quote" href="login.php">Get a Quote</a>
      </div>
    </div>
  </nav>

  <!-- HERO -->
  <section class="hero">
    <div>
      <h1 class="hero-title">
        Every Inch,<br>
        <span>Endless Possibilities</span>
      </h1>
      <p class="hero-sub">
        Wardrobe <span>|</span> Kitchen <span>|</span> Bathroom <span>|</span> Entertainment <span>|</span> Office
      </p>
      <div class="hero-btns">
        <a href="login.php" class="btn-get-started">Get Started &rarr;</a>
        <a href="#designs" class="btn-learn-more">Browse Designs</a>
      </div>
    </div>
  </section>

  <!-- BROWSE DESIGNS (view only) -->
  <section class="designs-section" id="designs">
    <p class="section-label">Gallery</p>
    <h2 class="section-title">Browse Our Designs</h2>
    <p class="designs-intro">
      Explore a selection of our custom cabinetry and interior projects. Viewing only —
      ready to build yours? Log in and request a quote.
    </p>

    <div class="designs-grid">
      <?php if (empty($galleryImages)): ?>
        <p class="designs-intro">No designs to show yet.</p>
      <?php else: foreach ($galleryImages as $idx => $g):
        $img = $g['file_path']; ?>
        <button type="button" class="design-card" onclick="openDesign('<?= htmlspecialchars($img, ENT_QUOTES) ?>')">
          <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($g['label'] ?: 'Cabinet design ' . ($idx + 1)) ?>" loading="lazy">
          <span class="design-overlay"><i class="bi bi-eye"></i> View</span>
        </button>
      <?php endforeach; endif; ?>
    </div>

    <div class="designs-pagination" id="designsPager"></div>
  </section>
  <div class="section-fade"></div>
  <!-- CONTACT SECTION -->
  <section class="contact-section" id="contact">
    <div class="container">

      <p class="section-label">Get in Touch</p>
      <h2 class="section-title contact-title">Contact Us</h2>

      <div class="contact-wrap">

        <!-- LEFT INFO -->
        <div class="contact-info">
          <h3>Let’s Talk</h3>
          <p>
            Have a project in mind? Send us a message and we’ll get back to you with a quotation.
          </p>

          <div class="info-item">
            <strong>Email:</strong>
            <span><?= $ce('web_email') ?></span>
          </div>

          <div class="info-item">
            <strong>Phone:</strong>
            <span><?= $ce('web_phone') ?></span>
          </div>

          <div class="info-item">
            <strong>Location:</strong>
            <span><?= $ce('web_location') ?></span>
          </div>
        </div>

        <!-- RIGHT FORM -->
        <form class="contact-form">
          <input type="text" placeholder="Your Name" required />
          <input type="email" placeholder="Your Email" required />
          <input type="text" placeholder="Subject" />
          <textarea placeholder="Your Message" rows="5" required></textarea>
          <button type="submit">Send Message</button>
        </form>

      </div>
    </div>
  </section>

  <footer>
    &copy; 2025 Vast Solutions. All rights reserved. &nbsp;|&nbsp; <a href="legal.php?doc=terms">Terms &amp; Conditions</a> &nbsp;|&nbsp; <a href="legal.php?doc=privacy">Privacy Policy</a>
  </footer>

  <!-- DESIGN LIGHTBOX (view only) -->
  <div class="design-lightbox" id="designLightbox" onclick="closeDesign()">
    <span class="design-lightbox-close" aria-label="Close">&times;</span>
    <img src="" alt="Design preview" id="designLightboxImg" onclick="event.stopPropagation()">
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function openDesign(src) {
      document.getElementById('designLightboxImg').src = src;
      document.getElementById('designLightbox').classList.add('open');
      document.body.style.overflow = 'hidden';
    }
    function closeDesign() {
      document.getElementById('designLightbox').classList.remove('open');
      document.body.style.overflow = '';
    }
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeDesign();
    });

    // ── Browse Designs pagination (responsive page size) ──
    (function () {
      const grid = document.querySelector('.designs-grid');
      const pager = document.getElementById('designsPager');
      if (!grid || !pager) return;
      const cards = Array.from(grid.querySelectorAll('.design-card'));

      function pageSizeFor() {
        const w = window.innerWidth;
        if (w <= 575) return 4;   // phone: 2×2
        if (w <= 767) return 6;   // tablet: 2×3
        return 9;                 // desktop: 3×3
      }
      let pageSize = pageSizeFor();
      let page = 1;
      const pageCount = () => Math.max(1, Math.ceil(cards.length / pageSize));

      function render() {
        const pc = pageCount();
        if (page > pc) page = pc;
        const start = (page - 1) * pageSize, end = start + pageSize;
        cards.forEach((c, i) => { c.style.display = (i >= start && i < end) ? '' : 'none'; });

        let html = `<button class="page-btn nav" data-go="prev" ${page === 1 ? 'disabled' : ''} aria-label="Previous">&lsaquo;</button>`;
        for (let p = 1; p <= pc; p++) html += `<button class="page-btn ${p === page ? 'active' : ''}" data-go="${p}">${p}</button>`;
        html += `<button class="page-btn nav" data-go="next" ${page === pc ? 'disabled' : ''} aria-label="Next">&rsaquo;</button>`;
        pager.innerHTML = pc > 1 ? html : '';
      }

      pager.addEventListener('click', function (e) {
        const b = e.target.closest('.page-btn');
        if (!b) return;
        const go = b.dataset.go;
        if (go === 'prev') page = Math.max(1, page - 1);
        else if (go === 'next') page = Math.min(pageCount(), page + 1);
        else page = parseInt(go, 10);
        render();
        document.getElementById('designs').scrollIntoView({ behavior: 'smooth', block: 'start' });
      });

      let rt;
      window.addEventListener('resize', function () {
        clearTimeout(rt);
        rt = setTimeout(function () {
          const ns = pageSizeFor();
          if (ns !== pageSize) { pageSize = ns; page = 1; render(); }
        }, 150);
      });

      render();
    })();

    // Close the mobile nav menu after tapping a link
    document.querySelectorAll('#navMenu a').forEach(function (a) {
      a.addEventListener('click', function () {
        document.getElementById('navMenu').classList.remove('open');
      });
    });
  </script>
</body>

</html>