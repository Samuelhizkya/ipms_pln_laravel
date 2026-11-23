@extends('layouts.guest')

@section('title', 'Login IPMS PLN UP3 Palu')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* Dasar khusus login: tidak mengubah halaman setelah login. */
.auth-login-page {
    --font-utama: 'Poppins', sans-serif;
    --font-data: 'Inter', sans-serif;
    --background: #F4F6F9;
    --pln-yellow: #FEE100;
    --pln-blue: #005B94;
    --border: #DDE4EA;
    --text-primary: #334155;
    --text-secondary: #64748B;
    font-family: var(--font-data);
    font-size: 14px;
    line-height: 1.5;
    color: var(--text-primary);
}
.auth-login-page *, .auth-login-page *::before, .auth-login-page *::after {
    box-sizing: border-box;
}
.auth-login-page h1, .auth-login-page h2, .auth-login-page h3 {
    font-family: var(--font-utama);
}
.auth-login-page button, .auth-login-page input, .auth-login-page select {
    font: inherit;
}
.auth-login-page button:not(:disabled) { cursor: pointer; }
.auth-login-page img { max-width: 100%; }
.auth-login-page input::placeholder { color: #94A3B8; opacity: 1; }
.auth-login-page :where(a, button, input, summary):focus-visible {
    outline: 3px solid #4E73DF;
    outline-offset: 3px;
}
.auth-login-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 30px;
    background: var(--background);
}

.auth-login-wrapper {
    width: min(1200px, 100%);
    min-height: 650px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    overflow: hidden;
    border-radius: 24px;
    background: #FFFFFF;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
}

.auth-login-visual {
    position: relative;
    display: flex;
    align-items: flex-end;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.auth-login-visual-content {
    position: relative;
    z-index: 1;
    padding: 50px;
    color: #FFFFFF;
}

.auth-login-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 25px;
    padding: 10px 16px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.15);
    font-size: 13px;
}

.auth-login-badge svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.auth-visual-title {
    margin: 0 0 10px;
    color: #FFFFFF;
    font-size: 42px;
    font-weight: 700;
}

.auth-visual-title span {
    display: block;
    color: var(--pln-yellow);
    font-size: 24px;
    letter-spacing: 5px;
}

.auth-visual-desc {
    max-width: 430px;
    margin: 0;
    font-size: 15px;
    line-height: 1.7;
}

.auth-login-form-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 50px;
}

.auth-login-form-box {
    width: 100%;
    max-width: 380px;
}

.auth-logo-pln {
    display: block;
    width: 120px;
    height: auto;
    margin-bottom: 25px;
}

.auth-form-heading h2 {
    margin: 0 0 10px;
    color: #17324D;
    font-size: 32px;
    font-weight: 400;
}

.auth-form-heading p {
    margin: 0 0 30px;
    color: #64748B;
    font-size: 14px;
    line-height: 1.7;
}

.auth-login-page .form-group {
    margin-bottom: 20px;
}

.auth-login-page .form-group > label {
    display: block;
    margin-bottom: 8px;
    color: #17324D;
    font-family: var(--font-utama);
    font-size: 14px;
    font-weight: 600;
}

.auth-login-page .input-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.auth-login-page .input-icon > svg,
.auth-login-page .input-icon > i {
    position: absolute;
    left: 14px;
    width: 18px;
    height: 18px;
    color: #64748B;
    pointer-events: none;
}

.auth-login-page .input-icon input {
    width: 100%;
    min-height: 46px;
    padding: 12px 45px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: #FFFFFF;
    color: var(--text-primary);
    font-family: var(--font-data);
}

.auth-login-page .input-icon input:focus {
    outline: none;
    border-color: var(--pln-blue);
    box-shadow: 0 0 0 4px rgba(0, 91, 148, 0.12);
}

.auth-login-page .password-button {
    position: absolute;
    top: 50%;
    right: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    padding: 0;
    transform: translateY(-50%);
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #64748B;
}

