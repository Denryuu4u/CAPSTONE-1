/* Shared, read-only printing for back-office tables, including modal tables. */
(() => {
    'use strict';
    const registered = new WeakSet();
    let printFrame;

    function tableTitle(table) {
        const scope = table.closest('.modal, .summ-section, .card, section');
        const heading = scope && scope.querySelector('.modal-title, .summ-section-title, h2, h3, h4, h5, .card-title');
        return (table.dataset.printTitle || (heading && heading.textContent) ||
            document.querySelector('.page-title, h1')?.textContent || 'Table').trim();
    }

    function cellText(cell) {
        const copy = cell.cloneNode(true);
        // Remove interactive actions and invisible helper content, not data labels.
        const originals = cell.querySelectorAll('input, select, textarea');
        copy.querySelectorAll('input, select, textarea').forEach((el, index) => {
            const source = originals[index];
            let value = source.value;
            if (source.matches('select')) value = source.selectedOptions[0]?.textContent || '';
            if (source.matches('[type=checkbox], [type=radio]')) value = source.checked ? 'Yes' : 'No';
            if (source.matches('[type=password], [type=file], [type=hidden]')) value = '';
            el.replaceWith(document.createTextNode(value));
        });
        copy.querySelectorAll('button, script, style, [hidden], .d-none, .visually-hidden').forEach(el => el.remove());
        copy.querySelectorAll('br').forEach(el => el.replaceWith(document.createTextNode(' ')));
        return copy.textContent.replace(/\s+/g, ' ').trim();
    }

    function printTable(table) {
        if (printFrame) printFrame.remove();
        const title = tableTitle(table);
        const headers = Array.from(table.tHead?.rows[0]?.cells || []);
        const columns = headers.map((cell, index) => ({cell, index})).filter(({cell}) => {
            const label = cellText(cell);
            return label && !/^(actions?|manage|select)$/i.test(label) && !cell.hidden && !cell.classList.contains('d-none');
        });
        if (!columns.length) return;
        printFrame = document.createElement('iframe');
        printFrame.title = 'Print ' + title;
        printFrame.style.cssText = 'position:fixed;width:1px;height:1px;left:-10000px;top:0;border:0;';
        document.body.appendChild(printFrame);
        const doc = printFrame.contentDocument;
        doc.open();
        doc.write('<!doctype html><html><head><meta charset="utf-8"><title></title><style>' +
            '@page{size:landscape;margin:12mm}body{font:11px Arial,sans-serif;color:#172b29;margin:0}' +
            'h1{font-size:20px;margin:0 0 8px}p{color:#556563;margin:0 0 16px}' +
            'table{width:100%;border-collapse:collapse;table-layout:auto}thead{display:table-header-group}' +
            'th,td{border:1px solid #b8c7c4;padding:8px;text-align:left;vertical-align:top;overflow-wrap:anywhere}' +
            'th{background:#e7efed;font-weight:bold}tr{break-inside:avoid}tfoot{display:table-footer-group}' +
            '</style></head><body></body></html>');
        doc.close();
        doc.title = title;
        const heading = doc.createElement('h1');
        heading.textContent = title;
        doc.body.appendChild(heading);
        const note = doc.createElement('p');
        note.textContent = 'Printed ' + new Date().toLocaleString('en-PH', {timeZone:'Asia/Manila'}) + ' · Current filtered records';
        doc.body.appendChild(note);
        const output = doc.createElement('table');
        const header = output.createTHead().insertRow();
        columns.forEach(({cell}) => {
            const th = doc.createElement('th');
            th.scope = 'col'; th.textContent = cellText(cell); header.appendChild(th);
        });
        const body = output.createTBody();
        const rows = typeof table.getPrintRows === 'function' ? table.getPrintRows() :
            Array.from(table.tBodies).flatMap(section => Array.from(section.rows)).filter(row =>
                !row.hidden && getComputedStyle(row).display !== 'none');
        rows.forEach(row => {
            const result = body.insertRow();
            if (row.cells.length === 1 && row.cells[0].colSpan > 1) {
                const td = result.insertCell(); td.colSpan = columns.length; td.textContent = cellText(row.cells[0]);
            } else columns.forEach(({index}) => {
                result.insertCell().textContent = row.cells[index] ? cellText(row.cells[index]) : '';
            });
        });
        if (!body.rows.length) {
            const td = body.insertRow().insertCell(); td.colSpan = columns.length; td.textContent = 'No matching records.';
        }
        doc.body.appendChild(output);
        const target = printFrame.contentWindow;
        requestAnimationFrame(() => { target.focus(); target.print(); });
    }

    function registerTables() {
        document.querySelectorAll('table').forEach(table => {
            if (registered.has(table) || !table.tHead || table.closest('#reportPreviewModal') || table.classList.contains('rp-table')) return;
            registered.add(table);
            const bar = document.createElement('div');
            bar.style.cssText = 'display:flex;justify-content:flex-end;padding:8px 12px;';
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'btn btn-sm btn-outline-secondary';
            button.innerHTML = '<i class="bi bi-printer me-1" aria-hidden="true"></i>Print';
            button.title = 'Print this table or save it as PDF';
            button.addEventListener('click', () => printTable(table));
            bar.appendChild(button);
            const host = table.closest('.table-responsive') || table;
            host.before(bar);
        });
    }
    registerTables();
    new MutationObserver(registerTables).observe(document.body, {childList:true, subtree:true});
})();
