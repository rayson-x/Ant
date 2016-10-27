<?php
namespace Ant\ResponseDecorator;

abstract class Renderer implements RendererInterface
{
    protected $wrapped;

    public function __construct($wrappable)
    {
        $this->wrapped = $wrappable;
    }

    public function renderData()
    {
        throw new \RuntimeException('RendererInterface is not implemented');
    }
}