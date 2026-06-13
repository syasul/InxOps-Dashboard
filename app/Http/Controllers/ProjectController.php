<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Deployment;
use App\Jobs\DeployProjectJob;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::withCount('deployments')->latest()->get();
        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        // Dynamically resolve webapps directory based on server environment
        $home = env('HOME', $_SERVER['HOME'] ?? '/home/inxdvi');
        $webappsPath = $home . '/webapps';
        
        $availableFolders = [];
        if (\Illuminate\Support\Facades\File::exists($webappsPath)) {
            $directories = \Illuminate\Support\Facades\File::directories($webappsPath);
            foreach ($directories as $dir) {
                $availableFolders[] = [
                    'name' => basename($dir),
                    'full_path' => $dir
                ];
            }
        }

        return view('projects.create', compact('availableFolders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'repo_url' => 'required|url',
            'branch' => 'required|string|max:255',
            'directory_path' => 'required|string',
            'port' => 'nullable|integer',
        ]);

        Project::create($validated);

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $project->load(['deployments' => fn ($q) => $q->latest()->limit(10)]);
        return view('projects.show', compact('project'));
    }

    public function deploy(Project $project)
    {
        $deployment = Deployment::create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        // Dispatch the job to the queue. 
        // Supervisor (running as user 'inxdvi') will automatically pick this up!
        DeployProjectJob::dispatch($project, $deployment);

        return back()->with('success', 'Deployment queued. Supervisor is handling it safely in the background.');
    }

    public function pull(Project $project)
    {
        // Expand tilde (~) to absolute home directory
        $path = $project->directory_path;
        if (str_starts_with($path, '~')) {
            $home = env('HOME', $_SERVER['HOME'] ?? '/home/inxdvi');
            $path = str_replace('~', $home, $path);
        }

        $process = new \Symfony\Component\Process\Process(['git', 'pull', 'origin', $project->branch], $path);
        $process->setEnv([
            'PATH' => '/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin',
            'HOME' => env('HOME', $_SERVER['HOME'] ?? '/home/inxdvi'),
        ]);
        $process->run();

        if ($process->isSuccessful()) {
            return back()->with('success', 'Code successfully synchronized via Git Pull.');
        }

        return back()->with('error', 'Pull failed: ' . $process->getErrorOutput());
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }
}