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
/* ($a??1) Eq (isset($a) ? $a : 1) */

/**
 * php7错误跟异常都继承于Throwable,可以用try...catch的方式来捕获程序中的错误
 */
if(version_compare(PHP_VERSION, '7.0.0', '<')){
    set_error_handler(function($errno,$errstr,$errfile,$errline){
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    });
}

$app = new Ant\App();
$app->registerService();

$app->addMiddleware('a',function($req,$res){
    newRequest($req->withParsedBody(['abc'=>'abc']));
    $ac = yield;
    var_dump($ac);
});

$app->addMiddleware('b',function($req,$res){
    var_dump($req->post());
    $ac = yield;
    return $ac + 11;
});

$app->addMiddleware('c',function($req,$res){
    return 123;
});

$app->run();


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