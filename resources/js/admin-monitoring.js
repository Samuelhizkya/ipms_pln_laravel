document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const monitoringPage = document.getElementById('monitoringKpiPage');

    if (!monitoringPage) return;

    const get = id => document.getElementById(id);
    const detailPage = get('monModal');

    monitoringPage.querySelectorAll('.mon-field select').forEach(select => {
        select.setAttribute('data-ipms-filter', '');
    });

    let ulps = {};
    let records = [];
    let targets = [];
    let currentRows = [];
    let page = 1;
    let lastFocus = null;
    let previousScrollY = 0;

    const PER_PAGE = 10;
    const apiBase = monitoringPage.dataset.apiBase;

    const months = [
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    const statusLabels = {
        achieved: 'Tercapai',
        warning: 'Waspada',
        danger: 'Belum Tercapai',
        missing: 'Belum dinilai'
    };

    const ulpSelect = get('monUlp');
    const monthSelect = get('monMonth');
    const yearSelect = get('monYear');
    const statusSelect = get('monStatus');

    const now = new Date();

    let applied = {
        ulp: 'all',
        month: now.getMonth() + 1,
        year: now.getFullYear(),
        status: 'all'
    };

    function number(value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        const result = Number(value);

        return Number.isFinite(result) ? result : null;
    }

    function classify(value) {
        if (value === null) return 'missing';
        if (value >= 100) return 'achieved';
        if (value >= 90) return 'warning';

        return 'danger';
    }

    function format(value, decimals = 1) {
        if (value === null) return '—';

        return Number(value).toLocaleString('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function statusColor(status) {
        if (status === 'achieved') return '#237A34';
        if (status === 'warning') return '#F39C12';
        if (status === 'danger') return '#E03E3E';

        return '#97A8B5';
    }

    function el(tag, className, text) {
        const element = document.createElement(tag);

        if (className) {
            element.className = className;
        }

        if (text !== undefined) {
            element.textContent = text;
        }

        return element;
    }

    async function load(endpoint) {
        const response = await fetch(apiBase + '/' + endpoint, {
            headers: {
                Accept: 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(
                'Gagal membaca ' + endpoint +
                ' (HTTP ' + response.status + ').'
            );
        }

        const result = await response.json();

        if (result.success !== true || !Array.isArray(result.data)) {
            throw new Error('Respons ' + endpoint + ' tidak sesuai.');
        }

        return result.data;
    }

    monthSelect.replaceChildren();

    months.forEach((name, index) => {
        monthSelect.add(
            new Option(name, String(index + 1))
        );
    });

    monthSelect.value = String(applied.month);

    if (!Array.from(statusSelect.options).some(option => {
        return option.value === 'missing';
    })) {
        statusSelect.add(
            new Option('Belum dinilai', 'missing')
        );
    }

    function row(item) {
        const kpi = item.kpi || {};
        const period = item.periode || {};
        const achievement = number(item.pencapaian);

        return {
            id: item.id_realisasi,
            ulpIndex: String(item.id_ulp),
            kpiIndex: item.id_kpi,
            supporting: item.nama_pendukung,

            ulp: item.ulp?.nama_ulp ||
                ulps[item.id_ulp] ||
                '—',

            code: kpi.kode_kpi || '—',

            name: (kpi.nama_kpi || '—') +
                (item.nama_pendukung
                    ? ' · ' + item.nama_pendukung
                    : ''),

            unit: item.satuan_pendukung ||
                kpi.satuan ||
                '—',

            target: number(item.target_periode),
            actual: number(item.realisasi),
            achievement,
            status: classify(achievement),
            polarity: kpi.polaritas,
            decimals: 2,
            year: Number(period.tahun),
            month: Number(period.bulan)
        };
    }

    function filteredRows() {
        const rows = records.filter(record => {
            return record.year === applied.year &&
                record.month === applied.month &&
                (
                    applied.ulp === 'all' ||
                    record.ulpIndex === applied.ulp
                );
        });

        targets.filter(target => {
            return Number(target.tahun) === applied.year &&
                (
                    applied.ulp === 'all' ||
                    String(target.id_ulp) === applied.ulp
                );
        }).forEach(target => {
            const alreadyExists = rows.some(record => {
                return record.ulpIndex === String(target.id_ulp) &&
                    Number(record.kpiIndex) === Number(target.id_kpi);
            });

            if (alreadyExists) return;

            const kpi = target.kpi || {};
            const monthly = target.target_bulanan ||
                target.targetBulanan ||
                [];

            const periodTarget = monthly.find(item => {
                return Number(item.bulan) === applied.month;
            });

            rows.push({
                id: 'target-' + target.id_target,
                ulpIndex: String(target.id_ulp),
                kpiIndex: target.id_kpi,
                supporting: null,

                ulp: target.ulp?.nama_ulp ||
                    ulps[target.id_ulp] ||
                    '—',

                code: kpi.kode_kpi || '—',
                name: kpi.nama_kpi || '—',
                unit: kpi.satuan || '—',
                target: number(periodTarget?.nilai_target),
                actual: null,
                achievement: null,
                status: 'missing',
                polarity: kpi.polaritas,
                decimals: 2,
                year: applied.year,
                month: applied.month
            });
        });

        return rows.filter(record => {
            return applied.status === 'all' ||
                record.status === applied.status;
        });
    }

    function shownRows() {
        const query = get('monSearch')
            .value
            .trim()
            .toLocaleLowerCase('id-ID');

        return currentRows.filter(record => {
            if (!query) return true;

            return [
                record.name,
                record.code,
                record.ulp,
                record.unit
            ].some(value => {
                return String(value)
                    .toLocaleLowerCase('id-ID')
                    .includes(query);
            });
        });
    }

    function render() {
        const all = shownRows();

        const totalPages = Math.max(
            1,
            Math.ceil(all.length / PER_PAGE)
        );

        page = Math.max(1, Math.min(page, totalPages));

        const subset = all.slice(
            (page - 1) * PER_PAGE,
            page * PER_PAGE
        );

        const body = get('monRows');

        body.replaceChildren();

        if (!subset.length) {
            const tr = el('tr');

            const td = el(
                'td',
                'mon-empty',
                'Belum ada data untuk ULP, periode, atau pencarian ini.'
            );

            td.colSpan = 9;

            tr.appendChild(td);
            body.appendChild(tr);
        }

        subset.forEach((record, index) => {
            const tr = el('tr');

            tr.appendChild(
                el(
                    'td',
                    '',
                    String((page - 1) * PER_PAGE + index + 1)
                )
            );

            const nameCell = el('td');

            nameCell.append(
                el('div', 'mon-code', record.code),
                el('div', 'mon-kpi-name', record.name)
            );

            tr.appendChild(nameCell);

            tr.append(
                el('td', '', record.ulp),
                el('td', '', record.unit),
                el('td', '', format(record.target, record.decimals)),
                el('td', '', format(record.actual, record.decimals))
            );

            const progressCell = el('td');
            const progress = el('div', 'mon-progress');
            const track = el('span', 'mon-progress-track');
            const bar = el('span', 'mon-progress-bar');

            const progressWidth = record.achievement === null
                ? 0
                : Math.max(0, Math.min(100, record.achievement));

            bar.style.width = progressWidth + '%';
            bar.style.background = statusColor(record.status);

            track.appendChild(bar);

            progress.append(
                track,
                el(
                    'span',
                    '',
                    record.achievement === null
                        ? 'Belum dinilai'
                        : format(record.achievement, 1) + '%'
                )
            );

            progressCell.appendChild(progress);
            tr.appendChild(progressCell);

            const statusCell = el('td');

            statusCell.appendChild(
                el(
                    'span',
                    'mon-status ' + record.status,
                    statusLabels[record.status]
                )
            );

            tr.appendChild(statusCell);

            const actionCell = el('td');

            const button = el(
                'button',
                'mon-btn mon-btn-outline mon-btn-small',
                'Detail'
            );

            button.type = 'button';

            button.setAttribute(
                'aria-label',
                'Detail ' + record.name + ' ' + record.ulp
            );

            button.addEventListener('click', function () {
                openDetail(record, button);
            });

            actionCell.appendChild(button);
            tr.appendChild(actionCell);
            body.appendChild(tr);
        });

        const firstNumber = all.length
            ? (page - 1) * PER_PAGE + 1
            : 0;

        const lastNumber = Math.min(
            page * PER_PAGE,
            all.length
        );

        get('monCount').textContent =
            'Menampilkan ' +
            firstNumber +
            '–' +
            lastNumber +
            ' dari ' +
            all.length +
            ' indikator';

        get('monPageText').textContent =
            page + ' / ' + totalPages;

        get('monPrev').disabled = page === 1;
        get('monNext').disabled = page === totalPages;
    }

    function updateAll() {
        currentRows = filteredRows();
        page = 1;

        get('monTotal').textContent = currentRows.length;

        get('monAchieved').textContent = currentRows.filter(record => {
            return record.status === 'achieved';
        }).length;

        get('monWarning').textContent = currentRows.filter(record => {
            return record.status === 'warning';
        }).length;

        get('monDanger').textContent = currentRows.filter(record => {
            return record.status === 'danger';
        }).length;

        const unitName = applied.ulp === 'all'
            ? 'Seluruh ULP'
            : ulps[applied.ulp] || '—';

        const statusName = applied.status === 'all'
            ? 'Semua status'
            : statusLabels[applied.status];

        const missingCount = currentRows.filter(record => {
            return record.status === 'missing';
        }).length;

        get('monPeriodInfo').textContent =
            'Menampilkan: ' +
            unitName +
            ' • ' +
            months[applied.month - 1] +
            ' ' +
            applied.year +
            ' • ' +
            statusName +
            ' • ' +
            missingCount +
            ' entri belum dinilai';

        render();
    }

    get('monApply').addEventListener('click', function () {
        applied = {
            ulp: ulpSelect.value,
            month: Number(monthSelect.value),
            year: Number(yearSelect.value),
            status: statusSelect.value
        };

        updateAll();
    });

    get('monSearch').addEventListener('input', function () {
        page = 1;
        render();
    });

    get('monPrev').addEventListener('click', function () {
        if (page > 1) {
            page--;
            render();
        }
    });

    get('monNext').addEventListener('click', function () {
        const totalPages = Math.ceil(
            shownRows().length / PER_PAGE
        );

        if (page < totalPages) {
            page++;
            render();
        }
    });

    function openDetail(record, trigger) {
        lastFocus = trigger;
        previousScrollY = window.scrollY;

        get('monDetailTitle').textContent = record.name;

        get('monDetailSub').textContent =
            record.code +
            ' • ' +
            record.ulp +
            ' • ' +
            months[record.month - 1] +
            ' ' +
            record.year;

        get('monDetailStatus').replaceChildren(
            el(
                'span',
                'mon-status ' + record.status,
                statusLabels[record.status]
            )
        );

        const metrics = get('monDetailMetrics');

        metrics.replaceChildren();

        const detailItems = [
            {
                label: 'Target',
                value: format(record.target, record.decimals) +
                    ' ' + record.unit
            },
            {
                label: 'Realisasi',
                value: format(record.actual, record.decimals) +
                    ' ' + record.unit
            },
            {
                label: 'Pencapaian',
                value: record.achievement === null
                    ? 'Belum dinilai'
                    : format(record.achievement, 2) + '%'
            },
            {
                label: 'Polaritas',
                value: record.polarity === 'negatif'
                    ? 'Negatif'
                    : record.polarity === 'positif'
                        ? 'Positif'
                        : 'Belum ditentukan'
            },
            {
                label: 'Unit',
                value: record.ulp
            },
            {
                label: 'Periode',
                value: months[record.month - 1] +
                    ' ' + record.year
            }
        ];

        detailItems.forEach(item => {
            const card = el('div', 'mon-detail-box');

            card.append(
                el('small', '', item.label),
                el('strong', '', item.value)
            );

            metrics.appendChild(card);
        });

        const history = get('monHistory');

        history.replaceChildren();

        for (let offset = 5; offset >= 0; offset--) {
            const date = new Date(
                record.year,
                record.month - 1 - offset,
                1
            );

            const historyRecord = records.find(item => {
                return item.ulpIndex === record.ulpIndex &&
                    String(item.kpiIndex) === String(record.kpiIndex) &&
                    (item.supporting ?? '') === (record.supporting ?? '') &&
                    item.year === date.getFullYear() &&
                    item.month === date.getMonth() + 1;
            });

            const achievement = historyRecord?.achievement ?? null;

            const status = historyRecord?.status || 'missing';

            const column = el('div', 'mon-history-col');

            const value = el(
                'span',
                'mon-history-number',
                achievement === null
                    ? '—'
                    : format(achievement, 0) + '%'
            );

            const bar = el('div', 'mon-history-bar');

            const height = achievement === null
                ? 0
                : Math.max(
                    5,
                    Math.min(145, achievement / 125 * 145)
                );

            bar.style.height = height + 'px';
            bar.style.background = statusColor(status);

            if (achievement === null) {
                bar.style.minHeight = '0';
            }

            const monthLabel =
                months[date.getMonth()].slice(0, 3) +
                ' ' +
                String(date.getFullYear()).slice(-2);

            column.append(
                value,
                bar,
                el('span', 'mon-history-month', monthLabel)
            );

            history.appendChild(column);
        }

        monitoringPage.hidden = true;
        detailPage.hidden = false;

        get('monDetailTitle').focus({
            preventScroll: true
        });

        window.scrollTo({
            top: 0,
            behavior: 'instant'
        });
    }

    function closeDetail() {
        detailPage.hidden = true;
        monitoringPage.hidden = false;

        if (lastFocus && lastFocus.isConnected) {
            lastFocus.focus({
                preventScroll: true
            });
        }

        window.scrollTo({
            top: previousScrollY,
            behavior: 'instant'
        });
    }


get('monCloseBottom').addEventListener('click', closeDetail);

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !detailPage.hidden) {
        closeDetail();
    }
});

    if (window.lucide) {
        window.lucide.createIcons();
    }

    get('monApply').disabled = true;
    get('monReset').disabled = true;

    get('monPeriodInfo').textContent = 'Memuat data database…';

    Promise.all([
        load('ulp'),
        load('realisasi-kpi'),
        load('target-kpi')
    ]).then(function ([units, realisasi, target]) {
        ulpSelect.replaceChildren(
            new Option('Seluruh ULP', 'all')
        );

        units.forEach(unit => {
            ulps[unit.id_ulp] = unit.nama_ulp;

            ulpSelect.add(
                new Option(
                    unit.nama_ulp,
                    String(unit.id_ulp)
                )
            );
        });

        records = realisasi.map(row);
        targets = target;

        const years = [
            ...new Set([
                ...records.map(record => record.year),
                ...targets.map(item => Number(item.tahun))
            ])
        ].filter(year => {
            return Number.isInteger(year) && year > 0;
        }).sort((a, b) => a - b);

        if (!years.length) {
            years.push(now.getFullYear());
        }

        yearSelect.replaceChildren();

        years.forEach(year => {
            yearSelect.add(
                new Option(String(year), String(year))
            );
        });

        const latest = records.slice().sort((a, b) => {
            return b.year - a.year || b.month - a.month;
        })[0];

        const initial = {
            ulp: 'all',
            year: latest?.year || years[years.length - 1],
            month: latest?.month || now.getMonth() + 1,
            status: 'all'
        };

        ulpSelect.value = initial.ulp;
        yearSelect.value = String(initial.year);
        monthSelect.value = String(initial.month);
        statusSelect.value = initial.status;

        applied = { ...initial };

        get('monReset').onclick = function () {
            ulpSelect.value = initial.ulp;
            yearSelect.value = String(initial.year);
            monthSelect.value = String(initial.month);
            statusSelect.value = initial.status;

            get('monSearch').value = '';

            applied = { ...initial };

            updateAll();
        };

        get('monApply').disabled = false;
        get('monReset').disabled = false;

        updateAll();
    }).catch(function (error) {
        get('monPeriodInfo').textContent =
            error.message +
            ' Muat ulang halaman untuk mencoba kembali.';

        get('monRows').replaceChildren();

        get('monCount').textContent = 'Data gagal dimuat.';
    });
});