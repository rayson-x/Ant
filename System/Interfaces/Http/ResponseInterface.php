<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2016/11/19
 * Time: 19:28
 */

namespace Ant\Interfaces\Http;

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
     * @return mixed
     */
    public function setCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false);
}