<?php
/**
 * Shared alert / confirm modal.
 *
 * Replaces the browser's native alert() and confirm() with a branded modal so
 * every notice in the system looks consistent. Included once per page (via the
 * admin + client sidebars). Self-contained: no Bootstrap JS dependency, so it
 * works regardless of script load order and sits above Bootstrap modals.
 *
 *   vsAlert(message, {title, okText, tone})            -> Promise (resolves on OK)
 *   vsConfirm(message, {title, okText, cancelText,     -> Promise<bool>
 *                        tone})
 *
 * window.alert is also overridden to route through vsAlert, so existing
 * alert('...') calls become modals with no code change.
 * tone: 'default' (teal) | 'danger' (red OK button).
 */
?>
<style>
  #vsModalOverlay{position:fixed;inset:0;z-index:20000;display:none;align-items:center;justify-content:center;
    background:rgba(13,27,42,.45);padding:20px;font-family:'Inter',system-ui,-apple-system,sans-serif;}
  #vsModalOverlay.show{display:flex;}
  #vsModalOverlay .vsm-box{background:#fff;border-radius:14px;max-width:400px;width:100%;
    box-shadow:0 18px 50px rgba(0,0,0,.25);overflow:hidden;animation:vsmPop .16s ease-out;}
  @keyframes vsmPop{from{transform:translateY(8px) scale(.98);opacity:0}to{transform:none;opacity:1}}
  #vsModalOverlay .vsm-body{padding:22px 24px 18px;}
  #vsModalOverlay .vsm-title{font-family:'Syne','Inter',sans-serif;font-size:1.02rem;font-weight:700;color:#0d1b2a;margin:0 0 6px;}
  #vsModalOverlay .vsm-msg{font-size:.88rem;color:#4b5563;line-height:1.55;white-space:pre-wrap;word-break:break-word;}
  #vsModalOverlay .vsm-foot{display:flex;justify-content:flex-end;gap:8px;padding:0 24px 20px;}
  #vsModalOverlay .vsm-btn{font-size:.82rem;font-weight:600;border-radius:8px;padding:8px 18px;cursor:pointer;
    border:1px solid transparent;font-family:inherit;transition:background .15s,border-color .15s,color .15s;}
  #vsModalOverlay .vsm-cancel{background:#fff;border-color:#e5e7eb;color:#374151;}
  #vsModalOverlay .vsm-cancel:hover{border-color:#cbd5e1;background:#f9fafb;}
  #vsModalOverlay .vsm-ok{background:#0D9676;color:#fff;}
  #vsModalOverlay .vsm-ok:hover{background:#0a7a60;}
  #vsModalOverlay.tone-danger .vsm-ok{background:#dc2626;}
  #vsModalOverlay.tone-danger .vsm-ok:hover{background:#b91c1c;}

  /* Toasts */
  #vsToastWrap{position:fixed;right:18px;bottom:18px;z-index:20050;display:flex;flex-direction:column;gap:10px;
    pointer-events:none;font-family:'Inter',system-ui,-apple-system,sans-serif;max-width:340px;}
  .vs-toast{display:flex;align-items:flex-start;gap:10px;background:#fff;border-radius:10px;padding:12px 14px;
    box-shadow:0 10px 30px rgba(13,27,42,.18);border-left:4px solid #0D9676;pointer-events:auto;
    opacity:0;transform:translateY(10px);transition:opacity .2s,transform .2s;}
  .vs-toast.in{opacity:1;transform:none;}
  .vs-toast .vs-toast-ico{font-size:1.05rem;line-height:1.2;flex-shrink:0;margin-top:1px;color:#0D9676;}
  .vs-toast .vs-toast-msg{font-size:.83rem;color:#1f2937;line-height:1.45;word-break:break-word;}
  .vs-toast.error{border-left-color:#dc2626;} .vs-toast.error .vs-toast-ico{color:#dc2626;}
  .vs-toast.info{border-left-color:#2563eb;}  .vs-toast.info .vs-toast-ico{color:#2563eb;}
</style>
<div id="vsModalOverlay" role="alertdialog" aria-modal="true" aria-labelledby="vsmTitle" aria-describedby="vsmMsg">
  <div class="vsm-box">
    <div class="vsm-body">
      <h5 class="vsm-title" id="vsmTitle">Notice</h5>
      <div class="vsm-msg" id="vsmMsg"></div>
    </div>
    <div class="vsm-foot">
      <button type="button" class="vsm-btn vsm-cancel" id="vsmCancel">Cancel</button>
      <button type="button" class="vsm-btn vsm-ok" id="vsmOk">OK</button>
    </div>
  </div>
</div>
<div id="vsToastWrap" aria-live="polite" aria-atomic="true"></div>
<script>
(function(){
  if (window.__vsModalInit) return;
  window.__vsModalInit = true;

  var overlay = document.getElementById('vsModalOverlay');
  var titleEl = document.getElementById('vsmTitle');
  var msgEl   = document.getElementById('vsmMsg');
  var okBtn   = document.getElementById('vsmOk');
  var cancelBtn = document.getElementById('vsmCancel');
  var resolver = null;   // pending Promise resolve
  var isConfirm = false;

  function close(result){
    overlay.classList.remove('show');
    document.removeEventListener('keydown', onKey);
    var r = resolver; resolver = null;
    if (r) r(result);
  }
  function onKey(e){
    if (e.key === 'Escape') { e.preventDefault(); close(isConfirm ? false : undefined); }
    else if (e.key === 'Enter') { e.preventDefault(); close(isConfirm ? true : undefined); }
  }
  okBtn.addEventListener('click', function(){ close(isConfirm ? true : undefined); });
  cancelBtn.addEventListener('click', function(){ close(false); });
  overlay.addEventListener('mousedown', function(e){
    if (e.target === overlay) close(isConfirm ? false : undefined); // backdrop click cancels
  });

  function open(message, opts, confirmMode){
    opts = opts || {};
    isConfirm = !!confirmMode;
    titleEl.textContent = opts.title || (isConfirm ? 'Please confirm' : 'Notice');
    msgEl.textContent = message == null ? '' : String(message);
    okBtn.textContent = opts.okText || (isConfirm ? 'Confirm' : 'OK');
    cancelBtn.textContent = opts.cancelText || 'Cancel';
    cancelBtn.style.display = isConfirm ? '' : 'none';
    overlay.classList.toggle('tone-danger', opts.tone === 'danger');
    overlay.classList.add('show');
    document.addEventListener('keydown', onKey);
    setTimeout(function(){ (isConfirm ? okBtn : okBtn).focus(); }, 30);
    return new Promise(function(res){ resolver = res; });
  }

  window.vsAlert   = function(message, opts){ return open(message, opts, false); };
  window.vsConfirm = function(message, opts){ return open(message, opts, true); };
  // Route native alert() through the branded modal (fire-and-forget).
  window.alert = function(m){ return window.vsAlert(m); };

  // ── Toasts ──────────────────────────────────────────────
  var toastWrap = document.getElementById('vsToastWrap');
  var TOAST_ICONS = {success:'✓', error:'⚠', info:'ℹ'};
  function showToast(message, type){
    if (!toastWrap) return;
    type = type || 'success';
    var t = document.createElement('div');
    t.className = 'vs-toast ' + type;
    var ico = document.createElement('span'); ico.className = 'vs-toast-ico'; ico.textContent = TOAST_ICONS[type] || TOAST_ICONS.success;
    var msg = document.createElement('span'); msg.className = 'vs-toast-msg'; msg.textContent = message == null ? '' : String(message);
    t.appendChild(ico); t.appendChild(msg);
    toastWrap.appendChild(t);
    requestAnimationFrame(function(){ t.classList.add('in'); });
    setTimeout(function(){
      t.classList.remove('in');
      setTimeout(function(){ t.remove(); }, 250);
    }, 3400);
  }
  // Show a toast now.
  window.vsToast = function(message, opts){ opts = opts || {}; showToast(message, opts.type || 'success'); };
  // Queue a toast to appear after the next page load (survives location.reload()
  // and server redirects) — for success paths that reload the page.
  window.vsToastFlash = function(message, type){
    try { sessionStorage.setItem('vsFlashToast', JSON.stringify({m: message, t: type || 'success'})); } catch (e) {}
  };
  // Drain any queued flash toast on load.
  try {
    var flash = sessionStorage.getItem('vsFlashToast');
    if (flash) { sessionStorage.removeItem('vsFlashToast'); var o = JSON.parse(flash); setTimeout(function(){ showToast(o.m, o.t); }, 150); }
  } catch (e) {}
})();
</script>
