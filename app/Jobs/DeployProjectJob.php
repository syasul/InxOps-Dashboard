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
        $path = $this->project->normalized_path;

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
                        try {
                            $filename = $file->getFilename();
                            if (preg_match('/^php[89]\.[0-9]+$/', $filename)) {
                                $phpPossibilities[] = $filename;
                            }
                        } catch (\Exception $e) {}
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

            $bestPhpFound = false;
            foreach ($phpPossibilities as $p) {
                $process = new Process(['which', $p]);
                $process->run();
                if ($process->isSuccessful()) {
                    $candidate = trim($process->getOutput());
                    if ($candidate) {
                        $php = $candidate;
                        if (str_contains($p, '8.4') || str_contains($p, '8.5') || str_contains($p, '9.')) {
                            $bestPhpFound = true;
                        }
                        break;
                    }
                }
            }

            // SELF-HEALING & COMPATIBILITY PATCH
            if (!$bestPhpFound) {
                // Try to install first (will likely fail if sudo requires password)
                if (\Illuminate\Support\Facades\File::exists('/usr/bin/apt-get')) {
                    $logOutput .= "> PHP 8.4+ not found. Attempting self-healing installation...\n";
                    $installCmds = [
                        ['sudo', '-n', 'add-apt-repository', 'ppa:ondrej/php', '-y'],
                        ['sudo', '-n', 'apt-get', 'update', '-y'],
                        ['sudo', '-n', 'apt-get', 'install', 'php8.4-cli', 'php8.4-common', 'php8.4-mysql', 'php8.4-xml', 'php8.4-curl', 'php8.4-mbstring', 'php8.4-zip', '-y']
                    ];
                    foreach ($installCmds as $icmd) {
                        $iprocess = new Process($icmd);
                        $iprocess->run();
                        $logOutput .= "\n> " . implode(' ', $icmd) . "\n" . $iprocess->getErrorOutput();
                    }
                }

                    // If we still don't have 8.4, we MUST patch the dependencies to be 8.3-compatible
                    if (!\Illuminate\Support\Facades\File::exists('/usr/bin/php8.4')) {
                        $logOutput .= "\n> Applying COMPATIBILITY PATCH: Forcing Symfony 7.1 (PHP 8.3 compatible)...\n";
                        
                        // We directly modify composer.json to cap Symfony at 7.1 to avoid PHP 8.4 property hooks
                        $composerJsonPath = $path . '/composer.json';
                        if (\Illuminate\Support\Facades\File::exists($composerJsonPath)) {
                            $composerJson = json_decode(\Illuminate\Support\Facades\File::get($composerJsonPath), true);
                            
                            // Force symfony components to a version that doesn't use 8.4 features
                            $composerJson['require']['symfony/http-foundation'] = '7.1.*';
                            $composerJson['require']['symfony/error-handler'] = '7.1.*';
                            $composerJson['require']['symfony/console'] = '7.1.*';
                            
                            \Illuminate\Support\Facades\File::put($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                            
                            $logOutput .= "> composer.json patched for PHP 8.3 compatibility.\n";

                            // IMPORTANT: Because we modified composer.json, we must run 'update' for these packages 
                            // to reconcile the lock file, otherwise 'composer install' will fail.
                            $patchUpdateCmd = [$php, $composer, 'update', 'symfony/http-foundation', 'symfony/error-handler', 'symfony/console', '--no-interaction', '--ignore-platform-reqs', '--with-all-dependencies'];
                            
                            // Insert this update command at the beginning of the deployment process
                            array_unshift($commands, $patchUpdateCmd);
                        }
                    } else {
                    $php = '/usr/bin/php8.4';
                    $logOutput .= "> PHP 8.4 successfully installed and selected.\n";
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
            // DIUBAH: Menggunakan 'update' alih-alih 'install' agar selalu otomatis menyamakan lock file
            $commands[1] = array_merge($commands[1], ['update', '--no-interaction', '--prefer-dist', '--optimize-autoloader', '--ignore-platform-reqs']);

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
            $tempBinDir = $home . '/.inxops_tmp_bin';
            if (!\Illuminate\Support\Facades\File::exists($tempBinDir)) {
                \Illuminate\Support\Facades\File::makeDirectory($tempBinDir, 0755, true);
            }
            // Shadow 'php' with the best version we found
            $shadowPhp = $tempBinDir . '/php';
            if (\Illuminate\Support\Facades\File::exists($shadowPhp)) {
                @unlink($shadowPhp);
            }
            @symlink($php, $shadowPhp);

            foreach ($commands as $cmd) {
                $process = new Process($cmd, $path);
                $process->setEnv([
                    'HOME' => $home,
                    'COMPOSER_HOME' => $home . '/.composer',
                    // Force the system to see our chosen PHP as the default 'php'
                    'PATH' => $tempBinDir . ':/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin:/opt/homebrew/bin',
                ]);
                $process->setTimeout(300);
                $process->run();

                $logPiece = "\n> " . implode(' ', $cmd) . "\n";
                $logPiece .= $process->getOutput();
                $logPiece .= $process->getErrorOutput();
                
                $logOutput .= $logPiece;
                
                // Incremental Logging: Update database after each command so user can see progress
                $this->deployment->update(['log_output' => $logOutput]);

                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }
            }

            // 5. Automatic App Management: Restart the application if it has a subdomain
            // This makes the "Build Now" button feel truly automatic.
            $nginx = new \App\Services\NginxService();
            $subdomain = \App\Models\Subdomain::where('project_id', $this->project->id)->first();
            if ($subdomain) {
                $logOutput .= "\n> Automatically restarting application via Nginx Control...\n";
                $nginx->startApplication($this->project);
                $nginx->reload();
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
        } finally {
            // Cleanup Shadowing
            if (isset($tempBinDir) && \Illuminate\Support\Facades\File::exists($tempBinDir)) {
                @\Illuminate\Support\Facades\File::deleteDirectory($tempBinDir);
            }
        }
    }
}