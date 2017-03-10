<?php
namespace Ant\Foundation\Debug;

use Exception;
use Ant\Http\Response;
use Ant\Http\Exception\HttpException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;

/**
 * 异常处理
 *
 * Class ExceptionHandle
 * @package Ant\Foundation\Debug
 */
class ExceptionHandle
{
    /**
     * @param Exception $exception
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param bool|true $debug
     * @return ResponseInterface
     */
    public function render (
        Exception $exception,
        RequestInterface $request,
        ResponseInterface $response,
        $debug = true
    ) {
        $fe = (!$exception instanceof HttpException)
            ? FlattenException::create($exception)
            : FlattenException::create($exception,$exception->getStatusCode(),$exception->getHeaders());

        // 设置响应码
        $response = $response->withStatus($fe->getStatusCode());

        // 处理异常
        $handler = new SymfonyExceptionHandler($debug);

        // 无法返回客户端想要的类型时,默认返回html格式
        $response->getBody()->write(
            $this->decorate($handler->getContent($fe), $handler->getStylesheet($fe))
        );

        return $response;
    }

    /**
     * 装饰错误信息.
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