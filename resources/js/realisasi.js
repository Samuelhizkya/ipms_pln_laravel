import {
    mountKpiExport,
    exportNumber,
} from './kpi-export';
function initializeRealisasiPage() {
    const root = document.getElementById('realisasi-workspace');

    if (!root) return;
    const canEdit = root.dataset.canEdit === 'true';
    const el = (id) => document.getElementById(id);

    const search = el('realisasi-search');
    const unit = el('realisasi-unit');
    const year = el('realisasi-year');
    const month = el('realisasi-month');
    const status = el('realisasi-status');
    const size = el('realisasi-size');
    const reload = el('realisasi-reload');
    const previous = el('realisasi-previous');
    const next = el('realisasi-next');
    const body = el('realisasi-body');
    const message = el('realisasi-message');

    let exportData = null;

const downloadMenu = mountKpiExport(
    root,
    () => exportData
);

    const months = [
        'Januari', 'Februari', 'Maret', 'April',
        'Mei', 'Juni', 'Juli', 'Agustus',
        'September', 'Oktober', 'November', 'Desember',
    ];

    const formatter = new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 6,
    });

    let rows = [];
    let accountUnit = null;
    let page = 1;
    let ready = false;
    let busy = false;
const initialParams = new URLSearchParams(window.location.search);
let initialFiltersApplied = false;
let canSelectUnit = false;

    function number(value) {
        if (value === null || value === undefined || value === '') {
            return '—';
        }

        const parsed = Number(value);

        return Number.isFinite(parsed)
            ? formatter.format(parsed)
            : '—';
    }

    function cell(row, value, className = '') {
        const td = document.createElement('td');
        td.textContent = value;
        td.className = className;
        row.append(td);
        return td;
    }

    function empty(text) {
        body.replaceChildren();

        const row = document.createElement('tr');
        cell(row, text, 'realisasi-empty').colSpan = 12;

        body.append(row);
    }

    function setBusy(value) {
        busy = value;
        if (value || !ready) {
    exportData = null;
    downloadMenu.setAvailable(false);
}
        root.setAttribute('aria-busy', String(value));

        [search, year, month, status, size].forEach((control) => {
            control.disabled = value || !ready;
        });

        unit.disabled =
    value ||
    !canSelectUnit ||
    unit.options.length === 0;
        reload.disabled = value;
        previous.disabled = true;
        next.disabled = true;
    }

    function render() {
    exportData = null;
    downloadMenu.setAvailable(false);

    if (!ready) return;

        const keyword = search.value.trim().toLocaleLowerCase('id-ID');

        const filtered = rows.filter((item) => {
            const matchesYear = !year.value ||
                String(item.periode?.tahun) === year.value;

            const matchesMonth = !month.value ||
                Number(item.periode?.bulan) === Number(month.value);

            const matchesStatus = !status.value ||
                String(item.status_data ?? '').toLowerCase() === status.value;

            const text = [
                item.kpi?.kode_kpi,
                item.kpi?.nama_kpi,
                item.nama_pendukung,
            ].join(' ').toLocaleLowerCase('id-ID');

            return matchesYear &&
                matchesMonth &&
                matchesStatus &&
                text.includes(keyword);
        });
exportData = {
    title: 'Realisasi KPI',

    context:
        `${accountUnit.nama_ulp} | ` +
        `Tahun: ${year.value || 'Semua'} | ` +
        `Bulan: ${
            month.value
                ? months[Number(month.value) - 1]
                : 'Semua'
        } | ` +
        `Status: ${status.value || 'Semua'} | ` +
        `Pencarian: ${search.value.trim() || 'Semua KPI'}`,

    filename:
        `Realisasi-KPI-${accountUnit.nama_ulp}-` +
        `${year.value || 'Semua-Tahun'}-` +
        `${month.value || 'Semua-Bulan'}`,

    headers: [
        'No.',
        'Kode KPI',
        'Nama KPI / Indikator Pendukung',
        'Periode',
        'Target Tahunan',
        'Target Periode',
        'Realisasi',
        'Pencapaian (%)',
        'Nilai KPI',
        'Sumber',
        'Status',
    ],

    rows: filtered.map((item, index) => {
        const monthName = months[
            Number(item.periode?.bulan) - 1
        ];

        const periodLabel = monthName && item.periode?.tahun
            ? `${monthName} ${item.periode.tahun}`
            : '';

        const name = [
            item.kpi?.nama_kpi,
            item.nama_pendukung,
        ].filter(Boolean).join('\n');

        return [
            index + 1,
            String(item.kpi?.kode_kpi ?? ''),
            name,
            periodLabel,
            exportNumber(item.target_tahunan),
            exportNumber(item.target_periode),
            exportNumber(item.realisasi),
            exportNumber(item.pencapaian),
            exportNumber(item.nilai_kpi),
            String(item.sumber_data ?? ''),
            String(item.status_data ?? ''),
        ];
    }),
};

downloadMenu.setAvailable(!busy && filtered.length > 0);
        const perPage = Number(size.value);
        const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));

        page = Math.min(Math.max(page, 1), totalPages);

        const start = (page - 1) * perPage;
        const visible = filtered.slice(start, start + perPage);

        body.replaceChildren();

        if (!visible.length) {
            empty('Belum ada realisasi yang sesuai dengan filter.');
        }

        visible.forEach((item, index) => {
            const row = document.createElement('tr');

            cell(row, String(start + index + 1));
            cell(row, item.kpi?.kode_kpi ?? '—');

            const nameCell = cell(
                row,
                item.kpi?.nama_kpi ?? 'KPI tidak tersedia'
            );

            if (item.nama_pendukung) {
                const supporting = document.createElement('span');
                supporting.className = 'realisasi-supporting';
                supporting.textContent = item.nama_pendukung;
                nameCell.append(supporting);
            }

            const monthName = months[Number(item.periode?.bulan) - 1];

            cell(
                row,
                monthName && item.periode?.tahun
                    ? `${monthName} ${item.periode.tahun}`
                    : '—'
            );

            cell(row, number(item.target_tahunan), 'realisasi-number');
            cell(row, number(item.target_periode), 'realisasi-number');
            cell(row, number(item.realisasi), 'realisasi-number');
            cell(row, number(item.pencapaian), 'realisasi-number');
            cell(row, number(item.nilai_kpi), 'realisasi-number');
            cell(row, item.sumber_data || '—');

            const statusCell = document.createElement('td');
            const badge = document.createElement('span');
            const statusValue = String(item.status_data ?? '').toLowerCase();

            badge.className = 'realisasi-badge';

            if (['valid', 'draft', 'error'].includes(statusValue)) {
                badge.classList.add(`is-${statusValue}`);
            }

            badge.textContent = item.status_data || '—';
statusCell.append(badge);
row.append(statusCell);

const actionCell = document.createElement('td');

if (canEdit && item.id_realisasi && root.dataset.editTemplate) {
    const editLink = document.createElement('a');

    editLink.className = 'realisasi-edit-button';
    editLink.textContent = 'Edit';

    editLink.href = root.dataset.editTemplate.replace(
        '__ID__',
        encodeURIComponent(String(item.id_realisasi))
    );

    editLink.setAttribute(
        'aria-label',
        `Edit realisasi ${item.kpi?.nama_kpi ?? 'KPI'}`
    );

    actionCell.append(editLink);
} else {
    actionCell.textContent = '—';
}

row.append(actionCell);

body.append(row);
        });

        el('realisasi-count').textContent = filtered.length
            ? `Menampilkan ${start + 1}–${start + visible.length} dari ${filtered.length} baris`
            : '0 baris';

        el('realisasi-page').textContent = `${page} / ${totalPages}`;

        previous.disabled = busy || page <= 1;
        next.disabled = busy || page >= totalPages;

        message.textContent =
            `${accountUnit.nama_ulp}. ` +
            'Nilai ditampilkan sesuai hasil backend. ' +
            'Indikator pendukung tetap ditampilkan sebagai baris terpisah.';
    }

    async function read(url) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'application/json',
            },
        });

        if (response.status === 401 || response.status === 419) {
            window.location.assign(root.dataset.loginUrl);
            throw new Error('Sesi berakhir. Silakan login kembali.');
        }

        const payload = await response.json().catch(() => null);

        if (!response.ok || payload?.success !== true) {
            throw new Error(
                response.status < 500 && payload?.message
                    ? payload.message
                    : 'Data gagal dimuat. Periksa log Laravel.'
            );
        }

        return payload.data;
    }

    async function load() {
    if (busy) return;

    const firstLoad = !initialFiltersApplied;

    const requestedUnit = firstLoad
        ? initialParams.get('id_ulp')
        : unit.value;

    const selectedYear = firstLoad
        ? initialParams.get('tahun')
        : year.value;

    ready = false;
    rows = [];
    accountUnit = null;

    setBusy(true);

    message.classList.remove('is-error');
    message.textContent = 'Memuat realisasi...';

    empty('Memuat data...');

    el('realisasi-count').textContent = 'Memuat data...';
    el('realisasi-page').textContent = '—';

    try {
        // Ambil daftar ULP yang boleh diakses akun.
        const unitPayload = await read(root.dataset.unitUrl);

        if (!Array.isArray(unitPayload?.units)) {
            throw new Error('Format daftar ULP tidak sesuai.');
        }

        const units = unitPayload.units;

        canSelectUnit = unitPayload.can_select_unit === true;

        unit.replaceChildren(
            ...units.map((item) =>
                new Option(
                    item.nama_ulp,
                    String(item.id_ulp)
                )
            )
        );

        if (units.length === 0) {
            throw new Error(
                'Belum ada ULP aktif yang dapat ditampilkan.'
            );
        }

        const requested = requestedUnit
            ? units.find((item) =>
                String(item.id_ulp) === String(requestedUnit)
            )
            : null;

        if (requestedUnit && !requested) {
            unit.selectedIndex = -1;

            throw new Error(
                'ULP yang diminta tidak tersedia atau tidak dapat diakses.'
            );
        }

        accountUnit = requested || units[0];
        unit.value = String(accountUnit.id_ulp);

        // Selalu sertakan ULP yang telah dipilih.
        const dataUrl = new URL(
            root.dataset.url,
            window.location.origin
        );

        dataUrl.searchParams.set(
            'id_ulp',
            String(accountUnit.id_ulp)
        );

        const realisasiData = await read(dataUrl.toString());

        if (!Array.isArray(realisasiData)) {
            throw new Error(
                'Format respons data realisasi tidak sesuai.'
            );
        }

        // Jangan tampilkan respons yang mencampur unit lain.
        if (realisasiData.some((item) =>
            Number(item.id_ulp) !== Number(accountUnit.id_ulp)
        )) {
            throw new Error(
                'Respons data mengandung ULP yang tidak sesuai.'
            );
        }

        rows = realisasiData;

        const years = [...new Set(
            rows
                .map((item) => Number(item.periode?.tahun))
                .filter((value) =>
                    Number.isInteger(value) &&
                    value >= 2000 &&
                    value <= 2100
                )
        )];

        let preferredYear = selectedYear || '';

        if (firstLoad) {
            const requestedPeriod =
                initialParams.get('id_periode');

            const importedRow = requestedPeriod
                ? rows.find((item) =>
                    String(item.id_periode) ===
                    String(requestedPeriod)
                )
                : null;

            preferredYear = String(
                importedRow?.periode?.tahun ??
                selectedYear ??
                ''
            );

            const preferredMonth = Number(
                importedRow?.periode?.bulan ??
                initialParams.get('bulan')
            );

            if (
                Number.isInteger(preferredMonth) &&
                preferredMonth >= 1 &&
                preferredMonth <= 12
            ) {
                month.value = String(preferredMonth);
            }

            search.value = '';
            status.value = '';
        }

        const preferredYearNumber = Number(preferredYear);

        const validPreferredYear =
            Number.isInteger(preferredYearNumber) &&
            preferredYearNumber >= 2000 &&
            preferredYearNumber <= 2100;

        if (
            validPreferredYear &&
            !years.includes(preferredYearNumber)
        ) {
            years.push(preferredYearNumber);
        }

        years.sort((a, b) => b - a);

        year.replaceChildren(
            new Option('Semua tahun', ''),
            ...years.map((value) =>
                new Option(String(value), String(value))
            )
        );

        year.value = validPreferredYear
            ? String(preferredYearNumber)
            : '';

        initialFiltersApplied = true;
        ready = true;
        page = 1;
    } catch (error) {
        window.ipmsNotify?.({
    icon: 'error',
    title: 'Data gagal dimuat',
    text: error.message || 'Silakan coba muat ulang.',
});
        rows = [];

        message.classList.add('is-error');
        message.textContent =
            error.message || 'Data gagal dimuat.';

        empty('Data belum dapat ditampilkan.');

        el('realisasi-count').textContent =
            'Gagal memuat data';

        el('realisasi-page').textContent = '—';
    } finally {
        setBusy(false);

        if (ready) {
            render();
        }
    }
}

    month.replaceChildren(
        new Option('Semua bulan', ''),
        ...months.map((name, index) =>
            new Option(name, String(index + 1))
        )
    );

    function resetPage() {
        page = 1;
        render();
    }

    search.addEventListener('input', resetPage);

    [year, month, status, size].forEach((control) => {
        control.addEventListener('change', resetPage);
    });

    previous.addEventListener('click', () => {
        page -= 1;
        render();
    });

    next.addEventListener('click', () => {
        page += 1;
        render();
    });

    unit.addEventListener('change', () => {
    if (busy || !canSelectUnit) return;

    initialFiltersApplied = true;
    search.value = '';
    page = 1;

    load();
});

reload.addEventListener('click', load);

load();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeRealisasiPage, {
        once: true,
    });
} else {
    initializeRealisasiPage();
}