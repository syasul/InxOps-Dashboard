<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deployment extends Model
{
    protected $fillable = [
        'project_id', 'commit_hash', 'status', 'log_output'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
