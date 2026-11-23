
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', 'IPMS PLN UP3 Palu') | IPMS PLN
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.js"></script>

    @stack('styles')

    <style>
        /* Layout utama admin */
        .ipms-app {
            --sidebar-width: 250px;
            background: #E7F6F9;
            color: #173447;
            font-family: 'Inter', sans-serif;
        }

        .ipms-app .main-area {
            margin-left: var(--sidebar-width);
            min-width: 0;
        }

        .ipms-app .page-content {
            min-height: calc(100vh - 56px);
            padding: 30px;
        }

        /* Judul halaman */
        .ipms-app .ipms-page-name {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        /* Judul lama dalam konten tidak perlu diulang */
        .main-content .dashboard-heading > div:first-child,
        .main-content .mon-heading > div:first-child,
        .main-content .st-heading > div:first-child,
        .main-content .rk-heading > div:first-child {
            display: none !important;
        }

        .main-content .dashboard-heading,
        .main-content .mon-heading,
        .main-content .st-heading,
        .main-content .rk-heading {
            justify-content: flex-end;
        }

        /* Responsif */
        @media (max-width: 900px) {
            .ipms-app .main-area {
                margin-left: 0;
            }

            .ipms-app .page-content {
                padding: 18px;
            }
        }
    </style>

</head>

<body class="ipms-app">

    <a class="ipms-skip-link" href="#main-content">
        Lewati navigasi
    </a>

    <div class="app-container">

        {{-- SIDEBAR BERSAMA TEMAN 2 DAN TEMAN 3 --}}
        @include('layouts.sidebar')

        <button
            type="button"
            id="ipms-backdrop"
            class="ipms-backdrop"
            aria-label="Tutup navigasi"
            hidden
        ></button>

        <div class="main-area">

            {{-- NAVBAR DAN DROPDOWN PROFIL --}}
            @include('layouts.navbar')

            {{-- KONTEN HALAMAN --}}
            <main
                id="main-content"
                class="page-content main-content"
                tabindex="-1"
            >
                @yield('content')
            </main>

        </div>

    </div>

    @stack('scripts')

    @include('components.notifications')

</body>

</html>
