<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ulp extends Model
{
    protected $table = 'ulp';

    protected $primaryKey = 'id_ulp';

    public $timestamps = false;

    protected $fillable = [
        'kode_ulp',
        'nama_ulp',
        'status',
    ];

    public function targetKpi()
    {
        return $this->hasMany(TargetKpi::class, 'id_ulp', 'id_ulp');
    }

    public function realisasiKpi()
    {
        return $this->hasMany(RealisasiKpi::class, 'id_ulp', 'id_ulp');
    }

    public function kpiUlp()
    {
        return $this->hasMany(KpiUlp::class, 'id_ulp', 'id_ulp');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'id_ulp', 'id_ulp');
    }
}