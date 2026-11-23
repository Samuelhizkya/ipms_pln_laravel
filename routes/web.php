<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UlpController;
use App\Http\Controllers\TargetKpiController;
use App\Http\Controllers\RealisasiKpiController;



Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/ulp', [UlpController::class, 'index'])
    ->middleware([
        \App\Http\Middleware\WebAuthMiddleware::class,
        \App\Http\Middleware\AdminMiddleware::class,
    ]);
/*
|--------------------------------------------------------------------------
| Impor Target KPI khusus Admin
|--------------------------------------------------------------------------
*/

Route::middleware([
    \App\Http\Middleware\WebAuthMiddleware::class,
    \App\Http\Middleware\AdminMiddleware::class,
])->group(function () {
    Route::get(
        '/target-kpi/import',
        [TargetKpiController::class, 'create']
    )->name('target-kpi.import.form');

    Route::post(
        '/target-kpi/import',
        [TargetKpiController::class, 'import']
    )->name('target-kpi.import');
});
    /*
|--------------------------------------------------------------------------
| Impor realisasi melalui browser
|--------------------------------------------------------------------------
*/

Route::middleware([
    \App\Http\Middleware\WebAuthMiddleware::class,
    \App\Http\Middleware\UlpAccessMiddleware::class,
])->group(function () {
    Route::get(
        '/realisasi-kpi/import',
        [\App\Http\Controllers\RealisasiKpiController::class, 'create']
    )->name('realisasi-kpi.import.form');

    Route::post(
        '/realisasi-kpi/import',
        [\App\Http\Controllers\RealisasiKpiController::class, 'import']
    )->name('realisasi-kpi.import');
});
    /*
|--------------------------------------------------------------------------
| Login melalui browser
|--------------------------------------------------------------------------
*/

Route::get(
    '/login',
    [\App\Http\Controllers\WebAuthController::class, 'index']
)->name('login');

Route::post(
    '/login',
    [\App\Http\Controllers\WebAuthController::class, 'login']
)->name('login.process');

Route::post(
    '/logout',
    [\App\Http\Controllers\WebAuthController::class, 'logout']
)->name('logout');

Route::middleware([
    \App\Http\Middleware\WebAuthMiddleware::class,
])->group(function () {
    Route::get(
        '/login/berhasil',
        [\App\Http\Controllers\WebAuthController::class, 'success']
    )->name('login.success');
});
/*
|--------------------------------------------------------------------------
| Halaman KPI melalui browser
|--------------------------------------------------------------------------
*/

Route::middleware([
    \App\Http\Middleware\WebAuthMiddleware::class,
])->group(function () {
    Route::view('/target-kpi', 'kpi.target')
        ->name('target.kpi');

    Route::view('/realisasi-kpi', 'kpi.realisasi')
        ->name('realisasi.kpi');
});
/*
|--------------------------------------------------------------------------
| Data halaman KPI
|--------------------------------------------------------------------------
|
| Memakai controller API bawaan melalui session browser.
|
*/

Route::prefix('data')
    ->middleware([
        \App\Http\Middleware\WebAuthMiddleware::class,
        \App\Http\Middleware\UlpAccessMiddleware::class,
    ])
    ->group(function () {
        Route::get(
            '/master-kpi',
            [\App\Http\Controllers\Api\MasterKpiController::class, 'index']
        )->name('data.master-kpi');

        Route::get(
            '/target-kpi',
            [\App\Http\Controllers\Api\TargetKpiController::class, 'index']
        )->name('data.target-kpi');

        Route::get('/unit-akun', function (
            \Illuminate\Http\Request $request
        ) {
            $unit = $request->attributes->get('account_ulp');

            return response()->json([
                'success' => true,
                'data' => [
                    'id_ulp' => $unit->id_ulp,
                    'nama_ulp' => $unit->nama_ulp,
                ],
            ]);
        })->name('data.unit-akun');
    });
    /*
|--------------------------------------------------------------------------
| Data realisasi untuk akun ULP
|--------------------------------------------------------------------------
*/

Route::get(
    '/data/realisasi-kpi',
    [
        \App\Http\Controllers\Api\RealisasiKpiController::class,
        'index',
    ]
)
    ->middleware([
        \App\Http\Middleware\WebAuthMiddleware::class,
        \App\Http\Middleware\RealisasiReadAccessMiddleware::class,
    ])
    ->name('data.realisasi-kpi');

/*
|--------------------------------------------------------------------------
| Manajemen User khusus Admin
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Manajemen User khusus Admin
|--------------------------------------------------------------------------
*/

