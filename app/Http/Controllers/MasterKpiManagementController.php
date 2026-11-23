<?php

namespace App\Http\Controllers;

use App\Models\MasterKpi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MasterKpiManagementController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                Rule::in(['aktif', 'nonaktif', 'kosong']),
            ],
        ]);

        $keyword = trim($filters['q'] ?? '');
        $status = $filters['status'] ?? '';

        $query = MasterKpi::query();

        if ($keyword !== '') {
            $query->where(function ($search) use ($keyword) {
                $search
                    ->where('kode_kpi', 'like', '%' . $keyword . '%')
                    ->orWhere(
                        'nama_kpi',
                        'like',
                        '%' . $keyword . '%'
                    );
            });
        }

        if ($status === 'kosong') {
            $query->whereNull('status');
        } elseif ($status !== '') {
            $query->where('status', $status);
        }

        $kpis = $query
            ->orderBy('kode_kpi')
            ->orderBy('id_kpi')
            ->paginate(20)
            ->withQueryString();

        $parentIds = $kpis->getCollection()
            ->pluck('id_parent')
            ->filter()
            ->unique()
            ->values();

        $parents = MasterKpi::query()
            ->whereIn('id_kpi', $parentIds)
            ->get(['id_kpi', 'kode_kpi', 'nama_kpi'])
            ->keyBy('id_kpi');

        $unitAssignments = DB::table('kpi_ulp')
            ->leftJoin(
                'ulp',
                'ulp.id_ulp',
                '=',
                'kpi_ulp.id_ulp'
            )
            ->whereIn(
                'kpi_ulp.id_kpi',
                $kpis->getCollection()->pluck('id_kpi')
            )
            ->orderBy('ulp.nama_ulp')
            ->get([
                'kpi_ulp.id_kpi',
                'kpi_ulp.id_ulp',
                'kpi_ulp.status as assignment_status',
                'ulp.nama_ulp',
                'ulp.status as unit_status',
            ])
            ->groupBy('id_kpi');

        return view('kpi.master', compact(
            'kpis',
            'parents',
            'unitAssignments',
            'keyword',
            'status'
        ));
    }
}