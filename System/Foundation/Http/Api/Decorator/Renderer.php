<?php
namespace Ant\Foundation\Http\Api\Decorator;

use Psr\Http\Message\MessageInterface as PsrMessage;

abstract class Renderer
{
    /**
     * 响应类型
     *
     * @var string
     */
    public $type = null;

    /**
     * 响应编码
     *
     * @var string
     */
    public $charset = 'utf-8';

    /**
     * 待装饰的包裹
     *
     * @var mixed
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
     * @return string
     */
    public function getType()
    {
        return $this->type.'; charset='.$this->charset;
    }

    /**
     * 装饰包裹
     *
     * @return PsrMessage
     */
    abstract public function decorate(PsrMessage $http);
}