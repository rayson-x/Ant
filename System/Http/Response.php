<?php
namespace Ant\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;

class Response extends Message implements ResponseInterface{

    protected $code = 200;

    protected $responsePhrase;

    public function __construct($code = 200,Header $header = null,StreamInterface $body = null)
    {
        //TODO::快捷响应错误状态
        $this->code = $code;
        $this->headers = $header ? : new Header();
        $this->body = $body ? : new Body(fopen('php://temp', 'r+'));
    }

    public function getStatusCode()
    {
        return $this->code;
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        if(!is_integer($code) || $code < 100 || $code > 599){
            throw new InvalidArgumentException('Invalid HTTP status code');
        }

        $this->code = $code;
        $this->responsePhrase = (string) $reasonPhrase;

        return $this;
    }

    public function getReasonPhrase()
    {
        if($this->responsePhrase){
            return $this->responsePhrase;
        }

        return $this->responsePhrase = StatusPhrase::getStatusPhrase($this->code);
    }
}