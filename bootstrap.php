<?php
include 'vendor/autoload.php';

/**
 * php7错误跟异常都继承于Throwable,可以用try...catch的方式来捕获程序中的错误
 */
set_error_handler(function($level, $message, $file = '', $line = 0){
    throw new \ErrorException($message, 0, $level, $file, $line);
});
error_reporting(-1);


//基础功能
//TODO::Config类
//TODO::日志功能 , psr-3 引入或自己开发
//TODO::监听异常与错误输出
//TODO::验证类
//TODO::字符处理,数组处理类

//重要功能
//TODO::储存 Service ,如mysql,redis,memcache等服务
//TODO::ORM

//依赖前两者功能
//TODO::上下文处理,如cookie,session,
//TODO::模板引擎,缓存视图
//TODO::单元测试
//TODO::Console

$app = new Ant\App(realpath(__DIR__));

/* 将中间件装载到应用中 */
$app->addMiddleware(function (Ant\Http\Request $request,Ant\Http\Response $response){
    yield;
    //获取脚本运行时间(ms)
    $response->withHeader(
        'x-run-time',(int)((microtime(true) - $request->getServerParam('REQUEST_TIME_FLOAT')) * 1000).'ms'
    );
});

$router = $app->createRouter();

$router->get('/',function($test = "Ant-Framework"){
    return (object)$test;
})->addMiddleware(function(Ant\Http\Request $request,Ant\Http\Response $response){
    try{
        $response->setJson(yield);
    }catch(Exception $e){
        $response->withStatus(500);
        $response->setJson($e->getMessage());
    }

    return $response;
});
