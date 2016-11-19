<?php
namespace Ant\Interfaces\Http;

use Psr\Http\Message\MessageInterface as PsrMessageInterface;

interface MessageInterface extends PsrMessageInterface
{
    /**
     * 选择Body装饰器
     *
     * @param $type
     * @return RendererInterface
     */
    public function selectRenderer($type);

    /**
     * 设置Body装饰器
     *
     * @param $type
     * @param RendererInterface $renderer
     * @return void
     */
    public function setRenderer($type,RendererInterface $renderer);

    /**
     * @return string
     */
    public function __toString();
}