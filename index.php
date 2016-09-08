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

$c = Ant\Container::getInstance();
$c->when('123');
//function exceptionHandle(Throwable $exception){
//    if($exception->getPrevious()){
//        return exceptionHandle($exception->getPrevious());
//    }
//
//    $headers = [];
//    $headers['Exception'] = sprintf('%s(%d) %s',get_class($exception),$exception->getCode(),$exception->getMessage());
//
//    foreach(explode("\n",$exception->getTraceAsString()) as $index => $line){
//        $key           = sprintf('X-Exception-Trace-%02d', $index);
//        $headers[$key] = $line;
//    }
//
//    return $headers;
//}

//try{
////    $pdo = new Ant\Database\Connector\Mysql($config);
////
////    $table = $pdo->getTables();
//////    $name = 'power';
//////    $stat = $pdo->select('demo')
//////        ->whereSub('id','IN',function(){
//////            $this->table = 'users';
//////            $this->columns('id')->where(['name'=>'power']);
//////        })
//////        ->orWhere(['score'=>'>='],[85])
//////        ->get();
////
////    show($table);
////
////    $request = new Ant\Http\Request(new Ant\Collection($_SERVER));
////
////    foreach($request->getHeaders() as $name => $values){
////        foreach($values as $value){
////            echo sprintf('%s: %s',$name,$value).PHP_EOL;
////        }
////    }
//    $response = new Ant\Http\Response();
//}catch(Exception $e){
//    foreach(explode("\n", $e->getTraceAsString()) as $index => $line ){
//        echo "{$line} <br>";
//    }
//}catch(Error $e){
//    echo " Error : {$e->getMessage()}";
//}catch(Throwable $e){
//    echo " Exception : {$e->getMessage()}";
//}


