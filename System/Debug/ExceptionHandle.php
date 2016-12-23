<?php
namespace Ant\Debug;

use Exception;
use Ant\Http\Exception\HttpException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Ant\Http\Interfaces\MessageInterface;
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
            // http异常始终返回错误信息
            $debug = true;
            if($exception instanceof MethodNotAllowedException && $request->getMethod() === 'OPTIONS'){
                //如果请求方法为Options,并且该方法不存在,响应允许请求的方法
                return $response->withStatus(200)->withHeader('Access-Control-Allow-Methods',implode(',',$exception->getAllowedMethod()));
            }

            $fe = FlattenException::create($exception,$exception->getStatusCode(),$exception->getHeaders());
        }else{
            $fe = FlattenException::create($exception);
        }

        // 处理异常
        $handler = new SymfonyExceptionHandler($debug);

        // 设置响应码
        $response->withStatus($fe->getStatusCode());

        // 添加响应头
        $headers = $fe->getHeaders();
        $debug && $headers += $this->getExceptionInfo($exception);
        foreach($headers as $name => $value){
            $response->withAddedHeader($name,$value);
        }

        if(!$result = $this->tryResponseClientAcceptType($exception,$request,$response,$debug)){
            // 无法返回客户端想要的类型时,默认返回html格式
            $response->getBody()->write(
                $this->decorate($handler->getContent($fe), $handler->getStylesheet($fe))
            );
            $result = $response;
        }

        return $result;
    }

    /**
     * 尝试响应客户端请求的类型
     *
     * @param Exception $e
     * @param RequestInterface $req
     * @param ResponseInterface $res
     * @param $debug
     * @return false|\Psr\Http\Message\MessageInterface
     */
    protected function tryResponseClientAcceptType(Exception $e, RequestInterface $req, ResponseInterface $res, $debug)
    {
        if(
            !method_exists($req,'getAcceptType')
            || !$res instanceof MessageInterface
            || 'html' == $type = $req->getAcceptType()
        ) {
            return false;
        }

        try{
            return $res->selectRenderer($type)
                ->setPackage([
                    'code'      =>  (int)$e->getCode(),
                    'message'   =>  $debug && $e->getMessage() ? $e->getMessage() : 'error'
                ])
                ->decorate();
        }catch(\Exception $e){
            return false;
        }
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