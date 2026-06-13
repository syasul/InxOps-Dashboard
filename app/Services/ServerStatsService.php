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
        try {
            if (PHP_OS_FAMILY === 'Darwin') {
                $process = new Process(["top", "-l", "1", "-n", "0"]);
                $process->run();
                $output = $process->getOutput();
                if (preg_match('/CPU usage: ([0-9.]+)% user/', $output, $matches)) {
                    return (float) $matches[1];
                }
            } else {
                // Gunakan /proc/stat jika ada untuk menghindari eksekusi top yang lambat/diblokir
                if (is_readable('/proc/stat')) {
                    $stat1 = file_get_contents('/proc/stat');
                    usleep(100000); // 100ms
                    $stat2 = file_get_contents('/proc/stat');
                    
                    $info1 = $this->parseProcStat($stat1);
                    $info2 = $this->parseProcStat($stat2);
                    
                    if ($info1 && $info2) {
                        $diffIdle = $info2['idle'] - $info1['idle'];
                        $diffTotal = $info2['total'] - $info1['total'];
                        if ($diffTotal > 0) {
                            return round((($diffTotal - $diffIdle) / $diffTotal) * 100, 1);
                        }
                    }
                }
                
                // Fallback ke top
                $process = new Process(["top", "-bn1"]);
                $process->run();
                $output = $process->getOutput();
                if (preg_match('/%Cpu\(s\):\s+([0-9.,]+)\s+us/', $output, $matches)) {
                    return (float) str_replace(',', '.', $matches[1]);
                }
            }
        } catch (\Throwable $e) {
            // Ignore
        }
        return 0.0;
    }

    private function parseProcStat($content)
    {
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (str_starts_with($line, 'cpu ')) {
                $parts = array_values(array_filter(explode(' ', $line)));
                if (count($parts) >= 5) {
                    $user = (int)$parts[1];
                    $nice = (int)$parts[2];
                    $system = (int)$parts[3];
                    $idle = (int)$parts[4];
                    $total = $user + $nice + $system + $idle + (int)($parts[5] ?? 0) + (int)($parts[6] ?? 0) + (int)($parts[7] ?? 0);
                    return ['idle' => $idle, 'total' => $total];
                }
            }
        }
        return null;
    }

    protected function getRamUsage()
    {
        try {
            if (PHP_OS_FAMILY === 'Darwin') {
                return ['used' => 4.2, 'total' => 16.0, 'percent' => 26];
            } else {
                if (is_readable('/proc/meminfo')) {
                    $meminfo = file_get_contents('/proc/meminfo');
                    if (preg_match('/MemTotal:\s+(\d+)/', $meminfo, $matchesTotal) &&
                        preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $matchesAvail)) {
                        $totalKb = (int)$matchesTotal[1];
                        $availKb = (int)$matchesAvail[1];
                        $usedKb = $totalKb - $availKb;
                        
                        $totalGb = round($totalKb / (1024 * 1024), 1);
                        $usedGb = round($usedKb / (1024 * 1024), 1);
                        $percent = $totalKb > 0 ? round(($usedKb / $totalKb) * 100) : 0;
                        
                        return [
                            'used' => $usedGb,
                            'total' => $totalGb,
                            'percent' => $percent
                        ];
                    }
                }
                
                if (function_exists('shell_exec')) {
                    $free = shell_exec('free -m');
                    if ($free) {
                        $free = (string)trim($free);
                        $free_arr = explode("\n", $free);
                        if (isset($free_arr[1])) {
                            $mem = explode(" ", preg_replace("/\s+/", " ", $free_arr[1]));
                            if (count($mem) >= 3) {
                                $total = (int)$mem[1];
                                $used = (int)$mem[2];
                                if ($total > 0) {
                                    return [
                                        'used' => round($used / 1024, 1),
                                        'total' => round($total / 1024, 1),
                                        'percent' => round(($used / $total) * 100)
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore
        }
        return ['used' => 0.0, 'total' => 0.0, 'percent' => 0];
    }

    protected function getDiskUsage()
    {
        try {
            $path = base_path();
            $total = @disk_total_space($path);
            $free = @disk_free_space($path);
            if ($total !== false && $free !== false && $total > 0) {
                $used = $total - $free;
                return [
                    'total' => round($total / (1024 ** 3), 1),
                    'used' => round($used / (1024 ** 3), 1),
                    'percent' => round(($used / $total) * 100)
                ];
            }
        } catch (\Throwable $e) {
            // Ignore
        }
        return ['total' => 0.0, 'used' => 0.0, 'percent' => 0];
    }

    protected function getUptime()
    {
        try {
            if (is_readable('/proc/uptime')) {
                $uptimeSecs = (float)explode(' ', file_get_contents('/proc/uptime'))[0];
                $days = floor($uptimeSecs / 86400);
                $hours = floor(($uptimeSecs % 86400) / 3600);
                $mins = floor(($uptimeSecs % 3600) / 60);
                
                $out = [];
                if ($days > 0) $out[] = "$days day" . ($days > 1 ? 's' : '');
                if ($hours > 0) $out[] = "$hours hour" . ($hours > 1 ? 's' : '');
                if ($mins > 0) $out[] = "$mins minute" . ($mins > 1 ? 's' : '');
                
                return count($out) > 0 ? implode(', ', $out) : '0 minutes';
            }
            
            $process = new Process(["uptime", "-p"]);
            $process->run();
            if ($process->isSuccessful()) {
                return trim(str_replace('up ', '', $process->getOutput()));
            }
        } catch (\Throwable $e) {
            // Ignore
        }
        return 'Unknown';
    }

    protected function getLoadAvg()
    {
        try {
            if (function_exists('sys_getloadavg')) {
                $load = @sys_getloadavg();
                if (is_array($load) && count($load) >= 3) {
                    $rounded = array_map(fn($val) => round($val, 2), $load);
                    return implode(", ", $rounded);
                }
            }
            if (is_readable('/proc/loadavg')) {
                $loadavg = explode(' ', file_get_contents('/proc/loadavg'));
                if (count($loadavg) >= 3) {
                    return "{$loadavg[0]}, {$loadavg[1]}, {$loadavg[2]}";
                }
            }
        } catch (\Throwable $e) {
            // Ignore
        }
        return '0.00, 0.00, 0.00';
    }
}
