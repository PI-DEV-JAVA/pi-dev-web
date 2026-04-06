<?php

$logPath = 'var/log/dev.log';
if (!file_exists($logPath)) {
    echo "No dev.log found.";
    exit;
}

$lines = file($logPath);
$exceptions = [];

for ($i = count($lines) - 1; $i >= 0; $i--) {
    if (strpos($lines[$i], 'Uncaught PHP Exception') !== false) {
        $exceptions[] = $lines[$i];
        if (count($exceptions) >= 5) break;
    }
}

foreach ($exceptions as $e) {
    echo $e . "\n";
}
