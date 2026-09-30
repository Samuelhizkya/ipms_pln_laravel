<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeKinerja extends Model
{
    protected $table = 'periode_kinerja';

    protected $primaryKey = 'id_periode';

    public $timestamps = false;

    protected $fillable = [
        'bulan',
        'tahun',
        'status',
    ];

    public function realisasiKpi()
    {
        return $this->hasMany(
            RealisasiKpi::class,
            'id_periode',
            'id_periode'
        );
    }
}