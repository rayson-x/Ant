<?php
namespace Ant\Interfaces\Router;

interface RouteInterface
{
    /**
     * 获取路由请求方式
     *
     * @return string
     */
    public function getMethod();

    /**
     * 获取路由uri
     *
     * @return string
     */
    public function getUri();

    /**
     * 获取路由将要执行的动作
     *
     * @return self|array
     */
    public function getAction();

    /**
     * 获取路由回调
     *
     * @return callable|string
     */
    public function getCallable();

    /**
     * 获取路由需要的中间件
     *
     * @return array
     */
    public function getMiddleware();

    /**
     * 替换现有中间件
     *
     * @param $middleware
     */
    public function replaceMiddleware($middleware);
    
    /**
     * 添加一个中间件
     *
     * @param $middleware
     */
    public function addMiddleware($middleware);

    /**
     * 重置中间件
     */
    public function resetMiddleware();

    /**
     * 获取一个默认路由参数
     *
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function getArgument($name, $default = null);

    /**
     * 获取所有默认路由参数
     *
     * @return array
     */
    public function getArguments();

    /**
     * 设置一个默认的路由参数
     *
     * @param $name
     * @param null $default
     */
    public function setArgument($name, $default = null);

    /**
     * 设置一组默认的路由参数
     *
     * @param array $arguments
     */
    public function setArguments(array $arguments);
}