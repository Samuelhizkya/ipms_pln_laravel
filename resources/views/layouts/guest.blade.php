<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'IPMS PLN UP3 Palu')</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.js"></script>

    @stack('styles')
</head>

<body>
    @yield('content')

       @stack('scripts')

    @include('components.notifications')
</body>
</html>