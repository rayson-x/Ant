<?php
namespace Ant\Interfaces;

use Closure;

Interface RouterInterface
{
    /**
     * @param array $rule
     * @param Closure $callback
     * @return void
     */
    public function group(array $rule,Closure $callback);

    /**
     * @param @method
     * @param $path
     * @param $callback
     * @return void
     */
    public function addRoute($method,$path,$callback);

    /**
     * @param $request \Psr\Http\Message\RequestInterface
     * @return mixed
     */
    public function dispatch(\Psr\Http\Message\RequestInterface $request);
}