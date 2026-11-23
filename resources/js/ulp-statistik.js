function initializeUlpStatistik() {
    const statistik = document.querySelector('.us-page');

    if (!statistik) return;

    statistik.querySelectorAll('.us-field select').forEach(function (select) {
        select.setAttribute('data-ipms-filter', '');
    });
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeUlpStatistik,
        { once: true }
    );
} else {
    initializeUlpStatistik();
}