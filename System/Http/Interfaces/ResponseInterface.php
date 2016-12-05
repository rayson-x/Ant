<?php
namespace Ant\Http\Interfaces;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface ResponseInterface extends PsrResponseInterface
{
    /**
     * 根据Http响应字符串创建一个Response对象
     *
     * @param $receiveBuffer
     * @return self
     */
    public static function createFromResponseStr($receiveBuffer);

    /**
     * 通过Http头数组跟响应Body创建一个Response对象
     *
     * @param array $header
     * @param $bodyBuffer
     * @return self
     */
    public static function createFromRequestResult(array $header, $bodyBuffer);

    /**
     * 设置Cookie
     *
     * @param $name
     * @param $value
     * return $this
     */
    public function setCookie($name, $value);

    /**
     * 获取所有Cookie
     *
     * @return array
     */
    public function getCookies();
}