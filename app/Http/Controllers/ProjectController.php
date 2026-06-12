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
        return view('projects.create');
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

        DeployProjectJob::dispatch($project, $deployment);

        return back()->with('success', 'Deployment started.');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }
}
