<?php
$test = [];
for($i = 0;$i < 1000;$i++){
    $test[] = file_get_contents('http://127.0.0.1/index.php/admin/test/123123');
}
$sum = array_sum($test);
echo "ƽ��ʱ��:".($sum/1000).PHP_EOL;
echo "��ʱ��:".$sum.PHP_EOL;