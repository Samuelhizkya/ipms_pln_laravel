document.addEventListener('DOMContentLoaded', function () {
    const dashboard = document.getElementById('adminDashboard');
    if (!dashboard) return;
if (window.Chart) {
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.set('plugins.title.font', {
        family: "'Poppins', sans-serif"
    });
    Chart.defaults.set('plugins.subtitle.font', {
        family: "'Inter', sans-serif"
    });
}
    if (window.lucide) {
        lucide.createIcons();
    }

    const ulpLocations = [
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
    const ulpMapContainer = document.getElementById('ulpMap');
    if (ulpMapContainer && typeof L !== 'undefined') {
        const map = L.map(ulpMapContainer, { scrollWheelZoom: false }).setView([-1.36, 120.85], 7);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors'
        }).addTo(map);
        const markers = [];
        const groups = { palu: [], outside: [], up3: [] };
        ulpLocations.forEach(unit => {
            const group = unit.up3 ? 'up3' : unit.inPalu ? 'palu' : 'outside';
            const color = unit.up3 ? '#FFC107' : unit.inPalu ? '#E03E3E' : '#168FC5';
            const icon = L.divIcon({ className: 'office-marker',
                html: '<span style="background:' + color + '">' + (unit.up3 ? '▦' : '●') + '</span>',
                iconSize: [34, 34], iconAnchor: [17, 17], popupAnchor: [0, -18] });
            const marker = L.marker([unit.lat, unit.lng], { icon, title: unit.name, keyboard: true, zIndexOffset: unit.up3 ? 1000 : 0 }).addTo(map);
            const popup = document.createElement('div');
            popup.className = 'office-popup';
            const text = (tag, value) => { const node = document.createElement(tag); node.textContent = value; popup.append(node); return node; };
            text('strong', unit.name);
            text('small', unit.wilayah);
            text('h4', 'Alamat kantor');
            text('p', unit.address);
            text('h4', 'Koordinat lokasi Google Maps');
            text('p', unit.lat.toFixed(6) + ', ' + unit.lng.toFixed(6));
            const link = text('a', 'Buka di Google Maps ↗');
            link.href = unit.mapsUrl; link.target = '_blank'; link.rel = 'noopener noreferrer';
            const copy = text('button', 'Salin tautan lokasi'); copy.type = 'button';
            copy.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(unit.mapsUrl);
                    copy.textContent = 'Tautan disalin';
                } catch (_) {
                    copy.textContent = 'Salin dari kolom berikut';
                    let field = popup.querySelector('input');
                    if (!field) { field = document.createElement('input'); field.readOnly = true; field.value = unit.mapsUrl; popup.append(field); }
                    field.focus(); field.select();
                }
            });
            marker.bindPopup(popup, { maxWidth: 340, minWidth: 230, autoPan: true });
            marker.bindTooltip(unit.name);
            markers.push(marker); groups[group].push(marker);
        });
        function showGroup(group) {
            const visible = group === 'all' ? markers : groups[group];
            markers.forEach(marker => {
                if (visible.includes(marker)) { if (!map.hasLayer(marker)) marker.addTo(map); }
                else if (map.hasLayer(marker)) map.removeLayer(marker);
            });
            map.closePopup();
            [['showPaluUlps', 'palu'], ['showOutsideUlps', 'outside'], ['showUp3Office', 'up3']].forEach(([id, key]) => document.getElementById(id)?.setAttribute('aria-pressed', String(group === key)));
            if (visible.length) map.fitBounds(L.featureGroup(visible).getBounds().pad(0.18), { maxZoom: group === 'up3' ? 14 : group === 'palu' ? 12 : 9 });
        }
        [['showPaluUlps', 'palu'], ['showOutsideUlps', 'outside'], ['showUp3Office', 'up3'], ['resetUlpMap', 'all']].forEach(([id, group]) => document.getElementById(id)?.addEventListener('click', () => showGroup(group)));
        document.getElementById('expandOfficeMap')?.addEventListener('click', async () => {
            try {
                if (ulpMapContainer.requestFullscreen) await ulpMapContainer.requestFullscreen();
            } catch (_) { /* Keep the embedded map available. */ }
            map.invalidateSize(); showGroup('all');
        });
        document.addEventListener('fullscreenchange', () => { map.invalidateSize(); });
        const chooser = document.getElementById('officeChooser');
        if (chooser) {
            chooser.add(new Option('Pilih kantor untuk melihat detail', ''));
            ulpLocations.forEach((unit, index) => chooser.add(new Option(unit.name, String(index))));
            chooser.addEventListener('change', () => {
                if (chooser.value === '') return;
                const marker = markers[Number(chooser.value)];
                showGroup('all'); map.setView(marker.getLatLng(), 14); marker.openPopup();
            });
        }
        requestAnimationFrame(() => { map.invalidateSize(); showGroup('all'); });
        window.addEventListener('load', () => { map.invalidateSize(); showGroup('all'); }, { once: true });
    } else if (ulpMapContainer) {
        ulpMapContainer.textContent = 'Peta belum dapat dimuat. Periksa koneksi internet.';
    }

    const $=id=>document.getElementById(id);
    const months=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const num=v=>v===null||v===undefined||v===''?null:Number(v);
    const fmt=v=>v===null?'—':Number(v).toLocaleString('id-ID',{maximumFractionDigits:2});
    const status=v=>v===null?'missing':v>=100?'achieved':v>=90?'warning':'danger';
    const labels={missing:'Belum dinilai',achieved:'Tercapai',warning:'Waspada',danger:'Belum Tercapai'};
    const colors={missing:'#94A3B8',achieved:'#237A34',warning:'#F39C12',danger:'#E03E3E'};
