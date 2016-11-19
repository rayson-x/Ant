<?php
namespace Ant\Interfaces\Http;

/**
 * Interface RendererInterface
 * @package Ant\Interfaces\Http
 */
interface RendererInterface
{
    /**
     * ���ð���
     *
     * @param $package
     * @return $this
     */
    public function setPackage($package);

    /**
     * װ�ΰ���
     *
     * @param \Psr\Http\Message\MessageInterface $http
     * @return \Psr\Http\Message\MessageInterface
     */
    public function decorate(\Psr\Http\Message\MessageInterface $http);
}