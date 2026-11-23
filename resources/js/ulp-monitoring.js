function initializeUlpMonitoring() {
    const monitoring = document.querySelector('.um-page');

    if (!monitoring) return;

    // Gunakan dropdown bersama seperti halaman Realisasi.
    monitoring.querySelectorAll('.um-field select').forEach(function (select) {
        select.setAttribute('data-ipms-filter', '');
    });

    // Pencarian tabel Monitoring.
    const search = monitoring.querySelector('#umSearch');
    const tbody = monitoring.querySelector('#umTableBody');
    const counter = monitoring.querySelector('#umTableCount');
    const emptyRow = monitoring.querySelector('#umEmptyRow');

    if (!search || !tbody || !counter || !emptyRow) {
        return;
    }

    const rows = tbody.querySelectorAll('tr[data-search]');

    function filterRows() {
        const term = search.value
            .trim()
            .toLocaleLowerCase('id-ID');

        let visible = 0;

        rows.forEach(function (row) {
            const text = row.dataset.search
                .toLocaleLowerCase('id-ID');

            const show = text.includes(term);

            row.hidden = !show;

            if (show) {
                visible++;
            }
        });

        emptyRow.hidden = visible > 0;

        counter.textContent =
            'Menampilkan ' + visible +
            ' dari ' + rows.length + ' entri realisasi.';
    }

    search.addEventListener('input', filterRows);

    filterRows();
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeUlpMonitoring,
        { once: true }
    );
} else {
    initializeUlpMonitoring();
}