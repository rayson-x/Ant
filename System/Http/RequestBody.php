<?php
namespace Ant\Http;

class RequestBody extends Body
{
    /**
     * 获取请求内容
     *
     * RequestBody constructor.
     */
    public function __construct()
    {
        $stream = fopen('php://temp','w+');
        stream_copy_to_stream(fopen('php://input','r'),$stream);
        rewind($stream);

        parent::__construct($stream);
    }

}