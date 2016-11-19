<?php
namespace Ant\Interfaces\Http;

/**
 * Interface RendererInterface
 * @package Ant\Interfaces\Http
 */
interface RendererInterface
{
    /**
     * 设置包裹
     *
     * @param $package
     * @return $this
     */
    public function setPackage($package);

    /**
     * 装饰包裹
     *
     * @param \Psr\Http\Message\MessageInterface $http
     * @return \Psr\Http\Message\MessageInterface
     */
    public function decorate(\Psr\Http\Message\MessageInterface $http);
}