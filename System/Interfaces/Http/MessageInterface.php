<?php
namespace Ant\Interfaces\Http;

use Psr\Http\Message\MessageInterface as PsrMessageInterface;

interface MessageInterface extends PsrMessageInterface
{
    /**
     * ѡ��Bodyװ����
     *
     * @param $type
     * @return RendererInterface
     */
    public function selectRenderer($type);

    /**
     * ����Bodyװ����
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