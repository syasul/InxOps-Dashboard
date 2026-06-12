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

        $commands = [
            ['git', 'pull', 'origin', $this->project->branch],
            ['composer', 'install', '--no-interaction', '--prefer-dist', '--optimize-autoloader'],
            ['npm', 'install'],
            ['npm', 'run', 'build'],
            ['php', 'artisan', 'migrate', '--force'],
        ];

        try {
            foreach ($commands as $cmd) {
                $process = new Process($cmd, $this->project->directory_path);
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
            $logOutput .= "\n\nError: " . $e->getMessage();
            $this->deployment->update([
                'status' => 'failed',
                'log_output' => $logOutput,
            ]);
        }
    }
}
