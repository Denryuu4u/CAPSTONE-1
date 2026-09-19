<?php
/**
 * Shared project-chat modal (client <-> back office). Include once per page that
 * needs it (admin monitoring, client my_projects). Exposes:
 *     window.openProjectChat(projectId, projectName)
 * It talks to 'project_chat.php' in the SAME directory as the including page, so
 * admin pages hit admin/project_chat.php and client pages hit
 * user-dashboard/project_chat.php automatically. Requires Bootstrap 5 (already
 * loaded by both areas).
 */
require_once __DIR__ . '/chat.php';
$__chatSide = chat_side(current_role());
?>
<div class="modal fade" id="chatModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content chat-modal">
      <div class="chat-head">
        <div>
          <div class="chat-title" id="chatTitle">Project Chat</div>
          <div class="chat-sub" id="chatSub">Messages between you and the team</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="chat-body" id="chatBody"><div class="chat-empty">Loading…</div></div>
      <div class="chat-input-row">
        <input type="text" id="chatInput" placeholder="Type a message…" maxlength="2000" autocomplete="off">
        <button type="button" id="chatSendBtn" aria-label="Send"><i class="bi bi-send-fill"></i></button>
      </div>
    </div>
  </div>
</div>

<style>
  #chatModal .modal-dialog { max-width: 460px; }
  .chat-modal { border:none; border-radius:14px; overflow:hidden; }
  .chat-head { display:flex; align-items:center; justify-content:space-between; gap:10px;
    padding:14px 18px; background:#0d1b2a; color:#fff; }
  .chat-title { font-family:'Montserrat',sans-serif; font-weight:700; font-size:.98rem; }
  .chat-sub { font-size:.72rem; color:rgba(255,255,255,.6); margin-top:1px; }
  .chat-head .btn-close { filter:invert(1) grayscale(1); opacity:.8; }
  .chat-body { background:#f4f5f7; padding:16px; height:340px; overflow-y:auto;
    display:flex; flex-direction:column; gap:10px; }
  .chat-empty { margin:auto; color:#9ca3af; font-size:.85rem; text-align:center; }
  .chat-msg { display:flex; flex-direction:column; max-width:78%; }
  .chat-msg.mine { align-self:flex-end; align-items:flex-end; }
  .chat-msg.theirs { align-self:flex-start; align-items:flex-start; }
  .chat-bubble { padding:8px 12px; border-radius:14px; font-size:.85rem; line-height:1.45;
    word-wrap:break-word; white-space:pre-wrap; }
  .chat-msg.mine .chat-bubble { background:var(--teal,#0D9676); color:#fff; border-bottom-right-radius:4px; }
  .chat-msg.theirs .chat-bubble { background:#fff; color:#111827; border:1px solid #e5e7eb; border-bottom-left-radius:4px; }
  .chat-meta { font-size:.65rem; color:#9ca3af; margin-top:3px; }
  .chat-input-row { display:flex; gap:8px; padding:12px 14px; background:#fff; border-top:1px solid #e5e7eb; }
  .chat-input-row input { flex:1; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px;
    font-size:.85rem; outline:none; font-family:'Inter',sans-serif; }
  .chat-input-row input:focus { border-color:var(--teal,#0D9676); }
  .chat-input-row button { flex-shrink:0; width:42px; border:none; border-radius:10px;
    background:var(--teal,#0D9676); color:#fff; font-size:1rem; cursor:pointer; transition:background .15s; }
  .chat-input-row button:hover { filter:brightness(.94); }
</style>

<script>
window.CHAT_SIDE = <?= json_encode($__chatSide) ?>;
(function () {
  var modalEl = document.getElementById('chatModal');
  if (!modalEl) return;
  var pid = 0, poll = null, bsModal = null;

  function esc(s){ var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

  function render(msgs){
    var b = document.getElementById('chatBody');
    if (!msgs.length){ b.innerHTML = '<div class="chat-empty">No messages yet.<br>Start the conversation 👋</div>'; return; }
    b.innerHTML = msgs.map(function(m){
      var mine = m.side === window.CHAT_SIDE;
      return '<div class="chat-msg ' + (mine ? 'mine' : 'theirs') + '">' +
               '<div class="chat-bubble">' + esc(m.body) + '</div>' +
               '<div class="chat-meta">' + esc(m.name) + ' · ' + esc(m.time) + '</div>' +
             '</div>';
    }).join('');
    b.scrollTop = b.scrollHeight;
  }

  function load(){
    if (!pid) return;
    fetch('project_chat.php?project_id=' + encodeURIComponent(pid), { headers: { 'X-Requested-With': 'fetch' } })
      .then(function(r){ return r.json(); })
      .then(function(d){ if (d && d.ok) render(d.messages || []); })
      .catch(function(){});
  }

  function send(){
    var inp = document.getElementById('chatInput');
    var t = inp.value.trim();
    if (!t || !pid) return;
    inp.value = '';
    fetch('project_chat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ project_id: pid, body: t })
    }).then(function(r){ return r.json(); })
      .then(function(d){ if (d && d.ok) { load(); } else if (window.vsAlert) { vsAlert((d && d.error) || 'Could not send message.'); } })
      .catch(function(){});
  }

  window.openProjectChat = function(projectId, projectName){
    pid = parseInt(projectId, 10) || 0;
    document.getElementById('chatTitle').textContent = projectName || 'Project Chat';
    document.getElementById('chatBody').innerHTML = '<div class="chat-empty">Loading…</div>';
    // Close any other open Bootstrap modal so the chat isn't stacked awkwardly.
    document.querySelectorAll('.modal.show').forEach(function(m){
      if (m !== modalEl) { var i = bootstrap.Modal.getInstance(m); if (i) i.hide(); }
    });
    if (!bsModal) bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
    load();
    if (poll) clearInterval(poll);
    poll = setInterval(load, 4000);
  };

  // Any element with data-chat-open opens the chat for its project.
  document.addEventListener('click', function(e){
    var t = e.target.closest ? e.target.closest('[data-chat-open]') : null;
    if (!t) return;
    e.preventDefault();
    window.openProjectChat(t.getAttribute('data-chat-id'), t.getAttribute('data-chat-name'));
  });

  modalEl.addEventListener('hidden.bs.modal', function(){ if (poll) { clearInterval(poll); poll = null; } });
  document.getElementById('chatSendBtn').addEventListener('click', send);
  document.getElementById('chatInput').addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); send(); } });
})();
</script>
