document.addEventListener('DOMContentLoaded', async function () {
    'use strict';

    const page = document.getElementById('igPage');
    if (!page) return;

    const $ = id => document.getElementById(id);

    page.querySelectorAll('select').forEach(select => {
        select.setAttribute('data-ipms-filter', '');
    });

    const locations = [
        {
            name: 'PLN UP3 Palu',
            address: 'Jl. R.A. Kartini No. 26, Lolu Selatan, Kecamatan Palu Selatan, Kota Palu, Sulawesi Tengah 94111, Indonesia',
            mapsUrl: 'https://maps.app.goo.gl/S7ZCB5iAfzZtA8xH7?g_st=ac',
            lat: -0.9018822,
            lng: 119.8789176,
            wilayah: 'Kota Palu',
            inPalu: true,
            up3: true
        },
        {
            name: 'ULP Palu Kota',
            address: 'Jl. Sulawesi, Lolu Utara, Kecamatan Palu Timur, Kota Palu, Sulawesi Tengah 94112, Indonesia',
            mapsUrl: 'https://maps.app.goo.gl/tzxNVPGK9wLgzP9x8',
            lat: -0.9003095,
            lng: 119.8721903,
            wilayah: 'Kota Palu',
            inPalu: true,
            up3: false
        },
        {
            name: 'ULP Kamonji',
            address: 'Jl. Munif Rahman, Kabonena, Kecamatan Ulujadi, Kota Palu, Sulawesi Tengah 94227, Indonesia',
            mapsUrl: 'https://maps.app.goo.gl/r1Zt32baMYVoBMYn8?g_st=ac',
            lat: -0.8827826,
            lng: 119.835946,
            wilayah: 'Kota Palu',
            inPalu: true,
            up3: false
        },
        {
            name: 'ULP Tawaeli',
            address: 'Jl. Tolitoli–Palu, Panau, Kecamatan Tawaeli, Kota Palu, Sulawesi Tengah 94352, Indonesia',
            mapsUrl: 'https://maps.app.goo.gl/WHHq9AMY4oFksAFV6?g_st=ac',
            lat: -0.7319372,
            lng: 119.8636457,
            wilayah: 'Kota Palu',
            inPalu: true,
            up3: false
        },
        {
            name: 'ULP Donggala',
            address: 'Jl. Lorong PLN, Gunung Bale, Kecamatan Banawa, Kabupaten Donggala, Sulawesi Tengah 94351, Indonesia',
            mapsUrl: 'https://maps.app.goo.gl/YixwN7UZo2yV7r7e9',
            lat: -0.6719303,
            lng: 119.7467719,
            wilayah: 'Kabupaten Donggala',
            inPalu: false,
            up3: false
        },
        {
            name: 'ULP Tambu',
            address: 'Mapane Tambu, Kecamatan Balaesang, Kabupaten Donggala, Sulawesi Tengah 94355, Indonesia',
            mapsUrl: 'https://maps.app.goo.gl/qqFouruU41ydUuC96',
            lat: -0.0407428,
            lng: 119.8840185,
            wilayah: 'Kabupaten Donggala',
            inPalu: false,
            up3: false
        },
        {
            name: 'ULP Parigi',
            address: 'Maesa, Kecamatan Parigi, Kabupaten Parigi Moutong, Sulawesi Tengah 94471, Indonesia',
            mapsUrl: 'https://maps.app.goo.gl/PKBYq3ry8HPMkAbm8?g_st=ac',
            lat: -0.8160205,
            lng: 120.1734322,
            wilayah: 'Kabupaten Parigi Moutong',
            inPalu: false,
            up3: false
        },
        {
            name: 'ULP Poso',
            address: 'Kasintuwu, Kecamatan Poso Kota Utara, Kabupaten Poso, Sulawesi Tengah 94611, Indonesia',
            mapsUrl: 'https://maps.app.goo.gl/TQSHYvEm77Mb6N1M9',
            lat: -1.3848989,
            lng: 120.7593312,
            wilayah: 'Kabupaten Poso',
            inPalu: false,
            up3: false
        },
        {
            name: 'ULP Tentena',
            address: 'Tentena, Kecamatan Pamona Puselemba, Kabupaten Poso, Sulawesi Tengah 94663, Indonesia',
            mapsUrl: 'https://maps.app.goo.gl/6foJQioTkpUDBd8s8',
            lat: -1.7551139,
            lng: 120.6492296,
            wilayah: 'Kabupaten Poso',
            inPalu: false,
            up3: false
        },
        {
            name: 'ULP Kolonedale',
            address: 'Jl. Trans Sulawesi, Desa Temporal, Tompira, Kecamatan Petasia Timur, Kabupaten Morowali Utara, Sulawesi Tengah 94971, Indonesia',
            mapsUrl: 'https://maps.app.goo.gl/VCS4RmH8G7oNPQpx5?g_st=ac',
            lat: -2.080452,
            lng: 121.3810964,
            wilayah: 'Kabupaten Morowali Utara',
            inPalu: false,
            up3: false
        },
        {
            name: 'ULP Bungku',
            address: 'Marsaoleh, Kecamatan Bungku Tengah, Kabupaten Morowali, Sulawesi Tengah 94973, Indonesia',
            mapsUrl: 'https://maps.app.goo.gl/BWhWjm2KzU3fwYUL9?g_st=ac',
            lat: -2.5462634,
            lng: 121.9704088,
            wilayah: 'Kabupaten Morowali',
            inPalu: false,
            up3: false
        }
    ];

    const months = [
        'Januari', 'Februari', 'Maret', 'April',
        'Mei', 'Juni', 'Juli', 'Agustus',
        'September', 'Oktober', 'November', 'Desember'
    ];

    const colors = {
        ok: '#237A34',
        warn: '#F39C12',
        bad: '#E03E3E',
        off: '#9AA9B5',
        cyan: '#14A2BA',
        yellow: '#F39C12'
    };

    const names = {
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
        const number = num(value);
        if (number === null) return '—';

        return number.toLocaleString('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    };

    const pct = value => {
        return num(value) === null ? '—' : fmt(value) + '%';
    };

    const safe = value => String(value ?? '').replace(
        /[&<>"']/g,
        character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[character])
    );

    const status = value => {
        if (value === null) return 'off';
        if (value >= 100) return 'ok';
        if (value >= 90) return 'warn';
        return 'bad';
    };

    const pill = value => {
        return '<span class="ig-statuspill ' + value + '">' +
            names[value] + '</span>';
    };

    const identity = row => JSON.stringify([
        Number(row.id_kpi),
        row.nama_pendukung || '',
        row.satuan_pendukung || row.kpi?.satuan || '—'
    ]);

    let units = [];
    let kpis = [];
    let actual = [];
    let targets = [];
    let filter;
    let selected = 0;
    let map = null;
    let officeMarkers = [];
    let selectedOffice = null;
    let applyId = 0;
    let loadingNko = false;
    let nkoError = '';

    const nkoPeriods = new Map();
    const periodKey = (year, month) => year + '-' + month;

    const emptyKpi = {
        code: '—',
        name: 'Belum tersedia',
        support: '',
        unit: '—'
    };

    const base = String(page.dataset.apiBase || '').replace(/\/$/, '');

    const rankingUrl = new URL(
        base.replace(/\/data\/monitoring$/, '/ranking'),
        window.location.origin
    );

    function getKpi() {
        return kpis[filter.ki] || emptyKpi;
    }

    function displayScore(value) {
        return filter.mode === 'nko' ? fmt(value, 2) : pct(value);
    }

    function nkoLabel(row) {
        return row?.nko?.status_penilaian ||
            nkoError ||
            (loadingNko ? 'Memuat NKO…' : 'NKO belum tersedia');
    }

    function iconify() {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    async function get(path) {
        const response = await fetch(base + '/' + path, {
            headers: { Accept: 'application/json' }
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

    async function loadNko(year, month) {
        const key = periodKey(year, month);
        if (nkoPeriods.has(key)) return;

        const url = new URL(rankingUrl.href);

        url.searchParams.set('dashboard_nko', '1');
        url.searchParams.set('tahun', String(year));
        url.searchParams.set('bulan', String(month + 1));

        const response = await fetch(url, {
            headers: { Accept: 'application/json' }
        });

        if (!response.ok) {
            throw new Error(
                'NKO gagal dimuat (HTTP ' + response.status + ')'
            );
        }

        const body = await response.json();

        if (body.success !== true || !Array.isArray(body.data)) {
            throw new Error('Format data NKO tidak sesuai.');
        }

        nkoPeriods.set(key, body.data);
    }

    function records(year, month, unit = 'all') {
        const rows = actual
            .filter(row =>
                Number(row.periode?.tahun) === year &&
                Number(row.periode?.bulan) === month + 1 &&
                (
                    unit === 'all' ||
                    String(row.id_ulp) === String(unit)
                )
            )
            .map(row => ({
                key: identity(row),
                kpiId: Number(row.id_kpi),
                unitId: String(row.id_ulp),
                name: row.kpi?.nama_kpi || '—',
                support: row.nama_pendukung || '',
                code: row.kpi?.kode_kpi || '—',
                unit: row.satuan_pendukung || row.kpi?.satuan || '—',
                target: num(row.target_periode),
                actual: num(row.realisasi),
                pct: num(row.pencapaian)
            }));

        targets
            .filter(target =>
                Number(target.tahun) === year &&
                (
                    unit === 'all' ||
                    String(target.id_ulp) === String(unit)
                )
            )
            .forEach(target => {
                const exists = rows.some(row =>
                    row.unitId === String(target.id_ulp) &&
                    row.kpiId === Number(target.id_kpi)
                );

                if (exists) return;

                const monthly = (
                    target.target_bulanan ||
                    target.targetBulanan ||
                    []
                ).find(item => Number(item.bulan) === month + 1);

                rows.push({
                    key: identity(target),
                    kpiId: Number(target.id_kpi),
                    unitId: String(target.id_ulp),
                    name: target.kpi?.nama_kpi || '—',
                    support: '',
                    code: target.kpi?.kode_kpi || '—',
                    unit: target.kpi?.satuan || '—',
                    target: num(monthly?.nilai_target),
                    actual: null,
                    pct: null
                });
            });

        return rows;
    }

    function kpiRow(ui, ki, year, month) {
        const empty = {
            target: null,
            actual: null,
            pct: null
        };

        const kpi = kpis[ki];
        const unit = units[ui];

        if (!kpi || !unit) return empty;

        const rows = records(year, month, String(unit.id_ulp));
        const exact = rows.find(row => row.key === kpi.key);

        if (exact) return exact;

        const supports = kpis.filter(item =>
            item.id === kpi.id &&
            item.support &&
            item.unit === kpi.unit
        );

        if (kpi.support && supports.length === 1) {
            const targetOnly = rows.find(row =>
                row.kpiId === kpi.id &&
                !row.support &&
                row.unit === kpi.unit &&
                row.actual === null
            );

            if (targetOnly) {
                return {
                    ...targetOnly,
                    actual: null,
                    pct: null
                };
            }
        }

        return empty;
    }

    function scores(current = filter) {
        const period = nkoPeriods.get(
            periodKey(current.year, current.month)
        ) || [];

        return units.map((unit, ui) => {
            const row = kpiRow(
                ui,
                current.ki,
                current.year,
                current.month
            );

            const nko = period.find(item =>
                String(item.id_ulp) === String(unit.id_ulp)
            );

            const value = current.mode === 'kpi'
                ? row.pct
                : nko?.status_nko === 'lengkap'
                    ? num(nko.nko)
                    : null;

            return {
                ...unit,
                ui,
                row,
                nko,
                val: value,
                status: current.mode === 'nko'
                    ? value === null ? 'off' : 'cyan'
                    : status(value)
            };
        });
    }

    function relevant(current = filter) {
        return scores(current).filter(row =>
            current.ulp === 'all' ||
            String(row.id_ulp) === String(current.ulp)
        );
    }

    function officeColor(office) {
        return office.up3 ? '#FFC107' : office.inPalu ? '#E03E3E' : '#168FBC';
    }

    function selectOffice(office, zoom = true) {
        selectedOffice = office;
        const ui = units.findIndex(unit => unit.name === office.name);
        selected = ui;
        render();
        const marker = officeMarkers[locations.indexOf(office)];
        if (map && marker && zoom) {
            map.setView(marker.getLatLng(), 15);
            marker.openPopup();
        }
    }

    function focus(ui) {
        const unit = units[ui];
        if (!unit || loadingNko) return;
        const office = locations.find(item => item.name === unit.name);
        if (office) selectOffice(office);
        else {
            selected = ui;
            selectedOffice = null;
            render();
        }
    }

    function redrawMap() {
        $('igLegend').innerHTML = [
            ['#FFC107', 'Kantor UP3 Palu'],
            ['#E03E3E', 'ULP di Kota Palu (3)'],
            ['#168FBC', 'ULP di luar Kota Palu (7)']
        ].map(([color, label]) =>
            '<span><i class="ig-dot" style="background:' +
            color + '"></i>' + label + '</span>'
        ).join('');

        officeMarkers.forEach((marker, index) => {
            const office = locations[index];
            marker.setStyle({
                weight: office.name === selectedOffice?.name ? 4 : 2.5
            });
        });

        $('igOfficePicker').value = selectedOffice?.name || '';
    }

    function renderOffice() {
        const office = selectedOffice;

        $('igDetailName').textContent =
            office?.name || 'Pilih kantor pada peta';

        $('igOfficeRegion').textContent =
            office?.wilayah || '—';

        $('igOfficeAddress').textContent =
            office?.address || 'Alamat belum tersedia.';

        $('igOfficeCoordinates').textContent = office
            ? office.lat.toFixed(7) + ', ' + office.lng.toFixed(7)
            : '—';

        const link = $('igOfficeMaps');
        link.hidden = !office;

        if (office) link.href = office.mapsUrl;
        else link.removeAttribute('href');

        const row = scores().find(item =>
            item.name === office?.name
        );

        $('igOfficeScore').textContent = !office
            ? 'Pilih kantor untuk melihat detail.'
            : office.up3
                ? 'Kantor UP3 Palu — lokasi kantor pengelola wilayah kerja.'
                : !row
                    ? 'Data kinerja kantor ini belum tersedia.'
                    : months[filter.month] + ' ' + filter.year + ' · ' +
                      (filter.mode === 'nko' ? 'NKO: ' : getKpi().code + ': ') +
                      displayScore(row.val) + ' · ' +
                      (filter.mode === 'nko'
                          ? nkoLabel(row)
                          : names[row.status]);
    }

    function render() {
        const rows = relevant();
        const kpi = getKpi();
        const isNko = filter.mode === 'nko';

        $('igAppliedLabel').textContent =
            'Menampilkan: ' +
            (isNko ? 'Mode NKO' : 'KPI ' + kpi.code) +
            ' · ' +
            (filter.ulp === 'all'
                ? 'Seluruh ULP'
                : rows[0]?.name || '—') +
            ' · ' + months[filter.month] + ' ' + filter.year +
            (nkoError && isNko ? ' · ' + nkoError : '');

        $('igRankTitle').textContent = isNko
            ? 'Daftar NKO ULP'
            : 'Daftar Pencapaian KPI';

        $('igRankSubtitle').textContent = isNko
            ? 'Klik ULP untuk detail. Peringkat mengikuti halaman Ranking.'
            : 'Klik ULP untuk detail. Urutan daftar bukan peringkat resmi.';

        $('igRankList').innerHTML = rows.map(row => {
            const value = isNko
                ? row.val === null
                    ? nkoLabel(row)
                    : fmt(row.val, 2)
                : pct(row.val);

            return (
                '<button type="button" class="ig-rankrow ' +
                (row.ui === selected ? 'selected' : '') +
                '" data-ui="' + row.ui + '">' +
                    '<span class="ig-rankname">' +
                        safe(row.name) +
                    '</span>' +
                    '<span class="ig-rankstatus" style="background:' +
                        colors[row.status] + '">' +
                    '</span>' +
                    '<span class="ig-rankvalue">' +
                        safe(value) +
                    '</span>' +
                '</button>'
            );
        }).join('');

        $('igRankList').querySelectorAll('[data-ui]').forEach(button => {
            button.onclick = () => focus(Number(button.dataset.ui));
        });

        $('igRankFootnote').textContent = isNko
            ? 'NKO dan peringkat mengikuti halaman Ranking. Nilai yang belum lengkap ditampilkan —.'
            : 'Status KPI: tercapai ≥100%, waspada ≥90%, lainnya belum tercapai. Tanda — berarti belum dinilai.';

        renderOffice();
        redrawMap();
        iconify();
    }

    function controls() {
        $('igKpi').disabled =
            $('igMode').value !== 'kpi' || !kpis.length;
    }

    async function apply() {
        const requestId = ++applyId;

        filter = {
            mode: $('igMode').value,
            ulp: $('igUlp').value,
            ki: Number($('igKpi').value),
            month: Number($('igMonth').value),
            year: Number($('igYear').value)
        };

        if (filter.ulp !== 'all') {
            selected = units.findIndex(unit =>
                String(unit.id_ulp) === String(filter.ulp)
            );
        }

        if (filter.ulp !== 'all') {
            selectedOffice = locations.find(office =>
                office.name === units[selected]?.name
            ) || null;
        }

        nkoError = '';
        loadingNko = filter.mode === 'nko';

        if (loadingNko) {
            $('igApply').disabled = true;
            $('igAppliedLabel').textContent = 'Memuat hasil NKO…';

            const year = filter.year;
            const endMonth = filter.month;

            try {
                await loadNko(year, endMonth);

                if (requestId !== applyId) return;
            } catch (error) {
                if (requestId !== applyId) return;

                nkoError = 'Data NKO gagal dimuat';
                console.error('Infografis NKO:', error);
            }
        }

        if (requestId !== applyId) return;

        loadingNko = false;
        $('igApply').disabled = false;

        render();
    }

    function fit() {
        if (map && officeMarkers.length) {
            map.fitBounds(
                L.featureGroup(officeMarkers).getBounds().pad(0.15),
                { maxZoom: 9 }
            );
        }
    }

    try {
        $('igApply').disabled = true;

        const [ulp, data, goal] = await Promise.all([
            get('ulp'),
            get('realisasi-kpi'),
            get('target-kpi')
        ]);

        actual = data;
        targets = goal;

        units = ulp.map(unit => ({
            ...unit,
            name: unit.nama_ulp,
            ...(
                locations.find(location =>
                    location.name === unit.nama_ulp
                ) || {}
            )
        }));

        if (!units.length) {
            throw new Error('Tidak ada ULP yang tersedia.');
        }

        const catalog = new Map();

        [...actual, ...targets].forEach(row => {
            const key = identity(row);

            if (!catalog.has(key)) {
                catalog.set(key, {
                    key,
                    code: row.kpi?.kode_kpi || '—',
                    name: row.kpi?.nama_kpi || '—',
                    support: row.nama_pendukung || '',
                    unit: row.satuan_pendukung ||
                          row.kpi?.satuan || '—',
                    id: Number(row.id_kpi)
                });
            }
        });

        kpis = [...catalog.values()].sort((a, b) =>
            a.id - b.id ||
            (a.support ? 0 : 1) - (b.support ? 0 : 1) ||
            a.support.localeCompare(b.support)
        );

        $('igUlp').replaceChildren(
            new Option('Seluruh ULP', 'all'),
            ...units.map(unit =>
                new Option(unit.name, String(unit.id_ulp))
            )
        );

        $('igKpi').replaceChildren(
            ...kpis.map((kpi, index) =>
                new Option(
                    kpi.code + ' · ' + kpi.name +
                    (kpi.support ? ' · ' + kpi.support : ''),
                    String(index)
                )
            )
        );

        if (!kpis.length) {
            $('igKpi').add(
                new Option('Belum ada indikator KPI', '0')
            );
        }

        $('igMonth').replaceChildren(
            ...months.map((month, index) =>
                new Option(month, String(index))
            )
        );

        const periods = actual
            .filter(row =>
                row.periode &&
                num(row.periode.tahun) !== null &&
                num(row.periode.bulan) !== null
            )
            .map(row => ({
                year: Number(row.periode.tahun),
                month: Number(row.periode.bulan) - 1
            }))
            .sort((a, b) =>
                b.year - a.year || b.month - a.month
            );

        const initial = periods[0] || {
            year: Number(targets[0]?.tahun) ||
                  new Date().getFullYear(),
            month: 0
        };

        const years = [...new Set([
            ...actual.map(row => Number(row.periode?.tahun)),
            ...targets.map(target => Number(target.tahun)),
            initial.year
        ])]
            .filter(year => Number.isFinite(year) && year > 0)
            .sort((a, b) => b - a);

        $('igYear').replaceChildren(
            ...years.map(year =>
                new Option(String(year), String(year))
            )
        );

        function reset() {
            $('igMode').value = 'kpi';
            $('igUlp').value = 'all';
            $('igKpi').value = '0';
            $('igMonth').value = String(initial.month);
            $('igYear').value = String(initial.year);

            selected = Math.max(
                0,
                units.findIndex(unit =>
                    unit.name === 'ULP Palu Kota'
                )
            );

            selectedOffice = locations.find(office =>
                office.name === units[selected]?.name
            ) || locations[0];

            controls();
            apply();
            fit();
        }

        if (typeof L !== 'undefined') {
            map = L.map('igMap', {
                scrollWheelZoom: false
            }).setView([-1.25, 120.5], 8);

            L.tileLayer(
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }
            ).addTo(map);

            locations.forEach((office, index) => {
                const marker = L.circleMarker(
                    [office.lat, office.lng],
                    {
                        radius: office.up3 ? 12 : 10,
                        color: '#fff',
                        weight: 2.5,
                        fillColor: officeColor(office),
                        fillOpacity: 1
                    }
                ).addTo(map);

                marker.bindTooltip(office.name);

                marker.bindPopup(
                    '<div class="ig-popup-title">' +
                        safe(office.name) +
                    '</div>' +
                    '<div class="ig-popup-line">' +
                        safe(office.address) +
                    '</div>' +
                    '<div class="ig-popup-line">' +
                        office.lat + ', ' + office.lng +
                    '</div>' +
                    '<a target="_blank" rel="noopener noreferrer" href="' +
                        safe(office.mapsUrl) +
                    '">Buka Google Maps ↗</a>'
                );

                marker.on('click', () =>
                    selectOffice(office, false)
                );

                officeMarkers[index] = marker;
            });
        } else {
            $('igMap').innerHTML =
                '<p class="ig-empty">' +
                'Peta belum dapat dimuat. Periksa koneksi internet.' +
                '</p>';
        }

        $('igApply').disabled = false;
        $('igApply').onclick = apply;
        $('igReset').onclick = reset;
        $('igMode').onchange = controls;
        $('igMapReset').onclick = fit;

        $('igOfficePicker').replaceChildren(
            new Option('Pilih kantor…', ''),
            ...locations.map(office =>
                new Option(office.name, office.name)
            )
        );

        $('igOfficePicker').onchange = event => {
            const office = locations.find(item =>
                item.name === event.target.value
            );

            if (office) selectOffice(office);
        };

        $('igClearSelection').onclick = () => {
            selectedOffice = null;
            selected = -1;
            render();
            fit();
        };

        reset();

        if (map) {
            setTimeout(() => map.invalidateSize(), 50);
        }
    } catch (error) {
        $('igAppliedLabel').textContent =
            'Data tidak berhasil dimuat. Periksa koneksi database, ' +
            'endpoint data monitoring, dan sesi login, lalu muat ulang halaman.';

        $('igRankSubtitle').textContent = 'Data belum dapat dimuat.';
        $('igDetailName').textContent = 'Data belum tersedia';
        $('igOfficeScore').textContent = 'Data kinerja belum dapat dimuat.';

        console.error('Infografis:', error);
    }
});