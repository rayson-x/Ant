<?php
include 'vendor/autoload.php';

$app = new Ant\Foundation\Cgi\Application(__DIR__);

// 如果是ajax,启用api中间件
if ($app['request']->isAjax()) {
    /* 注册通用中间件,根据不同的 accept 格式,响应不同格式的数据,目前支持 json,xml,jsonp */
    $app->addMiddleware(new \Ant\Foundation\Http\Api\Middleware);
}

/* 获取路由器 */
$router = $app['router'];

$router->get('/', function () {
    return "Ant-Framework";
});

$app->run();