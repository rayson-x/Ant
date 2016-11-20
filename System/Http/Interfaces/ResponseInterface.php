<?php
namespace Ant\Http\Interfaces;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface ResponseInterface extends PsrResponseInterface
{
    /**
     * @param $name
     * @param $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool|false $secure
     * @param bool|false $httponly
     * @return $this
     */
    public function setCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false);

    /**
     * @return array
     */
    public function getCookies();
}