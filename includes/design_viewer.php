<!-- A read-only design viewer, shared by the client and back-office pages. -->
<dialog id="designViewer" style="width:min(92vw,1000px);max-height:92vh;border:0;border-radius:12px;padding:18px;margin:auto;">
  <div class="d-flex justify-content-between align-items-center mb-3"><strong>Initial design</strong><button type="button" class="btn-close" aria-label="Close design" onclick="document.getElementById('designViewer').close()"></button></div>
  <p id="designLoadError" hidden>The design could not be loaded. Close this window and try again.</p>
  <img id="designViewerImage" alt="Initial project design" style="display:block;max-width:100%;max-height:75vh;object-fit:contain;margin:auto;">
</dialog>
<style>#designViewer::backdrop{background:rgba(0,0,0,.7)}</style>
<script>
function viewDesign(quotationId) {
    const img=document.getElementById('designViewerImage');
    document.getElementById('designLoadError').hidden=true;
    img.hidden=false;
    img.onerror=()=>{document.getElementById('designLoadError').hidden=false; img.hidden=true;};
    img.src=<?= json_encode(BASE_URL . '/design_image.php?quotation_id=') ?>+encodeURIComponent(quotationId);
    document.getElementById('designViewer').showModal();
}
</script>

