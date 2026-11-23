<?php

namespace App\Http\Controllers;

use App\Models\Ulp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim($validated['q'] ?? '');

        $users = User::query()
            ->leftJoin('ulp', 'users.id_ulp', '=', 'ulp.id_ulp')
            ->select([
                'users.id_user',
                'users.username',
                'users.nama_lengkap',
                'users.role',
                'users.status',
                'ulp.nama_ulp',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($filter) use ($search) {
                    $filter
                        ->where('users.username', 'like', '%' . $search . '%')
                        ->orWhere(
                            'users.nama_lengkap',
                            'like',
                            '%' . $search . '%'
                        );
                });
            })
            ->orderBy('users.nama_lengkap')
            ->orderBy('users.id_user')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('users.form', [
            'account' => new User([
                'role' => 'ulp',
                'status' => 'aktif',
            ]),
            'ulps' => Ulp::query()
                ->where('status', 'aktif')
                ->orderBy('nama_ulp')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only([
                    'nama_lengkap',
                    'username',
                    'id_ulp',
                    'status',
                ]));
        }

        $data = $validator->validated();

        // Role ditentukan server, bukan dari input form.
        $data['role'] = 'ulp';

        // Model User bawaan sudah memiliki cast password => hashed.
        User::create($data);

        return redirect()
            ->route('users')
            ->with('success', 'Akun ULP berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        $this->ensureEditable($user);

        return view('users.form', [
            'account' => $user,
            'ulps' => Ulp::query()
                ->where(function ($query) use ($user) {
                    $query
                        ->where('status', 'aktif')
                        ->orWhere('id_ulp', $user->id_ulp);
                })
                ->orderBy('nama_ulp')
                ->get(),
        ]);
    }

    public function update(
        Request $request,
        User $user
    ): RedirectResponse {
        $this->ensureEditable($user);

        $validator = $this->validator($request, $user);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only([
                    'nama_lengkap',
                    'username',
                    'id_ulp',
                    'status',
                ]));
        }

        $data = $validator->validated();

        // Password kosong berarti mempertahankan password sebelumnya.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        // Role tidak termasuk data yang boleh diperbarui.
\Illuminate\Support\Facades\DB::transaction(
    function () use ($user, $data) {
        $account = User::query()
            ->whereKey($user->id_user)
            ->lockForUpdate()
            ->firstOrFail();

        $this->ensureEditable($account);

        $revokeTokens =
            isset($data['password'])
            || $data['status'] !== 'aktif'
            || (int) $account->id_ulp !== (int) $data['id_ulp'];

        $account->update($data);

        if ($revokeTokens) {
            $account->tokens()->delete();
        }
    },
    3
);

        return redirect()
            ->route('users')
            ->with('success', 'Akun ULP berhasil diperbarui.');
    }
public function deleteConfirmation(User $user): View
{
    $this->ensureEditable($user);

    abort_if(
        (string) $user->id_user ===
            (string) session('user.id_user'),
        403,
        'Akun yang sedang digunakan tidak boleh dihapus.'
    );

    return view('users.delete', [
        'account' => $user,
        'unitName' => Ulp::query()
            ->where('id_ulp', $user->id_ulp)
            ->value('nama_ulp'),
    ]);
}

