<?php
namespace Ant\Foundation\Http\Api\Exception;

use Exception;
use Ant\Http\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;
use Ant\Foundation\Http\Api\Decorator\RendererFactory;

/**
 * 异常处理
 *
 * Class ExceptionHandle
 * @package Ant\Foundation\Debug
 */
class Handler
{
    /**
     * @param Exception $e
     * @param ResponseInterface $response
     * @param string $rendererType
     * @param bool|true $debug
     * @return \Psr\Http\Message\MessageInterface
     */
    public function render (
        Exception $e,
        ResponseInterface $response,
        $rendererType,
        $debug = true
    ) {
        $headers  = [];
        $statusCode = 500;

        if ($e instanceof HttpException) {
            $headers = $e->getHeaders();
            $statusCode = $e->getStatusCode();
        }

        // 设置响应码
        $response = $response->withStatus($statusCode);

        // 添加响应头
        $debug && $headers += $this->getExceptionInfo($e);
        foreach ($headers as $name => $value) {
            $response = $response->withAddedHeader($name,$value);
        }

        // 设置错误信息
        return RendererFactory::create($response, $rendererType)
            ->setPackage($this->getErrorInfo($e, $debug))
            ->decorate();
    }

    /**
     * 获取错误详细信息
     *
     * @param Exception $e
     * @param bool|true $debug
     * @return array
     */
    protected function getErrorInfo(\Exception $e, $debug = true)
    {
        return [
            'error'     =>  [
                'code'          =>  $e->getCode(),
                'message'       =>  $debug && $e->getMessage() ? $e->getMessage() : 'error',
            ]
        ];
    }

    /**
     * 获取错误信息
     *
     * @param $exception
     * @return array
     */
    protected function getExceptionInfo(\Exception $exception)
    {
        if ($exception->getPrevious()) {
            // 返回异常链中的前一个异常的信息
            return $this->getExceptionInfo($exception->getPrevious());
        }

        $exceptionInfo = [];
        $exceptionInfo['X-Exception-Message'] = $exception->getMessage();

        foreach(explode("\n",$exception->getTraceAsString()) as $index => $line) {
            $key = sprintf('X-Exception-Trace-%02d', $index);
            $exceptionInfo[$key] = $line;
        }

        array_pop($exceptionInfo);

        return $exceptionInfo;
    }
}