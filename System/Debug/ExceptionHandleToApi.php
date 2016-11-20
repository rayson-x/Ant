<?php
namespace Ant\Debug;

use Exception;
use Ant\Http\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;

/**
 * Api的异常处理
 *
 * Class ExceptionHandleToApi
 * @package Ant\Debug
 */
class ExceptionHandleToApi
{
    protected $renderer = 'json_encode';

    /**
     * @param Exception $exception
     * @param ResponseInterface $response
     * @param bool|true $debug
     */
    public function render(Exception $exception, ResponseInterface $response, $debug = true)
    {
        $headers = [];
        if($exception instanceof HttpException){
            // 获取HTTP状态码
            $statusCode = $exception->getStatusCode();
            $headers = $exception->getHeaders();
            $message = $exception->getMessage();
        }else{
            $statusCode = 500;
            $message = $debug ? $exception->getMessage() : null;
        }

        if($debug){
            $headers = array_merge($headers,$this->getExceptionInfo($exception));
        }

        foreach($headers as $name => $value){
            $response->withAddedHeader($name,$value);
        }

        $response->withStatus($statusCode);

        $response->getBody()->write($this->decorate([
            "error" =>  [
                'code'      => $exception->getCode() ,
                'message'   => $message ?: $response->getReasonPhrase(),
            ]
        ]));
    }

    /**
     * 注册渲染器
     *
     * @param callable $renderer
     */
    public function registerRenderer(callable $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * @param $content
     * @return mixed
     */
    protected function decorate($content)
    {
        return call_user_func($this->renderer,$content);
    }

    /**
     * 获取错误信息
     *
     * @param $exception
     * @return array
     */
    protected function getExceptionInfo(\Exception $exception)
    {
        if($exception->getPrevious()){
            // 返回异常链中的前一个异常的信息
            return $this->getExceptionInfo($exception->getPrevious());
        }

        $exceptionInfo = [];
        $exceptionInfo['X-Exception-Message'] = $exception->getMessage();

        foreach(explode("\n",$exception->getTraceAsString()) as $index => $line){
            $key = sprintf('X-Exception-Trace-%02d', $index);
            $exceptionInfo[$key] = $line;
        }

        array_pop($exceptionInfo);

        return $exceptionInfo;
    }
}