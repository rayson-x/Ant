<?php
include 'vendor/autoload.php';
/* 初始化框架 */
$app = new Ant\App(realpath(__DIR__));

/* 注册应用程序中间件 */
$app->addMiddleware(function (Ant\Http\Request $request,Ant\Http\Response $response){
    // 路由匹配之前执行的代码
    // code...

    // 获取响应信息
    yield;

    // 匹配成功之后执行的代码,如果匹配失败,响应404
    // 此处为匹配成功之后的响应头
    $response->addHeaderFromIterator([
        'expires' => 0,
        'x-powered-by' => '.NET',
        'x-run-time' => (int)((microtime(true) - $request->getServerParam('REQUEST_TIME_FLOAT')) * 1000).'ms',
        'access-control-allow-origin' => '*',
        'Cache-Control' => 'no-cache',
    ]);
});

/* 获取路由器 */
$router = $app['router'];

/* 注册路由 */
$router->group([],function($router){
    $router->get('/',function($request,$response){
        return ['a' => 123,'b' => 123];
    });

    $router->get('/file[/{string}]',function($string,$request,$response){
        return $response->write($string);
    })->setArgument('string','hello world')->addMiddleware(function(){
        // 此路由响应结果为txt文件
        $response = yield;
        $response->addHeaderFromIterator([
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="example.txt"',
            'Content-Transfer-Encoding' => 'binary',
        ]);
    });
});

return $app;