<?php
namespace Ant\Router;

use Ant\Interfaces\Router\RouteInterface;
use FastRoute\RouteCollector as FastRouteCollector;

Class RouteCollector extends FastRouteCollector
{
    public function addRoute(RouteInterface $route) {
        parent::addRoute(
            $route->getMethod(),
            $route->getUri(),
            $route->getCallable()
        );
    }
}