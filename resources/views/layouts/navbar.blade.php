@php
    $profileUser = session('user', []);

    $profileName = data_get($profileUser, 'nama_lengkap')
        ?: data_get($profileUser, 'username')
        ?: 'Pengguna';

    $profileUsername = data_get($profileUser, 'username', '');
    $profileRole = data_get($profileUser, 'role', '');

    $canManageUsers = $profileRole === 'admin';

    $pageTitle = match (true) {
        request()->routeIs('admin.dashboard') => 'Dashboard Master UP3',
        request()->routeIs('target.kpi') => 'Data KPI & Target KPI',
        request()->routeIs('realisasi.kpi') => 'Realisasi KPI',
        request()->routeIs('admin.monitoring') => 'Monitoring KPI',
        request()->routeIs('admin.statistik') => 'Statistik Kinerja',
        request()->routeIs('ranking.index') => 'Ranking ULP',
        request()->routeIs('admin.infografis') => 'Infografis Kinerja',
        request()->routeIs('admin.laporan') => 'Laporan Kinerja',
        request()->routeIs('profile') => 'Profil Saya',
        request()->routeIs('users', 'users.*') => 'Manajemen User',
        request()->routeIs('master-kpi.*') => 'Master KPI',
        request()->is('ulp') => 'Master ULP',
        default => trim(
            $__env->yieldContent('navbar-title', 'IPMS PLN UP3 Palu')
        ),
    };
@endphp

<header class="navbar ipms-topbar">

    <div class="ipms-topbar-left">

        <button
            type="button"
            id="ipms-menu-toggle"
            class="ipms-icon-button ipms-menu-toggle"
            aria-label="Buka navigasi"
            aria-controls="ipms-sidebar"
            aria-expanded="false"
        >
            <i data-lucide="menu" aria-hidden="true"></i>
        </button>

        <h1 class="ipms-page-name">
            {{ $pageTitle }}
        </h1>

    </div>

    <div class="ipms-account-controls">

        <a
            href="{{ route('profile') }}"
            class="ipms-avatar ipms-avatar-link"
            aria-label="Buka profil {{ $profileName }}"
            title="Profil Saya"
        >
            <span aria-hidden="true">
                @include('components.account-initials', [
                    'name' => $profileName,
                ])
            </span>
        </a>

        <details class="ipms-profile" id="ipms-profile-menu">

            <summary
                class="ipms-profile-trigger"
                aria-label="Menu akun {{ $profileName }}"
            >

                <span class="ipms-profile-text">

                    <span class="ipms-profile-name">
                        {{ $profileName }}
                    </span>

                    @if ($profileRole)
                        <span class="ipms-profile-role">
                            {{ strtoupper(str_replace('_', ' ', $profileRole)) }}
                        </span>
                    @endif

                </span>

                <i
                    data-lucide="chevron-down"
                    class="ipms-profile-chevron"
                    aria-hidden="true"
                ></i>

            </summary>

            <div class="ipms-profile-dropdown">

                <div class="ipms-dropdown-heading">

                    <strong>{{ $profileName }}</strong>

                    @if ($profileUsername)
                        <span>{{ $profileUsername }}</span>
                    @endif

                </div>

                <a
                    href="{{ route('profile') }}"
                    class="ipms-dropdown-item"
                >
                    <i data-lucide="user-round" aria-hidden="true"></i>
                    <span>Profil Saya</span>
                </a>

                @if ($canManageUsers)

                    <a
                        href="{{ route('users') }}"
                        class="ipms-dropdown-item"
                    >
                        <i data-lucide="users" aria-hidden="true"></i>
                        <span>Manajemen User</span>
                    </a>

                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button
                        type="submit"
                        class="ipms-dropdown-item ipms-dropdown-logout"
                    >
                        <i data-lucide="log-out" aria-hidden="true"></i>
                        <span>Keluar</span>
                    </button>
                </form>

            </div>

        </details>

    </div>

</header>