<?php
include 'vendor/autoload.php';
/* 初始化框架 */
$app = new Ant\App(realpath(__DIR__));

/* 注册应用程序中间件 */
$app->addMiddleware(function (Ant\Http\Request $request,Ant\Http\Response $response){
    // code...

    yield;

    // 设置响应头
    $response->addHeaderFromIterator([
        // 超时时间
        'Expires' => 0,
        // 程序支持
        'X-Powered-By' => 'Ant-Framework',
        // 允许暴露给客户端访问的字段
        'Access-Control-Expose-Headers' => '*',
        // 设置跨域信息
        'Access-Control-Allow-Origin' => 'www.example.com',
        // 是否允许携带认证信息
        'Access-Control-Allow-Credentials' => 'false',
        // 缓存控制
        'Cache-Control' => 'no-cache',
        // 脚本运行时间
        'X-Run-Time' => (int)((microtime(true) - $request->getServerParam('REQUEST_TIME_FLOAT')) * 1000).'ms',
    ]);
});

/* 获取路由器 */
$router = $app['router'];

/* 注册路由 */
$router->group(['type' => ['json','xml','html']],function(Ant\Routing\Router $router){
    $router->get('/',function(){
        return "Ant-Framework";
    });
});

return $app;