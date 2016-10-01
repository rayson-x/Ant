<?php
namespace Ant\Interfaces\Router;

Interface RouterInterface
{
    public function group(array $attributes,\Closure $action);

    public function map($method,$uri,$action);
    
    public function dispatch($request);
}