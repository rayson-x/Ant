<?php
namespace Ant\Http\Message;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Ant\Interfaces\Http\RendererInterface;

abstract class Renderer implements RendererInterface
{
    /**
     * 待装饰的包裹
     *
     * @var mixed
     */
    protected $package;

    /**
     * 响应编码
     *
     * @var string
     */
    protected $charset = 'utf-8';

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
        return ($http instanceof ResponseInterface) ? ';charset='.$this->charset : '';
    }
}