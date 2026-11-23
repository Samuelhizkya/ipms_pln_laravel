@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
<section class="card users-workspace">
    <div class="users-header">
        <div class="users-heading">
            <h2>Manajemen User</h2>
            <p>Daftar akun pengguna IPMS PLN UP3 Palu.</p>
        </div>

        <a href="{{ route('users.create') }}" class="users-button is-primary">
            Tambah Akun ULP
        </a>
    </div>


    <form method="GET" action="{{ route('users') }}" class="users-search">
        <div>
            <label for="user-search">Cari pengguna</label>
            <input
                id="user-search"
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="Nama atau username..."
                maxlength="100"
            >
        </div>

        <button type="submit">Cari</button>

        @if ($search !== '')
            <a href="{{ route('users') }}">Reset</a>
        @endif
    </form>

    <div
        class="users-table-wrapper"
        role="region"
        aria-label="Daftar pengguna"
        tabindex="0"
    >
        <table>
            <thead>
                <tr>
                    <th scope="col">No.</th>
                    <th scope="col">Nama Lengkap</th>
                    <th scope="col">Username</th>
                    <th scope="col">ULP</th>
                    <th scope="col">Role</th>
                    <th scope="col">Status</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $users->firstItem() + $loop->index }}</td>
                        <td>{{ $user->nama_lengkap }}</td>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->nama_ulp ?? 'Tidak terhubung ke ULP' }}</td>
                        <td>{{ $user->role }}</td>
                        <td>
    {{ $user->status === 'aktif' ? 'Aktif' : 'Nonaktif' }}
</td>
                        <td>
    @if ($user->role === 'ulp')
        <div class="users-row-actions">
            <a
                href="{{ route('users.edit', $user->id_user) }}"
                class="users-button"
            >
                Edit
            </a>

            <a
                href="{{ route('users.delete', $user->id_user) }}"
                class="users-button is-danger-outline"
            >
                Hapus
            </a>
        </div>
    @else
        —
    @endif
</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="users-empty">
                            Tidak ada pengguna yang sesuai.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="users-pagination">
        {{ $users->onEachSide(1)->links('components.pagination') }}
    </div>
</section>
@endsection