import {
    mountKpiExport,
    exportNumber,
} from './kpi-export';
function initializeTargetPage() {
    const root = document.getElementById('kpi-workspace');

    if (!root) return;

    const el = (id) => document.getElementById(id);

    const search = el('kpi-search');
    const unit = el('kpi-unit');
    const year = el('kpi-year');
    const month = el('kpi-month');
    const size = el('kpi-size');
    const reload = el('kpi-reload');
    const previous = el('kpi-previous');
    const next = el('kpi-next');
    const body = el('kpi-body');
    const message = el('kpi-message');
    const dialog = el('kpi-dialog');
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

    let master = [];
    let allTargets = [];
    let targetMap = new Map();
    let accountUnit = null;
    let page = 1;
    let ready = false;
    let busy = false;
    const initialParams = new URLSearchParams(window.location.search);

let initialFiltersApplied = false;
let canSelectUnit = false;

function withUnit(url, unitId) {
    const result = new URL(url, window.location.origin);
    result.searchParams.set('id_ulp', String(unitId));
    return result.toString();
}

    function number(value) {
        if (value === null || value === undefined || value === '') {
            return '—';
        }

        const parsed = Number(value);
        return Number.isFinite(parsed) ? formatter.format(parsed) : '—';
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
        cell(row, text, 'kpi-empty').colSpan = 9;
        body.append(row);
    }

    function monthly(target, selectedMonth) {
        return target?.target_bulanan?.find(
            (item) => Number(item.bulan) === Number(selectedMonth)
        )?.nilai_target;
    }

    function setBusy(value) {
    busy = value;
    if (value || !ready) {
    exportData = null;
    downloadMenu.setAvailable(false);
}
    root.setAttribute('aria-busy', String(value));

    [search, year, month, size].forEach((control) => {
        control.disabled = value || !ready;
    });

    unit.disabled =
        value || !canSelectUnit || unit.options.length === 0;

    reload.disabled = value;
    previous.disabled = true;
    next.disabled = true;
}

function detail(kpi) {
    const target = targetMap.get(String(kpi.id_kpi));

    el('kpi-detail-title').textContent =
        `${kpi.kode_kpi} — ${kpi.nama_kpi}`;

    el('kpi-detail-context').textContent =
        `${accountUnit.nama_ulp} · Tahun ${year.value}`;

    el('kpi-detail-annual').textContent =
        `Target tahunan: ${number(target?.target_tahunan)}`;

    const detailBody = el('kpi-detail-body');
    detailBody.replaceChildren();

    months.forEach((name, index) => {
        const row = document.createElement('tr');

        cell(row, name);
        cell(
            row,
            number(monthly(target, index + 1)),
            'kpi-number'
        );

        detailBody.append(row);
    });

    const editButton = el('target-edit-open');
    const editForm = el('target-edit-form');
    const editMessage = el('target-edit-message');

    if (editButton) {
        editButton.dataset.targetId = target?.id_target
            ? String(target.id_target)
            : '';

        editButton.hidden = !target?.id_target;
        editButton.disabled = false;
    }

    if (editForm) {
        editForm.hidden = true;
    }

    if (editMessage) {
        editMessage.textContent = target?.id_target
            ? ''
            : 'Target belum tersedia. Impor target terlebih dahulu.';
    }

    const previousScroll = window.scrollY;
    const previousFocus = document.activeElement;

    root.classList.add('kpi-detail-mode');
    dialog.show();

    window.scrollTo({ top: 0, behavior: 'instant' });

    dialog.addEventListener('close', () => {
        root.classList.remove('kpi-detail-mode');

        previousFocus?.focus({ preventScroll: true });

        window.scrollTo({
            top: previousScroll,
            behavior: 'instant',
        });
    }, { once: true });
}

    function render() {
    exportData = null;
    downloadMenu.setAvailable(false);

    if (!ready) return;

        targetMap = new Map();

        for (const target of allTargets) {
            if (Number(target.tahun) !== Number(year.value)) continue;

            const key = String(target.id_kpi);

            if (targetMap.has(key)) {
                message.classList.add('is-error');
                message.textContent =
                    'Terdapat target ganda untuk KPI, ULP, dan tahun yang sama. ' +
                    'Hubungi pengelola data.';

                empty('Data target perlu diperiksa.');
                el('kpi-count').textContent = 'Data tidak ditampilkan';
                el('kpi-page').textContent = '—';
                previous.disabled = true;
                next.disabled = true;
                return;
            }

            targetMap.set(key, target);
        }

        message.classList.remove('is-error');
        message.textContent =
            `${accountUnit.nama_ulp} · Tahun ${year.value}. ` +
            'Daftar master KPI aktif. Tanda — berarti target belum tersedia.';

        const keyword = search.value.trim().toLocaleLowerCase('id-ID');

        const filtered = master.filter((kpi) =>
            `${kpi.kode_kpi} ${kpi.nama_kpi}`
                .toLocaleLowerCase('id-ID')
                .includes(keyword)
        );

exportData = {
    title: 'Data KPI & Target KPI',

    context:
        `${accountUnit.nama_ulp} | Tahun ${year.value} | ` +
        `Bulan ${months[Number(month.value) - 1]} | ` +
        `Pencarian: ${search.value.trim() || 'Semua KPI'}`,

    filename:
        `Target-KPI-${accountUnit.nama_ulp}-` +
        `${year.value}-${month.value}`,

    headers: [
        'No.',
        'Kode KPI',
        'Nama KPI',
        'Satuan',
        'Bobot (%)',
        'Polaritas',
        'Target Tahunan',
        `Target ${months[Number(month.value) - 1]}`,
    ],

    rows: filtered.map((kpi, index) => {
        const target = targetMap.get(String(kpi.id_kpi));

        return [
            index + 1,
            String(kpi.kode_kpi ?? ''),
            String(kpi.nama_kpi ?? ''),
            String(kpi.satuan ?? ''),
            exportNumber(kpi.bobot),
            String(kpi.polaritas ?? ''),
            exportNumber(target?.target_tahunan),
            exportNumber(monthly(target, month.value)),
        ];
    }),
};

downloadMenu.setAvailable(!busy && filtered.length > 0);
        const perPage = Number(size.value);
        const pages = Math.max(1, Math.ceil(filtered.length / perPage));

        page = Math.min(Math.max(page, 1), pages);

        const start = (page - 1) * perPage;
        const visible = filtered.slice(start, start + perPage);

        el('kpi-month-heading').textContent =
            `Target ${months[Number(month.value) - 1]}`;

        body.replaceChildren();

        if (!visible.length) {
            empty('Tidak ada KPI yang sesuai dengan pencarian.');
        }

        visible.forEach((kpi, index) => {
            const target = targetMap.get(String(kpi.id_kpi));
            const row = document.createElement('tr');

            cell(row, String(start + index + 1));
            cell(row, kpi.kode_kpi ?? '—');
            cell(row, kpi.nama_kpi ?? '—');
            cell(row, kpi.satuan ?? '—');
            cell(row, number(kpi.bobot), 'kpi-number');
            cell(row, kpi.polaritas ?? '—');
            cell(row, number(target?.target_tahunan), 'kpi-number');
            cell(row, number(monthly(target, month.value)), 'kpi-number');

            const action = document.createElement('td');
            const button = document.createElement('button');

            button.type = 'button';
            button.className = 'kpi-page-button kpi-rincian-button';
            button.textContent = 'Rincian';
            button.setAttribute(
                'aria-label',
                `Lihat rincian target ${kpi.kode_kpi}`
            );
            button.addEventListener('click', () => detail(kpi));

            action.append(button);
            row.append(action);
            body.append(row);
        });

        el('kpi-count').textContent = filtered.length
            ? `Menampilkan ${start + 1}–${start + visible.length} dari ${filtered.length} KPI`
            : '0 KPI';

        el('kpi-page').textContent = `${page} / ${pages}`;
        previous.disabled = busy || page <= 1;
        next.disabled = busy || page >= pages;
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
            const reason = response.status < 500
                ? payload?.message
                : null;

            throw new Error(
                reason || 'Data gagal dimuat. Periksa log Laravel.'
            );
        }

        return payload.data;
    }

    async function load() {
    if (busy) return;

    const requestedUnit = initialFiltersApplied
        ? unit.value
        : initialParams.get('id_ulp');

    const requestedYear = initialFiltersApplied
        ? year.value
        : initialParams.get('tahun');

    ready = false;
    master = [];
    allTargets = [];
    targetMap = new Map();

    setBusy(true);

    message.classList.remove('is-error');
    message.textContent = 'Memuat data KPI dan target...';
    empty('Memuat data...');
    el('kpi-count').textContent = 'Memuat data...';
    el('kpi-page').textContent = '—';

    try {
        const unitPayload = await read(root.dataset.unitUrl);

        if (!Array.isArray(unitPayload?.units)) {
            throw new Error('Format daftar ULP tidak sesuai.');
        }

        const units = unitPayload.units;
        canSelectUnit = unitPayload.can_select_unit === true;

        unit.replaceChildren(
            ...units.map((item) =>
                new Option(item.nama_ulp, String(item.id_ulp))
            )
        );

        if (!units.length) {
            throw new Error('Belum ada ULP aktif yang dapat ditampilkan.');
        }

        const requested = requestedUnit
            ? units.find((item) =>
                String(item.id_ulp) === String(requestedUnit)
            )
            : null;

        if (requestedUnit && !requested) {
            unit.selectedIndex = -1;

            throw new Error(
                'ULP pada alamat halaman tidak tersedia atau tidak dapat diakses.'
            );
        }

        accountUnit = requested || units[0];
        unit.value = String(accountUnit.id_ulp);

        // Pilihan awal sudah ditentukan. Jika permintaan data gagal,
        // tombol Muat ulang tetap menggunakan pilihan yang sama.
        initialFiltersApplied = true;

        const [masterData, targetData] = await Promise.all([
            read(withUnit(root.dataset.masterUrl, accountUnit.id_ulp)),
            read(withUnit(root.dataset.targetUrl, accountUnit.id_ulp)),
        ]);

        if (!Array.isArray(masterData) || !Array.isArray(targetData)) {
            throw new Error('Format respons backend tidak sesuai.');
        }

        if (targetData.some((item) =>
            Number(item.id_ulp) !== Number(accountUnit.id_ulp)
        )) {
            throw new Error(
                'Respons target mengandung ULP yang tidak sesuai.'
            );
        }

        master = masterData;
        allTargets = targetData;

        const years = [...new Set(
            allTargets
                .map((item) => Number(item.tahun))
                .filter((value) =>
                    Number.isInteger(value) &&
                    value >= 2000 &&
                    value <= 2100
                )
        )];

        const preferredYear = Number(requestedYear);

        if (
            Number.isInteger(preferredYear) &&
            preferredYear >= 2000 &&
            preferredYear <= 2100 &&
            !years.includes(preferredYear)
        ) {
            years.push(preferredYear);
        }

        if (!years.length) {
            years.push(new Date().getFullYear());
        }

        years.sort((a, b) => b - a);

        year.replaceChildren(
            ...years.map((value) =>
                new Option(String(value), String(value))
            )
        );

        year.value = years.includes(preferredYear)
            ? String(preferredYear)
            : String(years[0]);

        ready = true;
        page = 1;
    } catch (error) {
        window.ipmsNotify?.({
    icon: 'error',
    title: 'Data gagal dimuat',
    text: error.message || 'Silakan coba muat ulang.',
});
        message.classList.add('is-error');
        message.textContent = error.message || 'Data gagal dimuat.';
        empty('Data belum dapat ditampilkan.');
        el('kpi-count').textContent = 'Gagal memuat data';
    } finally {
        setBusy(false);

        if (ready) render();
    }
}

    month.replaceChildren(
        ...months.map((name, index) =>
            new Option(name, String(index + 1))
        )
    );

    month.value = String(new Date().getMonth() + 1);

    search.addEventListener('input', () => {
        page = 1;
        render();
    });

    year.addEventListener('change', () => {
        page = 1;
        render();
    });

    month.addEventListener('change', render);

    size.addEventListener('change', () => {
        page = 1;
        render();
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
    initialFiltersApplied = true;
    search.value = '';
    page = 1;
    load();
});
    reload.addEventListener('click', load);

    load();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeTargetPage, {
        once: true,
    });
} else {
    initializeTargetPage();
}