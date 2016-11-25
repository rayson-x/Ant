<?php
namespace Ant\Http\Interfaces;

use Psr\Http\Message\RequestInterface as PsrRequest;

interface RequestInterface extends PsrRequest
{
    /**
     * 通过Http请求的字符流生成Request对象
     *
     * @param $receiveBuffer
     * @return self
     */
    public static function createFromRequestStr($receiveBuffer);
}