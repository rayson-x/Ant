<?php

namespace Ant\Interfaces;

use Closure;

interface ContainerInterface
{
    /**
     * 检查服务是否绑定.
     *
     * @param  string  $server
     * @return bool
     */
    public function bound($server);

    /**
     * 给服务定义别名.
     *
     * @param  string  $server
     * @param  string  $alias
     * @return void
     */
    public function alias($server, $alias);

    /**
     * 为一系列服务添加标签.
     *
     * @param  array|string  $server
     * @param  array|mixed   ...$tags
     * @return void
     */
    public function tag($server, $tags);

    /**
     * 将指定标签的服务进行实例化.
     *
     * @param  array  $tag
     * @return array
     */
    public function tagged($tag);

    /**
     * 注册一个服务到容器中.
     *
     * @param  string|array  $server
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bind($server, $concrete = null, $shared = false);

    /**
     * 当服务未被注册时,注册服务.
     *
     * @param  string  $server
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bindIf($server, $concrete = null, $shared = false);

    /**
     * 注册一个单例服务.
     *
     * @param  string|array  $server
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    public function singleton($server, $concrete = null);

    /**
     * 给指定服务注册扩展回调.
     *
     * @param  string    $server
     * @param  \Closure  $closure
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function extend($server, Closure $closure);

    /**
     * 注册一个实例到容器中.
     *
     * @param  string  $server
     * @param  mixed   $instance
     * @return void
     */
    public function instance($server, $instance);

    /**
     * 定义上下文绑定.
     *
     * @param  string  $concrete
     * @return \Ant\Interfaces\ContextualBindingBuilderInterface
     */
    public function when($concrete);

    /**
     * 从容器中获取实例.
     *
     * @param  string  $server
     * @param  array   $parameters
     * @return mixed
     */
    public function make($server, array $parameters = []);

    /**
     * 调用给定的 Closure | class@method，并注入它的依赖关系.
     *
     * @param  callable|string  $callback
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = null);

    /**
     * 确定给定的抽象类型是否已被解决.
     *
     * @param  string $server
     * @return bool
     */
    public function resolved($server);
}
