<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MobileAccountNotificationController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->getKey();

        $rows = DB::table('mobile_account_notifications')
            ->where('id_user', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $data = $rows->map(function ($row) {
            return [
                'id' => $row->id,
                'type' => $row->type,
                'title' => $row->title,
                'message' => $row->message,
                'read_at' => $row->read_at === null
                    ? null
                    : Carbon::parse($row->read_at)->toIso8601String(),
                'created_at' => Carbon::parse($row->created_at)
                    ->toIso8601String(),
            ];
        });

        return response()->json([
    'success' => true,
    'data' => $data,
    'unread_count' => DB::table('mobile_account_notifications')
        ->where('id_user', $userId)
        ->whereNull('read_at')
        ->count(),
]);
    }

    public function markRead(Request $request, string $notification)
    {
        $query = DB::table('mobile_account_notifications')
            ->where('id_user', $request->user()->getKey())
            ->where('id', $notification);

        abort_unless(
            (clone $query)->exists(),
            404,
            'Notifikasi tidak ditemukan.'
        );

        $query->whereNull('read_at')->update([
            'read_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca.',
        ]);
    }
}