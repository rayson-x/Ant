<?php
namespace Ant\ResponseDecorator;

use Ant\ResponseDecorator\Renderer\XmlRenderer;
use Ant\ResponseDecorator\Renderer\JsonRenderer;
use Ant\ResponseDecorator\Renderer\TextRenderer;
use Ant\ResponseDecorator\Renderer\FileRenderer;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

/**
 * 响应包装器,根据不同的请求响应不同格式的数据
 * 使用最低需求PHP7
 *
 * Class Decorator
 * @package Ant\ResponseDecorator
 */
class Decorator
{
    protected $renderer = [
        'xml'   =>  XmlRenderer::class,
        'file'  =>  FileRenderer::class,
        'json'  =>  JsonRenderer::class,
    ];

    /**
     * @param PsrRequest $request
     * @param PsrResponse $response
     * @return PsrResponse
     */
    public function __invoke(PsrRequest $request, PsrResponse $response)
    {
        $dot = explode('.',$request->getRequestTarget());

        $renderer = $this->selectRenderer([
            array_pop($dot),
            $request->getHeaderLine('accept-type')
        ]);

        try{
            // 获取响应数据
            $result = yield;
        }catch(\Exception $exception){
            // 处理异常
            $handle = new HandleException($exception,true);

            $response->withStatus($handle->getStatus());

            foreach($handle->getHeaders() as $name => $value){
                $response->withAddedHeader($name,$value);
            }

            $result = $handle->getContent();
        }

        if(!$result instanceof PsrResponse){
            // 渲染Response
            $result = $renderer
                ->setWrapped($result)
                ->renderResponse($response);
        }

        return $result;
    }

    /**
     * @param $types
     * @return Renderer
     */
    protected function selectRenderer(array $types)
    {
        $renderer = null;

        foreach($types as $type){
            if(array_key_exists($type,$this->renderer)){
                $renderer = new $this->renderer[$type];
            }
        }

        if(!$renderer){
            $renderer = new TextRenderer;
        }

        return $renderer;
    }
}