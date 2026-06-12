<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class ServerStatsService
{
    public function getStats()
    {
        return [
            'cpu' => $this->getCpuUsage(),
            'ram' => $this->getRamUsage(),
            'disk' => $this->getDiskUsage(),
            'uptime' => $this->getUptime(),
            'load' => $this->getLoadAvg(),
        ];
    }

    protected function getCpuUsage()
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            $process = new Process(["top", "-l", "1", "-n", "0"]);
            $process->run();
            $output = $process->getOutput();
            if (preg_match('/CPU usage: ([0-9.]+)% user/', $output, $matches)) {
                return (float) $matches[1];
            }
        } else {
            $process = new Process(["top", "-bn1"]);
            $process->run();
            $output = $process->getOutput();
            if (preg_match('/%Cpu\(s\):\s+([0-9.]+)\s+us/', $output, $matches)) {
                return (float) $matches[1];
            }
        }
        return 0;
    }

    protected function getRamUsage()
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            $process = new Process(["vm_stat"]);
            $process->run();
            $output = $process->getOutput();
            // Complex parsing for macOS
            return ['used' => 4.2, 'total' => 16.0, 'percent' => 26]; // Placeholder for mac
        } else {
            $free = shell_exec('free -m');
            $free = (string)trim($free);
            $free_arr = explode("\n", $free);
            $mem = explode(" ", preg_replace("/\s+/", " ", $free_arr[1]));
            $total = $mem[1];
            $used = $mem[2];
            return [
                'used' => round($used / 1024, 1),
                'total' => round($total / 1024, 1),
                'percent' => round(($used / $total) * 100)
            ];
        }
    }

    protected function getDiskUsage()
    {
        $path = base_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;
        return [
            'total' => round($total / (1024 ** 3), 1),
            'used' => round($used / (1024 ** 3), 1),
            'percent' => round(($used / $total) * 100)
        ];
    }

    protected function getUptime()
    {
        $process = new Process(["uptime", "-p"]);
        $process->run();
        return trim(str_replace('up ', '', $process->getOutput()));
    }

    protected function getLoadAvg()
    {
        $load = sys_getloadavg();
        return implode(", ", $load);
    }
}
