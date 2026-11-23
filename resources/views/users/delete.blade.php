@extends('layouts.app')

@section('title', 'Hapus Akun ULP')

@section('content')
<section class="card users-form-card">
    <div class="users-heading">
        <h2>Hapus Akun ULP</h2>
        <p>Periksa identitas akun sebelum melanjutkan.</p>
    </div>

    <dl class="users-delete-summary">
        <div>
            <dt>Nama lengkap</dt>
            <dd>{{ $account->nama_lengkap }}</dd>
        </div>

        <div>
            <dt>Username</dt>
            <dd>{{ $account->username }}</dd>
        </div>

        <div>
            <dt>ULP</dt>
            <dd>{{ $unitName ?? 'Tidak terhubung ke ULP' }}</dd>
        </div>

        <div>
            <dt>Status</dt>
            <dd>{{ $account->status }}</dd>
        </div>
    </dl>

    @if ($account->status !== 'nonaktif')
        <div class="users-error" role="alert">
            Akun masih aktif. Nonaktifkan melalui halaman Edit
            sebelum melakukan penghapusan.
        </div>

        <div class="users-form-actions">
            <a href="{{ route('users') }}" class="users-button">
                Kembali
            </a>

            <a
                href="{{ route('users.edit', $account->id_user) }}"
                class="users-button is-primary"
            >
                Edit Akun
            </a>
        </div>
    @else
        <p class="users-delete-note">
            Penghapusan akan menghapus akun dan token API miliknya.
            Data ULP, target, dan realisasi tetap dipertahankan.
            Akun yang dihapus tidak dapat dipulihkan melalui halaman ini.
        </p>

        <form
            method="POST"
            action="{{ route('users.destroy', $account->id_user) }}"
            class="users-form"
            data-confirm-delete="Akun {{ $account->username }} dan token API miliknya akan dihapus. Data KPI tetap dipertahankan."
        >
            @csrf
            @method('DELETE')

            <div class="users-field">
                <label for="confirm_username">
                    Ketik username
                    <strong>{{ $account->username }}</strong>
                    untuk mengonfirmasi
                </label>

                <input
                    id="confirm_username"
                    name="confirm_username"
                    type="text"
                    maxlength="50"
                    autocomplete="off"
                    spellcheck="false"
                    required
                >
            </div>

            <div class="users-form-actions">
                <a href="{{ route('users') }}" class="users-button">
                    Batal
                </a>

                <button type="submit" class="users-button is-danger">
                    Hapus Akun Permanen
                </button>
            </div>
        </form>
    @endif
</section>
@endsection