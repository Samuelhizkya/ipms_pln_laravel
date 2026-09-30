<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RealisasiKpi extends Model
{
    protected $table = 'realisasi_kpi';

    protected $primaryKey = 'id_realisasi';

    public $timestamps = true;

    protected $fillable = [
        'id_ulp',
        'id_kpi',
        'id_pendukung',
        'nama_pendukung',
        'satuan_pendukung',
        'id_periode',
        'target_tahunan',
        'target_periode',
        'realisasi',
        'pencapaian',
        'nilai_kpi',
        'sumber_data',
        'status_data',
    ];

    public function ulp()
    {
        return $this->belongsTo(
            Ulp::class,
            'id_ulp',
            'id_ulp'
        );
    }

    public function kpi()
    {
        return $this->belongsTo(
            MasterKpi::class,
            'id_kpi',
            'id_kpi'
        );
    }

    public function periode()
    {
        return $this->belongsTo(
            PeriodeKinerja::class,
            'id_periode',
            'id_periode'
        );
    }
}