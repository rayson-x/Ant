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
}