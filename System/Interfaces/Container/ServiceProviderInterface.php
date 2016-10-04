<?php
namespace Ant\Interfaces\Container;

/**
 * 服务提供者
 *
 * Interface ServiceProviderInterface
 * @package Ant\Interfaces
 */
interface ServiceProviderInterface
{
    /**
     * 将服务绑定至服务容器中
     *
     * @param ContainerInterface $container
     * @return mixed
     */
    public function register(ContainerInterface $container);
}