<?php
namespace Ant\ResponseDecorator;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

/**
 * 这是一个针对Api的中间件
 * 它主要工作是负责响应不同类型的数据
 * 根据Http Request的数据判断返回的数据类型
 *
 * Class Decorator
 * @package Ant\ResponseDecorator
 */
class Decorator
{
    protected $renderer = [
        'json'  =>  \Ant\ResponseDecorator\Renderer\JsonRenderer::class,
        'xml'   =>  \Ant\ResponseDecorator\Renderer\XmlRenderer::class,
    ];

    public function __invoke(PsrRequest $request, PsrResponse $response)
    {
        try{
            //Todo::选择装饰器
            $result = yield;
        }catch(\Exception $exception){
            $handle = new HandleException($exception,true);

            $response->withStatus($handle->getStatus());

            foreach($handle->getHeaders() as $name => $value){
                $response->withAddedHeader($name,$value);
            }

            $result = $handle->getContent();
        }

        return $response->getBody()->write(
            (new $this->renderer['json']($result))->renderData()
        );
    }
}