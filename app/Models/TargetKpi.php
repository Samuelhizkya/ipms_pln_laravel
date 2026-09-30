<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TargetKpi extends Model
{
    protected $table = 'target_kpi';

    protected $primaryKey = 'id_target';

    public $timestamps = true;

    protected $fillable = [
        'id_ulp',
        'id_kpi',
        'tahun',
        'target_tahunan',
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

    public function targetBulanan()
    {
        return $this->hasMany(
            TargetKpiBulanan::class,
            'id_target',
            'id_target'
        );
    }
}