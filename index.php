<?php
define('EXT','.php');
include 'vendor\autoload.php';
include 'system/Helper.php';
//\Ant\Autoloader::register();
//\Ant\Autoloader::addNamespace('Ant\\Database','System'.DIRECTORY_SEPARATOR.'Database');

function show($msg){
    echo "<pre>";
    var_dump($msg);
    echo "</pre>";
}

$config = [
    'dsn'=>'mysql:dbname=test;host=127.0.0.1',
    'user'=>'root',
    'password'=>'123456',
];

$app = new Ant\App();
$app->registerService();

$app->setErrorHandler(function($errno,$errstr,$errfile,$errline){
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

$app->setExceptionHandler(function($exception){
    echo " Exception : {$exception->getMessage()}.<br>";
    foreach(explode("\n", $exception->getTraceAsString()) as $index => $line ){
        echo "{$line} <br>";
    }
});

$app->addMiddleware(function ($request, $response) {
    $startTime = microtime(true);

    yield;
    $endTime = (microtime(true) - $startTime) * 1000;
    $response->withHeader('x-run-time', (int) $endTime);
});

$app->addMiddleware(function($req,$res){
    newRequest($req->withParsedBody(['abc'=>'abc']));
    yield;
});

$app->addMiddleware(function($req,$res){
//    var_dump($req->post());
    yield;
});

$app->run();

function exceptionHandle($exception){
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