<?php
namespace Ant\Routing;

use Ant\Routing\Renderer\XmlRenderer;
use Ant\Routing\Renderer\JsonRenderer;
use Ant\Routing\Renderer\FileRenderer;
use Ant\Routing\Renderer\HtmlRenderer;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

/**
 * Class Decorator
 * @package Ant\ResponseDecorator
 */
class Decorator
{
    //Todo:;渲染不应该由路由完成,应该跟响应关联
    protected static $renderer = [
        'xml'   =>  XmlRenderer::class,
        'file'  =>  FileRenderer::class,
        'json'  =>  JsonRenderer::class,
        'html'  =>  HtmlRenderer::class,
    ];

    /**
     * @param $type
     * @return \Ant\Routing\Renderer\Renderer
     */
    public static function selectRenderer($type)
    {
        if(!array_key_exists($type,static::$renderer)){
            throw new \RuntimeException();
        }

        return new static::$renderer[$type];
    }
}