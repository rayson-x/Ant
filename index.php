<?php
$start = microtime(true);
require __DIR__.'/bootstrap.php';

$app->run();

echo (int)((microtime(true) - $start) * 1000);