<?php

namespace Ant\Interfaces;

use Closure;

interface ContainerInterface
{
    /**
     * 检查服务是否绑定.
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound($abstract);

    /**
     * 给服务定义别名.
     *
     * @param  string  $abstract
     * @param  string  $alias
     * @return void
     */
    public function alias($abstract, $alias);

    /**
     * 为一系列服务添加标签.
     *
     * @param  array|string  $abstracts
     * @param  array|mixed   ...$tags
     * @return void
     */
    public function tag($abstracts, $tags);

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
     * @param  string|array  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false);

    /**
     * 当服务未被注册时,注册服务.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bindIf($abstract, $concrete = null, $shared = false);

    /**
     * 注册一个单例服务.
     *
     * @param  string|array  $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    public function singleton($abstract, $concrete = null);

    /**
     * 给指定服务注册扩展回调.
     *
     * @param  string    $abstract
     * @param  \Closure  $closure
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function extend($abstract, Closure $closure);

    /**
     * 注册一个实例到容器中.
     *
     * @param  string  $abstract
     * @param  mixed   $instance
     * @return void
     */
    public function instance($abstract, $instance);

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
     * @param  string  $abstract
     * @param  array   $parameters
     * @return mixed
     */
    public function make($abstract, array $parameters = []);

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
     * @param  string $abstract
     * @return bool
     */
    public function resolved($abstract);
}