.auth-login-page .password-button svg {
    position: static;
    width: 18px;
    height: 18px;
}

.auth-login-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 24px;
}

.auth-login-page .remember-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #64748B;
    font-size: 13px;
    white-space: nowrap;
}

.auth-login-page .remember-wrap input {
    width: 14px;
    height: 14px;
    margin: 0;
}

.auth-password-help {
    position: relative;
    color: var(--pln-blue);
    font-size: 13px;
}

.auth-password-help summary {
    cursor: pointer;
}

.auth-password-help p {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    z-index: 5;
    width: 240px;
    margin: 0;
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: #FFFFFF;
    color: var(--text-primary);
    line-height: 1.6;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.auth-login-page .auth-login-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    min-height: 46px;
    padding: 11px 20px;
    border: none;
    border-radius: 10px;
    background: var(--pln-yellow);
    color: #17324D;
    font-family: var(--font-utama);
    font-weight: 600;
}

.auth-login-page .auth-login-button:hover {
    background: #FFD800;
}

.auth-login-button svg {
    width: 20px;
    height: 20px;
}

.auth-login-page .alert-error {
    margin-bottom: 20px;
    padding: 12px;
    border-radius: 10px;
    background: #FEE2E2;
    color: #B91C1C;
    font-size: 13px;
}

.auth-login-page .alert-error ul {
    margin: 0;
    padding-left: 18px;
    list-style: disc;
}

.auth-login-footer {
    margin-top: 30px;
    color: #94A3B8;
    font-size: 12px;
    text-align: center;
}

/* Halaman konfirmasi login sementara */

.auth-session-card {
    width: min(480px, 100%);
    padding: 32px;
    border: 1px solid var(--border);
    border-radius: 20px;
    background: #FFFFFF;
    box-shadow: 0 12px 36px rgba(0, 0, 0, 0.08);
}

.auth-session-card h1 {
    margin: 0 0 12px;
    color: var(--pln-blue);
    font-size: 24px;
    font-weight: 600;
}

.auth-session-card p {
    margin: 0 0 16px;
    line-height: 1.7;
}

.auth-session-card dl {
    margin: 24px 0;
}

.auth-session-card dl > div {
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
}

.auth-session-card dt {
    color: var(--text-secondary);
    font-size: 12px;
}

.auth-session-card dd {
    margin: 4px 0 0;
    overflow-wrap: anywhere;
    font-weight: 500;
}

@media (max-width: 900px) {
    .auth-login-wrapper {
        grid-template-columns: 1fr;
    }

    .auth-login-visual {
        min-height: 350px;
    }
}

@media (max-width: 600px) {
    .auth-login-page {
        padding: 16px;
    }

    .auth-login-wrapper {
        border-radius: 18px;
    }

    .auth-login-visual-content,
    .auth-login-form-panel {
        padding: 28px;
    }

    .auth-visual-title {
        font-size: 34px;
    }

    .auth-visual-title span {
        font-size: 21px;
    }

    .auth-form-heading h2 {
        font-size: 28px;
    }

    .auth-login-badge {
        font-size: 11px;
    }
}
/* Background foto di belakang card login */
.auth-login-page {
    position: relative;
    isolation: isolate;
    min-height: 100vh;
    min-height: 100dvh;
    background: #F4F6F9;
}

.auth-login-page::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -1;
    pointer-events: none;
    background-image: var(--login-background);
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    opacity: 0.16;
}

.auth-login-wrapper {
    position: relative;
    background: #FFFFFF;
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 24px 64px rgba(0, 55, 90, 0.18);
}

.auth-login-page .remember-wrap input {
    width: 16px;
    height: 16px;
    accent-color: #005B94;
}

/* Judul halaman login */
.auth-login-page .auth-form-heading h2 {
    font-family: var(--font-utama, 'Poppins', sans-serif);
    font-weight: 700;
}

/* Tombol Login */
.auth-login-page .auth-login-button {
    background: #125D72;
    border: 1px solid #125D72;
    color: #FFFFFF;
    font-family: var(--font-utama, 'Poppins', sans-serif);
    font-weight: 600;
    transition:
        background-color 150ms ease,
        border-color 150ms ease;
}