const base = dashboard.dataset.apiBase;
    let units=[],master=[],actual=[],targets=[],shown=[],pieChart=null;
    const el=(tag,cls,text)=>{const e=document.createElement(tag);if(cls)e.className=cls;if(text!==undefined)e.textContent=text;return e;};
    async function load(path){const response=await fetch(base+'/'+path,{headers:{Accept:'application/json'}});if(!response.ok)throw Error('Gagal membaca '+path+' (HTTP '+response.status+').');const result=await response.json();if(result.success!==true||!Array.isArray(result.data))throw Error('Respons '+path+' tidak sesuai.');return result.data;}
    function records(year,month,unit){
        const rows=actual.filter(r=>Number(r.periode?.tahun)===year&&Number(r.periode?.bulan)===month&&(unit==='all'||String(r.id_ulp)===unit)).map(r=>({id:r.id_realisasi,unitId:String(r.id_ulp),kpiId:Number(r.id_kpi),name:r.kpi?.nama_kpi||'—',support:r.nama_pendukung,code:r.kpi?.kode_kpi||'—',ulp:r.ulp?.nama_ulp||'—',unit:r.satuan_pendukung||r.kpi?.satuan||'—',target:num(r.target_periode),value:num(r.realisasi),pct:num(r.pencapaian)}));
        targets.filter(t=>Number(t.tahun)===year&&(unit==='all'||String(t.id_ulp)===unit)).forEach(t=>{if(rows.some(r=>r.unitId===String(t.id_ulp)&&r.kpiId===Number(t.id_kpi)))return;const monthly=(t.target_bulanan||t.targetBulanan||[]).find(x=>Number(x.bulan)===month);rows.push({id:'t'+t.id_target,unitId:String(t.id_ulp),kpiId:Number(t.id_kpi),name:t.kpi?.nama_kpi||'—',support:null,code:t.kpi?.kode_kpi||'—',ulp:t.ulp?.nama_ulp||'—',unit:t.kpi?.satuan||'—',target:num(monthly?.nilai_target),value:null,pct:null});});
        return rows;
    }
    function drawTable(){const q=$('searchKpi').value.toLocaleLowerCase('id-ID').trim(),rows=shown.filter(r=>[r.name,r.code,r.ulp,r.support||''].join(' ').toLocaleLowerCase('id-ID').includes(q));const body=$('kpiTableBody');body.replaceChildren();
        rows.forEach((r,i)=>{const tr=el('tr');tr.append(el('td','',i+1));const name=el('td','indicator-name',r.code+' · '+r.name+(r.support?' · '+r.support:''));name.append(el('div','attention-unit',r.ulp));tr.append(name,el('td','',r.unit),el('td','',fmt(r.target)),el('td','',fmt(r.value)),el('td','',r.pct===null?'—':fmt(r.pct)+'%'));const td=el('td');const badge=el('span','status-empty',labels[status(r.pct)]);badge.style.color=colors[status(r.pct)];td.append(badge);tr.append(td);body.append(tr);});
        if(!rows.length){const td=el('td','','Belum ada data sesuai filter atau pencarian.');td.colSpan=7;body.append(el('tr'));body.lastChild.append(td);}
        $('tableCount').textContent='Menampilkan '+rows.length+' dari '+shown.length+' entri (termasuk pendukung)';
    }
    function rankingBadge(index) {
        const badge = el('span', 'dashboard-rank-badge' +
            (index < 3 ? ' podium-' + (index + 1) : ''));
        if (index < 3) {
            badge.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 2h10v3h4v4a5 5 0 0 1-5 5h-.54A6 6 0 0 1 13 15.92V19h4v3H7v-3h4v-3.08A6 6 0 0 1 8.54 14H8a5 5 0 0 1-5-5V5h4V2Zm0 5H5v2a3 3 0 0 0 2.28 2.91A6 6 0 0 1 7 10V7Zm10 0v3c0 .67-.1 1.31-.28 1.91A3 3 0 0 0 19 9V7h-2Z"/></svg>';
            badge.append(el('small', '', String(index + 1)));
        } else {
            badge.textContent = String(index + 1);
        }
        badge.setAttribute('aria-label', 'Urutan daftar ' + (index + 1));
        return badge;
    }
 let rankingRequestId = 0;

