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
     * @param string $data
     * @return static
     */
    public static function createFromTcpStream($data)
    {
        if(!is_string($data)){
            throw new \InvalidArgumentException("");
        }

        $stream = fopen("php://temp","w+");
        fwrite($stream,$data);
        rewind($stream);

        return new static($stream);
    }
}