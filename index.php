<?php
include 'vendor/autoload.php';

$app = new Ant\Foundation\Cgi\Application(__DIR__);

/* 注册通用中间件 */
$app->addMiddleware(function (Ant\Http\ServerRequest $request, Ant\Http\Response $response) {
    // before
    yield;
    // 设置响应头
    return $response->addHeaderFromIterator([
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
        'X-Run-Time' => (int)((microtime(true) - $request->getServerParam('REQUEST_TIME_FLOAT')) * 1000).'ms'
    ]);
});

/* 根据不同的 accept 格式,响应不同格式的数据,目前支持 json,xml,jsonp */
$app->addMiddleware(
    $app[\Ant\Foundation\Http\Api\Middleware::class]
);

/* 获取路由器 */
$router = $app['router'];

/* 注册路由 */
$router->get('/',function () {
    return "Ant-Framework";
});

$app->run();
