
@php
    $user = session('user', []);
    $role = data_get($user, 'role', '');
    $isAdmin = $role === 'admin';

    $menuAdmin = [
        [
            'label' => 'Dashboard',
            'icon' => 'layout-dashboard',
            'route' => 'admin.dashboard',
            'active' => 'admin.dashboard',
        ],
        [
            'label' => 'Data KPI & Target KPI',
            'icon' => 'clipboard-list',
            'route' => 'target.kpi',
            'active' => 'target.kpi',
        ],
        [
            'label' => 'Realisasi KPI',
            'icon' => 'chart-no-axes-combined',
            'route' => 'realisasi.kpi',
            'active' => 'realisasi.kpi',
        ],
        [
            'label' => 'Monitoring KPI',
            'icon' => 'activity',
            'route' => 'admin.monitoring',
            'active' => 'admin.monitoring',
        ],
        [
            'label' => 'Statistik Kinerja',
            'icon' => 'chart-column',
            'route' => 'admin.statistik',
            'active' => 'admin.statistik',
        ],
        [
            'label' => 'Ranking ULP',
            'icon' => 'trophy',
            'route' => 'ranking.index',
            'active' => 'ranking.index',
        ],
        [
            'label' => 'Infografis Kinerja',
            'icon' => 'map',
            'route' => 'admin.infografis',
            'active' => 'admin.infografis',
        ],
        [
            'label' => 'Laporan Kinerja',
            'icon' => 'file-text',
            'route' => 'admin.laporan',
            'active' => 'admin.laporan',
        ],
    ];

$menuUlp = [
    [
        'label' => 'Dashboard',
        'icon' => 'layout-dashboard',
        'route' => 'ulp.dashboard',
        'active' => 'ulp.dashboard',
    ],
    [
        'label' => 'Data KPI & Target KPI',
        'icon' => 'clipboard-list',
        'route' => 'target.kpi',
        'active' => 'target.kpi',
    ],
    [
        'label' => 'Realisasi KPI',
        'icon' => 'chart-no-axes-combined',
        'route' => 'realisasi.kpi',
        'active' => 'realisasi.kpi',
    ],
    [
        'label' => 'Monitoring KPI',
        'icon' => 'activity',
        'route' => 'ulp.monitoring',
        'active' => 'ulp.monitoring',
    ],
    [
    'label' => 'Statistik Kinerja',
    'icon' => 'chart-column',
    'route' => 'ulp.statistik',
    'active' => 'ulp.statistik',
],
    [
        'label' => 'Ranking ULP',
        'icon' => 'trophy',
        'route' => 'ulp.ranking',
        'active' => 'ulp.ranking',
    ],
    [
    'label' => 'Infografis Kinerja',
    'icon' => 'map',
    'route' => 'ulp.infografis',
    'active' => 'ulp.infografis',
],
[
    'label' => 'Laporan Kinerja',
    'icon' => 'file-text',
    'route' => 'ulp.laporan',
    'active' => 'ulp.laporan',
],
];

    $menus = $isAdmin ? $menuAdmin : $menuUlp;
@endphp

<aside
    id="ipms-sidebar"
    class="sidebar ipms-sidebar"
    aria-label="Navigasi utama"
>
    <a
        href="{{ $isAdmin ? route('admin.dashboard') : route('ulp.dashboard') }}"
        class="ipms-brand"
        aria-label="IPMS PLN UP3 Palu"
    >
        <img
            src="{{ asset('images/logo-pln.png') }}"
            alt="Logo PLN"
            class="ipms-brand-logo"
            width="50"
        >

        <span class="ipms-brand-text">
            <span class="ipms-brand-name">IPMS PLN</span>
            <span class="ipms-brand-unit">UP3 PALU</span>
        </span>
    </a>

    <nav class="ipms-navigation">
        <ul class="ipms-menu-list">
            @foreach ($menus as $menu)
                <li>
                    <a
                        href="{{ route($menu['route']) }}"
                        class="ipms-menu-link {{ request()->routeIs($menu['active']) ? 'is-active' : '' }}"
                        @if (request()->routeIs($menu['active']))
                            aria-current="page"
                        @endif
                    >
                        <i
                            data-lucide="{{ $menu['icon'] }}"
                            aria-hidden="true"
                        ></i>

                        <span>{{ $menu['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="ipms-sidebar-footer">
        <span>Integrated Performance</span>
        <span>Monitoring System</span>
    </div>
</aside>
