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
                        } catch (\Exception $e) {}
                    }
                }
            }

            usort($phpPossibilities, function ($a, $b) {
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

            $commands = [];

            // SELF-HEALING & COMPATIBILITY PATCH
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
                    $composerJsonPath = $path . '/composer.json';
                    if (\Illuminate\Support\Facades\File::exists($composerJsonPath)) {
                        $composerJson = json_decode(\Illuminate\Support\Facades\File::get($composerJsonPath), true);
                        $composerJson['require']['symfony/http-foundation'] = '7.1.*';
                        $composerJson['require']['symfony/error-handler'] = '7.1.*';
                        $composerJson['require']['symfony/console'] = '7.1.*';

                        \Illuminate\Support\Facades\File::put($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                        
                        $patchUpdateCmd = [$php, $composer, 'update', 'symfony/http-foundation', 'symfony/error-handler', 'symfony/console', '--no-interaction', '--ignore-platform-reqs', '--with-all-dependencies'];
                        array_unshift($commands, $patchUpdateCmd);
                    }
                } else {
                    $php = '/usr/bin/php8.4';
                }
            }

            // Cari absolute path untuk Composer
            $process = new Process(['which', 'composer']);
            $process->run();
            if ($process->isSuccessful()) {
                $composerPath = trim($process->getOutput());
                if ($composerPath) $composer = $composerPath;
            } else {
                foreach (['/usr/local/bin/composer', '/usr/bin/composer', '/opt/homebrew/bin/composer'] as $cp) {
                    if (\Illuminate\Support\Facades\File::exists($cp)) { $composer = $cp; break; }
                }
            }

            // === BEDAH JANTUNG COMPOSER.JSON ===
            $composerJsonPath = $path . '/composer.json';
            if (\Illuminate\Support\Facades\File::exists($composerJsonPath)) {
                $composerJson = json_decode(\Illuminate\Support\Facades\File::get($composerJsonPath), true);
                $modified = false;

                if (isset($composerJson['scripts']['post-update-cmd'])) {
                    foreach ($composerJson['scripts']['post-update-cmd'] as $key => $scriptCommand) {
                        if (str_contains($scriptCommand, 'boost:update') || str_contains($scriptCommand, 'boost:install')) {
                            unset($composerJson['scripts']['post-update-cmd'][$key]);
                            $modified = true;
                        }
                    }
                    if ($modified) {
                        $composerJson['scripts']['post-update-cmd'] = array_values($composerJson['scripts']['post-update-cmd']); 
                    }
                }

                if ($modified) {
                    \Illuminate\Support\Facades\File::put($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            }

            // --- FASE 1: TARIK KODE & UPDATE VENDOR ---
            $baseCommands = [
                ['git', 'pull', 'origin', $this->project->branch],
                array_merge((str_contains($composer, '/') ? [$php, $composer] : [$composer]), ['update', '--no-interaction', '--prefer-dist', '--optimize-autoloader', '--ignore-platform-reqs', '--no-scripts'])
            ];
            $commands = array_merge($commands, $baseCommands);

            // --- FASE 2: BUAT FILE .ENV DAN DATABASE ---
            // Kita harus membuat filenya dulu agar nanti bisa di-chmod tanpa pesan error "No such file"
            $envPath = $path . '/.env';
            if (!\Illuminate\Support\Facades\File::exists($envPath)) {
                $logOutput .= "> Membuat dan mengonfigurasi otomatis file .env ke mode SQLite...\n";
                if (\Illuminate\Support\Facades\File::exists($path . '/.env.example')) {
                    $envContent = \Illuminate\Support\Facades\File::get($path . '/.env.example');

                    // Modifikasi Paksa ke SQLite
                    $envContent = preg_replace('/^DB_CONNECTION=.*$/m', 'DB_CONNECTION=sqlite', $envContent);
                    // Nonaktifkan konfigurasi MySQL dengan memberikan komentar (#)
                    $envContent = preg_replace('/^(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)=/m', '#$0', $envContent);

                    \Illuminate\Support\Facades\File::put($envPath, $envContent);
                }
            }

            // Pastikan database SQLite selalu ada
            if (\Illuminate\Support\Facades\File::exists($envPath)) {
                $envContent = \Illuminate\Support\Facades\File::get($envPath);
                if (str_contains($envContent, 'DB_CONNECTION=sqlite')) {
                    \Illuminate\Support\Facades\File::ensureDirectoryExists($path . '/database');
                    if (!\Illuminate\Support\Facades\File::exists($path . '/database/database.sqlite')) {
                        \Illuminate\Support\Facades\File::put($path . '/database/database.sqlite', '');
                        $logOutput .= "> File database.sqlite berhasil dibuat otomatis.\n";
                    }
                }
            }

            // --- FASE 3: AMBIL ALIH HAK AKSES TOTAL ---
            // Perbaikan hak akses WAJIB dilakukan sebelum Artisan menyentuh database dan env
            $commands[] = ['sudo', 'chown', '-R', 'inxdvi:www-data', '.'];
            $commands[] = ['sudo', 'chmod', '-R', '775', 'storage', 'bootstrap/cache', 'database', '.env'];

            // --- FASE 4: ARTISAN (Aman dari Permission Denied) ---
            $commands[] = [$php, 'artisan', 'key:generate'];
            $commands[] = [$php, 'artisan', 'optimize:clear'];
            $commands[] = [$php, 'artisan', 'migrate', '--force'];
            $commands[] = [$php, 'artisan', 'storage:link'];

            // --- FASE 5: FRONTEND BUILD ---
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

            // --- FASE 6: RESTART QUEUE & IZIN FINAL ---
            $commands[] = [$php, 'artisan', 'queue:restart'];
            // Memastikan ulang agar Nginx tetap bisa membaca public dan assets
            $commands[] = ['sudo', 'chown', '-R', 'inxdvi:www-data', 'storage', 'bootstrap/cache', 'database', 'public'];
            $commands[] = ['sudo', 'chmod', '-R', '775', 'storage', 'bootstrap/cache', 'database', 'public'];

            // ============================================

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

            // Eksekusi berurutan
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

                $this->deployment->update(['log_output' => $logOutput]);

                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }
            }

            // Reload Nginx
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