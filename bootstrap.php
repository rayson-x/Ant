<?php
include 'vendor/autoload.php';
include 'system/Support/Helper.php';

/**
 * php7错误跟异常都继承于Throwable,可以用try...catch的方式来捕获程序中的错误
 */
if(version_compare(PHP_VERSION, '7.0.0', '<')){
    set_error_handler(function($errno,$errstr,$errfile,$errline){
        throw new \ErrorException($errstr, $errno, $errno, $errfile, $errline);
    });
}

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

$app = new Ant\App();

$app->addMiddleware(function (Ant\Http\Request $request,Ant\Http\Response $response){
    yield ;

    $response->withHeader('x-run-time',(microtime(true) - START) * 1000);
});
/* 将路由中间件装载到应用中 */
$router = new Ant\Router();

$app->addMiddleware([$router,'execute']);

$router->addMiddleware([
    'test' => function(Ant\Http\Request $request,Ant\Http\Response $response){
        $response->withHeader('x-test','test');
    }
]);

$router->group(['middleware'=>'test','prefix'=>'index','namespace'=>'app\\'],function($router){
    $router->get('/',function($request,$response){
        $response->write("Ant-Framework");
    });

    $router->any('/hello[/{name:\w+}]',function($name = 'world',$request,$response){
        $response->write("hello {$name}");
    });
});

