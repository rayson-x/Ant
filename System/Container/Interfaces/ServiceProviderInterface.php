<?php
namespace Ant\Container\Interfaces;

/**
 * 服务提供者
 *
 * Interface ServiceProviderInterface
 * @package Ant\Interfaces
 */
interface ServiceProviderInterface
{
    /**
     * 注册服务
     *
     * @param ContainerInterface $container
     */
    public function register(ContainerInterface $container);
}