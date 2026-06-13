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
            $composer = 'composer';

            // 1. Try to find the best PHP version (prefer latest)
            $phpPossibilities = ['php9.0', 'php8.5', 'php8.4', 'php8.3', 'php8.2', 'php'];
            
            // Proactively search for any php8.x or php9.x binaries
            $binPaths = ['/usr/bin', '/usr/local/bin', '/opt/homebrew/bin'];
            foreach ($binPaths as $bp) {
                if (\Illuminate\Support\Facades\File::exists($bp)) {
                    $files = \Illuminate\Support\Facades\File::files($bp);
                    foreach ($files as $file) {
                        $filename = $file->getFilename();
                        if (preg_match('/^php[89]\.[0-9]+$/', $filename)) {
                            $phpPossibilities[] = $filename;
                        }
                    }
                }
            }
            // Sort to get highest versions first (e.g. 9.0, 8.5, 8.4...)
            usort($phpPossibilities, function($a, $b) {
                if ($a === 'php') return 1;
                if ($b === 'php') return -1;
                return version_compare(str_replace('php', '', $b), str_replace('php', '', $a));
            });
            $phpPossibilities = array_unique($phpPossibilities);

            foreach ($phpPossibilities as $p) {
                $process = new Process(['which', $p]);
                $process->run();
                if ($process->isSuccessful()) {
                    $candidate = trim($process->getOutput());
                    if ($candidate) {
                        $php = $candidate;
                        break;
                    }
                }
            }

            // 2. Try to find Composer absolute path
            $process = new Process(['which', 'composer']);
            $process->run();
            if ($process->isSuccessful()) {
                $composerPath = trim($process->getOutput());
                // Use the absolute path if found
                if ($composerPath) $composer = $composerPath;
            } else {
                // Secondary fallback search
                foreach (['/usr/local/bin/composer', '/usr/bin/composer', '/opt/homebrew/bin/composer'] as $cp) {
                    if (\Illuminate\Support\Facades\File::exists($cp)) {
                        $composer = $cp;
                        break;
                    }
                }
            }

            $commands = [
                ['git', 'pull', 'origin', $this->project->branch],
                // We use $php prefix ONLY if we have an absolute path to composer phar
                // If composer is an executable, we can just run it
                (str_contains($composer, '/') ? [$php, $composer] : [$composer]),
            ];
            
            // Reconstruct the composer command with its arguments
            $commands[1] = array_merge($commands[1], ['install', '--no-interaction', '--prefer-dist', '--optimize-autoloader']);

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
                    // Prepend best PHP directory to PATH
                    'PATH' => dirname($php) . ':/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin:/opt/homebrew/bin',
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
