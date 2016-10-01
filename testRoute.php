<?php
include 'vendor/autoload.php';

$c = Ant\Container\Container::getInstance();

$c->bind([\FastRoute\RouteCollector::class => 'RouteCollector'],function(){
   return new FastRoute\RouteCollector(
       new \FastRoute\RouteParser\Std,
       new \FastRoute\DataGenerator\GroupCountBased
   );
});

$router = new Ant\Router\Router();

$router->group([],function($router){
    $router->get('/test',function(){
        echo 123;
    });
});