Route::middleware([
    \App\Http\Middleware\WebAuthMiddleware::class,
    \App\Http\Middleware\AdminMiddleware::class,
])->group(function () {
    Route::get(
        '/users',
        [\App\Http\Controllers\UserManagementController::class, 'index']
    )->name('users');

    Route::get(
        '/users/create',
        [\App\Http\Controllers\UserManagementController::class, 'create']
    )->name('users.create');

    Route::post(
        '/users',
        [\App\Http\Controllers\UserManagementController::class, 'store']
    )->name('users.store');

    Route::get(
        '/users/{user}/edit',
        [\App\Http\Controllers\UserManagementController::class, 'edit']
    )->whereNumber('user')->name('users.edit');

    Route::put(
        '/users/{user}',
        [\App\Http\Controllers\UserManagementController::class, 'update']
    )->whereNumber('user')->name('users.update');
        Route::get(
        '/users/{user}/delete',
        [
            \App\Http\Controllers\UserManagementController::class,
            'deleteConfirmation',
        ]
    )->whereNumber('user')->name('users.delete');

    Route::delete(
        '/users/{user}',
        [
            \App\Http\Controllers\UserManagementController::class,
            'destroy',
        ]
    )->whereNumber('user')->name('users.destroy');
});
/*
|--------------------------------------------------------------------------
| Profil akun yang sedang login
|--------------------------------------------------------------------------
*/

Route::get(
    '/profil',
    [\App\Http\Controllers\ProfileController::class, 'index']
)
    ->middleware([
        \App\Http\Middleware\WebAuthMiddleware::class,
    ])
    ->name('profile');
    /*
|--------------------------------------------------------------------------
| Pilihan unit dan data halaman Target KPI
|--------------------------------------------------------------------------
*/

Route::middleware([
    \App\Http\Middleware\WebAuthMiddleware::class,
])->group(function () {
    Route::get('/data/target/units', function (
        \Illuminate\Http\Request $request
    ) {
        $role = $request->session()->get('user.role');

        abort_unless(
            in_array($role, ['admin', 'ulp'], true),
            403,
            'Anda tidak memiliki akses ke data target KPI.'
        );

        $query = \App\Models\Ulp::query()
            ->where('status', 'aktif')
            ->orderBy('nama_ulp');

        if ($role === 'ulp') {
            $unitId = $request->session()->get('user.id_ulp');

            abort_unless(
                $unitId,
                403,
                'Akun belum memiliki ULP. Hubungi Admin.'
            );

            $query->where('id_ulp', $unitId);
        }

        $units = $query->get(['id_ulp', 'nama_ulp']);

        abort_if(
            $role === 'ulp' && $units->isEmpty(),
            403,
            'ULP akun tidak tersedia atau tidak aktif.'
        );

        return response()->json([
            'success' => true,
            'data' => [
                'can_select_unit' => $role === 'admin',
                'units' => $units,
            ],
        ]);
    })->name('data.target.units');

    Route::middleware([
        \App\Http\Middleware\TargetAccessMiddleware::class,
    ])->group(function () {
        Route::get(
            '/data/target/master',
            [\App\Http\Controllers\Api\MasterKpiController::class, 'index']
        )->name('data.target.master');

        Route::get(
            '/data/target/rows',
            [\App\Http\Controllers\Api\TargetKpiController::class, 'index']
        )->name('data.target.rows');
    });
});
/*
|--------------------------------------------------------------------------
| Edit cepat Target KPI khusus Admin
|--------------------------------------------------------------------------
*/

Route::middleware([
    \App\Http\Middleware\WebAuthMiddleware::class,
    \App\Http\Middleware\AdminMiddleware::class,
])->group(function () {
    Route::get(
        '/target-kpi/{target}/edit-data',
        [\App\Http\Controllers\TargetKpiEditController::class, 'show']
    )
        ->whereNumber('target')
        ->name('target-kpi.edit-data');

    Route::put(
        '/target-kpi/{target}',
        [\App\Http\Controllers\TargetKpiEditController::class, 'update']
    )
        ->whereNumber('target')
        ->name('target-kpi.update');
});
/*
|--------------------------------------------------------------------------
| Edit Realisasi KPI oleh ULP pemilik data
|--------------------------------------------------------------------------
*/

Route::middleware([
    \App\Http\Middleware\WebAuthMiddleware::class,
    \App\Http\Middleware\UlpAccessMiddleware::class,
])->group(function () {
    Route::get(
        '/realisasi-kpi/{realisasi}/edit',
        [
            \App\Http\Controllers\RealisasiKpiEditController::class,
            'edit',
        ]
    )
        ->whereNumber('realisasi')
        ->name('realisasi-kpi.edit');

    Route::put(
        '/realisasi-kpi/{realisasi}',
        [
            \App\Http\Controllers\RealisasiKpiEditController::class,
            'update',
        ]
    )
        ->whereNumber('realisasi')
        ->name('realisasi-kpi.update');
});
/*
|--------------------------------------------------------------------------
| Daftar Master KPI khusus Admin
|--------------------------------------------------------------------------
*/

Route::get(
    '/master-kpi',
    [
        \App\Http\Controllers\MasterKpiManagementController::class,
        'index',
    ]
)
    ->middleware([
        \App\Http\Middleware\WebAuthMiddleware::class,
        \App\Http\Middleware\AdminMiddleware::class,
    ])
    ->name('master-kpi.index');
    /*
|--------------------------------------------------------------------------
| Tambah dan edit Master KPI khusus Admin
|--------------------------------------------------------------------------
*/

