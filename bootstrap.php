<?php
include 'vendor/autoload.php';
include 'system/Helper.php';

/**
 * php7错误跟异常都继承于Throwable,可以用try...catch的方式来捕获程序中的错误
 */
if(version_compare(PHP_VERSION, '7.0.0', '<')){
    set_error_handler(function($errno,$errstr,$errfile,$errline){
        throw new \ErrorException($errstr, $errno, $errno, $errfile, $errline);
    });
}

//TODO::监听异常与错误输出
//TODO::日志功能 , psr-3 引入或自己开发
//TODO::上下文处理,如cookie,session,
//TODO::模板引擎,缓存视图
//TODO::储存 Service ,如mysql,redis,memcache等服务
//TODO::ORM
//TODO::单元测试
//TODO::Console
//TODO::Config类
//TODO::验证类
$app = new Ant\App();

$app->addMiddleware(function ($request,$response){
    $start = microtime(true);

    yield;

    $end = (microtime(true) - $start) * 1000;
    $response->withHeader('x-run-time',$end);
});

$router = new Ant\Router();

$router->addMiddleware([
    'test'  =>  function($request,$response){
        /* @var $response Ant\Http\Response */
        $response->withHeader('X-Powered-By','.net');
    },
]);

$router->group([
    'keyword'=>'admin',
    'middleware'=>'test',
    'cacheFile' => __DIR__.'/admin.cache.php',
],function(Ant\Router $router)use($app){
    $router->any('/{id:\d+}',function($id)use($app){
        $response = $app->make('response');
        $response->setJson(['id' => $id]);
    });
});

$app->addMiddleware([$router,'execute']);
