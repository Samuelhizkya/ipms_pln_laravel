document.addEventListener('DOMContentLoaded', async function () {
    'use strict';
        const laporanPage = document.getElementById('lpPage');

    if (!laporanPage) return;

    laporanPage.querySelectorAll('select').forEach(select => {
        select.setAttribute('data-ipms-filter', '');
    });
    const $ = id => document.getElementById(id);

    let ulps = [];
    let actual = [];
    let targets = [];

    const months = [
        'Januari', 'Februari', 'Maret', 'April',
        'Mei', 'Juni', 'Juli', 'Agustus',
        'September', 'Oktober', 'November', 'Desember'
    ];

    const colors = {
        ok: '#237A34',
        warn: '#F39C12',
        bad: '#E03E3E',
        off: '#9AA9B5'
    };

    const labels = {
        ok: 'Tercapai',
        warn: 'Waspada',
        bad: 'Belum Tercapai',
        off: 'Belum dinilai'
    };

    const num = value => {
        if (
            value === null ||
            value === undefined ||
            value === '' ||
            !Number.isFinite(Number(value))
        ) {
            return null;
        }

        return Number(value);
    };

    const fmt = (value, decimals = 1) => {
        if (value == null) return '—';

        return Number(value).toLocaleString('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    };

    const esc = value => String(value ?? '').replace(
        /[&<>"']/g,
        character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[character])
    );

    const csvCell = value => {
        const text = String(value ?? '');

        const numeric =
            /^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(text);

        const content =
            !numeric && /^\s*[=+@\-\t\r]/.test(text)
                ? "'" + text
                : text;

        return '"' + content.replace(/"/g, '""') + '"';
    };

    const monthAt = month => months[month];

    const statusOf = value => {
        if (value == null) return 'off';
        if (value >= 100) return 'ok';
        if (value >= 90) return 'warn';
        return 'bad';
    };

    function recordsFor(current) {
        const result = [];

        const selectedMonths = current.month === 'all'
            ? months.map((_, index) => index)
            : [Number(current.month)];

        for (const month of selectedMonths) {
            const rows = actual
                .filter(row =>
                    Number(row.periode?.tahun) === current.year &&
                    Number(row.periode?.bulan) === month + 1 &&
                    (
                        current.ulp === 'all' ||
                        String(row.id_ulp) === current.ulp
                    )
                )
                .map(row => ({
                    unitId: String(row.id_ulp),
                    kpiId: Number(row.id_kpi),
                    year: current.year,
                    month,
                    ulp: row.ulp?.nama_ulp ||
                        ulps.find(unit =>
                            String(unit.id_ulp) === String(row.id_ulp)
                        )?.nama_ulp || '—',
                    code: row.kpi?.kode_kpi || '—',
                    name: (row.kpi?.nama_kpi || '—') +
                        (
                            row.nama_pendukung
                                ? ' · ' + row.nama_pendukung
                                : ''
                        ),
                    unit: row.satuan_pendukung ||
                        row.kpi?.satuan || '—',
                    digits: 2,
                    target: num(row.target_periode),
                    actual: num(row.realisasi),
                    pct: num(row.pencapaian)
                }));

            targets
                .filter(target =>
                    Number(target.tahun) === current.year &&
                    (
                        current.ulp === 'all' ||
                        String(target.id_ulp) === current.ulp
                    )
                )
                .forEach(target => {
                    if (rows.some(row =>
                        row.unitId === String(target.id_ulp) &&
                        row.kpiId === Number(target.id_kpi)
                    )) {
                        return;
                    }

                    const monthly = (
                        target.target_bulanan ||
                        target.targetBulanan ||
                        []
                    ).find(item => Number(item.bulan) === month + 1);

                    rows.push({
                        unitId: String(target.id_ulp),
                        kpiId: Number(target.id_kpi),
                        year: current.year,
                        month,
                        ulp: target.ulp?.nama_ulp ||
                            ulps.find(unit =>
                                String(unit.id_ulp) === String(target.id_ulp)
                            )?.nama_ulp || '—',
                        code: target.kpi?.kode_kpi || '—',
                        name: target.kpi?.nama_kpi || '—',
                        unit: target.kpi?.satuan || '—',
                        digits: 2,
                        target: num(monthly?.nilai_target),
                        actual: null,
                        pct: null
                    });
                });

            rows.forEach(row => {
                row.status = statusOf(row.pct);

                if (
                    current.status === 'all' ||
                    row.status === current.status
                ) {
                    result.push(row);
                }
            });
        }

        return result.sort((a, b) =>
            a.month - b.month ||
            a.ulp.localeCompare(b.ulp) ||
            a.kpiId - b.kpiId
        );
    }

    let applied = {
        ulp: 'all',
        year: 2026,
        month: '1',
        status: 'all'
    };

    let allRows = [];
    let filteredRows = [];
    let page = 1;
    const pageSize = 12;


    function displayPeriod(current) {
        return current.month === 'all'
            ? 'Januari–Desember ' + current.year
            : monthAt(Number(current.month)) + ' ' + current.year;
    }

    function currentFiltered() {
        const key = $('lpSearch').value.toLowerCase().trim();

        return allRows.filter(row =>
            row.ulp.toLowerCase().includes(key) ||
            row.code.toLowerCase().includes(key) ||
            row.name.toLowerCase().includes(key) ||
            monthAt(row.month).toLowerCase().includes(key)
        );
    }

    function renderRows() {
        filteredRows = currentFiltered();

        const totalPages = Math.max(
            1,
            Math.ceil(filteredRows.length / pageSize)
        );

        page = Math.min(page, totalPages);

        const subset = filteredRows.slice(
            (page - 1) * pageSize,
            page * pageSize
        );

        $('lpTableBody').innerHTML = subset.length
            ? subset.map((row, index) => {
                const width =
                    Math.max(0, Math.min(row.pct ?? 0, 110)) /
                    110 * 100;

                return (
                    '<tr>' +
                        '<td>' +
                            ((page - 1) * pageSize + index + 1) +
                        '</td>' +
                        '<td>' + esc(row.ulp) + '</td>' +
                        '<td>' +
                            monthAt(row.month).slice(0, 3) +
                            ' ' + row.year +
                        '</td>' +
                        '<td>' +
                            '<span class="lp-kcode">' +
                                esc(row.code) +
                            '</span>' +
                            '<span class="lp-kname">' +
                                esc(row.name) +
                            '</span>' +
                        '</td>' +
                        '<td>' + esc(row.unit) + '</td>' +
                        '<td>' + fmt(row.target, row.digits) + '</td>' +
                        '<td>' + fmt(row.actual, row.digits) + '</td>' +
                        '<td>' +
                            '<div class="lp-progress">' +
                                '<div class="lp-track">' +
                                    '<div class="lp-fill" style="background:' +
                                        colors[row.status] +
                                        ';width:' + width + '%">' +
                                    '</div>' +
                                '</div>' +
                                '<strong>' +
                                    (
                                        row.pct === null
                                            ? '—'
                                            : fmt(row.pct, 1) + '%'
                                    ) +
                                '</strong>' +
                            '</div>' +
                        '</td>' +
                        '<td>' +
                            '<span class="lp-pill ' + row.status + '">' +
                                labels[row.status] +
                            '</span>' +
                        '</td>' +
                    '</tr>'
                );
            }).join('')
            : '<tr><td colspan="9" ' +
              'style="padding:25px;text-align:center">' +
              'Data tidak ditemukan untuk pencarian ini.' +
              '</td></tr>';

        $('lpRowCount').textContent =
            'Menampilkan ' +
            (subset.length ? (page - 1) * pageSize + 1 : 0) +
            '–' +
            Math.min(page * pageSize, filteredRows.length) +
            ' dari ' + filteredRows.length + ' baris';

        $('lpPageNumber').textContent =
            'Halaman ' + page + ' / ' + totalPages;

        $('lpPrev').disabled = page <= 1;
        $('lpNext').disabled = page >= totalPages;
    }

    function renderSummary() {
        const rows = currentFiltered();
        const count = rows.length;

        const green = rows.filter(row => row.status === 'ok').length;
        const yellow = rows.filter(row => row.status === 'warn').length;
        const red = rows.filter(row => row.status === 'bad').length;
        const off = rows.filter(row => row.status === 'off').length;

        const assessed = rows.filter(row => row.pct !== null);

        const average = assessed.length
            ? assessed.reduce((sum, row) => sum + row.pct, 0) /
              assessed.length
            : null;

        $('lpUnits').textContent =
            new Set(rows.map(row => row.unitId)).size;

        $('lpEntries').textContent = count.toLocaleString('id-ID');
        $('lpAchieved').textContent = green.toLocaleString('id-ID');

        $('lpAverage').textContent =
            average === null ? '--' : fmt(average, 1) + '%';

        $('lpReportPeriod').textContent = displayPeriod(applied);

        $('lpReportUlp').textContent = applied.ulp === 'all'
            ? 'Seluruh ULP'
            : ulps.find(unit =>
                String(unit.id_ulp) === applied.ulp
            )?.nama_ulp || '—';

const nkoBox = $('lpNko');
const nkoToken = Symbol('nko-request');

nkoBox.nkoToken = nkoToken;
nkoBox.textContent = 'Memuat NKO…';
nkoBox.title = '';

const nkoFilter = {
    ulp: String(applied.ulp),
    year: Number(applied.year),
    month: String(applied.month)
};

(async function () {
    if (nkoFilter.month === 'all') {
        nkoBox.textContent = 'Pilih satu bulan';
        nkoBox.title =
            'NKO bulanan tidak dijumlahkan menjadi NKO tahunan.';
        return;
    }

    if (nkoFilter.ulp === 'all') {
        nkoBox.textContent = 'Pilih satu ULP';
        nkoBox.title =
            'NKO setiap ULP berbeda. Jumlah atau rata-ratanya bukan NKO UP3.';
        return;
    }

    const namaBulanNko = [
        'Januari', 'Februari', 'Maret', 'April',
        'Mei', 'Juni', 'Juli', 'Agustus',
        'September', 'Oktober', 'November', 'Desember'
    ];

    const bulanTerpilih = monthAt(Number(nkoFilter.month));

    const nomorBulan = namaBulanNko.findIndex(
        nama => nama.toLowerCase() ===
            String(bulanTerpilih).trim().toLowerCase()
    ) + 1;

    try {
        if (nomorBulan < 1 || nomorBulan > 12) {
            throw new Error('Bulan laporan tidak dikenali.');
        }

        const url = new URL(window.location.href);

        url.pathname = url.pathname.replace(
            /\/laporan\/?$/,
            '/ranking'
        );

        url.search = '';
        url.hash = '';

        url.searchParams.set('dashboard_nko', '1');
        url.searchParams.set('tahun', String(nkoFilter.year));
        url.searchParams.set('bulan', String(nomorBulan));

        const response = await fetch(url, {
            headers: {
                Accept: 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(
                'Gagal membaca NKO (HTTP ' + response.status + ').'
            );
        }

        const result = await response.json();

        if (
            result.success !== true ||
            !Array.isArray(result.data)
        ) {
            throw new Error('Format data NKO tidak sesuai.');
        }

        if (nkoBox.nkoToken !== nkoToken) return;

        const unit = result.data.find(item =>
            String(item.id_ulp) === nkoFilter.ulp
        );

        if (!unit) {
            nkoBox.textContent = 'Data ULP tidak tersedia';
            return;
        }

        const nilaiTersedia =
            unit.status_nko === 'lengkap' &&
            unit.nko !== null &&
            unit.nko !== undefined &&
            Number.isFinite(Number(unit.nko));

        nkoBox.textContent = nilaiTersedia
            ? Number(unit.nko).toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
            : unit.status_penilaian || 'NKO belum tersedia';

        const komponenKurang = (unit.rincian_nko || [])
            .filter(item => item.nilai_kpi == null)
            .map(item =>
                item.nama_kpi || 'KPI ID ' + item.id_kpi
            );

        nkoBox.title = komponenKurang.length
            ? 'Komponen belum dapat dihitung: ' +
              komponenKurang.join('; ')
            : 'NKO mengikuti ULP dan bulan laporan. ' +
              'Filter status dan pencarian tidak mengubah NKO.';
    } catch (error) {
        if (nkoBox.nkoToken !== nkoToken) return;

        nkoBox.textContent = 'Gagal memuat NKO';
        nkoBox.title = error.message;

        console.error('NKO Laporan:', error);
    }
})();

        $('lpAttention').textContent =
            (yellow + red).toLocaleString('id-ID');

        $('lpSubtitle').textContent =
            'Rekapitulasi pencapaian KPI untuk ' +
            $('lpReportUlp').textContent +
            ' pada ' + displayPeriod(applied) + '. ' +
            assessed.length + ' entri dinilai; ' +
            off + ' belum dinilai. ' +
            'Entri pendukung ditampilkan terpisah.';

        $('lpActive').textContent =
            'Menampilkan: ' +
            $('lpReportUlp').textContent +
            ' • ' + displayPeriod(applied) +
            ' • ' +
            (
                applied.status === 'all'
                    ? 'Semua Status'
                    : labels[applied.status]
            );

        $('lpPrintDescription').textContent =
            $('lpReportUlp').textContent +
            ' | ' + displayPeriod(applied) +
            ' | ' +
            (
                applied.status === 'all'
                    ? 'Semua status'
                    : labels[applied.status]
            ) +
            ' | Pencarian: ' + ($('lpSearch').value || '—');

        [
            ['lpGreenCount', 'lpGreenBar', green],
            ['lpYellowCount', 'lpYellowBar', yellow],
            ['lpRedCount', 'lpRedBar', red],
            ['lpOffCount', 'lpOffBar', off]
        ].forEach(([id, bar, value]) => {
            $(id).textContent = value;
            $(bar).style.width =
                count ? (value / count * 100) + '%' : '0%';
        });

    }

    function apply() {
        applied = {
            ulp: $('lpUlp').value,
            year: Number($('lpYear').value),
            month: $('lpMonth').value,
            status: $('lpStatus').value
        };

        allRows = recordsFor(applied);
        $('lpSearch').value = '';
        page = 1;

        renderRows();
        renderSummary();

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function exportCsv() {
        const rows = currentFiltered();

        if (!rows.length) {
            alert('Tidak ada data untuk diekspor.');
            return;
        }

        const columns = [[
            'No',
            'ULP',
            'Tahun',
            'Bulan',
            'Kode KPI',
            'Indikator KPI',
            'Satuan',
            'Target',
            'Realisasi',
            'Pencapaian (%)',
            'Status'
        ]];

        rows.forEach((row, index) => {
            columns.push([
                index + 1,
                row.ulp,
                row.year,
                monthAt(row.month),
                row.code,
                row.name,
                row.unit,
                fmt(row.target, row.digits),
                fmt(row.actual, row.digits),
                fmt(row.pct, 1),
                labels[row.status]
            ]);
        });

        const csv =
            '\uFEFFsep=;\r\n' +
            columns.map(row =>
                row.map(csvCell).join(';')
            ).join('\r\n');

        const blob = new Blob([csv], {
            type: 'text/csv;charset=utf-8'
        });

        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');

        link.href = url;
        link.download =
            'Laporan_KPI_' + applied.year + '_' +
            (
                applied.month === 'all'
                    ? 'Tahunan'
                    : monthAt(Number(applied.month))
            ) +
            '.csv';

        document.body.append(link);
        link.click();
        link.remove();

        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    function printRowsHtml(rows) {
        return rows.map((row, index) =>
            '<tr>' +
                '<td>' + (index + 1) + '</td>' +
                '<td>' + esc(row.ulp) + '</td>' +
                '<td>' +
                    esc(monthAt(row.month) + ' ' + row.year) +
                '</td>' +
                '<td>' +
                    esc(row.code + ' ' + row.name) +
                '</td>' +
                '<td>' + esc(row.unit) + '</td>' +
                '<td class="number">' +
                    fmt(row.target, row.digits) +
                '</td>' +
                '<td class="number">' +
                    fmt(row.actual, row.digits) +
                '</td>' +
                '<td class="number">' +
                    (
                        row.pct === null
                            ? '—'
                            : fmt(row.pct, 1) + '%'
                    ) +
                '</td>' +
                '<td>' + labels[row.status] + '</td>' +
            '</tr>'
        ).join('');
    }

    function preparePrint() {
        $('lpPrintBody').innerHTML =
            printRowsHtml(currentFiltered());
    }

    function planPrintPages(heights, capacity) {
        const pages = [];
        let currentPage = [];
        let used = 0;

        heights.forEach((height, index) => {
            if (currentPage.length && used + height > capacity) {
                pages.push(currentPage);
                currentPage = [];
                used = 0;
            }

            currentPage.push(index);
            used += height;
        });

        if (currentPage.length) {
            pages.push(currentPage);
        }

        // Seimbangkan halaman terakhir tanpa mengubah urutan.
        for (let p = pages.length - 1; p > 0; p--) {
            const left = pages[p - 1];
            const right = pages[p];

            let leftHeight = left.reduce(
                (sum, index) => sum + heights[index],
                0
            );

            let rightHeight = right.reduce(
                (sum, index) => sum + heights[index],
                0
            );

            while (left.length > 1) {
                const index = left[left.length - 1];
                const height = heights[index];

                if (
                    rightHeight + height > capacity ||
                    Math.abs(
                        (leftHeight - height) -
                        (rightHeight + height)
                    ) >= Math.abs(leftHeight - rightHeight)
                ) {
                    break;
                }

                left.pop();
                right.unshift(index);

                leftHeight -= height;
                rightHeight += height;
            }
        }

        return pages;
    }

    function paginatePrintDocument(doc) {
        const table = doc.querySelector('table');

        if (!table) return;

        doc.body.style.width = '273mm';

        const header = doc.createElement('div');
        header.className = 'sheet-header';

        while (
            doc.body.firstChild &&
            doc.body.firstChild !== table
        ) {
            header.append(doc.body.firstChild);
        }

        doc.body.insertBefore(header, table);

        const note = doc.querySelector('.note');

        if (note) {
            header.append(note);
        }

        const rows = Array.from(table.tBodies[0].rows);

        const heights = rows.map(row =>
            row.getBoundingClientRect().height
        );

        const pixelsPerMm = 96 / 25.4;

        const capacity =
            166 * pixelsPerMm -
            header.getBoundingClientRect().height -
            table.tHead.getBoundingClientRect().height -
            8;

        if (
            capacity < 40 * pixelsPerMm ||
            heights.some(height => height > capacity)
        ) {
            // Gunakan aliran normal jika sebuah baris terlalu tinggi.
            return;
        }

        const pages = planPrintPages(heights, capacity);
        const fragment = doc.createDocumentFragment();

        pages.forEach((indices, pageIndex) => {
            const sheet = doc.createElement('section');
            sheet.className = 'sheet';

            sheet.append(header.cloneNode(true));

            const copy = table.cloneNode(false);

            copy.append(
                table.querySelector('colgroup').cloneNode(true),
                table.tHead.cloneNode(true)
            );

            const tbody = doc.createElement('tbody');

            indices.forEach(index => {
                tbody.append(rows[index].cloneNode(true));
            });

            copy.append(tbody);
            sheet.append(copy);

            const footer = doc.createElement('div');
            footer.className = 'page-footer';

            const label = doc.createElement('span');
            label.textContent =
                'IPMS PLN UP3 Palu · ' +
                indices.length +
                ' entri pada halaman ini';

            const number = doc.createElement('span');
            number.textContent =
                'Halaman ' + (pageIndex + 1) +
                ' / ' + pages.length;

            footer.append(label, number);
            sheet.append(footer);
            fragment.append(sheet);
        });

        doc.body.replaceChildren(fragment);
    }

    function printReport() {
        const rows = currentFiltered();

        if (!rows.length) {
            alert('Tidak ada data untuk dicetak.');
            return;
        }

        document.getElementById('lpPrintFrame')?.remove();

        const frame = document.createElement('iframe');
        frame.id = 'lpPrintFrame';
        frame.title = 'Dokumen cetak laporan kinerja';

        frame.style.cssText =
            'position:fixed;left:-12000px;top:0;' +
            'width:1120px;height:800px;border:0;';

        document.body.append(frame);

        const body = printRowsHtml(rows);

        const counts = rows.reduce((result, row) => {
            result[row.status]++;
            return result;
        }, {
            ok: 0,
            warn: 0,
            bad: 0,
            off: 0
        });

        /*
         * Dokumen cetak tersendiri.
         * Semua sel bergaris dan kepala laporan rata tengah.
         */
        const printCss = `
            @page {
                size: A4 landscape;
                margin: 12mm;
            }

            * {
                box-sizing: border-box;
            }

            html,
            body {
                margin: 0;
                padding: 0;
                background: #fff;
                color: #000;
                font-family: Inter, Arial, sans-serif;
            }

            h1 {
                font-family: Poppins, Inter, Arial, sans-serif;
                font-size: 15pt;
                font-weight: 700;
                text-align: center;
                color: #000;
                margin: 0 0 7px;
            }

            p {
                font-size: 9pt;
                line-height: 1.4;
                margin: 5px 0;
            }

            .report-description {
                text-align: center;
            }

            .summary {
                text-align: center;
                margin: 9px 0 12px;
                padding: 5px 0;
                font-size: 9pt;
                line-height: 1.6;
                border: none;
            }

            table {
                width: 100%;
                table-layout: fixed;
                border-collapse: collapse;
                border-spacing: 0;
                border: 1px solid #000;
                font-size: 8.5pt;
                line-height: 1.3;
            }

            thead {
                display: table-header-group;
            }

            tbody {
                display: table-row-group;
            }

            th,
            td {
                border: 1px solid #000;
                padding: 6px;
                vertical-align: top;
                word-break: normal;
                overflow-wrap: break-word;
            }

            th {
                font-family: Poppins, Inter, Arial, sans-serif;
                background: #fff;
                color: #000;
                text-align: center;
                font-weight: 700;
                font-size: 8pt;
                white-space: normal;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            td:nth-child(1),
            td:nth-child(2),
            td:nth-child(3),
            td:nth-child(5),
            td:nth-child(9) {
                text-align: center;
            }

            td:nth-child(4) {
                text-align: left;
            }

            td.number {
                white-space: nowrap;
                text-align: right;
                font-size: 8pt;
                overflow-wrap: normal;
                font-variant-numeric: tabular-nums;
            }

            .sheet {
                width: 273mm;
                height: 180mm;
                position: relative;
                break-after: page;
                page-break-after: always;
                overflow: visible;
            }

            .sheet:last-child {
                break-after: auto;
                page-break-after: auto;
            }

            .sheet-header {
                text-align: center;
                padding-bottom: 3mm;
            }

            .sheet-header > p {
                text-align: center;
            }

            .page-footer {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                border-top: 1px solid #999;
                padding-top: 2mm;
                font-size: 8pt;
                color: #444;
                display: flex;
                justify-content: space-between;
            }

            .note {
                font-size: 8pt;
                color: #444;
                margin-top: 8px;
                line-height: 1.4;
                text-align: center;
            }
        `;

        const html =
            '<!doctype html>' +
            '<html lang="id">' +
                '<head>' +
                    '<meta charset="utf-8">' +
                    '<title>Laporan Kinerja IPMS PLN UP3 Palu</title>' +
                    '<link href="https://fonts.googleapis.com/css2?' +
                        'family=Inter:wght@400;500;600;700&amp;' +
                        'family=Poppins:wght@400;500;600;700&amp;' +
                        'display=swap" rel="stylesheet">' +
                    '<style>' + printCss + '</style>' +
                '</head>' +
                '<body>' +
                    '<h1>IPMS PLN UP3 Palu | Laporan Kinerja</h1>' +

                    '<p class="report-description">' +
                        esc($('lpPrintDescription').textContent) +
                    '</p>' +

                    '<div class="summary">' +
                        rows.length + ' entri · ' +
                        'Tercapai: ' + counts.ok + ' · ' +
                        'Waspada: ' + counts.warn + ' · ' +
                        'Belum tercapai: ' + counts.bad + ' · ' +
                        'Belum dinilai: ' + counts.off +
                        '<br>' +
                    'NKO: ' + esc($('lpNko').textContent) + '. ' +
                    esc($('lpNko').title || '') + '<br>' +
                        'Entri pendukung ditampilkan terpisah.' +
                    '</div>' +

                    '<table>' +
                        '<colgroup>' +
                            '<col style="width:3%">' +
                            '<col style="width:8%">' +
                            '<col style="width:8%">' +
                            '<col style="width:29%">' +
                            '<col style="width:10%">' +
                            '<col style="width:12%">' +
                            '<col style="width:12%">' +
                            '<col style="width:9%">' +
                            '<col style="width:9%">' +
                        '</colgroup>' +

                        '<thead>' +
                            '<tr>' +
                                '<th>No.</th>' +
                                '<th>ULP</th>' +
                                '<th>Periode</th>' +
                                '<th>Kode / Indikator</th>' +
                                '<th>Satuan</th>' +
                                '<th>Target</th>' +
                                '<th>Realisasi</th>' +
                                '<th>Pencapaian</th>' +
                                '<th>Status</th>' +
                            '</tr>' +
                        '</thead>' +

                        '<tbody>' + body + '</tbody>' +
                    '</table>' +

                    '<p class="note">' +
                        'Target memakai nilai periode. Realisasi dan ' +
                        'pencapaian mengikuti data tersimpan; — berarti ' +
                        'belum tersedia, bukan nol. Kategori sementara: ' +
                        'tercapai ≥100%, waspada ≥90% dan &lt;100%, ' +
                        'selebihnya belum tercapai; perlu disepakati tim.' +
                    '</p>' +
                '</body>' +
            '</html>';

        let started = false;

        frame.onload = () => {
            if (
                started ||
                !frame.contentDocument?.getElementById('lpReady')
            ) {
                return;
            }

            started = true;

            const win = frame.contentWindow;

            Promise.resolve(frame.contentDocument.fonts?.ready)
                .then(() => {
                    win.requestAnimationFrame(() => {
                        paginatePrintDocument(frame.contentDocument);

                        win.requestAnimationFrame(() => {
                            win.focus();
                            win.print();
                        });
                    });
                });
        };

        const doc = frame.contentDocument;

        doc.open();
        doc.write(
            html.replace(
                '</body>',
                '<span id="lpReady" hidden></span></body>'
            )
        );
        doc.close();
    }

    window.addEventListener('beforeprint', preparePrint);

    $('lpApply').addEventListener('click', apply);

    $('lpSearch').addEventListener('input', () => {
        page = 1;
        renderRows();
        renderSummary();
    });

    $('lpPrev').addEventListener('click', () => {
        page--;
        renderRows();
    });

    $('lpNext').addEventListener('click', () => {
        page++;
        renderRows();
    });

    $('lpExport').addEventListener('click', exportCsv);
    $('lpPrint').addEventListener('click', printReport);

const base = laporanPage.dataset.apiBase;

    async function get(path) {
        const response = await fetch(base + '/' + path, {
            headers: {
                Accept: 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(path + ' (HTTP ' + response.status + ')');
        }

        const body = await response.json();

        if (body.success === false || !Array.isArray(body.data)) {
            throw new Error('Format data tidak sesuai: ' + path);
        }

        return body.data;
    }

    let initial = {
        year: 2026,
        month: '1'
    };

    function reset() {
        $('lpUlp').value = 'all';
        $('lpYear').value = String(initial.year);
        $('lpMonth').value = initial.month;
        $('lpStatus').value = 'all';
        apply();
    }

    $('lpReset').onclick = reset;

    try {
        ['lpApply', 'lpExport', 'lpPrint', 'lpReset'].forEach(id => {
            $(id).disabled = true;
        });

        [ulps, actual, targets] = await Promise.all([
            get('ulp'),
            get('realisasi-kpi'),
            get('target-kpi')
        ]);

        ulps.forEach(unit => {
            $('lpUlp').add(
                new Option(unit.nama_ulp, String(unit.id_ulp))
            );
        });

        months.forEach((month, index) => {
            $('lpMonth').add(
                new Option(month, String(index))
            );
        });

        const periods = actual
            .filter(row => row.periode)
            .map(row => ({
                year: Number(row.periode.tahun),
                month: Number(row.periode.bulan) - 1
            }))
            .sort((a, b) =>
                b.year - a.year || b.month - a.month
            );

        initial = periods.length
            ? {
                year: periods[0].year,
                month: String(periods[0].month)
            }
            : {
                year: Number(targets[0]?.tahun) ||
                    new Date().getFullYear(),
                month: '0'
            };

        const years = [...new Set([
            ...periods.map(period => period.year),
            ...targets.map(target => Number(target.tahun)),
            initial.year
        ])]
            .filter(year => year > 0)
            .sort((a, b) => b - a);

        $('lpYear').replaceChildren(
            ...years.map(year =>
                new Option(String(year), String(year))
            )
        );

        ['lpApply', 'lpExport', 'lpPrint', 'lpReset'].forEach(id => {
            $(id).disabled = false;
        });

        reset();
    } catch (error) {
        $('lpActive').textContent =
            'Gagal memuat laporan. Periksa koneksi database, ' +
            'endpoint data monitoring, dan sesi login, ' +
            'lalu muat ulang halaman.';

        console.error('Laporan:', error);
    }
});