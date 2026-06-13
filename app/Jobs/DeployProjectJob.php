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

            // 1. Try to find the best PHP version (prefer 8.4+)
            $phpPossibilities = ['php9.0', 'php8.5', 'php8.4', 'php'];
            $foundPhp = false;
            foreach ($phpPossibilities as $p) {
                $process = new Process(['which', $p]);
                $process->run();
                if ($process->isSuccessful()) {
                    $candidate = trim($process->getOutput());
                    if ($candidate) {
                        // If it's just 'php', check version
                        if ($p === 'php') {
                            $vProcess = new Process([$candidate, '-r', 'echo PHP_VERSION;']);
                            $vProcess->run();
                            if ($vProcess->isSuccessful() && version_compare(trim($vProcess->getOutput()), '8.4.0', '>=')) {
                                $php = $candidate;
                                $foundPhp = true;
                                break;
                            }
                        } else {
                            $php = $candidate;
                            $foundPhp = true;
                            break;
                        }
                    }
                }
            }

            // 2. Try to find Composer
            $process = new Process(['which', 'composer']);
            $process->run();
            if ($process->isSuccessful()) {
                $composer = trim($process->getOutput());
            } else {
                // Manual fallback search
                $composerPaths = [
                    '/usr/local/bin/composer',
                    '/opt/homebrew/bin/composer',
                    '/usr/bin/composer',
                    '/usr/local/share/composer/composer.phar',
                    '/usr/share/composer/composer.phar',
                    '/home/inxdvi/bin/composer'
                ];
                foreach ($composerPaths as $cp) {
                    if (\Illuminate\Support\Facades\File::exists($cp)) {
                        $composer = $cp;
                        break;
                    }
                }
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
