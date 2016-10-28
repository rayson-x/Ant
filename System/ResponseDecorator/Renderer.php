<?php
namespace Ant\ResponseDecorator;

use Psr\Http\Message\ResponseInterface;

abstract class Renderer
{
    protected $wrapped;

    public function setWrapped($wrappable)
    {
        $this->wrapped = $wrappable;

        return $this;
    }

    /**
     * 渲染数据
     *
     * @return ResponseInterface
     */
    abstract public function renderResponse(ResponseInterface $response);
}