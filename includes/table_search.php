<?php
/**
 * Shared client-side search for admin list pages.
 *
 * Any text input with a class like "<name>-search" is wired to filter the table
 * with the matching class "<name>-table" (e.g. customer-search → customer-table).
 * Rows are shown/hidden by matching the search text against the row's text.
 * Included once via admin/sidebar.php, so every list page gets it automatically.
 * (archive.php uses its own filter and the class "arch-search-input", which does
 *  NOT end in "-search", so it is left untouched.)
 */
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('input[class*="-search"]').forEach(function (input) {
    var cls = Array.prototype.find.call(input.classList, function (c) { return /-search$/.test(c); });
    if (!cls) return;
    var table = document.querySelector('.' + cls.slice(0, -7) + '-table');
    if (!table) return;
    var tbody = table.querySelector('tbody');
    if (!tbody) return;

    // Rows present at load are the real data rows (a lone colspan row means the
    // table is empty — nothing to search).
    var dataRows = Array.prototype.filter.call(tbody.querySelectorAll(':scope > tr'), function (tr) {
      return !tr.querySelector('td[colspan]');
    });
    if (!dataRows.length) return;

    var colCount = table.querySelectorAll('thead th').length || 1;
    var noRow = document.createElement('tr');
    noRow.innerHTML = '<td colspan="' + colCount + '" class="text-center text-muted py-4">No matching results.</td>';
    noRow.style.display = 'none';
    tbody.appendChild(noRow);

    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      var visible = 0;
      dataRows.forEach(function (tr) {
        var ok = tr.textContent.toLowerCase().indexOf(q) !== -1;
        tr.style.display = ok ? '' : 'none';
        if (ok) visible++;
      });
      noRow.style.display = visible === 0 ? '' : 'none';
    });
  });
});
</script>
