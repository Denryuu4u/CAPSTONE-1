<?php
/**
 * Shared list-table tools: live search + client-side pagination.
 *
 * Any text input with a class like "<name>-search" is paired with the table
 * "<name>-table" (e.g. customer-search → customer-table). The table's rows are
 * filtered by the search text and split into pages (default 10 rows/page; set
 * data-page-size on the table to change). Included once via each sidebar, so
 * every list page gets it automatically. Tables without a matching search input
 * are left untouched.
 */
?>
<style>
  .tbl-pager { display:flex; align-items:center; justify-content:space-between; gap:12px;
    flex-wrap:wrap; padding:12px 4px 2px; font-size:.8rem; color:#6b7280; }
  .tbl-pager-info { font-weight:500; }
  .tbl-pager-btns { display:flex; align-items:center; gap:6px; }
  .tbl-pager-btn { display:inline-flex; align-items:center; gap:5px; font-size:.8rem; font-weight:600;
    padding:6px 12px; border-radius:8px; border:1px solid #e5e7eb; background:#fff; color:#374151; cursor:pointer;
    transition:background .15s,border-color .15s; }
  .tbl-pager-btn:hover:not(:disabled) { border-color:#cbd5e1; background:#f9fafb; }
  .tbl-pager-btn:disabled { opacity:.45; cursor:not-allowed; }
  .tbl-pager-page { min-width:30px; text-align:center; font-weight:700; color:#111827; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('input[class*="-search"]').forEach(function (input) {
    var cls = Array.prototype.find.call(input.classList, function (c) { return /-search$/.test(c); });
    if (!cls) return;
    var name = cls.slice(0, -7);
    var table = document.querySelector('.' + name + '-table');
    // Optional filter dropdowns paired by class "<name>-filter"; each carries a
    // data-col (0-based column index) whose cell text is matched against the value.
    var filters = Array.prototype.slice.call(document.querySelectorAll('select.' + name + '-filter'));
    // Optional date-range inputs "<name>-datefrom" / "<name>-dateto" (data-col = the
    // column whose date the range applies to).
    var dateFrom = document.querySelector('.' + name + '-datefrom');
    var dateTo   = document.querySelector('.' + name + '-dateto');
    if (table) initTableTool(table, input, filters, dateFrom, dateTo);
  });

  function initTableTool(table, input, filters, dateFrom, dateTo) {
    filters = filters || [];

    // Parse a cell's displayed date (e.g. "Sep 19, 2026") to a YYYY-MM-DD string.
    function cellDateISO(tr, col) {
      var cell = tr.children[col];
      if (!cell) return '';
      var t = (cell.textContent || '').trim();
      if (!t || t === '—') return '';
      var d = new Date(t);
      if (isNaN(d.getTime())) return '';
      var m = ('0' + (d.getMonth() + 1)).slice(-2), day = ('0' + d.getDate()).slice(-2);
      return d.getFullYear() + '-' + m + '-' + day;
    }
    var dateCol = dateFrom ? parseInt(dateFrom.getAttribute('data-col'), 10)
                : (dateTo ? parseInt(dateTo.getAttribute('data-col'), 10) : -1);
    var tbody = table.querySelector('tbody');
    if (!tbody) return;
    var dataRows = Array.prototype.filter.call(tbody.querySelectorAll(':scope > tr'), function (tr) {
      return !tr.querySelector('td[colspan]');
    });
    if (!dataRows.length) return;

    var pageSize = parseInt(table.getAttribute('data-page-size'), 10) || 10;
    var colCount = table.querySelectorAll('thead th').length || 1;
    var page = 1, filtered = dataRows.slice();

    // "No results" row.
    var noRow = document.createElement('tr');
    noRow.innerHTML = '<td colspan="' + colCount + '" class="text-center text-muted py-4">No matching results.</td>';
    noRow.style.display = 'none';
    tbody.appendChild(noRow);

    // Pager controls, placed just after the table's scroll wrapper (or the table).
    var host = table.closest('.table-responsive, .mp-scroll') || table;
    var nav = document.createElement('div');
    nav.className = 'tbl-pager';
    nav.innerHTML =
      '<span class="tbl-pager-info"></span>' +
      '<span class="tbl-pager-btns">' +
        '<button type="button" class="tbl-pager-btn" data-dir="-1"><i class="bi bi-chevron-left"></i> Prev</button>' +
        '<span class="tbl-pager-page"></span>' +
        '<button type="button" class="tbl-pager-btn" data-dir="1">Next <i class="bi bi-chevron-right"></i></button>' +
      '</span>';
    host.parentNode.insertBefore(nav, host.nextSibling);
    var info = nav.querySelector('.tbl-pager-info');
    var pageLbl = nav.querySelector('.tbl-pager-page');
    var prevBtn = nav.querySelector('[data-dir="-1"]');
    var nextBtn = nav.querySelector('[data-dir="1"]');

    function apply() {
      var q = input ? input.value.trim().toLowerCase() : '';
      var active = filters.map(function (f) { return { col: parseInt(f.getAttribute('data-col'), 10), val: f.value }; });
      var from = dateFrom && dateFrom.value ? dateFrom.value : '';
      var to   = dateTo   && dateTo.value   ? dateTo.value   : '';
      filtered = dataRows.filter(function (tr) {
        if (q && tr.textContent.toLowerCase().indexOf(q) === -1) return false;
        for (var i = 0; i < active.length; i++) {
          var a = active[i];
          if (!a.val) continue;                       // "" = All
          var cell = tr.children[a.col];
          var txt = (cell ? cell.textContent : '').trim().toLowerCase();
          if (txt.indexOf(a.val.toLowerCase()) === -1) return false;
        }
        if ((from || to) && dateCol >= 0) {
          var iso = cellDateISO(tr, dateCol);
          if (!iso) return false;                     // no date → excluded when filtering by date
          if (from && iso < from) return false;
          if (to && iso > to) return false;
        }
        return true;
      });
      var pages = Math.max(1, Math.ceil(filtered.length / pageSize));
      if (page > pages) page = pages;
      dataRows.forEach(function (tr) { tr.style.display = 'none'; });
      var start = (page - 1) * pageSize;
      filtered.slice(start, start + pageSize).forEach(function (tr) { tr.style.display = ''; });
      noRow.style.display = filtered.length === 0 ? '' : 'none';

      if (filtered.length === 0) { info.textContent = 'No results'; }
      else {
        info.textContent = 'Showing ' + (start + 1) + '–' + Math.min(start + pageSize, filtered.length) +
          ' of ' + filtered.length;
      }
      pageLbl.textContent = page + ' / ' + pages;
      prevBtn.disabled = page <= 1;
      nextBtn.disabled = page >= pages;
      nav.style.display = (filtered.length > pageSize || q) ? 'flex' : (dataRows.length > pageSize ? 'flex' : 'none');
    }

    prevBtn.addEventListener('click', function () { if (page > 1) { page--; apply(); } });
    nextBtn.addEventListener('click', function () { page++; apply(); });
    if (input) input.addEventListener('input', function () { page = 1; apply(); });
    filters.forEach(function (f) { f.addEventListener('change', function () { page = 1; apply(); }); });
    [dateFrom, dateTo].forEach(function (el) { if (el) el.addEventListener('change', function () { page = 1; apply(); }); });
    apply();
  }
});
</script>
