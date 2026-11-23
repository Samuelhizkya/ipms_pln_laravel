<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MobileAccountNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class MobileAccountController extends Controller
{
    private function publicUser(User $user): array
    {
        return $user->only([
            'id_user',
            'id_ulp',
            'username',
            'nama_lengkap',
            'role',
            'status',
        ]);
    }

    private function checkPassword(User $user, string $password): void
    {
        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }
    }

    public function updateProfile(Request $request)
    {
        $request->merge([
            'nama_lengkap' => trim((string) $request->input('nama_lengkap')),
            'username' => trim((string) $request->input('username')),
        ]);

        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')
                    ->ignore($request->user()->id_user, 'id_user'),
            ],
            'current_password' => ['required', 'string'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max' => 'Nama lengkap maksimal 100 karakter.',
            'username.required' => 'Username wajib diisi.',
            'username.max' => 'Username maksimal 50 karakter.',
            'username.regex' =>
                'Username hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda minus.',
            'username.unique' => 'Username sudah digunakan akun lain.',
            'current_password.required' =>
                'Masukkan password saat ini untuk menyimpan perubahan.',
        ]);

        $user = DB::transaction(function () use ($request, $data) {
            $user = User::whereKey($request->user()->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $user->status === 'aktif'
                    && in_array($user->role, ['admin', 'ulp'], true),
                403,
                'Akun tidak memiliki akses.'
            );

            $this->checkPassword($user, $data['current_password']);

            // Hanya dua atribut ini yang boleh diubah melalui profil.
$user->nama_lengkap = $data['nama_lengkap'];
$user->username = $data['username'];

$profileChanged = $user->isDirty([
    'nama_lengkap',
    'username',
]);

$user->save();

if ($profileChanged) {
    MobileAccountNotificationService::record(
        $user,
        'profile_updated',
        'Profil berhasil diperbarui',
        'Nama lengkap atau username akun Anda telah diperbarui melalui aplikasi.'
    );
}

return $user;
        });

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => $this->publicUser($user),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:64',
                'confirmed',
                'different:current_password',
            ],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.max' => 'Password baru maksimal 64 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.different' =>
                'Password baru harus berbeda dari password saat ini.',
        ]);

        $token = $request->user()->currentAccessToken();

        abort_unless(
            $token instanceof PersonalAccessToken,
            400,
            'Gunakan token API untuk mengubah password.'
        );

        DB::transaction(function () use ($request, $data, $token) {
            $user = User::whereKey($request->user()->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $user->status === 'aktif'
                    && in_array($user->role, ['admin', 'ulp'], true),
                403,
                'Akun tidak memiliki akses.'
            );

            $this->checkPassword($user, $data['current_password']);

            // Batasi panjang byte agar sesuai dengan bcrypt.
            if (strlen($data['password']) > 72) {
                throw ValidationException::withMessages([
                    'password' =>
                        'Password terlalu panjang. Gunakan kombinasi yang lebih pendek.',
                ]);
            }

            $user->password = Hash::make($data['password']);
            $user->save();

            // Pertahankan token perangkat yang melakukan perubahan.
            // Token API perangkat lain dicabut.
            $user->tokens()
                ->where('id', '!=', $token->getKey())
                ->delete();
                MobileAccountNotificationService::record(
    $user,
    'password_updated',
    'Password berhasil diubah',
    'Password akun Anda telah diubah. Token API perangkat lain telah dicabut.'
);
        });

        return response()->json([
            'success' => true,
            'message' =>
                'Password berhasil diperbarui. Token API perangkat lain telah dicabut.',
        ]);
    }
}