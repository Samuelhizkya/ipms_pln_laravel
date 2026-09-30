<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiUlp extends Model
{
    protected $table = 'kpi_ulp';

    protected $primaryKey = 'id_kpi_ulp';

    public $timestamps = false;

    protected $fillable = [
        'id_kpi',
        'id_ulp',
        'status',
    ];

    public function kpi()
    {
        return $this->belongsTo(
            MasterKpi::class,
            'id_kpi',
            'id_kpi'
        );
    }

    public function ulp()
    {
        return $this->belongsTo(
            Ulp::class,
            'id_ulp',
            'id_ulp'
        );
    }
}