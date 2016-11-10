<?php
include 'vendor/autoload.php';

$request = <<<EOT
GET /test HTTP/1.1
Host: 127.0.0.1
Connection: keep-alive
Cache-Control: max-age=0
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.87 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8
Accept-Encoding: gzip, deflate, sdch, br
Accept-Language: zh-CN,zh;q=0.8
Cookie: PHPSESSID=8t3l0bqeot5ralsj57c6deoee0

Test
EOT;

Ant\Http\Request::createFromRequestString($request);
/* 初始化框架 */
//$app = new Ant\App(realpath(__DIR__));

/* 注册应用程序中间件 */
//$app->addMiddleware(function (Ant\Http\Request $request,Ant\Http\Response $response){
//    // code...
//
//    yield;
//
//    // 设置响应头
//    $response->addHeaderFromIterator([
//        // 超时时间
//        'Expires' => 0,
//        // 程序支持
//        'X-Powered-By' => 'Ant-Framework',
//        // 允许暴露给客户端访问的字段
//        'Access-Control-Expose-Headers' => '*',
//        // 设置跨域信息
//        'Access-Control-Allow-Origin' => 'www.example.com',
//        // 是否允许携带认证信息
//        'Access-Control-Allow-Credentials' => 'false',
//        // 缓存控制
//        'Cache-Control' => 'no-cache',
//        // 脚本运行时间
//        'X-Run-Time' => (int)((microtime(true) - $request->getServerParam('REQUEST_TIME_FLOAT')) * 1000).'ms',
//    ]);
//});

/* 获取路由器 */
//$router = $app['router'];

/* 注册路由 */
//$router->group(['type' => ['json','xml','html']],function(Ant\Routing\Router $router){
//    $router->any('test/',function(){
//        return 'Ant-Framework';
//    });
//});

//return $app;