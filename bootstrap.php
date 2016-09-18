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

$app = new Ant\App();
$app->registerService();

/**
 * 注册异常处理
 */
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

$app->addMiddleware(function ($request, $response) {
    throw Ant\Http\Exception::factory(404);
    yield;
});
$app->addMiddleware(function ($request, $response) {
    yield;
});
$app->addMiddleware(function ($request, $response) {
    yield;
});
