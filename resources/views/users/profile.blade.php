@extends('layouts.app')

@section('title', 'Profil Saya')
@section('navbar-title', 'Profil Saya')

@section('content')
@php
    $displayName = $account->nama_lengkap ?: $account->username;



    $roleLabel = match ($account->role) {
        'admin' => 'Administrator',
        'ulp' => 'Pengguna ULP',
        default => 'Pengguna',
    };

    $homeRoute = $account->role === 'admin'
        ? 'users'
        : 'target.kpi';

    $unitLabel = $unitName
        ?: (
            $account->role === 'admin'
                ? 'Tidak ditetapkan ke ULP'
                : 'ULP belum tersedia'
        );
@endphp

<section class="profile-page" aria-labelledby="profile-title">
    <div class="profile-page-heading">
        <div>
            <h2 id="profile-title">Profil Saya</h2>
            <p>Informasi akun yang sedang Anda gunakan.</p>
        </div>

        <a href="{{ route($homeRoute) }}" class="profile-link-button">
            <i data-lucide="arrow-left" aria-hidden="true"></i>
            <span>Kembali</span>
        </a>
    </div>

    <div class="profile-grid">
        <section
            class="profile-card profile-identity"
            aria-label="Identitas pengguna"
        >
            <div class="profile-cover" aria-hidden="true"></div>

            <div class="profile-identity-content">
                <div class="profile-large-avatar" aria-hidden="true">
    @include('components.account-initials', [
        'name' => $displayName,
    ])
</div>

                <h3>{{ $displayName }}</h3>

                <p class="profile-username">
                    {{ $account->username }}
                </p>

                <span class="profile-role-badge">
                    {{ $roleLabel }}
                </span>

                <div class="profile-active-status">
                    <span aria-hidden="true"></span>
                    Akun aktif
                </div>

                <p class="profile-organization">
                    IPMS PLN · UP3 Palu
                </p>
            </div>
        </section>

        <section
            class="profile-card profile-details"
            aria-labelledby="profile-detail-title"
        >
            <div class="profile-section-heading">
                <i data-lucide="user-round" aria-hidden="true"></i>

                <div>
                    <h3 id="profile-detail-title">Informasi Akun</h3>
                    <p>Data identitas yang tercatat dalam sistem.</p>
                </div>
            </div>

            <dl class="profile-data">
                <div>
                    <dt>Nama lengkap</dt>
                    <dd>{{ $account->nama_lengkap ?: 'Belum diisi' }}</dd>
                </div>

                <div>
                    <dt>Username</dt>
                    <dd>{{ $account->username }}</dd>
                </div>

                <div>
                    <dt>Role</dt>
                    <dd>{{ $roleLabel }}</dd>
                </div>

                <div>
                    <dt>Status akun</dt>
                    <dd>Aktif</dd>
                </div>

                <div class="profile-data-wide">
                    <dt>Unit Layanan Pelanggan</dt>
                    <dd>{{ $unitLabel }}</dd>
                </div>
            </dl>

            <div class="profile-information">
                <i data-lucide="info" aria-hidden="true"></i>

                <p>
                    @if ($account->role === 'admin')
                        Akun ini memiliki akses Manajemen User.
                        Halaman ini menampilkan identitas akun
                        Administrator yang sedang digunakan.
                    @else
                        Untuk memperbarui identitas akun atau mereset
                        password, hubungi administrator IPMS PLN UP3 Palu.
                    @endif
                </p>
            </div>

            <div class="profile-actions">
                @if ($account->role === 'admin')
                    <a
                        href="{{ route('users') }}"
                        class="profile-link-button is-primary"
                    >
                        <i data-lucide="users" aria-hidden="true"></i>
                        <span>Manajemen User</span>
                    </a>
                @elseif ($account->role === 'ulp')
                    <a
                        href="{{ route('target.kpi') }}"
                        class="profile-link-button is-primary"
                    >
                        <i data-lucide="clipboard-list" aria-hidden="true"></i>
                        <span>Data KPI & Target KPI</span>
                    </a>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button
                        type="submit"
                        class="profile-link-button is-danger"
                    >
                        <i data-lucide="log-out" aria-hidden="true"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </section>
    </div>
</section>
@endsection