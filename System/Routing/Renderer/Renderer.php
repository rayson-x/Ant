<?php
namespace Ant\Routing\Renderer;

use Psr\Http\Message\ResponseInterface;

abstract class Renderer
{
    protected $wrapped;

    /**
     * 设置数据
     *
     * @param $wrappable
     * @return $this
     */
    public function setWrapped($wrappable)
    {
        $this->wrapped = $wrappable;

        return $this;
    }

    /**
     * 渲染响应数据
     *
     * @return ResponseInterface
     */
    abstract public function renderResponse(ResponseInterface $response);
}