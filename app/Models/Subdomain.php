<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subdomain extends Model
{
    protected $fillable = [
        'project_id', 'subdomain_name', 'ssl_enabled', 'config_path'
    ];

    protected $casts = [
        'ssl_enabled' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