Route::middleware([
    \App\Http\Middleware\WebAuthMiddleware::class,
    \App\Http\Middleware\AdminMiddleware::class,
])->group(function () {
    Route::get(
        '/master-kpi/create',
        [\App\Http\Controllers\MasterKpiFormController::class, 'create']
    )->name('master-kpi.create');

    Route::post(
        '/master-kpi',
        [\App\Http\Controllers\MasterKpiFormController::class, 'store']
    )->name('master-kpi.store');

    Route::get(
        '/master-kpi/{kpi}/edit',
        [\App\Http\Controllers\MasterKpiFormController::class, 'edit']
    )->whereNumber('kpi')->name('master-kpi.edit');

    Route::put(
        '/master-kpi/{kpi}',
        [\App\Http\Controllers\MasterKpiFormController::class, 'update']
    )->whereNumber('kpi')->name('master-kpi.update');
});

/*
|--------------------------------------------------------------------------
| Halaman Admin Master - hasil integrasi Orang 3
|--------------------------------------------------------------------------
*/
Route::middleware([
    \App\Http\Middleware\WebAuthMiddleware::class,
    \App\Http\Middleware\AdminMiddleware::class,
])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
        ->name('admin.dashboard');

    Route::get('/monitoring', [\App\Http\Controllers\MonitoringController::class, 'index'])
        ->name('admin.monitoring');

    Route::get('/statistik', [\App\Http\Controllers\StatistikController::class, 'index'])
        ->name('admin.statistik');

    Route::get('/infografis', [\App\Http\Controllers\InfografisController::class, 'index'])
        ->name('admin.infografis');

    Route::get('/laporan', [\App\Http\Controllers\LaporanController::class, 'index'])
        ->name('admin.laporan');
});

/*
|--------------------------------------------------------------------------
| Ranking ULP - dapat diakses Admin dan User ULP
|--------------------------------------------------------------------------
*/
Route::get('/ranking', [\App\Http\Controllers\RankingController::class, 'index'])
    ->middleware([
        \App\Http\Middleware\WebAuthMiddleware::class,
    ])
    ->name('ranking.index');


/* Data halaman monitoring Admin menggunakan sesi login browser. */
Route::prefix('data/monitoring')->name('data.monitoring.')
    ->middleware([
        \App\Http\Middleware\WebAuthMiddleware::class,
        \App\Http\Middleware\AdminMiddleware::class,
    ])->group(function () {
        Route::get('/ulp', [\App\Http\Controllers\MonitoringController::class, 'units'])->name('units');
        Route::get('/master-kpi', [\App\Http\Controllers\Api\MasterKpiController::class, 'index'])->name('master');
        Route::get('/target-kpi', [\App\Http\Controllers\Api\TargetKpiController::class, 'index'])->name('targets');
        Route::get('/realisasi-kpi', [\App\Http\Controllers\MonitoringController::class, 'realisasi'])->name('realisasi');
    });

    Route::get('/beranda', function (\Illuminate\Http\Request $request) {
    $user = \App\Support\WebLogin::resolve($request);

    abort_unless(
        $user && in_array($user->role, ['admin', 'ulp'], true),
        403,
        'Role pengguna tidak memiliki akses.'
    );

    return redirect()->route(
        $user->role === 'admin'
            ? 'admin.dashboard'
            : 'ulp.dashboard'
    );
})
    ->middleware(\App\Http\Middleware\WebAuthMiddleware::class)
    ->name('dashboard');

Route::get(
    '/ulp/dashboard',
    [\App\Http\Controllers\UlpDashboardController::class, 'index']
)
    ->middleware(\App\Http\Middleware\WebAuthMiddleware::class)
    ->name('ulp.dashboard');

    Route::get(
    '/ulp/ranking',
    [\App\Http\Controllers\UlpRankingController::class, 'index']
)
    ->middleware(\App\Http\Middleware\WebAuthMiddleware::class)
    ->name('ulp.ranking');

    Route::get(
    '/ulp/monitoring',
    [\App\Http\Controllers\UlpMonitoringController::class, 'index']
)
    ->middleware(\App\Http\Middleware\WebAuthMiddleware::class)
    ->name('ulp.monitoring');

    Route::get(
    '/ulp/statistik',
    [\App\Http\Controllers\UlpStatistikController::class, 'index']
)
    ->middleware(\App\Http\Middleware\WebAuthMiddleware::class)
    ->name('ulp.statistik');

    Route::get(
    '/ulp/infografis',
    [\App\Http\Controllers\UlpInfografisController::class, 'index']
)
    ->middleware(\App\Http\Middleware\WebAuthMiddleware::class)
    ->name('ulp.infografis');

    Route::get(
    '/ulp/laporan',
    [\App\Http\Controllers\UlpLaporanController::class, 'index']
)
    ->middleware(\App\Http\Middleware\WebAuthMiddleware::class)
    ->name('ulp.laporan');