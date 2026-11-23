@extends('layouts.app')

@section('title', $account->exists ? 'Edit Akun ULP' : 'Tambah Akun ULP')

@section('content')
<section class="card users-form-card">
    <div class="users-heading">
        <h2>{{ $account->exists ? 'Edit Akun ULP' : 'Tambah Akun ULP' }}</h2>
        <p>Lengkapi identitas akun dan unit pengguna.</p>
    </div>


    <form
        method="POST"
        action="{{ $account->exists
            ? route('users.update', $account->id_user)
            : route('users.store') }}"
        class="users-form"
    >
        @csrf

        @if ($account->exists)
            @method('PUT')
        @endif

        <div class="users-form-grid">
            <div class="users-field">
                <label for="nama_lengkap">Nama lengkap</label>
                <input
                    id="nama_lengkap"
                    name="nama_lengkap"
                    type="text"
                    value="{{ old('nama_lengkap', $account->nama_lengkap) }}"
                    maxlength="100"
                    required
                >
            </div>

            <div class="users-field">
                <label for="username">Username</label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    value="{{ old('username', $account->username) }}"
                    maxlength="50"
                    pattern="[a-zA-Z0-9._-]+"
                    autocomplete="off"
                    spellcheck="false"
                    required
                >
            </div>

            <div class="users-field">
                <label for="id_ulp">ULP</label>
                <select id="id_ulp" name="id_ulp" required>
                    <option value="">Pilih ULP</option>

                    @foreach ($ulps as $ulp)
                        <option
                            value="{{ $ulp->id_ulp }}"
                            @selected(
                                (string) old('id_ulp', $account->id_ulp) ===
                                (string) $ulp->id_ulp
                            )
                        >
                            {{ $ulp->nama_ulp }}
                            {{ $ulp->status !== 'aktif' ? '(nonaktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="users-field">
                <label for="status">Status akun</label>
                <select id="status" name="status" required>
                    <option
                        value="aktif"
                        @selected(old('status', $account->status) === 'aktif')
                    >
                        Aktif
                    </option>
                    <option
                        value="nonaktif"
                        @selected(old('status', $account->status) === 'nonaktif')
                    >
                        Nonaktif
                    </option>
                </select>
            </div>

            <div class="users-field">
                <label for="account-password">
                    {{ $account->exists ? 'Password baru' : 'Password' }}
                </label>
                <input
                    id="account-password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    minlength="8"
                    @required(!$account->exists)
                >
                <small>
                    Minimal 8 karakter.
                    @if ($account->exists)
                        Kosongkan untuk mempertahankan password.
                    @endif
                </small>
            </div>

            <div class="users-field">
                <label for="password_confirmation">Konfirmasi password</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    minlength="8"
                    @required(!$account->exists)
                >
            </div>
        </div>

        <div class="users-form-actions">
            <a href="{{ route('users') }}" class="users-button">
                Batal
            </a>

            <button type="submit" class="users-button is-primary">
                Simpan
            </button>
        </div>
    </form>
</section>
@endsection