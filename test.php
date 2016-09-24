<?php
$test = [];
for($i = 0;$i < 1000;$i++){
    $test[] = file_get_contents('http://127.0.0.1/index.php/admin/test/123123');
}
$sum = array_sum($test);
echo "平均时间:".($sum/1000).PHP_EOL;
echo "总时间:".$sum.PHP_EOL;