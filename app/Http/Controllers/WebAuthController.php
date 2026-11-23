<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\WebLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class WebAuthController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        // Periksa session serta cookie "Ingat Saya".
        // Status akun selalu diperiksa kembali dari database.
        $user = WebLogin::resolve($request);

        if ($user) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $input = $request->only([
            'username',
            'password',
            'remember',
        ]);

        if (isset($input['username']) && is_string($input['username'])) {
            $input['username'] = trim($input['username']);
        }

        $validator = Validator::make(
            $input,
            [
                'username' => ['required', 'string', 'max:50'],
                'password' => ['required', 'string', 'max:4096'],
                'remember' => ['sometimes', 'boolean'],
            ],
            [
                'username.required' => 'Username wajib diisi.',
                'username.string' => 'Username tidak valid.',
                'username.max' => 'Username maksimal 50 karakter.',
                'password.required' => 'Password wajib diisi.',
                'password.string' => 'Password tidak valid.',
                'password.max' => 'Password terlalu panjang.',
                'remember.boolean' => 'Pilihan Ingat Saya tidak valid.',
            ]
        );

        // Password tidak dimasukkan ke flash session.
        $oldInput = [
            'username' => is_string($input['username'] ?? null)
                ? $input['username']
                : '',
            'remember' => $request->boolean('remember') ? '1' : '0',
        ];

        if ($validator->fails()) {
            return redirect()
                ->route('login')
                ->withErrors($validator)
                ->withInput($oldInput);
        }

        $credentials = $validator->validated();

        $rateLimitKey = 'web-login:' . hash(
            'sha256',
            mb_strtolower($credentials['username'])
                . '|'
                . $request->ip()
        );

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    "Terlalu banyak percobaan login. "
                    . "Coba kembali dalam {$seconds} detik."
                )
                ->withInput($oldInput);
        }

        $user = User::query()
            ->where('username', $credentials['username'])
            ->where('status', 'aktif')
            ->first();

        if (
            !$user ||
            !Hash::check($credentials['password'], $user->password)
        ) {
            RateLimiter::hit($rateLimitKey, 60);

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Username atau password salah, atau akun tidak aktif.'
                )
                ->withInput($oldInput);
        }

        RateLimiter::clear($rateLimitKey);

        // Hapus token pengingat sebelumnya dari browser ini.
        WebLogin::forget($request);

        // Buat session login baru.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Simpan identitas serta fingerprint password melalui satu fungsi.
        WebLogin::sync($request, $user);

        if ($request->boolean('remember')) {
            WebLogin::remember($request, $user);
        }

        return redirect()->route('dashboard');
    }

    public function success(): RedirectResponse
    {
        // Pertahankan kompatibilitas route /login/berhasil.
        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        // Hapus cookie dan catatan token "Ingat Saya".
        WebLogin::forget($request);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Anda berhasil keluar dari aplikasi.');
    }
}