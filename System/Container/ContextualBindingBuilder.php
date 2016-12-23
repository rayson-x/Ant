<?php
namespace Ant\Container;

class ContextualBindingBuilder
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * 实例的服务名称
     *
     * @var
     */
    protected $concrete;

    /**
     * 需要的参数名称
     *
     * @var
     */
    protected $needs;

    /**
     * @param Container $container
     * @param $concrete
     */
    public function __construct(Container $container, $concrete)
    {
        $this->concrete = $concrete;
        $this->container = $container;
    }

    /**
     * 依赖的参数或实例
     *
     * @param $abstract
     * @return $this
     */
    public function needs($abstract)
    {
        $this->needs = $abstract;

        return $this;
    }

    /**
     * 传入的参数
     *
     * @param $implementation
     */
    public function give($implementation)
    {
        $this->container->addContextualBinding($this->concrete, $this->needs, $implementation);
    }
}