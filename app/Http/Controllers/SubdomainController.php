<?php

namespace App\Http\Controllers;

use App\Models\Subdomain;
use App\Models\Project;
use App\Services\NginxService;
use Illuminate\Http\Request;

class SubdomainController extends Controller
{
    protected $nginx;
    protected $cloudflare;

    public function __construct(NginxService $nginx, \App\Services\CloudflareService $cloudflare)
    {
        $this->nginx = $nginx;
        $this->cloudflare = $cloudflare;
    }

    public function index()
    {
        $subdomains = Subdomain::with('project')->latest()->get();
        // Fallback if active scope is not defined
        $projects = Project::latest()->get();
        return view('subdomains.index', compact('subdomains', 'projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'subdomain_name' => 'required|string|unique:subdomains,subdomain_name|regex:/^[a-z0-9.-]+$/',
        ]);

        $subdomain = Subdomain::create($validated);

        // 1. Generate and Save Nginx Config
        $configPath = $this->nginx->generateConfig($subdomain);
        $subdomain->update(['config_path' => $configPath]);

        // 2. Automate Enable & Reload Nginx
        $this->nginx->enableConfig($subdomain);

        // 3. Register DNS with Cloudflare
        $this->cloudflare->registerDns($subdomain->subdomain_name);

        return back()->with('success', 'Subdomain live! Nginx enabled and Cloudflare DNS registered.');
    }

    public function destroy(Subdomain $subdomain)
    {
        $subdomain->delete();
        return back()->with('success', 'Subdomain removed.');
    }
}
