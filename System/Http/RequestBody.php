<?php
namespace Ant\Http;

class RequestBody extends Body
{
    /**
     * 获取请求内容
     */
    public static function createFromCgi()
    {
        $stream = fopen('php://temp','w+');
        stream_copy_to_stream(fopen('php://input','r'),$stream);
        rewind($stream);

        return new static($stream);
    }

    /**
     * 通过Tcp流获取请求内容
     *
     * @param string $stream
     */
    public static function createFromTcpStream($stream)
    {
        if(!is_string($stream)){
            throw new \InvalidArgumentException("");
        }

        $body = new static(fopen("php://temp","w+"));
        return $body->write($stream);
    }

    /**
     * 解析表单内容
     *
     * @param string $body              请求主体
     * @param string $bodyBoundary      body分割标示
     */
    public static function parseForm($body,$bodyBoundary)
    {

    }
}