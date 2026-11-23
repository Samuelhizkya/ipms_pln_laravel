document.addEventListener('DOMContentLoaded', function () {
    const page = document.querySelector('.ul-page');

    if (!page) return;

    page.querySelectorAll('.ul-field select').forEach(function (select) {
        select.setAttribute('data-ipms-filter', '');
    });

    const search = page.querySelector('#ulSearch');
    const status = page.querySelector('#ulStatus');
    const button = page.querySelector('#ulPrint');
    const table = page.querySelector('#ulTable');
    const tbody = page.querySelector('#ulBody');

    if (!search || !status || !button || !table || !tbody) return;

    const rows = [...tbody.querySelectorAll('tr[data-entry]')];

    const unitName = page.dataset.unit;
    const period = page.dataset.period;

    let visibleRows = [];

    function totals(list) {
        const counts = {
            ok: 0,
            warn: 0,
            bad: 0,
            off: 0
        };

        list.forEach(function (row) {
            counts[row.dataset.status]++;
        });

        return counts;
    }

    function apply() {
        const term = search.value
            .trim()
            .toLocaleLowerCase('id-ID');

        visibleRows = rows.filter(function (row) {
            const matchesText = row.dataset.search
                .toLocaleLowerCase('id-ID')
                .includes(term);

            const matchesStatus =
                status.value === 'all' ||
                row.dataset.status === status.value;

            const show = matchesText && matchesStatus;
            row.hidden = !show;

            return show;
        });

        const displayedGroups = new Set();

        visibleRows.forEach(function (row, index) {
            const firstInGroup = !displayedGroups.has(
                row.dataset.group
            );

            row.cells[0].textContent = firstInGroup
                ? row.dataset.code
                : '';

            row.cells[1].textContent = firstInGroup
                ? row.dataset.name
                : '';

            row.classList.toggle(
                'ul-group-start',
                firstInGroup
            );

            row.querySelector('.ul-entry-number').textContent =
                (index + 1) + '.';

            displayedGroups.add(row.dataset.group);
        });

        const counts = totals(visibleRows);

        document.getElementById('ulTotal').textContent =
            visibleRows.length;

        document.getElementById('ulAchieved').textContent =
            counts.ok;

        document.getElementById('ulAttention').textContent =
            counts.warn + counts.bad;

        document.getElementById('ulMissing').textContent =
            counts.off;

        document.getElementById('ulEmpty').hidden =
            visibleRows.length > 0;

        document.getElementById('ulCount').textContent =
            'Menampilkan ' + visibleRows.length +
            ' dari ' + rows.length +
            ' entri laporan dalam ' +
            displayedGroups.size + ' kelompok KPI.';

        button.disabled = visibleRows.length === 0;
    }

    button.addEventListener('click', function () {
        if (!visibleRows.length) return;

        document.getElementById('ulPrintFrame')?.remove();

        const frame = document.createElement('iframe');
        frame.id = 'ulPrintFrame';
        frame.title = 'Dokumen cetak laporan kinerja';
        frame.style.cssText =
            'position:fixed;left:-12000px;top:0;' +
            'width:1120px;height:800px;border:0;';

        document.body.appendChild(frame);

        const doc = frame.contentDocument;

        doc.open();
        doc.write(`<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Kinerja ULP</title>
<style>
    @page {
        size: A4 landscape;
        margin: 12mm;
    }

    body {
        margin: 0;
        color: #000;
        font-family: Arial, sans-serif;
        font-size: 9pt;
    }

    header {
        text-align: center;
        margin-bottom: 16px;
    }

    h1 {
        font-size: 15pt;
        margin: 0 0 8px;
    }

    p {
        margin: 5px 0;
        line-height: 1.5;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 8pt;
    }

    th,
    td {
        border: 1px solid #000;
        padding: 6px;
        vertical-align: top;
        overflow-wrap: anywhere;
        line-height: 1.45;
    }

    th {
        text-align: center;
        background: #fff;
    }

    thead {
        display: table-header-group;
    }

    tr {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .ul-code,
    .ul-kpi-name {
        font-weight: bold;
    }

    .ul-code {
        white-space: nowrap;
    }

    .ul-indicator-content {
        display: flex;
        align-items: baseline;
        gap: 5px;
    }

    .ul-entry-number {
        flex-shrink: 0;
        min-width: 16px;
    }

    .ul-number {
        text-align: right;
    }

    footer {
        margin-top: 12px;
        font-size: 8pt;
    }
</style>
</head>
<body></body>
</html>`);
        doc.close();

        doc.title =
            'Laporan Kinerja - ' + unitName + ' - ' + period;

        const header = doc.createElement('header');
        const heading = doc.createElement('h1');

        heading.textContent =
            'IPMS PLN UP3 Palu | Laporan Kinerja';

        header.appendChild(heading);

        function addLine(text) {
            const paragraph = doc.createElement('p');
            paragraph.textContent = text;
            header.appendChild(paragraph);
        }

        addLine(
            unitName + ' | ' + period + ' | ' +
            status.options[status.selectedIndex].text +
            ' | Pencarian: ' + (search.value.trim() || '—')
        );

        const counts = totals(visibleRows);

        addLine(
            visibleRows.length + ' entri · Tercapai: ' +
            counts.ok +
            ' · Waspada: ' + counts.warn +
            ' · Belum tercapai: ' + counts.bad +
            ' · Belum dinilai: ' + counts.off
        );

const nkoSummary = document.getElementById('ulNkoSummary');

if (nkoSummary) {
    addLine(
        nkoSummary.innerText.replace(/\s+/g, ' ').trim()
    );
}

        doc.body.appendChild(header);

        const printTable = doc.createElement('table');
        const colgroup = doc.createElement('colgroup');

        [7, 23, 22, 8, 10, 10, 9, 11].forEach(function (width) {
            const col = doc.createElement('col');
            col.style.width = width + '%';
            colgroup.appendChild(col);
        });

        printTable.appendChild(colgroup);

        printTable.appendChild(
            doc.importNode(table.tHead, true)
        );

        const printBody = doc.createElement('tbody');

        visibleRows.forEach(function (row) {
            const copy = doc.importNode(row, true);

            [...copy.attributes].forEach(function (attribute) {
                if (
                    attribute.name.startsWith('data-') ||
                    attribute.name === 'hidden'
                ) {
                    copy.removeAttribute(attribute.name);
                }
            });

            printBody.appendChild(copy);
        });

        printTable.appendChild(printBody);
        doc.body.appendChild(printTable);

        const footer = doc.createElement('footer');

        footer.textContent =
            'Nilai mengikuti data tersimpan. Tanda — berarti ' +
            'belum tersedia, bukan nol. Kategori sementara: ' +
            'tercapai ≥100%, waspada ≥90% dan <100%, ' +
            'selebihnya belum tercapai.';

        doc.body.appendChild(footer);

        frame.contentWindow.requestAnimationFrame(function () {
            frame.contentWindow.requestAnimationFrame(function () {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            });
        });
    });

    search.addEventListener('input', apply);
    status.addEventListener('change', apply);

    apply();
});