public function destroy(
    Request $request,
    User $user
): RedirectResponse {
    $this->ensureEditable($user);

    $validated = $request->validate([
        'confirm_username' => ['required', 'string', 'max:50'],
    ], [
        'confirm_username.required' =>
            'Ketik username akun untuk mengonfirmasi penghapusan.',
        'confirm_username.string' =>
            'Konfirmasi username tidak valid.',
        'confirm_username.max' =>
            'Konfirmasi username maksimal 50 karakter.',
    ]);

    try {
        \Illuminate\Support\Facades\DB::transaction(
            function () use ($request, $user, $validated) {
                $account = User::query()
                    ->where('id_user', $user->id_user)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensureEditable($account);

                abort_if(
                    (string) $account->id_user ===
                        (string) $request->session()->get('user.id_user'),
                    403,
                    'Akun yang sedang digunakan tidak boleh dihapus.'
                );

                if ($account->status !== 'nonaktif') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'account' =>
                            'Nonaktifkan akun melalui halaman Edit sebelum menghapusnya.',
                    ]);
                }

                if (
                    $validated['confirm_username'] !==
                    $account->username
                ) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'confirm_username' =>
                            'Username konfirmasi tidak sama dengan akun yang akan dihapus.',
                    ]);
                }

                // Hapus token milik akun, bukan token pengguna lain.
                $account->tokens()->delete();

                // Hanya menghapus baris pengguna.
                $account->delete();
            }
        );
    } catch (\Illuminate\Database\QueryException $exception) {
        report($exception);

        return back()->with(
            'error',
            'Akun gagal dihapus. Periksa keterkaitan data dan log Laravel.'
        );
    }

    return redirect()
        ->route('users')
        ->with('success', 'Akun ULP berhasil dihapus.');
}
    private function ensureEditable(User $user): void
{
    abort_unless(
        $user->role === 'ulp',
        403,
        'Form ini hanya digunakan untuk mengelola akun ULP.'
    );
}

    private function validator(Request $request, ?User $user = null)
    {
        $usernameRule = Rule::unique('users', 'username');

        if ($user) {
            $usernameRule->ignore($user->id_user, 'id_user');
        }

        $input = $request->only([
            'nama_lengkap',
            'username',
            'id_ulp',
            'status',
            'password',
            'password_confirmation',
        ]);

        foreach (['nama_lengkap', 'username'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $input[$field] = trim($input[$field]);
            }
        }

        $validator = Validator::make($input, [
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/\A[a-zA-Z0-9._-]+\z/',
                $usernameRule,
            ],
            'id_ulp' => [
                'required',
                'integer',
                Rule::exists('ulp', 'id_ulp'),
            ],
            'status' => [
                'required',
                Rule::in(['aktif', 'nonaktif']),
            ],
            'password' => [
                $user ? 'nullable' : 'required',
                'string',
                'min:8',
                'confirmed',
                function ($attribute, $value, $fail) {
                    if (is_string($value) && strlen($value) > 72) {
                        $fail('Password maksimal 72 byte.');
                    }
                },
            ],
        ], [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'integer' => ':attribute tidak valid.',
            'max' => ':attribute maksimal :max karakter.',
            'username.regex' =>
                'Username hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda hubung.',
            'username.unique' => 'Username sudah digunakan.',
            'id_ulp.exists' => 'ULP yang dipilih tidak tersedia.',
            'status.in' => 'Status harus aktif atau nonaktif.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ], [
            'nama_lengkap' => 'Nama lengkap',
            'username' => 'Username',
            'id_ulp' => 'ULP',
            'status' => 'Status',
            'password' => 'Password',
        ]);

        $validator->after(function ($validator) use ($input, $user) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $unit = Ulp::query()->find($input['id_ulp']);

            // Unit nonaktif hanya boleh dipertahankan pada akun
            // lama yang juga disimpan dalam keadaan nonaktif.
            $keepingInactiveUnit = $user &&
                (int) $user->id_ulp === (int) $unit->id_ulp &&
                $input['status'] === 'nonaktif';

            if ($unit->status !== 'aktif' && !$keepingInactiveUnit) {
                $validator->errors()->add(
                    'id_ulp',
                    'Pilih ULP aktif untuk akun ini.'
                );
            }

            if (
                $user &&
                (string) $user->id_user ===
                    (string) session('user.id_user') &&
                $input['status'] !== 'aktif'
            ) {
                $validator->errors()->add(
                    'status',
                    'Akun yang sedang digunakan tidak boleh dinonaktifkan.'
                );
            }
        });

        return $validator;
    }
}