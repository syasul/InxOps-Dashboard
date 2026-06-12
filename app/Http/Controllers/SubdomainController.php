<?php

namespace App\Http\Controllers;

use App\Models\Subdomain;
use App\Models\Project;
use App\Services\NginxService;
use Illuminate\Http\Request;

class SubdomainController extends Controller
{
    protected $nginx;

    public function __construct(NginxService $nginx)
    {
        $this->nginx = $nginx;
    }

    public function index()
    {
        $subdomains = Subdomain::with('project')->latest()->get();
        $projects = Project::active()->get();
        return view('subdomains.index', compact('subdomains', 'projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'subdomain_name' => 'required|string|unique:subdomains,subdomain_name|regex:/^[a-z0-9.-]+$/',
        ]);

        $subdomain = Subdomain::create($validated);

        // Generate Nginx Config
        $configPath = $this->nginx->generateConfig($subdomain);
        $subdomain->update(['config_path' => $configPath]);

        return back()->with('success', 'Subdomain added and Nginx config generated.');
    }

    public function destroy(Subdomain $subdomain)
    {
        $subdomain->delete();
        return back()->with('success', 'Subdomain removed.');
    }
}
