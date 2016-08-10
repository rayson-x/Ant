<?php
define('EXT','.php');
include 'vendor\autoload.php';
//\Ant\Autoloader::register();
//\Ant\Autoloader::addNamespace('Ant\\Database','System'.DIRECTORY_SEPARATOR.'Database');

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


$config = [
    'dsn'=>'mysql:dbname=test;host=127.0.0.1',
    'user'=>'root',
    'password'=>'123456',
];



//
//$pdo = new Ant\Database\Connector\Mysql($config);
//
//$stat = $pdo->table('info')
//    ->whereNotIn('name',['aulun','alex','ajax'])
//    ->get();
////    ->select(['a.name','a.sex','a.age'])
////    ->where(['name'=>'in','age'=>'<>'],[['aulun','alex','ajax'],18])
////    ->where('age = ?','18')
////    ->orWhere(['sex'=>'in'],[['woman','man']])
////    ->where(['name'=>'ajax','age'=>'18'])
////    ->order('age','DESC')
////    ->alias('a')
////    ->get();
//
//show($stat->getAll());
//
