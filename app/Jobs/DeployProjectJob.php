<?php

namespace App\Jobs;

use App\Models\Deployment;
use App\Models\Project;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DeployProjectJob implements ShouldQueue
{
    use Queueable;

    protected $project;
    protected $deployment;

    public function __construct(Project $project, Deployment $deployment)
    {
        $this->project = $project;
        $this->deployment = $deployment;
    }

    public function handle(): void
    {
        $this->deployment->update(['status' => 'running']);
        $logOutput = "";
        $path = $this->project->directory_path;

        // Expand tilde (~) to absolute home directory
        if (str_starts_with($path, '~')) {
            $home = env('HOME', $_SERVER['HOME'] ?? '/home/inxdvi');
            $path = str_replace('~', $home, $path);
        }

        try {
            // 1. Check if we need to clone the repository
            if (!\Illuminate\Support\Facades\File::exists($path . '/.git')) {
                $logOutput .= "> Initializing fresh clone...\n";
                // Ensure parent directory exists
                \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($path), 0755, true);
                
                $home = env('HOME', $_SERVER['HOME'] ?? '/home/inxdvi');
                $cloneProcess = new Process(['git', 'clone', '-b', $this->project->branch, $this->project->repo_url, $path]);
                $cloneProcess->setEnv([
                    'HOME' => $home,
                    'PATH' => '/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin',
                ]);
                $cloneProcess->setTimeout(600);
                $cloneProcess->run();
                
                $logOutput .= $cloneProcess->getOutput() . $cloneProcess->getErrorOutput();
                if (!$cloneProcess->isSuccessful()) throw new \Exception("Clone failed: " . $cloneProcess->getErrorOutput());
            }

            // Path to PHP and Composer
            $php = PHP_BINARY;
            // Let's try to find composer if it's not in the PATH
            $composer = 'composer';
            if (\Illuminate\Support\Facades\File::exists('/opt/homebrew/bin/composer')) {
                $composer = '/opt/homebrew/bin/composer';
            }

            $commands = [
                ['git', 'pull', 'origin', $this->project->branch],
                [$php, $composer, 'install', '--no-interaction', '--prefer-dist', '--optimize-autoloader'],
            ];

            // 2. Setup .env if missing
            if (!\Illuminate\Support\Facades\File::exists($path . '/.env')) {
                $logOutput .= "> Setting up environment variables...\n";
                if (\Illuminate\Support\Facades\File::exists($path . '/.env.example')) {
                    \Illuminate\Support\Facades\File::copy($path . '/.env.example', $path . '/.env');
                    $commands[] = [$php, 'artisan', 'key:generate'];
                }
            }

            // 3. Database & Optimization
            $commands[] = [$php, 'artisan', 'migrate', '--force'];
            $commands[] = [$php, 'artisan', 'storage:link'];
            $commands[] = [$php, 'artisan', 'optimize:clear'];

            // 4. Critical Permissions (Storage & Cache)
            $commands[] = ['sudo', 'chown', '-R', 'inxdvi:www-data', 'storage', 'bootstrap/cache'];
            $commands[] = ['sudo', 'chmod', '-R', '777', 'storage', 'bootstrap/cache'];

            $home = env('HOME', $_SERVER['HOME'] ?? '/home/inxdvi');

            foreach ($commands as $cmd) {
                $process = new Process($cmd, $path);
                $process->setEnv([
                    'HOME' => $home,
                    'COMPOSER_HOME' => $home . '/.composer',
                    'PATH' => '/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin',
                ]);
                $process->setTimeout(300);
                $process->run();

                $logOutput .= "\n> " . implode(' ', $cmd) . "\n";
                $logOutput .= $process->getOutput();
                $logOutput .= $process->getErrorOutput();

                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }
            }

            $this->deployment->update([
                'status' => 'success',
                'log_output' => $logOutput,
            ]);

            $this->project->update(['last_deploy_at' => now()]);

        } catch (\Exception $e) {
            $logOutput .= "\n\nCRITICAL FAILURE: " . $e->getMessage();
            $this->deployment->update([
                'status' => 'failed',
                'log_output' => $logOutput,
            ]);
        }
    }
}
