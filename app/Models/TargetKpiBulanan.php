<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TargetKpiBulanan extends Model
{
    protected $table = 'target_kpi_bulanan';

    protected $primaryKey = 'id_target_bulanan';

    public $timestamps = false;

    protected $fillable = [
        'id_target',
        'bulan',
        'nilai_target',
    ];

    public function targetKpi()
    {
        return $this->belongsTo(
            TargetKpi::class,
            'id_target',
            'id_target'
        );
    }
}