<?php
namespace Ant\Debug;

use Exception;
use Ant\Http\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;
use Ant\Http\Interfaces\RequestInterface;
use Ant\Http\Exception\MethodNotAllowedException;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;

/**
 * Class ExceptionHandle
 * @package Ant\Debug
 */
class ExceptionHandle
{
    /**
     * @param Exception $exception
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function render(Exception $exception, RequestInterface $request,ResponseInterface $response, $debug = true)
    {
        if($exception instanceof HttpException){
            $fe = FlattenException::create($exception,$exception->getStatusCode(),$exception->getHeaders());
        }else{
            $fe = FlattenException::create($exception);
        }

        $handler = new SymfonyExceptionHandler($debug);

        foreach($fe->getHeaders() as $name => $value){
            $response->withAddedHeader($name,$value);
        }

        $response->withStatus($fe->getStatusCode());

        if($exception instanceof MethodNotAllowedException && $request->getMethod() === 'OPTIONS'){
            //如果请求方法为Options,并且该方法不存在,响应允许请求的方法
            $response->withStatus(200);
            $response->withHeader('Access-Control-Allow-Methods',implode(',',$exception->getAllowedMethod()));
        }else{
            $response->getBody()->write(
                $this->decorate($handler->getContent($fe), $handler->getStylesheet($fe))
            );
        }

        return $response;
    }

    /**
     * 获取响应内容.
     *
     * @param  string  $content
     * @param  string  $css
     * @return string
     */
    protected function decorate($content, $css)
    {
        return <<<EOF
<!DOCTYPE html>
<html>
    <head>
        <meta name="robots" content="noindex,nofollow" />
        <style>
            $css
        </style>
    </head>
    <body>
        $content
    </body>
</html>
EOF;
    }
}