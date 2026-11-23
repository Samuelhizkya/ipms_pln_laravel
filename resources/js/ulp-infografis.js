function initializeUlpInfografis() {
    const page = document.querySelector('.ui-page');

    if (!page) return;

    page.querySelectorAll('.ui-field select').forEach(function (select) {
        select.setAttribute('data-ipms-filter', '');
    });

    const rows = JSON.parse(page.dataset.indicators || '[]');
    const unitName = page.dataset.unit || '';
    const period = page.dataset.period || '';

    const select = page.querySelector('#uiIndicator');
    const mapBox = page.querySelector('#uiMap');

    if (!select || !mapBox) return;


    const formatter = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    function format(value) {
        return value === null || value === undefined
            ? '—'
            : formatter.format(value);
    }

    function getStatus(value) {
        if (value === null || value === undefined) {
            return {
                label: 'Belum dinilai',
                color: '#97a8b5',
                background: '#eef4f6'
            };
        }

        if (value >= 100) {
            return {
                label: 'Tercapai',
                color: '#237a37',
                background: '#e4f6e9'
            };
        }

        if (value >= 90) {
            return {
                label: 'Waspada',
                color: '#946200',
                background: '#fff5dc'
            };
        }

        return {
            label: 'Belum tercapai',
            color: '#df4545',
            background: '#fdeaea'
        };
    }

    function render() {
        const row = rows[Number(select.value)] ?? null;
        const state = getStatus(row?.pct);

        page.querySelector('#uiDetailName').textContent =
            row?.label ?? 'Belum ada realisasi pada periode ini';

        page.querySelector('#uiUnit').textContent =
            row?.unit ?? '—';

        page.querySelector('#uiTarget').textContent =
            format(row?.target);

        page.querySelector('#uiActual').textContent =
            format(row?.actual);

        page.querySelector('#uiPct').textContent =
            row && row.pct !== null
                ? format(row.pct) + '%'
                : '—';

        const badge = page.querySelector('#uiStatus');

        badge.textContent = state.label;
        badge.style.color = state.color;
        badge.style.background = state.background;

    }

    select.addEventListener('change', render);
    render();

    if (!window.L) {
        mapBox.textContent = 'Peta belum dapat dimuat. Periksa koneksi internet.';
        mapBox.classList.add('ui-empty');
        return;
    }
    const locations = [
    {
        "name": "PLN UP3 Palu",
        "address": "Jl. R.A. Kartini No. 26, Lolu Selatan, Kecamatan Palu Selatan, Kota Palu, Sulawesi Tengah 94111, Indonesia",
        "mapsUrl": "https://maps.app.goo.gl/S7ZCB5iAfzZtA8xH7?g_st=ac",
        "lat": -0.9018822,
        "lng": 119.8789176,
        "wilayah": "Kota Palu",
        "inPalu": true,
        "up3": true
    },
    {
        "name": "ULP Palu Kota",
        "address": "Jl. Sulawesi, Lolu Utara, Kecamatan Palu Timur, Kota Palu, Sulawesi Tengah 94112, Indonesia",
        "mapsUrl": "https://maps.app.goo.gl/tzxNVPGK9wLgzP9x8",
        "lat": -0.9003095,
        "lng": 119.8721903,
        "wilayah": "Kota Palu",
        "inPalu": true,
        "up3": false
    },
    {
        "name": "ULP Kamonji",
        "address": "Jl. Munif Rahman, Kabonena, Kecamatan Ulujadi, Kota Palu, Sulawesi Tengah 94227, Indonesia",
        "mapsUrl": "https://maps.app.goo.gl/r1Zt32baMYVoBMYn8?g_st=ac",
        "lat": -0.8827826,
        "lng": 119.835946,
        "wilayah": "Kota Palu",
        "inPalu": true,
        "up3": false
    },
    {
        "name": "ULP Tawaeli",
        "address": "Jl. Tolitoli–Palu, Panau, Kecamatan Tawaeli, Kota Palu, Sulawesi Tengah 94352, Indonesia",
        "mapsUrl": "https://maps.app.goo.gl/WHHq9AMY4oFksAFV6?g_st=ac",
        "lat": -0.7319372,
        "lng": 119.8636457,
        "wilayah": "Kota Palu",
        "inPalu": true,
        "up3": false
    },
    {
        "name": "ULP Donggala",
        "address": "Jl. Lorong PLN, Gunung Bale, Kecamatan Banawa, Kabupaten Donggala, Sulawesi Tengah 94351, Indonesia",
        "mapsUrl": "https://maps.app.goo.gl/YixwN7UZo2yV7r7e9",
        "lat": -0.6719303,
        "lng": 119.7467719,
        "wilayah": "Kabupaten Donggala",
        "inPalu": false,
        "up3": false
    },
    {
        "name": "ULP Tambu",
        "address": "Mapane Tambu, Kecamatan Balaesang, Kabupaten Donggala, Sulawesi Tengah 94355, Indonesia",
        "mapsUrl": "https://maps.app.goo.gl/qqFouruU41ydUuC96",
        "lat": -0.0407428,
        "lng": 119.8840185,
        "wilayah": "Kabupaten Donggala",
        "inPalu": false,
        "up3": false
    },
    {
        "name": "ULP Parigi",
        "address": "Maesa, Kecamatan Parigi, Kabupaten Parigi Moutong, Sulawesi Tengah 94471, Indonesia",
        "mapsUrl": "https://maps.app.goo.gl/PKBYq3ry8HPMkAbm8?g_st=ac",
        "lat": -0.8160205,
        "lng": 120.1734322,
        "wilayah": "Kabupaten Parigi Moutong",
        "inPalu": false,
        "up3": false
    },
    {
        "name": "ULP Poso",
        "address": "Kasintuwu, Kecamatan Poso Kota Utara, Kabupaten Poso, Sulawesi Tengah 94611, Indonesia",
        "mapsUrl": "https://maps.app.goo.gl/TQSHYvEm77Mb6N1M9",
        "lat": -1.3848989,
        "lng": 120.7593312,
        "wilayah": "Kabupaten Poso",
        "inPalu": false,
        "up3": false
    },
    {
        "name": "ULP Tentena",
        "address": "Tentena, Kecamatan Pamona Puselemba, Kabupaten Poso, Sulawesi Tengah 94663, Indonesia",
        "mapsUrl": "https://maps.app.goo.gl/6foJQioTkpUDBd8s8",
        "lat": -1.7551139,
        "lng": 120.6492296,
        "wilayah": "Kabupaten Poso",
        "inPalu": false,
        "up3": false
    },
    {
        "name": "ULP Kolonedale",
        "address": "Jl. Trans Sulawesi, Desa Temporal, Tompira, Kecamatan Petasia Timur, Kabupaten Morowali Utara, Sulawesi Tengah 94971, Indonesia",
        "mapsUrl": "https://maps.app.goo.gl/VCS4RmH8G7oNPQpx5?g_st=ac",
        "lat": -2.080452,
        "lng": 121.3810964,
        "wilayah": "Kabupaten Morowali Utara",
        "inPalu": false,
        "up3": false
    },
    {
        "name": "ULP Bungku",
        "address": "Marsaoleh, Kecamatan Bungku Tengah, Kabupaten Morowali, Sulawesi Tengah 94973, Indonesia",
        "mapsUrl": "https://maps.app.goo.gl/BWhWjm2KzU3fwYUL9?g_st=ac",
        "lat": -2.5462634,
        "lng": 121.9704088,
        "wilayah": "Kabupaten Morowali",
        "inPalu": false,
        "up3": false
    }
];
    const L = window.L;
    const map = L.map(mapBox, { scrollWheelZoom: false }).setView([-1.36, 120.85], 7);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors'
    }).addTo(map);
    const markers = [];
    locations.forEach((office, index) => {
        const color = office.up3 ? '#FFC107' : office.inPalu ? '#E03E3E' : '#168FC5';
        const icon = L.divIcon({
            className: 'ui-office-marker',
            html: '<span style="background:' + color + '">' + (office.up3 ? '▦' : '●') + '</span>',
            iconSize: [34, 34], iconAnchor: [17, 17], popupAnchor: [0, -18]
        });
        const marker = L.marker([office.lat, office.lng], {
            icon, title: office.name, keyboard: true, zIndexOffset: office.up3 ? 1000 : 0
        }).addTo(map);
        const popup = document.createElement('div');
        popup.className = 'ui-office-popup';
        const text = (tag, value) => {
            const node = document.createElement(tag); node.textContent = value;
            popup.append(node); return node;
        };
        text('strong', office.name);
        text('small', office.wilayah);
        text('h4', 'Alamat kantor'); text('p', office.address);
        text('h4', 'Koordinat kantor'); text('p', office.lat.toFixed(7) + ', ' + office.lng.toFixed(7));
        // Other offices expose public location information only.
        if (!office.up3 && office.name === unitName) {
            text('p', mapBox.dataset.count + ' entri realisasi · ' + period);
        }
        const link = text('a', 'Buka Google Maps ↗');
        link.href = office.mapsUrl; link.target = '_blank'; link.rel = 'noopener noreferrer';
        const copy = text('button', 'Salin tautan lokasi'); copy.type = 'button';
        copy.addEventListener('click', async () => {
            try { await navigator.clipboard.writeText(office.mapsUrl); copy.textContent = 'Tautan disalin'; }
            catch (_) {
                copy.textContent = 'Salin tautan dari kolom berikut';
                let field = popup.querySelector('input');
                if (!field) { field = document.createElement('input'); field.readOnly = true; field.value = office.mapsUrl; popup.append(field); }
                field.focus(); field.select();
            }
        });
        marker.bindPopup(popup, { maxWidth: 340, minWidth: 230 });
        marker.bindTooltip(office.name);
        marker.on('click', () => marker.openPopup());
        markers.push(marker);
    });
    const fit = () => {
        map.closePopup();
        map.fitBounds(L.featureGroup(markers).getBounds().pad(0.15), { maxZoom: 9 });
    };
    requestAnimationFrame(() => { map.invalidateSize(); fit(); });
    window.addEventListener('load', () => { map.invalidateSize(); fit(); }, { once: true });
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeUlpInfografis,
        { once: true }
    );
} else {
    initializeUlpInfografis();
}