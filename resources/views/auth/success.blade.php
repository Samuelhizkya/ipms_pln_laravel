@extends('layouts.guest')

@section('title', 'Login Berhasil | IPMS PLN')

@section('content')
    <div class="auth-login-page">
        <main class="auth-session-card">
            <img
                src="{{ asset('images/logo-pln.png') }}"
                class="auth-logo-pln"
                alt="Logo PLN"
            >

            <h1>Login berhasil</h1>

            <p>
                Selamat datang,
                <strong>{{ session('user.nama_lengkap') }}</strong>.
                Akun kamu berhasil masuk ke IPMS PLN UP3 Palu.
            </p>

            <dl>
                <div>
                    <dt>Username</dt>
                    <dd>{{ session('user.username') }}</dd>
                </div>

                <div>
                    <dt>Role akun</dt>
                    <dd>{{ session('user.role') }}</dd>
                </div>

                <div>
                    <dt>Status</dt>
                    <dd>{{ session('user.status') }}</dd>
                </div>
            </dl>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="btn-primary auth-login-button"
                >
                    <i data-lucide="log-out" aria-hidden="true"></i>
                    <span>Keluar</span>
                </button>
            </form>
        </main>
    </div>
@endsection