.auth-login-page .auth-login-button:hover:not(:disabled) {
    background: #0E4C5E;
    border-color: #0E4C5E;
    color: #FFFFFF;
}

.auth-login-page .auth-login-button:focus-visible {
    outline: 3px solid #14A2BA;
    outline-offset: 3px;
}

.auth-login-page .auth-login-button:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.auth-login-page .auth-login-button svg {
    color: currentColor;
}
</style>
@endpush

@section('content')
    <div class="auth-login-page"
    style="--login-background: url('{{ asset('images/up3-palu.png') }}');"
    >
        <div class="auth-login-wrapper">

            <div
                class="auth-login-visual"
                style="
                    background-image:
                    linear-gradient(
                        rgba(0, 91, 148, .65),
                        rgba(0, 91, 148, .45)
                    ),
                    url('{{ asset('images/pln-up3.png') }}');
                "
            >
                <div class="auth-login-visual-content">
                    <div class="auth-login-badge">
                        <i data-lucide="activity" aria-hidden="true"></i>
                        <span>Monitoring Kinerja Terintegrasi</span>
                    </div>

                    <h1 class="auth-visual-title">
                        IPMS PLN
                        <span>UP3 PALU</span>
                    </h1>

                    <p class="auth-visual-desc">
                        Sistem monitoring kinerja terintegrasi untuk pengelolaan
                        data ULP, Master KPI, Target KPI, dan Realisasi KPI
                        di lingkungan PLN UP3 Palu.
                    </p>
                </div>
            </div>

            <div class="auth-login-form-panel">
                <div class="auth-login-form-box">
                    <img
                        src="{{ asset('images/logo-pln.png') }}"
                        class="auth-logo-pln"
                        alt="Logo PLN"
                    >

                    <div class="auth-form-heading">
                        <h2 class="font-bold text-2xl">Selamat Datang</h2>

                        <p>
                            Silakan login menggunakan akun yang telah diberikan
                            untuk mengakses sistem IPMS PLN UP3 Palu.
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('login.process') }}"
                    >
                        @csrf

                        <div class="form-group">
                            <label for="username">Username</label>

                            <div class="input-icon">
                                <i data-lucide="user" aria-hidden="true"></i>

                                <input
                                    id="username"
                                    type="text"
                                    name="username"
                                    value="{{ old('username') }}"
                                    placeholder="Masukkan username"
                                    autocomplete="username"
                                    autocapitalize="none"
                                    spellcheck="false"
                                    maxlength="50"
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>

                            <div class="input-icon">
                                <i data-lucide="lock" aria-hidden="true"></i>

                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    placeholder="Masukkan password"
                                    autocomplete="current-password"
                                    required
                                >

                                <button
                                    type="button"
                                    id="togglePassword"
                                    class="password-button"
                                    aria-label="Tampilkan password"
                                    aria-controls="password"
                                    aria-pressed="false"
                                >
                                    <i data-lucide="eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="auth-login-option">
                            <label
    class="remember-wrap"
    for="remember"
    title="Pertahankan login pada browser ini selama 30 hari."
>
    <input
        id="remember"
        type="checkbox"
        name="remember"
        value="1"
        @checked(old('remember') == '1')
    >

    <span>Ingat Saya</span>
</label>

                            <details class="auth-password-help">
                                <summary>Lupa Password?</summary>

                                <p>
                                    Hubungi Administrator IPMS PLN UP3 Palu
                                    untuk bantuan pemulihan akses akun.
                                </p>
                            </details>
                        </div>

                        <button
                            class="btn-primary auth-login-button"
                            type="submit"
                        >
                            <i data-lucide="log-in" aria-hidden="true"></i>
                            <span>Login</span>
                        </button>
                    </form>

                    <div class="auth-login-footer">
                        © {{ date('Y') }} PT PLN (Persero) UP3 Palu
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection