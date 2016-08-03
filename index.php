<?php

define('EXT','.php');
include 'System\Autoloader.php';

function show($msg){
    echo "<pre>";
    var_dump($msg);
    echo "</pre>";
}
function debug(){
    echo "<pre>";
    var_dump(func_get_args());
    echo "</pre>";
    die;
}
\Ant\Autoloader::register();
\Ant\Autoloader::addNamespace('Ant\\Database','System'.DIRECTORY_SEPARATOR.'Database');

$config = [
    'dsn'=>'mysql:dbname=test;host=127.0.0.1',
    'user'=>'root',
    'password'=>'123456',
];


$pdo = new Ant\Database\Connector\Mysql($config);
$stat = $pdo->table('info')
//    ->where(['name'=>'in','age'=>'<>'],[['aulun','alex','ajax'],18])
//    ->where('age >= ?','18')
//    ->where(['name'=>'ajax','age'=>'18'])
//    ->order('score','DESC')
    ->get();

show($stat->getAll());

/*
* order('foo,name,baz desc');
* order('foe','name','baz asc');
* order('foo','name','baz','desc');
* order(['foo'=>'desc','asd'=>'asc']);
*/