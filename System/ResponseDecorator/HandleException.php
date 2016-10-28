<?php
namespace Ant\ResponseDecorator;

use Psr\Http\Message\ResponseInterface;

class HandleException
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
            $this->content = $exception->getMessage();

            $this->headers = array_merge(
                $this->headers,$this->getExceptionInfo($exception)
            );
        }else{
            $this->content = 'Error';
        }
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

        return $exceptionInfo;
    }

    public function getStatus()
    {
        return $this->statusCode;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getContent()
    {
        return $this->content;
    }
}