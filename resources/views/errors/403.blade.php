@php
    $sessionUser = session('user');

    $isLoggedIn = is_array($sessionUser)
        && !empty($sessionUser['id_user']);

    $isAdmin = $isLoggedIn
        && ($sessionUser['role'] ?? null) === 'admin';

    if (!$isLoggedIn) {
        $destination = url('/login');
        $buttonLabel = 'Ke Halaman Login';
    } elseif ($isAdmin) {
        $destination = url('/users');
        $buttonLabel = 'Ke Manajemen User';
    } else {
        $destination = url('/target-kpi');
        $buttonLabel = 'Ke Data KPI & Target KPI';
    }
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta name="robots" content="noindex, nofollow">

    <title>Akses Dibatasi — IPMS PLN</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --font-utama: 'Poppins', sans-serif;
            --font-data: 'Inter', sans-serif;

            --pln-blue: #005B94;
            --pln-light-blue: #4E73DF;
            --pln-green: #237A34;
            --pln-yellow: #FEE100;

            --page-background: #F4F6F9;
            --text-primary: #203449;
            --text-secondary: #617184;
            --border-color: #E3E9EF;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            padding: 24px;
            display: grid;
            place-items: center;
            background: var(--page-background);
            color: var(--text-primary);
            font-family: var(--font-data);
            line-height: 1.6;
        }

        .access-card {
            width: 100%;
            max-width: 560px;
            padding: 40px;
            background: #FFFFFF;
            border: 1px solid var(--border-color);
            border-top: 5px solid var(--pln-blue);
            border-radius: 18px;
            box-shadow: 0 12px 36px rgba(0, 49, 82, 0.07);
            text-align: center;
        }

        .access-brand {
            margin: 0 0 28px;
            color: var(--pln-blue);
            font-family: var(--font-utama);
            font-size: 14px;
            font-weight: 600;
        }

        .access-code {
            display: inline-block;
            margin-bottom: 16px;
            padding: 6px 16px;
            border: 1px solid #D5E8F4;
            border-radius: 999px;
            background: #EDF6FC;
            color: var(--pln-blue);
            font-weight: 600;
            font-size: 14px;
        }

        h1 {
            margin: 0 0 12px;
            font-family: var(--font-utama);
            font-size: clamp(24px, 5vw, 30px);
            font-weight: 600;
            line-height: 1.35;
        }

        .access-description {
            margin: 0;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .access-help {
            margin: 18px 0 0;
            color: var(--text-secondary);
            font-size: 13px;
        }

        .access-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            max-width: 100%;
            margin-top: 28px;
            padding: 12px 20px;
            border: 1px solid var(--pln-blue);
            border-radius: 10px;
            background: var(--pln-blue);
            color: #FFFFFF;
            font-family: var(--font-utama);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 150ms ease;
        }

        .access-button:hover {
            background: #004775;
        }

        .access-button:focus-visible {
            outline: 3px solid var(--pln-yellow);
            outline-offset: 4px;
        }

        .access-footer {
            margin: 24px 0 0;
            color: var(--text-secondary);
            font-size: 12px;
        }

        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .access-card {
                padding: 28px 20px;
            }

            .access-button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <main class="access-card" aria-labelledby="access-title">
        <p class="access-brand">IPMS PLN · UP3 Palu</p>

        <span class="access-code">Error 403</span>

        <h1 id="access-title">Akses Dibatasi</h1>

        <p class="access-description">
            Akun Anda tidak memiliki izin untuk mengakses
            halaman atau melakukan tindakan ini.
        </p>

        <p class="access-help">
            Jika akses ini diperlukan untuk pekerjaan Anda,
            hubungi administrator untuk memeriksa izin akun.
        </p>

        <a class="access-button" href="{{ $destination }}">
            {{ $buttonLabel }}
        </a>

        <p class="access-footer">
            Sistem Monitoring Kinerja PLN UP3 Palu
        </p>
    </main>
</body>
</html>