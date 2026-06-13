<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerMetric extends Model
{
    protected $fillable = [
        'cpu_usage',
        'ram_usage',
        'disk_usage',
        'load_avg',
        'recorded_at',
    ];
}
