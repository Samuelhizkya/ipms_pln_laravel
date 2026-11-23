function initializeUlpRanking() {
    const ranking = document.getElementById('rkPage');

    if (!ranking) return;

    ranking.querySelectorAll('.rk-field select').forEach(function (select) {
        select.setAttribute('data-ipms-filter', '');
    });

    const search = ranking.querySelector('#rkSearch');
    const tbody = ranking.querySelector('#rkTableBody');
    const counter = ranking.querySelector('#rkTableCount');
    const emptyRow = ranking.querySelector('#rkEmptyRow');

    if (search && tbody && counter && emptyRow) {
        const rows = tbody.querySelectorAll('tr[data-name]');

        function filterRows() {
            const term = search.value.trim().toLowerCase();
            let visible = 0;

            rows.forEach(function (row) {
                const show = row.dataset.name.includes(term);

                row.hidden = !show;

                if (show) {
                    visible++;
                }
            });

            emptyRow.hidden = visible > 0;

            counter.textContent =
                'Menampilkan ' + visible +
                ' dari ' + rows.length + ' ULP';
        }

        search.addEventListener('input', filterRows);
        filterRows();
    }

    if (typeof window.lucide?.createIcons === 'function') {
        window.lucide.createIcons();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeUlpRanking,
        { once: true }
    );
} else {
    initializeUlpRanking();
}