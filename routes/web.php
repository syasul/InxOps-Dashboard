<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\SubdomainController;
use App\Http\Controllers\TerminalController;
use App\Http\Controllers\FileExplorerController;
use App\Http\Controllers\Api\MetricsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/api/metrics', [MetricsController::class, 'index'])->name('api.metrics');
Route::post('/webhook/github', [WebhookController::class, 'github'])->name('webhook.github');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        $projectCount = \App\Models\Project::count();
        $activeProjects = \App\Models\Project::where('active', true)->count();
        $subdomainCount = \App\Models\Subdomain::count();
        $recentDeployments = \App\Models\Deployment::with('project')->latest()->limit(5)->get();
        $projects = \App\Models\Project::latest()->limit(5)->get();

        return view('dashboard', compact('projectCount', 'activeProjects', 'subdomainCount', 'recentDeployments', 'projects'));
    })->name('dashboard');

    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/deploy', [ProjectController::class, 'deploy'])->name('projects.deploy');
    Route::post('projects/{project}/pull', [ProjectController::class, 'pull'])->name('projects.pull');

    Route::get('/monitoring', function () {
        return view('monitoring.index');
    })->name('monitoring');

    Route::resource('subdomains', SubdomainController::class)->only(['index', 'store', 'destroy']);

    Route::get('/terminal', [TerminalController::class, 'index'])->name('terminal.index');
    Route::post('/terminal/execute', [TerminalController::class, 'execute'])->name('terminal.execute');

    Route::get('/storage', [FileExplorerController::class, 'index'])->name('storage.index');
    Route::post('/storage/upload', [FileExplorerController::class, 'upload'])->name('storage.upload');
    Route::get('/storage/download', [FileExplorerController::class, 'download'])->name('storage.download');
    Route::post('/storage/folder', [FileExplorerController::class, 'createFolder'])->name('storage.folder');
    Route::delete('/storage/delete', [FileExplorerController::class, 'destroy'])->name('storage.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
