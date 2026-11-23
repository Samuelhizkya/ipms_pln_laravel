document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const statistikPage = document.getElementById('stPage');

    if (!statistikPage) return;

    const $ = id => document.getElementById(id);
    const apiBase = statistikPage.dataset.apiBase;

    statistikPage.querySelectorAll('select').forEach(select => {
        select.setAttribute('data-ipms-filter', '');
    });

    if (window.Chart) {
        window.Chart.defaults.font.family = "'Inter', sans-serif";

        window.Chart.defaults.set('plugins.title.font', {
            family: "'Poppins', sans-serif"
        });

        window.Chart.defaults.set('plugins.subtitle.font', {
            family: "'Inter', sans-serif"
        });
    }

    let names = {};
    let units = [];
    let kpis = [];
    let actual = [];
    let targets = [];
    let initial;

    let trendChart;
    let donutChart;
    let targetChart;
    let ulpChart;

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
        all: 'Semua Status',
        achieved: 'Tercapai',
        warning: 'Waspada',
        danger: 'Belum Tercapai',
        missing: 'Belum dinilai'
    };

    const css = {
        green: '#237A34',
        yellow: '#F39C12',
        red: '#E03E3E',
        cyan: '#14A2BA',
        deep: '#125D72'
    };

    const now = new Date();

    // Bulan pada halaman Statistik menggunakan indeks 0–11.
    let applied = {
        ulp: 'all',
        month: now.getMonth(),
        year: now.getFullYear(),
        status: 'all'
    };

    function num(value) {
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

    function identity(record) {
        return JSON.stringify([
            record.kpiId,
            record.support || '',
            record.unit
        ]);
    }

    function fmt(value, decimals = 1) {
        if (value === null || value === undefined) {
            return '—';
        }

        return Number(value).toLocaleString('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function safe(value) {
        const characters = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        };

        return String(value).replace(
            /[&<>"']/g,
            character => characters[character]
        );
    }

    function average(rows) {
        const assessed = rows.filter(record => {
            return record.pct !== null;
        });

        if (!assessed.length) return null;

        return assessed.reduce((sum, record) => {
            return sum + record.pct;
        }, 0) / assessed.length;
    }

    function countStatus(rows, status) {
        return rows.filter(record => {
            return record.status === status;
        }).length;
    }

    async function load(path) {
        const response = await fetch(apiBase + '/' + path, {
            headers: {
                Accept: 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(
                'Gagal membaca ' + path +
                ' (HTTP ' + response.status + ').'
            );
        }

        const result = await response.json();

        if (result.success !== true || !Array.isArray(result.data)) {
            throw new Error('Respons ' + path + ' tidak sesuai.');
        }

        return result.data;
    }

    function records(year, month, unit) {
        const rows = actual.filter(record => {
            return Number(record.periode?.tahun) === year &&
                Number(record.periode?.bulan) === month &&
                (
                    unit === 'all' ||
                    String(record.id_ulp) === unit
                );
        }).map(record => {
            const kpi = record.kpi || {};

            return {
                id: record.id_realisasi,
                unitId: String(record.id_ulp),
                kpiId: Number(record.id_kpi),
                name: kpi.nama_kpi || '—',
                support: record.nama_pendukung,
                code: kpi.kode_kpi || '—',

                ulp: record.ulp?.nama_ulp ||
                    names[record.id_ulp] ||
                    '—',

                unit: record.satuan_pendukung ||
                    kpi.satuan ||
                    '—',

                target: num(record.target_periode),
                value: num(record.realisasi),
                pct: num(record.pencapaian)
            };
        });

        targets.filter(target => {
            return Number(target.tahun) === year &&
                (
                    unit === 'all' ||
                    String(target.id_ulp) === unit
                );
        }).forEach(target => {
            const alreadyExists = rows.some(record => {
                return record.unitId === String(target.id_ulp) &&
                    record.kpiId === Number(target.id_kpi);
            });

            if (alreadyExists) return;

            const kpi = target.kpi || {};

            const monthlyTargets = target.target_bulanan ||
                target.targetBulanan ||
                [];

            const monthly = monthlyTargets.find(item => {
                return Number(item.bulan) === month;
            });

            rows.push({
                id: 't' + target.id_target,
                unitId: String(target.id_ulp),
                kpiId: Number(target.id_kpi),
                name: kpi.nama_kpi || '—',
                support: null,
                code: kpi.kode_kpi || '—',

                ulp: target.ulp?.nama_ulp ||
                    names[target.id_ulp] ||
                    '—',

                unit: kpi.satuan || '—',
                target: num(monthly?.nilai_target),
                value: null,
                pct: null
            });
        });

        return rows;
    }

    function gather(ulp, month, year, status) {
        return records(year, month + 1, ulp).map(record => {
            return {
                ...record,

                name: record.name +
                    (
                        record.support
                            ? ' · ' + record.support
                            : ''
                    ),

                actual: record.value,
                status: classify(record.pct),
                dec: 2,
                ulpi: record.unitId,
                ki: identity(record),
                year,
                month
            };
        }).filter(record => {
            return status === 'all' ||
                record.status === status;
        });
    }

    $('stMonth').replaceChildren();

    months.forEach((month, index) => {
        $('stMonth').add(
            new Option(month, String(index))
        );
    });

    $('stMonth').value = String(applied.month);

    const hasMissingOption = Array.from(
        $('stStatus').options
    ).some(option => {
        return option.value === 'missing';
    });

    if (!hasMissingOption) {
        $('stStatus').add(
            new Option('Belum dinilai', 'missing')
        );
    }

    function chart(canvasId, config, previousChart) {
        if (previousChart) {
            previousChart.destroy();
        }

        if (!window.Chart) return null;

        return new window.Chart(
            $(canvasId),
            config
        );
    }

    const common = {
        responsive: true,
        maintainAspectRatio: false,

        plugins: {
            legend: {
                labels: {
                    boxWidth: 12,
                    font: {
                        size: 11
                    }
                }
            }
        },

        scales: {
            x: {
                grid: {
                    display: false
                }
            },

            y: {
                beginAtZero: true,
                grid: {
                    color: '#E8EFF2'
                }
            }
        }
    };

    function updateGraphs(rows) {
        const trend = months.map((month, index) => {
            if (index > applied.month) return null;

            return average(
                gather(
                    applied.ulp,
                    index,
                    applied.year,
                    applied.status
                )
            );
        });

        trendChart = chart(
            'stTrendChart',
            {
                type: 'line',

                data: {
                    labels: months.map(month => {
                        return month.slice(0, 3);
                    }),

                    datasets: [
                        {
                            label: 'Target Acuan',

                            data: trend.map(value => {
                                return value === null ? null : 100;
                            }),

                            borderColor: css.cyan,
                            borderWidth: 2,
                            pointRadius: 0,
                            tension: 0.3
                        },

                        {
                            label: 'Rata-rata Pencapaian',
                            data: trend,
                            borderColor: css.yellow,
                            backgroundColor: '#F39C1220',
                            borderWidth: 3,
                            pointRadius: 3,
                            spanGaps: false,
                            tension: 0.3
                        }
                    ]
                },

                options: {
                    ...common,

                    scales: {
                        ...common.scales,

                        y: {
                            suggestedMax: 125,
                            beginAtZero: true,

                            ticks: {
                                callback: value => value + '%'
                            },

                            grid: {
                                color: '#E8EFF2'
                            }
                        }
                    }
                }
            },
            trendChart
        );

        const statusCounts = [
            countStatus(rows, 'achieved'),
            countStatus(rows, 'warning'),
            countStatus(rows, 'danger'),
            countStatus(rows, 'missing')
        ];

        donutChart = chart(
            'stDonutChart',
            {
                type: 'doughnut',

                data: {
                    labels: rows.length
                        ? [
                            'Tercapai',
                            'Waspada',
                            'Belum Tercapai',
                            'Belum dinilai'
                        ]
                        : ['Tidak ada data'],

                    datasets: [
                        {
                            data: rows.length
                                ? statusCounts
                                : [1],

                            backgroundColor: rows.length
                                ? [
                                    css.green,
                                    css.yellow,
                                    css.red,
                                    '#94A3B8'
                                ]
                                : ['#E5EEF2'],

                            borderColor: '#fff',
                            borderWidth: 3
                        }
                    ]
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '74%',

                    plugins: {
                        legend: {
                            display: false
                        },

                        tooltip: {
                            enabled: rows.length > 0
                        }
                    }
                }
            },
            donutChart
        );

        const indicatorKey = $('stIndicator').value;

        const chosen = rows.filter(record => {
            return record.ki === indicatorKey;
        });

        const indicator = kpis.find(item => {
            return item.key === indicatorKey;
        }) || {
            name: 'Indikator',
            unit: '—',
            dec: 2
        };

        const selected = chosen.slice(0, 10);

        targetChart = chart(
            'stTargetChart',
            {
                type: 'bar',

                data: {
                    labels: selected.map(record => {
                        return record.ulp.replace('ULP ', '');
                    }),

                    datasets: [
                        {
                            label: 'Target',

                            data: selected.map(record => {
                                return record.target;
                            }),

                            backgroundColor: css.cyan
                        },

                        {
                            label: 'Realisasi',

                            data: selected.map(record => {
                                return record.actual;
                            }),

                            backgroundColor: css.yellow
                        }
                    ]
                },

                options: {
                    ...common,

                    plugins: {
                        ...common.plugins,

                        tooltip: {
                            callbacks: {
                                label: context => {
                                    return context.dataset.label +
                                        ': ' +
                                        fmt(
                                            context.parsed.y,
                                            indicator.dec
                                        ) +
                                        ' ' +
                                        indicator.unit;
                                }
                            }
                        }
                    }
                }
            },
            targetChart
        );

        $('stTargetNote').textContent =
            'Perbandingan ' +
            indicator.name +
            ' (' +
            indicator.unit +
            '). ' +
            (
                selected.length
                    ? 'Menampilkan ' +
                        selected.length +
                        ' entri ULP pada filter saat ini.'
                    : 'Tidak ada data indikator ini pada hasil filter status.'
            );

        const unitComparisons = units.filter(unit => {
            return applied.ulp === 'all' ||
                String(unit.id_ulp) === applied.ulp;
        }).map(unit => {
            return {
                name: unit.nama_ulp,

                mean: average(
                    gather(
                        String(unit.id_ulp),
                        applied.month,
                        applied.year,
                        applied.status
                    )
                )
            };
        });

        ulpChart = chart(
            'stUlpChart',
            {
                type: 'bar',

                data: {
                    labels: unitComparisons.map(unit => {
                        return unit.name.replace('ULP ', '');
                    }),

                    datasets: [
                        {
                            label: 'Rata-rata Pencapaian (%)',

                            data: unitComparisons.map(unit => {
                                return unit.mean;
                            }),

                            backgroundColor: css.cyan,
                            borderRadius: 5
                        }
                    ]
                },

                options: {
                    ...common,
                    indexAxis: 'y',

                    scales: {
                        x: {
                            beginAtZero: true,

                            ticks: {
                                callback: value => value + '%'
                            },

                            grid: {
                                color: '#E8EFF2'
                            }
                        },

                        y: {
                            grid: {
                                display: false
                            }
                        }
                    },

                    plugins: {
                        legend: {
                            display: false
                        },

                        tooltip: {
                            callbacks: {
                                label: context => {
                                    return fmt(
                                        context.parsed.x,
                                        1
                                    ) + '%';
                                }
                            }
                        }
                    }
                }
            },
            ulpChart
        );
    }

    function update() {
        const rows = gather(
            applied.ulp,
            applied.month,
            applied.year,
            applied.status
        );

        const achieved = countStatus(rows, 'achieved');
        const warning = countStatus(rows, 'warning');
        const danger = countStatus(rows, 'danger');
        const missing = countStatus(rows, 'missing');

        const assessedCount = rows.filter(record => {
            return record.pct !== null;
        }).length;

        const mean = average(rows);

        $('stTotal').textContent = assessedCount;

        $('stAverage').textContent = mean === null
            ? '—'
            : fmt(mean, 1) + '%';

        $('stDonutTotal').textContent = assessedCount;
        $('stDonutAchieved').textContent = achieved;
        $('stDonutWarning').textContent = warning;
        $('stDonutDanger').textContent = danger;

        const unitName = applied.ulp === 'all'
            ? 'Seluruh ULP'
            : names[applied.ulp] || '—';

        $('stActiveFilter').textContent =
            'Menampilkan: ' +
            unitName +
            ' • ' +
            months[applied.month] +
            ' ' +
            applied.year +
            ' • ' +
            statusLabels[applied.status] +
            ' • ' +
            rows.length +
            ' entri; ' +
            missing +
            ' belum dinilai';

        updateGraphs(rows);

        if (!window.Chart) {
            $('stActiveFilter').textContent +=
                ' • Grafik belum tampil karena Chart.js gagal dimuat.';
        }
    }

    $('stApply').addEventListener('click', function () {
        applied = {
            ulp: $('stUlp').value,
            month: Number($('stMonth').value),
            year: Number($('stYear').value),
            status: $('stStatus').value
        };

        update();
    });

    $('stReset').addEventListener('click', function () {
        if (!initial) return;

        $('stUlp').value = initial.ulp;
        $('stMonth').value = String(initial.month);
        $('stYear').value = String(initial.year);
        $('stStatus').value = initial.status;

        applied = { ...initial };

        update();
    });

    $('stIndicator').addEventListener('change', function () {
        updateGraphs(
            gather(
                applied.ulp,
                applied.month,
                applied.year,
                applied.status
            )
        );
    });

    if (window.lucide) {
        window.lucide.createIcons();
    }

    $('stApply').disabled = true;
    $('stReset').disabled = true;
    $('stActiveFilter').textContent = 'Memuat data…';

    Promise.all([
        load('ulp'),
        load('realisasi-kpi'),
        load('target-kpi')
    ]).then(function ([unitData, actualData, targetData]) {
        units = unitData;
        actual = actualData;
        targets = targetData;

        $('stUlp').replaceChildren(
            new Option('Seluruh ULP', 'all')
        );

        units.forEach(unit => {
            names[unit.id_ulp] = unit.nama_ulp;

            $('stUlp').add(
                new Option(
                    unit.nama_ulp,
                    String(unit.id_ulp)
                )
            );
        });

        const years = [
            ...new Set([
                ...actual.map(record => {
                    return Number(record.periode?.tahun);
                }),

                ...targets.map(target => {
                    return Number(target.tahun);
                })
            ])
        ].filter(year => {
            return Number.isInteger(year) && year > 0;
        }).sort((a, b) => a - b);

        const latest = actual.filter(record => {
            const year = Number(record.periode?.tahun);
            const month = Number(record.periode?.bulan);

            return Number.isInteger(year) &&
                year > 0 &&
                month >= 1 &&
                month <= 12;
        }).slice().sort((a, b) => {
            return Number(b.periode.tahun) -
                Number(a.periode.tahun) ||
                Number(b.periode.bulan) -
                Number(a.periode.bulan);
        })[0];

        const year = Number(latest?.periode?.tahun) ||
            years[years.length - 1] ||
            now.getFullYear();

        if (!years.length) {
            years.push(year);
        }

        $('stYear').replaceChildren();

        years.forEach(item => {
            $('stYear').add(
                new Option(
                    String(item),
                    String(item)
                )
            );
        });

        const seen = new Set();

        actual.forEach(record => {
            const kpi = record.kpi || {};

            const item = {
                kpiId: Number(record.id_kpi),
                support: record.nama_pendukung,

                unit: record.satuan_pendukung ||
                    kpi.satuan ||
                    '—'
            };

            const key = identity(item);

            if (seen.has(key)) return;

            seen.add(key);

            kpis.push({
                key,
                code: kpi.kode_kpi || '—',

                name: (kpi.nama_kpi || '—') +
                    (
                        record.nama_pendukung
                            ? ' · ' + record.nama_pendukung
                            : ''
                    ),

                unit: item.unit,
                dec: 2
            });
        });

        targets.forEach(target => {
            const kpi = target.kpi || {};

            const item = {
                kpiId: Number(target.id_kpi),
                support: null,
                unit: kpi.satuan || '—'
            };

            const key = identity(item);

            if (seen.has(key)) return;

            seen.add(key);

            kpis.push({
                key,
                code: kpi.kode_kpi || '—',
                name: kpi.nama_kpi || '—',
                unit: item.unit,
                dec: 2
            });
        });

        $('stIndicator').replaceChildren();

        kpis.forEach(indicator => {
            $('stIndicator').add(
                new Option(
                    indicator.code + ' · ' + indicator.name,
                    indicator.key
                )
            );
        });

        initial = {
            ulp: 'all',
            year,
            month: Number(latest?.periode?.bulan || 1) - 1,
            status: 'all'
        };

        applied = { ...initial };

        $('stUlp').value = initial.ulp;
        $('stYear').value = String(initial.year);
        $('stMonth').value = String(initial.month);
        $('stStatus').value = initial.status;

        update();

        $('stApply').disabled = false;
        $('stReset').disabled = false;
    }).catch(function (error) {
        $('stActiveFilter').textContent =
            error.message +
            ' Muat ulang halaman untuk mencoba kembali.';
    });
});