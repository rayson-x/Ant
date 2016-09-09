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

try{
    $c = Ant\Container::getInstance();
//    /* 注册HTTP请求 */
    $c->when(Ant\Http\Request::class)->needs(Ant\Collection::class)->give(function(){
        return new Ant\Collection($_SERVER);
    });
//    $request = $c[Ant\Http\Request::class];
//
//    var_dump($request->getBodyParam());
    $c->bind([Ant\Middleware::class => 'request']);
    $c->bind('request',Ant\Http\Request::class);
}catch(Exception $e){
    echo $e->getMessage();
    foreach(explode("\n", $e->getTraceAsString()) as $index => $line ){
        echo "{$line} <br>";
    }
}catch(Error $e){
    echo " Error : {$e->getMessage()}";
}catch(Throwable $e){
    echo " Exception : {$e->getMessage()}";
}

function exceptionHandle(Throwable $exception){
    if($exception->getPrevious()){
        return exceptionHandle($exception->getPrevious());
    }

    $headers = [];
    $headers['Exception'] = sprintf('%s(%d) %s',get_class($exception),$exception->getCode(),$exception->getMessage());

    foreach(explode("\n",$exception->getTraceAsString()) as $index => $line){
        $key           = sprintf('X-Exception-Trace-%02d', $index);
        $headers[$key] = $line;
    }

    return $headers;
}

