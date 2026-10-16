<?php

namespace App\Http\Controllers;

use App\Imports\TargetKpiImport;
use App\Models\TargetKpi;
use App\Models\Ulp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TargetKpiController extends Controller
{
    public function create()
    {
        $ulps = Ulp::where('status', 'aktif')
            ->orderBy('nama_ulp')
            ->get();

        return view('target-kpi.import', compact('ulps'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'id_ulp' => 'required|exists:ulp,id_ulp',
            'tahun' => 'required|integer|min:2000|max:2100',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new TargetKpiImport(
            (int) $request->id_ulp,
            (int) $request->tahun
        );

        Excel::import(
            $import,
            $request->file('file')
        );

        // Jika ada error, jangan simpan data apa pun.
        if (count($import->getErrors()) > 0) {

            return back()
                ->withInput()
                ->with('import_errors', $import->getErrors());
        }

        $rows = $import->getValidRows();

        if (count($rows) === 0) {

            return back()
                ->withInput()
                ->with('import_errors', [
                    'Tidak ada data target KPI yang dapat diimport.'
                ]);
        }

        DB::transaction(function () use ($rows) {

            foreach ($rows as $row) {

                $target = TargetKpi::updateOrCreate(
                    [
                        'id_ulp' => $row['id_ulp'],
                        'id_kpi' => $row['id_kpi'],
                        'tahun' => $row['tahun'],
                    ],
                    [
                        'target_tahunan' => $row['target_tahunan'],
                    ]
                );

                // Hapus target bulanan lama jika sebelumnya
                // sudah pernah diimport.
                $target->targetBulanan()->delete();

                foreach ($row['bulanan'] as $bulan => $nilai) {

                    if ($nilai === null || $nilai === '') {
                        continue;
                    }

                    $target->targetBulanan()->create([
                        'bulan' => $bulan,
                        'nilai_target' => $nilai,
                    ]);
                }
            }
        });

        return back()->with(
            'success',
            'Data Target KPI berhasil diimport. Total KPI: ' . count($rows)
        );
    }
}