<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Deployment;
use App\Jobs\DeployProjectJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function github(Request $request)
    {
        $payload = $request->all();
        $repoUrl = $payload['repository']['html_url'] ?? null;
        $branch = str_replace('refs/heads/', '', $payload['ref'] ?? '');

        if (!$repoUrl) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $project = Project::where('repo_url', $repoUrl)
            ->where('branch', $branch)
            ->where('active', true)
            ->first();

        if (!$project) {
            return response()->json(['message' => 'Project not found or inactive'], 404);
        }

        // Create deployment record
        $deployment = Deployment::create([
            'project_id' => $project->id,
            'commit_hash' => $payload['after'] ?? null,
            'status' => 'pending',
        ]);

        // Dispatch Job
        DeployProjectJob::dispatch($project, $deployment);

        return response()->json(['message' => 'Deployment triggered', 'deployment_id' => $deployment->id]);
    }
}
