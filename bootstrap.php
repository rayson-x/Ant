<?php
define('EXT','.php');
include 'vendor/autoload.php';
include 'system/Helper.php';

/**
 * php7错误跟异常都继承于Throwable,可以用try...catch的方式来捕获程序中的错误
 */
if(version_compare(PHP_VERSION, '7.0.0', '<')){
    set_error_handler(function($errno,$errstr,$errfile,$errline){
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    });
}
//TODO::监听异常与错误输出
$app = new Ant\App();

$app->addMiddleware(function($request,$response){
    $start = microtime(true);

    yield;

    $end = (microtime(true) - $start) * 1000;
    $response->withHeader('x-run-time',$end);
});

//$route = new Ant\Router\RouterRequest();
//
//$route->group([],function($app){
//
//});
//
//$app->addMiddleware([$route,'execute']);
