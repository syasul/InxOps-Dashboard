<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class TerminalController extends Controller
{
    public function index()
    {
        return view('terminal.index');
    }

    public function execute(Request $request)
    {
        $command = $request->input('command');
        $cwd = $request->input('cwd', base_path());

        if (empty($command)) {
            return response()->json(['output' => '', 'cwd' => $cwd]);
        }

        // Security: Prevention of some interactive commands that might hang
        if (str_contains($command, 'nano') || str_contains($command, 'vi ') || str_contains($command, 'top')) {
            return response()->json(['output' => 'Interactive commands like nano/vi/top are not supported in this web terminal.', 'cwd' => $cwd]);
        }

        // Handle 'cd' manually because Symfony Process environment is fresh every time
        if (str_starts_with($command, 'cd ')) {
            $newDir = trim(substr($command, 3));
            if ($newDir === '~') $newDir = $_SERVER['HOME'];
            
            $targetDir = ($newDir[0] === '/') ? $newDir : $cwd . '/' . $newDir;
            $targetDir = realpath($targetDir);

            if ($targetDir && is_dir($targetDir)) {
                return response()->json(['output' => "Switched to $targetDir", 'cwd' => $targetDir]);
            } else {
                return response()->json(['output' => "Directory not found: $newDir", 'cwd' => $cwd]);
            }
        }

        try {
            $process = Process::fromShellCommandline($command, $cwd);
            $process->setEnv([
                'PATH' => '/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin',
                'HOME' => env('HOME', $_SERVER['HOME'] ?? '/home/inxdvi'),
            ]);
            $process->setTimeout(60);
            $process->run();

            $output = $process->getOutput();
            $error = $process->getErrorOutput();

            return response()->json([
                'output' => $output ?: $error,
                'cwd' => $cwd
            ]);
        } catch (\Exception $e) {
            return response()->json(['output' => 'Error: ' . $e->getMessage(), 'cwd' => $cwd]);
        }
    }
}
