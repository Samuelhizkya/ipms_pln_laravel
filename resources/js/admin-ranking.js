document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const rankingPage = document.getElementById('rkPage');

    if (!rankingPage || !document.getElementById('rkData')) return;

    const get = id => document.getElementById(id);

    const detailPage = get('rkDetailPage');
    const search = get('rkSearch');
    const tbody = get('rkTableBody');
    const emptyRow = get('rkEmptyRow');

    let data = [];
    let lastFocus = null;
    let previousScrollY = 0;

    try {
        data = JSON.parse(get('rkData').textContent);

        if (!Array.isArray(data)) {
            throw new Error('Format data Ranking tidak sesuai.');
        }
    } catch (error) {
        get('rkTableCount').textContent =
            'Data detail Ranking gagal dibaca.';

        console.error(error);
        return;
    }

    rankingPage.querySelectorAll('select').forEach(select => {
        select.setAttribute('data-ipms-filter', '');
    });

    function format(value) {
        const number = Number(value ?? 0);

        return Number.isFinite(number)
            ? number.toLocaleString('id-ID')
            : '—';
    }

    function filterTable() {
        const term = search.value
            .trim()
            .toLocaleLowerCase('id-ID');

        let visible = 0;

        tbody.querySelectorAll('tr[data-name]').forEach(row => {
            const name = row.dataset.name
                .toLocaleLowerCase('id-ID');

            const show = name.includes(term);

            row.hidden = !show;

            if (show) visible++;
        });

        emptyRow.hidden = visible > 0;

        get('rkTableCount').textContent =
            'Menampilkan ' +
            visible +
            ' dari ' +
            data.length +
            ' ULP';
    }

    search.addEventListener('input', filterTable);

    function openDetail(item, trigger) {
        lastFocus = trigger;
        previousScrollY = window.scrollY;

        get('rkDetailTitle').textContent =
            'Detail ' + item.nama_ulp;

        get('rkDetailSubtitle').textContent =
            'Periode ' + rankingPage.dataset.period;

        get('rkDetailTotal').textContent =
            format(item.total_kpi);

        get('rkDetailValid').textContent =
            format(item.kpi_valid);

        get('rkDetailDraft').textContent =
            format(item.kpi_draft);

        get('rkDetailError').textContent =
            format(item.kpi_error);

        rankingPage.hidden = true;
        detailPage.hidden = false;

        get('rkDetailTitle').focus({
            preventScroll: true
        });

        window.scrollTo({
            top: 0,
            behavior: 'instant'
        });
    }

    function closeDetail() {
        detailPage.hidden = true;
        rankingPage.hidden = false;

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

    tbody.querySelectorAll('button[data-ulp]').forEach(button => {
        button.addEventListener('click', function () {
            const item = data[Number(button.dataset.ulp)];

            if (!item) return;

            openDetail(item, button);
        });
    });

    get('rkBack').addEventListener('click', closeDetail);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !detailPage.hidden) {
            closeDetail();
        }
    });

    if (window.lucide) {
        window.lucide.createIcons();
    }

    filterTable();
});