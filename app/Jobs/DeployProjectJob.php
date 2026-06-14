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
            // 1. Cek apakah perlu clone repository baru atau tidak
            if (!\Illuminate\Support\Facades\File::exists($path . '/.git')) {
                $logOutput .= "> Initializing fresh clone...\n";
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
                if (!$cloneProcess->isSuccessful()) {
                    throw new \Exception("Clone failed: " . $cloneProcess->getErrorOutput());
                }
            }

            // Inisialisasi PHP dan Composer
            $php = PHP_BINARY;
            $composer = 'composer';

            // Deteksi versi PHP terbaik di server (Mendukung PHP 8.4 ke atas)
            $phpPossibilities = ['php9.0', 'php8.5', 'php8.4', 'php8.3', 'php8.2', 'php'];

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
                        } catch (\Exception $e) {
                        }
                    }
                }
            }

            usort($phpPossibilities, function ($a, $b) {
                if ($a === 'php')
                    return 1;
                if ($b === 'php')
                    return -1;
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

            $commands = [];

            // SELF-HEALING & COMPATIBILITY PATCH (Jika server memakai versi PHP di bawah 8.4)
            if (!$bestPhpFound) {
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

                if (!\Illuminate\Support\Facades\File::exists('/usr/bin/php8.4')) {
                    $logOutput .= "\n> Applying COMPATIBILITY PATCH: Forcing Symfony 7.1 (PHP 8.3 compatible)...\n";
                    $composerJsonPath = $path . '/composer.json';
                    if (\Illuminate\Support\Facades\File::exists($composerJsonPath)) {
                        $composerJson = json_decode(\Illuminate\Support\Facades\File::get($composerJsonPath), true);
                        $composerJson['require']['symfony/http-foundation'] = '7.1.*';
                        $composerJson['require']['symfony/error-handler'] = '7.1.*';
                        $composerJson['require']['symfony/error-handler'] = '7.1.*';
                        $composerJson['require']['symfony/console'] = '7.1.*';

                        \Illuminate\Support\Facades\File::put($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                        $logOutput .= "> composer.json patched for PHP 8.3 compatibility.\n";

                        $patchUpdateCmd = [$php, $composer, 'update', 'symfony/http-foundation', 'symfony/error-handler', 'symfony/console', '--no-interaction', '--ignore-platform-reqs', '--with-all-dependencies'];
                        array_unshift($commands, $patchUpdateCmd);
                    }
                } else {
                    $php = '/usr/bin/php8.4';
                    $logOutput .= "> PHP 8.4 successfully installed and selected.\n";
                }
            }

            // Cari absolute path untuk Composer
            $process = new Process(['which', 'composer']);
            $process->run();
            if ($process->isSuccessful()) {
                $composerPath = trim($process->getOutput());
                if ($composerPath)
                    $composer = $composerPath;
            } else {
                foreach (['/usr/local/bin/composer', '/usr/bin/composer', '/opt/homebrew/bin/composer'] as $cp) {
                    if (\Illuminate\Support\Facades\File::exists($cp)) {
                        $composer = $cp;
                        break;
                    }
                }
            }

            // Susun antrean perintah deployment utama
            $baseCommands = [
                ['git', 'pull', 'origin', $this->project->branch],
                (str_contains($composer, '/') ? [$php, $composer] : [$composer]),
            ];

            // Menggunakan 'update' agar otomatis menyamakan lock file di environment server
            $baseCommands[1] = array_merge($baseCommands[1], ['update', '--no-interaction', '--prefer-dist', '--optimize-autoloader', '--ignore-platform-reqs', '--no-scripts']);
            $commands = array_merge($commands, $baseCommands);

            // Automasi Setup awal .env & SQLite jika belum ada file env-nya
            if (!\Illuminate\Support\Facades\File::exists($path . '/.env')) {
                $logOutput .= "> Setting up environment variables...\n";
                if (\Illuminate\Support\Facades\File::exists($path . '/.env.example')) {
                    \Illuminate\Support\Facades\File::copy($path . '/.env.example', $path . '/.env');
                    $commands[] = [$php, 'artisan', 'key:generate'];

                    if (!\Illuminate\Support\Facades\File::exists($path . '/database/database.sqlite')) {
                        \Illuminate\Support\Facades\File::ensureDirectoryExists($path . '/database');
                        \Illuminate\Support\Facades\File::put($path . '/database/database.sqlite', '');
                    }
                }
            }

            // Database, Asset, & Optimization
            $commands[] = [$php, 'artisan', 'migrate', '--force'];
            $commands[] = [$php, 'artisan', 'storage:link'];
            $commands[] = [$php, 'artisan', 'optimize:clear'];

            // === OTOMATISASI NPM / VITE BUILD ===
            if (\Illuminate\Support\Facades\File::exists($path . '/package.json')) {
                $npm = 'npm';
                foreach (['/usr/bin/npm', '/usr/local/bin/npm', '/opt/homebrew/bin/npm'] as $np) {
                    if (\Illuminate\Support\Facades\File::exists($np)) {
                        $npm = $np;
                        break;
                    }
                }
                $commands[] = [$npm, 'install'];
                $commands[] = [$npm, 'run', 'build'];
            }

            // === AUTO RESTART WORKER QUEUE (Mencegah status --% di dashboard) ===
            $commands[] = [$php, 'artisan', 'queue:restart'];

            // Pengaturan hak akses folder krusial agar tidak memicu error permission/500
            $commands[] = ['sudo', 'chown', '-R', 'inxdvi:www-data', 'storage', 'bootstrap/cache', 'database', 'public'];
            $commands[] = ['sudo', 'chmod', '-R', '775', 'storage', 'bootstrap/cache', 'database', 'public'];

            $home = env('HOME', $_SERVER['HOME'] ?? '/home/inxdvi');
            $tempBinDir = $home . '/.inxops_tmp_bin';
            if (!\Illuminate\Support\Facades\File::exists($tempBinDir)) {
                \Illuminate\Support\Facades\File::makeDirectory($tempBinDir, 0755, true);
            }
            $shadowPhp = $tempBinDir . '/php';
            if (\Illuminate\Support\Facades\File::exists($shadowPhp)) {
                @unlink($shadowPhp);
            }
            @symlink($php, $shadowPhp);

            // Eksekusi semua antrean perintah secara incremental
            foreach ($commands as $cmd) {
                $process = new Process($cmd, $path);
                $process->setEnv([
                    'HOME' => $home,
                    'COMPOSER_HOME' => $home . '/.composer',
                    'PATH' => $tempBinDir . ':/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin:/opt/homebrew/bin',
                ]);
                $process->setTimeout(300);
                $process->run();

                $logPiece = "\n> " . implode(' ', $cmd) . "\n";
                $logPiece .= $process->getOutput();
                $logPiece .= $process->getErrorOutput();
                $logOutput .= $logPiece;

                // Live Logging: Update database per satu perintah selesai agar user bisa melihat progress secara berkala
                $this->deployment->update(['log_output' => $logOutput]);

                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }
            }

            // Manajemen App Otomatis: Reload Nginx jika proyek memiliki subdomain pendaftaran
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
            if (isset($tempBinDir) && \Illuminate\Support\Facades\File::exists($tempBinDir)) {
                @\Illuminate\Support\Facades\File::deleteDirectory($tempBinDir);
            }
        }
    }
}