<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobileAccountNotificationService
{
    public static function record(
        User $user,
        string $type,
        string $title,
        string $message
    ): void {
        DB::table('mobile_account_notifications')->insert([
            'id' => (string) Str::uuid(),
            'id_user' => $user->getKey(),
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}