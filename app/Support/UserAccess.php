<?php

namespace App\Support;

use App\Models\User;

class UserAccess
{
    public static function isPerencanaan(User $user): bool
    {
        if ($user->status !== 'aktif') {
            return false;
        }

        $allowedIds = array_map(
            'strval',
            config('access.perencanaan_user_ids', [])
        );

        return in_array(
            (string) $user->id_user,
            $allowedIds,
            true
        );
    }
}