<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name', 'repo_url', 'branch', 'directory_path', 'port', 'active', 'last_deploy_at'
    ];

    protected $casts = [
        'last_deploy_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function deployments()
    {
        return $this->hasMany(Deployment::class);
    }

    public function subdomains()
    {
        return $this->hasMany(Subdomain::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
