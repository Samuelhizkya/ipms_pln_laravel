<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'IPMS PLN UP3 Palu') | IPMS PLN</title>

    {{-- FONT SAMA DENGAN DASHBOARD --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.js"></script>

    @stack('styles')
</head>

<body class="ipms-app">
    <a class="ipms-skip-link" href="#main-content">
        Lewati navigasi
    </a>

    <div class="app-container">
        @include('layouts.sidebar')

        <button
            type="button"
            id="ipms-backdrop"
            class="ipms-backdrop"
            aria-label="Tutup navigasi"
            hidden
        ></button>

        <div class="main-area">
            @include('layouts.navbar')

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

    <script>
        if (window.lucide) {
            window.lucide.createIcons();
        }
    </script>
</body>
</html>