<?php
namespace Ant\Interfaces\Router;

use Psr\Http\Message\RequestInterface as PsrRequest;
use Psr\Http\Message\ResponseInterface as PsrResponse;

/**
 * 路由器接口类
 *
 * Interface RouterInterface
 * @package Ant\Interfaces\Router
 */
Interface RouterInterface
{
    /**
     * 创建一组路由,共用路由属性
     *
     * @param array $attributes
     * @param \Closure $action
     */
    public function group(array $attributes,\Closure $action);

    /**
     * 创建一条路由映射
     *
     * @param $method
     * @param $uri
     * @param $action
     */
    public function map($method,$uri,$action);

    /**
     * 路由分发
     *
     * @param PsrRequest $request
     * @param PsrResponse $response
     * @return PsrResponse
     */
    public function dispatch(PsrRequest $request, PsrResponse$response);
}