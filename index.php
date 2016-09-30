<?php
define('START',microtime(true));
//require __DIR__.'/bootstrap.php';
//
//$app->run();

class route
{
    protected $attributes;

    public function __construct()
    {

    }

    public function getMethod(){}

    public function getUri(){}

    public function getAction(){}


}
$routes = [];

foreach(range(0,5000) as $num){
    $routes[] = [$num];
//    $routes[] = new test($num);
}

echo (microtime(true) - START) * 1000;