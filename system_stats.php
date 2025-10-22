<?php
header('Content-Type: application/json');

function getCpuUsage() {
    // This command is for Linux. It gets the idle CPU % and subtracts from 100.
    $command = "top -bn1 | grep 'Cpu(s)' | sed 's/.*, *\\([0-9.]*\\)%* id.*/\\1/' | awk '{print 100 - $1}'";
    $cpu_load = @exec($command);

    if ($cpu_load !== false && is_numeric($cpu_load)) {
        return round((float)$cpu_load, 2);
    }

    // Fallback using sys_getloadavg() if top fails
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        $cores = getCpuCores();
        return round(($load[0] / $cores) * 100, 2);
    }

    return null;
}

function getCpuCores() {
    if (is_file('/proc/cpuinfo')) {
        $cpuinfo = file_get_contents('/proc/cpuinfo');
        preg_match_all('/^processor/m', $cpuinfo, $matches);
        return count($matches[0]);
    }
    // Fallback for non-Linux
    return 1;
}


function getMemoryUsage() {
    // Use `free` command on Linux, it's more reliable
    $command = "free | grep Mem | awk '{print $3/$2 * 100.0}'";
    $mem_load = @exec($command);

    if ($mem_load !== false && is_numeric($mem_load)) {
        return round((float)$mem_load, 2);
    }

    // Fallback to /proc/meminfo if `free` is not available or fails
    if (is_readable('/proc/meminfo')) {
        $meminfo_raw = file_get_contents('/proc/meminfo');
        preg_match('/^MemTotal:\s+(\d+)\s*kB/', $meminfo_raw, $matches_total);
        preg_match('/^MemAvailable:\s+(\d+)\s*kB/', $meminfo_raw, $matches_avail);

        if (isset($matches_total[1]) && isset($matches_avail[1])) {
            $memTotal = $matches_total[1];
            $memAvailable = $matches_avail[1];
            $memUsed = $memTotal - $memAvailable;
            $memPercent = ($memUsed / $memTotal) * 100;
            return round($memPercent, 2);
        }
    }

    // Fallback for macOS
    if (PHP_OS_FAMILY === 'Darwin') {
        $command = "vm_stat | grep -E 'Pages free|Pages active|Pages inactive|Pages speculative|Pages wired down' | awk '{print $3}' | sed 's/\\.//'";
        $vm_stat = @exec($command, $output);
        if ($vm_stat !== false && count($output) === 5) {
            $pages_free = $output[0];
            $pages_active = $output[1];
            $pages_inactive = $output[2];
            $pages_speculative = $output[3];
            $pages_wired = $output[4];

            $total_pages = $pages_free + $pages_active + $pages_inactive + $pages_speculative + $pages_wired;
            $used_pages = $pages_active + $pages_inactive + $pages_wired;
            $memPercent = ($used_pages / $total_pages) * 100;
            return round($memPercent, 2);
        }
    }

    return null;
}

echo json_encode([
    'cpu' => getCpuUsage(),
    'memory' => getMemoryUsage()
]);
?>