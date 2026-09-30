<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterKpi extends Model
{
    protected $table = 'master_kpi';

    protected $primaryKey = 'id_kpi';

    public $timestamps = true;

    protected $fillable = [
        'kode_kpi',
        'nama_kpi',
        'id_parent',
        'polaritas',
        'satuan',
        'bobot',
        'status',
    ];

    public function kpiUlp()
    {
        return $this->hasMany(KpiUlp::class, 'id_kpi', 'id_kpi');
    }

    public function targetKpi()
    {
        return $this->hasMany(TargetKpi::class, 'id_kpi', 'id_kpi');
    }

    public function realisasiKpi()
    {
        return $this->hasMany(RealisasiKpi::class, 'id_kpi', 'id_kpi');
    }
}