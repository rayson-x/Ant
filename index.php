<?php

define('EXT','.php');
include 'System\Autoloader.php';

function show($msg){
    echo "<pre>";
    var_dump($msg);
    echo "</pre>";
}
\Ant\Autoloader::register();
$config = [
    'dsn'=>'mysql:dbname=test;host=127.0.0.1',
    'user'=>'root',
    'password'=>'123456',
];


$pdo = new Ant\Database\Connector\Mysql($config);
$pdo->table('demo')->where(['name'=>'in','score'=>'>'],[['aulun','alex','ajax'],'60'])->get();

