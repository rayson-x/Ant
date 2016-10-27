<?php
namespace Ant\ResponseDecorator;

/**
 * 渲染器
 *
 * Interface RendererInterface
 * @package Ant\ResponseDecorator
 */
interface RendererInterface
{
    /**
     * 渲染数据
     *
     * @return string
     */
    public function renderData();
}