async function ranking(unit) {
    const box = $('dashboardRankingList');
    const requestId = ++rankingRequestId;
    const year = $('filterYear').value;
    const month = $('filterMonth').value;

    function showMessage(message) {
        box.replaceChildren(el('p', 'dashboard-ranking-message', message));
    }

    showMessage('Memuat NKO ULP...');

    try {
        const url = new URL(
            dashboard.dataset.rankingUrl,
            window.location.origin
        );

        url.searchParams.set('dashboard_nko', '1');
        url.searchParams.set('tahun', year);
        url.searchParams.set('bulan', month);

        const response = await fetch(url, {
            headers: { Accept: 'application/json' }
        });

        if (!response.ok) {
            throw new Error('Gagal memuat NKO ULP.');
        }

        const result = await response.json();

        if (result.success !== true || !Array.isArray(result.data)) {
            throw new Error('Format data NKO tidak sesuai.');
        }

        if (requestId !== rankingRequestId) return;

        const entries = [...result.data];
        units.forEach(office => {
            if (!entries.some(item => String(item.id_ulp) === String(office.id_ulp) ||
                item.nama_ulp === office.nama_ulp)) {
                entries.push({ id_ulp: office.id_ulp, nama_ulp: office.nama_ulp });
            }
        });
        const officialRank = item => {
            const value = Number(item.ranking);
            return item.ranking != null && Number.isFinite(value) && value > 0
                ? value : 999;
        };
        const rows = entries.sort((a, b) => officialRank(a) - officialRank(b) ||
            String(a.nama_ulp).localeCompare(String(b.nama_ulp), 'id')).slice(0, 10);

        if (!rows.length) {
            showMessage('Tidak ada ULP pada pilihan ini.');
            return;
        }

        box.replaceChildren();

        rows.forEach((item, index) => {
            const row = el('div', 'dashboard-ulp-row');
            row.setAttribute('role', 'listitem');
            row.append(rankingBadge(index));
            const information = el('div', 'dashboard-ulp-information');
            information.append(el('strong', '', item.nama_ulp));
            const rank = officialRank(item);
            information.append(el('small', '', rank === 999
                ? 'Belum berperingkat' : 'Peringkat ' + rank));
            const value = item.nko == null || String(item.nko).trim() === ''
                ? null : Number(item.nko);
            const score = value !== null && Number.isFinite(value)
                ? value.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : '—';
            row.append(information, el('span', 'dashboard-ulp-score', 'NKO ' + score));
            box.append(row);
        });
    } catch (error) {
        if (requestId !== rankingRequestId) return;

        showMessage('Data NKO gagal dimuat. Silakan muat ulang halaman.');
        console.error(error);
    }
}
    function monitoringPreview() {
        const box = $('dashboardMonitoringList');
        box.replaceChildren();
        const year = Number($('filterYear').value);
        const month = Number($('filterMonth').value);
        const unit = $('filterUlp').value;
        // Same source and selection as Flutter: saved realisations with a score below 100%.
        const rows = actual.filter(row =>
            Number(row.periode?.tahun) === year &&
            Number(row.periode?.bulan) === month &&
            (unit === 'all' || String(row.id_ulp) === unit) &&
            num(row.pencapaian) !== null && num(row.pencapaian) < 100
        ).sort((a, b) => num(a.pencapaian) - num(b.pencapaian));
        rows.slice(0, 3).forEach(row => {
            const item = el('div', 'dashboard-monitoring-row');
            const information = el('div', 'dashboard-monitoring-information');
            information.append(el('strong', '', row.kpi?.nama_kpi || 'KPI'));
            const ulp = row.ulp?.nama_ulp || units.find(entry =>
                String(entry.id_ulp) === String(row.id_ulp))?.nama_ulp || '—';
            information.append(el('small', '', (row.kpi?.kode_kpi || '—') + ' · ' + ulp));
            const value = num(row.pencapaian);
            const score = el('strong', 'dashboard-monitoring-score', fmt(value) + '%');
            score.style.color = colors[status(value)];
            item.append(information, score);
            box.append(item);
        });
        if (!rows.length) {
            box.append(el('p', 'chart-note',
                'Tidak ada entri dengan pencapaian di bawah 100% pada filter ini.'));
        }
    }
    function charts(year,month,unit){if(typeof Chart==='undefined')return;
        const assessed=shown.filter(r=>r.pct!==null),counts=['achieved','warning','danger','missing'].map(s=>shown.filter(r=>status(r.pct)===s).length);
        if(pieChart)pieChart.destroy();pieChart=new Chart($('statistikKpiChart'),{type:'doughnut',data:{labels:['Tercapai','Waspada','Belum Tercapai','Belum dinilai'],datasets:[{data:counts,backgroundColor:Object.values({a:colors.achieved,b:colors.warning,c:colors.danger,d:colors.missing}),borderColor:'#fff',borderWidth:3}]},options:{responsive:true,maintainAspectRatio:false,cutout:'68%',plugins:{legend:{display:false}}}});
    }
        // Ringkasan Target dan Realisasi KPI pada Dashboard.
    function renderKpiPreviews(year, month, unit) {
        let container = $('dashboardKpiPreviews');

        if (!container) {
            container = el('div');
            container.id = 'dashboardKpiPreviews';
            container.className = 'insight-grid';

            const summary = document.querySelector(
                '.dashboard-page .summary-grid'
            );

document.querySelector('.dashboard-overview')
    .insertAdjacentElement('afterend', container);
        }

        container.replaceChildren();

        const unitName = row =>
            row.ulp?.nama_ulp ||
            units.find(item =>
                String(item.id_ulp) === String(row.id_ulp)
            )?.nama_ulp ||
            '—';

        const indicatorName = row =>
            (row.kpi?.kode_kpi || '—') + ' · ' +
            (row.kpi?.nama_kpi || '—') +
            (row.nama_pendukung ? ' · ' + row.nama_pendukung : '');

        const targetRows = targets
            .filter(row =>
                Number(row.tahun) === year &&
                (unit === 'all' || String(row.id_ulp) === unit)
            )
            .slice()
            .sort((a, b) =>
                unitName(a).localeCompare(unitName(b)) ||
                Number(a.id_kpi) - Number(b.id_kpi)
            );

        const actualRows = actual
            .filter(row =>
                Number(row.periode?.tahun) === year &&
                Number(row.periode?.bulan) === month &&
                (unit === 'all' || String(row.id_ulp) === unit)
            )
            .slice()
            .sort((a, b) =>
                unitName(a).localeCompare(unitName(b)) ||
                Number(a.id_kpi) - Number(b.id_kpi) ||
                String(a.nama_pendukung || '').localeCompare(
                    String(b.nama_pendukung || '')
                )
            );

        function makePanel(title, icon, url, headers, rows, makeCells) {
            const panel = el('section', 'dash-panel');

            const heading = el('div', 'panel-heading');
            heading.style.flexWrap = 'wrap';

            const name = el('h2', 'panel-title');
            const symbol = el('i');
            symbol.setAttribute('data-lucide', icon);
            name.append(symbol, document.createTextNode(title));

            const link = el('a', 'detail-link', 'Lihat Detail →');
            link.href = url;

            heading.append(name, link);

            const note = el(
                'p',
                'simulation-caption',
                months[month - 1] + ' ' + year +
                ' · ' +
                (
                    unit === 'all'
                        ? 'Seluruh ULP'
                        : units.find(item =>
                            String(item.id_ulp) === unit
                        )?.nama_ulp || '—'
                )
            );

            const wrapper = el('div', 'table-wrapper');
            const table = el('table', 'kpi-table');

            table.style.minWidth = '460px';
            table.style.fontSize = '11px';

            const head = el('thead');
            const headRow = el('tr');

            headers.forEach(label => headRow.append(el('th', '', label)));
            head.append(headRow);

            const body = el('tbody');

            rows.slice(0, 5).forEach(row => {
                const tr = el('tr');

                makeCells(row).forEach(value => {
                    const td = el('td', '', value);
                    td.style.verticalAlign = 'top';
                    tr.append(td);
                });

                body.append(tr);
            });

            if (!rows.length) {
                const tr = el('tr');
                const td = el(
                    'td',
                    '',
                    'Belum ada data pada ULP dan periode ini.'
                );

                td.colSpan = headers.length;
                td.style.textAlign = 'center';
                tr.append(td);
                body.append(tr);
            }

            table.append(head, body);
            wrapper.append(table);

            const footer = el(
                'p',
                'simulation-caption',
                'Menampilkan ' + Math.min(5, rows.length) +
                ' dari ' + rows.length + ' entri.'
            );

            panel.append(heading, note, wrapper, footer);
            container.append(panel);
        }

        makePanel(
            'Data KPI & Target KPI',
            'clipboard-list',
dashboard.dataset.targetUrl,
            ['KPI', 'ULP', 'Target ' + months[month - 1]],
            targetRows,
            row => {
                const monthly = (
                    row.target_bulanan ||
                    row.targetBulanan ||
                    []
                ).find(item => Number(item.bulan) === month);

                const value = num(monthly?.nilai_target);
                const measurement = row.kpi?.satuan || '';

                return [
                    indicatorName(row),
                    unitName(row),
                    value === null
                        ? '—'
                        : fmt(value) + ' ' + measurement
                ];
            }
        );

        makePanel(
            'Realisasi KPI',
            'chart-no-axes-combined',
dashboard.dataset.realisasiUrl,
            ['KPI', 'ULP', 'Realisasi', 'Pencapaian'],
            actualRows,
            row => {
                const value = num(row.realisasi);
                const achievement = num(row.pencapaian);
                const measurement =
                    row.satuan_pendukung || row.kpi?.satuan || '';

                return [
                    indicatorName(row),
                    unitName(row),
                    value === null
                        ? '—'
                        : fmt(value) + ' ' + measurement,
                    achievement === null
                        ? '—'
                        : fmt(achievement) + '%'
                ];
            }
        );

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function render(){const year=Number($('filterYear').value),month=Number($('filterMonth').value),unit=$('filterUlp').value;shown=records(year,month,unit);$('activePeriod').textContent=months[month-1]+' '+year;$('ulpCount').textContent=unit==='all'?units.length:1;$('masterCount').textContent=master.length;
        const assessed=shown.filter(r=>r.pct!==null),missing=shown.length-assessed.length,problem=assessed.filter(r=>r.pct<100).length;
        $('problemCount').textContent=problem;$('problemNote').textContent='Entri dengan pencapaian <100%; '+missing+' belum dinilai';
        $('assessedCount').textContent=assessed.length;
        ['achieved','warning','danger'].forEach(s=>$('count-'+s).textContent=shown.filter(r=>status(r.pct)===s).length);
        $('statusNote').textContent=shown.length+' entri: '+assessed.length+' memiliki pencapaian, '+missing+' belum dinilai. Batas warna sementara: ≥100% tercapai, ≥90% waspada; lainnya belum tercapai. Jumlah termasuk rincian pendukung, bukan KPI unik.';
        ranking(unit);
        monitoringPreview();
        drawTable();
        charts(year, month, unit);
        renderKpiPreviews(year, month, unit);
    }
    $('applyFilter').disabled=true;$('activePeriod').textContent='Memuat data…';
    $('searchKpi').addEventListener('input',drawTable);$('applyFilter').addEventListener('click',render);
    Promise.all([load('ulp'),load('master-kpi'),load('realisasi-kpi'),load('target-kpi')]).then(([u,k,r,t])=>{units=u;master=k;actual=r;targets=t;
        $('filterUlp').replaceChildren(new Option('Seluruh ULP','all'));units.forEach(x=>$('filterUlp').add(new Option(x.nama_ulp,String(x.id_ulp))));
        $('filterMonth').replaceChildren();months.forEach((m,i)=>$('filterMonth').add(new Option(m,String(i+1))));
        const years=[...new Set([...actual.map(x=>Number(x.periode?.tahun)),...targets.map(x=>Number(x.tahun))])].filter(Number.isFinite).sort((a,b)=>a-b);
        const latest=actual.filter(x=>x.periode).slice().sort((a,b)=>Number(b.periode.tahun)-Number(a.periode.tahun)||Number(b.periode.bulan)-Number(a.periode.bulan))[0];const year=Number(latest?.periode?.tahun)||years.at(-1)||new Date().getFullYear();if(!years.length)years.push(year);
        $('filterYear').replaceChildren();years.forEach(y=>$('filterYear').add(new Option(String(y),String(y))));$('filterYear').value=String(year);$('filterMonth').value=String(latest?.periode?.bulan||1);
        $('applyFilter').disabled=false;render();
    }).catch(error=>{$('activePeriod').textContent='Gagal memuat';$('statusNote').textContent=error.message+' Muat ulang halaman untuk mencoba lagi.';});

});

document.addEventListener('DOMContentLoaded', function () {
    const page = document.querySelector('.dashboard-page');
    const ringkasan = page?.querySelector('.summary-grid');
    const petaRanking = page?.querySelector('.dashboard-overview');

    if (ringkasan && petaRanking) {
        ringkasan.insertAdjacentElement('afterend', petaRanking);
    }
});