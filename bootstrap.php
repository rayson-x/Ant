<?php
include 'vendor/autoload.php';
/* 初始化框架 */
$app = new Ant\App(realpath(__DIR__));

/* 注册应用程序中间件 */
$app->addMiddleware(function (Ant\Http\Request $request,Ant\Http\Response $response){
    // 路由匹配之前执行的代码
    // code...

    // 获取响应信息
    $response = yield;

    // 匹配成功之后执行的代码,如果匹配失败,响应404
    // 此处为匹配成功之后的响应头
    $newResponse = $response->addHeaderFromIterator([
        'Expires' => gmdate("D, d M Y H:i:s T"),
        'X-Powered-By' => '.NET',
        'x-run-time' => (int)((microtime(true) - $request->getServerParam('REQUEST_TIME_FLOAT')) * 1000).'ms',
    ]);

    // 根据PHP版本选择中间件返回值的方式
    if(version_compare(PHP_VERSION, '7.0.0', '>=')){
        return $newResponse;
    }else{
        yield $newResponse;
    }
});

/* 获取路由器 */
$router = $app['router'];

/* 注册路由 */
$router->get('/',function($request,$response){
    return $response->write('hello world');
});

$router->get('/getFile/[{string}]',function($string,$request,$response){
    return $response->write($string);
})->setArgument('string','hello world')->addMiddleware(function(){
    // 此路由响应结果为txt文件
    $oldResponse = yield;
    $newResponse = $oldResponse->addHeaderFromIterator([
        'Content-Type' => 'application/octet-stream',
        'Content-Disposition' => 'attachment; filename="example"',
        'Content-Transfer-Encoding' => 'binary',
    ]);
    return $newResponse;
});

return $app;