<?php
include 'vendor/autoload.php';
include 'system/Helper.php';

/**
 * php7错误跟异常都继承于Throwable,可以用try...catch的方式来捕获程序中的错误
 */
if(version_compare(PHP_VERSION, '7.0.0', '<')){
    set_error_handler(function($errno,$errstr,$errfile,$errline){
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    });
}

//TODO::监听异常与错误输出
$app = new Ant\App();

$app->addMiddleware(function($request,$response){
    $start = microtime(true);

    yield;

    $end = (microtime(true) - $start) * 1000;
    $response->withHeader('x-run-time',$end);
});

$app->addMiddleware(function($request,$response)use($app){
    $routes = [
        [['GET','POST'],'index0','a'],
        [['GET','POST'],'index1','b'],
        [['GET','POST'],'index2','c'],
        [['GET','POST'],'index3','d'],
        [['GET','POST'],'index4','e'],
        [['GET','POST'],'index5','f'],
        [['GET','POST'],'index6','g'],
        [['GET','POST'],'index7','h'],
        [['GET','POST'],'index8','i'],
        [['GET','POST'],'index9','j'],
        [['GET','POST'],'index10','k'],
        ['POST',trim('/index/{id:\d+}[/{name}]','/'),function($id,$name = ''){
            echo $id,$name;
        }],
    ];
    /* @var $request Ant\Http\Request */
    $dispatcher = FastRoute\simpleDispatcher(function($r)use($routes){
        /* @var $r FastRoute\RouteCollector */
        foreach($routes as list($method,$path,$handle)){
            $r->addRoute($method,$path,$handle);
        }
    });
    /* @var $dispatcher FastRoute\Dispatcher\GroupCountBased */
    $routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getAttribute('virtualPath'));
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            throw new Ant\Http\Exception(404);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            throw new Ant\Http\Exception(405);
            break;
        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            $app->call($handler,$vars);
            break;
    }
});


//$route = new Ant\Router\RouterRequest();
//
//$route->group([],function($app){
//
//});
//
//$app->addMiddleware([$route,'execute']);
