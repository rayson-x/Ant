<?php
include 'vendor/autoload.php';

$app = new Ant\App(realpath(__DIR__));

$app->addMiddleware(function (Ant\Http\Request $request,Ant\Http\Response $response){
    yield;
    //获取脚本运行时间(ms)
    $response->withHeader(
        'x-run-time',(int)((microtime(true) - $request->getServerParam('REQUEST_TIME_FLOAT')) * 1000).'ms'
    );
});

$router = $app->createRouter();

$router->get('/',function($request,$response){
    $response->setCookie('test','test');
    return [123,123];
});

$router->post('/',function(){
    var_dump(post());
});

return $app;
