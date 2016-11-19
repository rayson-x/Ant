<?php
namespace Ant\Http\Message;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Ant\Interfaces\Http\RendererInterface;

abstract class Renderer implements RendererInterface
{
    /**
     * @var mixed 包裹
     */
    protected $package;

    /**
     * 设置包裹
     *
     * @param $package
     * @return $this
     */
    public function setPackage($package)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * @param MessageInterface $http
     * @return string
     */
    public function getCharset(MessageInterface $http)
    {
        return ($http instanceof ResponseInterface) ? ';charset=utf-8' : '';
    }
}