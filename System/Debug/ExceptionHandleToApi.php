<?php
namespace Ant\Debug;

/**
 * Api的异常处理
 *
 * Class ExceptionHandleToApi
 * @package Ant\Debug
 *
 * @example
 *
 * //注册应用程序异常捕获之后的处理函数
 * $app->registerExceptionHandler(function($exception,$request,$response){
 *     $handle = new \Ant\Debug\ExceptionHandleToApi($exception);
 *     //设置响应状态码
 *     $response->withStatus($handle->getStatusCode());
 *     //设置Http头
 *     $response->addHeaderFromIterator($handle->getHeaders());
 *     //写入Body内容
 *     $response->write(safe_json_encode($handle->getContent()));
 * });
 */
class ExceptionHandleToApi
{
    protected $statusCode;

    protected $headers = [];

    protected $content;

    protected $debugEnable = true;

    /**
     * HandleException constructor.
     *
     * @param \Exception $exception
     * @param bool|true $debug
     */
    public function __construct(\Exception $exception, $debug = true)
    {
        if($exception instanceof \Ant\Exception\HttpException){
            // 获取HTTP状态码
            $this->statusCode = $exception->getStatusCode();
            $this->headers = $exception->getHeaders();
        }else{
            $this->statusCode = 500;
        }

        if($debug){
            $this->headers = array_merge(
                $this->headers,$this->getExceptionInfo($exception)
            );
        }

        $this->content = [
            'status'    => $this->statusCode ,
            'message'   => $exception->getMessage() ?: 'Error'
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
        if($exception->getPrevious()){
            // 返回异常链中的前一个异常的信息
            return $this->getExceptionInfo($exception->getPrevious());
        }

        $exceptionInfo = [];
        $exceptionInfo['Exception'] = sprintf(
            "{$exception->getFile()}({$exception->getLine()}) %s %s",get_class($exception),$exception->getMessage()
        );

        foreach(explode("\n",$exception->getTraceAsString()) as $index => $line){
            $key = sprintf('X-Exception-Trace-%02d', $index);
            $exceptionInfo[$key] = $line;
        }

        array_pop($exceptionInfo);

        return $exceptionInfo;
    }

    /**
     * 获取HTTP状态码
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * 获取HTTP头
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * 获取错误内容
     *
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }
}