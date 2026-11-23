<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;

class WebLogin
{
    private const COOKIE = 'ipms_remember';

    private const DAYS = 30;

    private static function fingerprint(User $user): string
    {
        return hash_hmac(
            'sha256',
            (string) $user->password,
            (string) config('app.key')
        );
    }

    private static function token(Request $request): ?string
    {
        $token = $request->cookie(self::COOKIE);

        if (
            !is_string($token) ||
            !preg_match('/\A[a-f0-9]{64}\z/', $token)
        ) {
            return null;
        }

        return $token;
    }

    private static function cacheKey(string $token): string
    {
        return 'web-remember:' . hash('sha256', $token);
    }

    public static function sync(Request $request, User $user): void
    {
        $request->session()->put('user', [
            'id_user' => $user->id_user,
            'id_ulp' => $user->id_ulp,
            'username' => $user->username,
            'nama_lengkap' => $user->nama_lengkap,
            'role' => $user->role,
            'status' => $user->status,
            'can_manage_users' => $user->role === 'admin',
            'can_import_targets' => $user->role === 'admin',
            'can_import_realisasi' => $user->role === 'ulp',
            'can_view_all_units' => $user->role === 'admin',
        ]);

        $request->session()->put(
            'web_password_fingerprint',
            self::fingerprint($user)
        );
    }

    public static function resolve(Request $request): ?User
    {
        $userId = $request->session()->get('user.id_user');

        if ($userId) {
            $user = User::query()
                ->where('id_user', $userId)
                ->where('status', 'aktif')
                ->first();

            $fingerprint = $request->session()->get(
                'web_password_fingerprint'
            );

            if (
                !$user ||
                (
                    $fingerprint !== null &&
                    (
                        !is_string($fingerprint) ||
                        !hash_equals(
                            self::fingerprint($user),
                            $fingerprint
                        )
                    )
                )
            ) {
                self::forget($request);

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return null;
            }

            self::sync($request, $user);

            return $user;
        }

        $token = self::token($request);

        if (!$token) {
            return null;
        }

        $record = Cache::store('file')->get(
            self::cacheKey($token)
        );

        if (
            !is_array($record) ||
            !isset(
                $record['user_id'],
                $record['fingerprint'],
                $record['expires_at']
            ) ||
            (int) $record['expires_at'] <= time()
        ) {
            self::forget($request);

            return null;
        }

        $user = User::query()
            ->where('id_user', $record['user_id'])
            ->where('status', 'aktif')
            ->first();

        if (
            !$user ||
            !is_string($record['fingerprint']) ||
            !hash_equals(
                self::fingerprint($user),
                $record['fingerprint']
            )
        ) {
            self::forget($request);

            return null;
        }

        $request->session()->regenerate();
        self::sync($request, $user);

        return $user;
    }

    public static function remember(Request $request, User $user): void
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = now()->addDays(self::DAYS);

        Cache::store('file')->put(
            self::cacheKey($token),
            [
                'user_id' => $user->id_user,
                'fingerprint' => self::fingerprint($user),
                'expires_at' => $expiresAt->getTimestamp(),
            ],
            $expiresAt
        );

        Cookie::queue(Cookie::make(
            self::COOKIE,
            $token,
            self::DAYS * 24 * 60,
            '/',
            config('session.domain'),
            $request->isSecure() ||
                (bool) config('session.secure', false),
            true,
            false,
            'lax'
        ));
    }

    public static function forget(Request $request): void
    {
        $token = self::token($request);

        if ($token) {
            Cache::store('file')->forget(
                self::cacheKey($token)
            );
        }

        Cookie::queue(Cookie::forget(
            self::COOKIE,
            '/',
            config('session.domain')
        ));
    }
}