<?php
namespace Ant\Interfaces\Router;

interface RouteInterface
{
    public function getMethod();

    public function getUri();

    public function getAction();
}