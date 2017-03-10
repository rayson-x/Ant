<?php
namespace Ant\Foundation\Http\Api\Decorator;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Ant\Http\Exception\NotAcceptableException;

/**
 * Class RendererFactory
 * @package Ant\Foundation\Http\Api\Decorator
 */
class RendererFactory
{
    /**
     * 装饰器列表
     *
     * @var array
     */
    protected static $renderer = [
        'xml'           =>  XmlRenderer::class,
        'file'          =>  FileRenderer::class,
        'json'          =>  JsonRenderer::class,
        'text'          =>  TextRenderer::class,
        'html'          =>  TextRenderer::class,
        'javascript'    =>  JsonpRenderer::class,
    ];

    /**
     * 选择装饰器
     *
     * @param $type
     * @return Renderer
     */
    public static function create(ResponseInterface $response, $type)
    {
        if (!is_string($type)) {
            throw new InvalidArgumentException('type must be a string');
        }

        if (!array_key_exists($type,static::$renderer)) {
            throw new NotAcceptableException('Decorative device does not exist');
        }

        $renderer = static::$renderer[$type];

        return new $renderer($response);
    }
}