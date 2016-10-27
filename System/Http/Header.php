<?php
namespace Ant\Http;

/**
 * Todo::重构Header
 *
 * Class Header
 * @package Ant\Http
 */
class Header
{
    /**
     * 在“$_SERVER”中不是以“HTTP_”开头的Http头
     *
     * @var array
     */
    protected static $special = [
        'CONTENT_TYPE' => 1,
        'CONTENT_LENGTH' => 1,
        'PHP_AUTH_USER' => 1,
        'PHP_AUTH_PW' => 1,
        'PHP_AUTH_DIGEST' => 1,
        'AUTH_TYPE' => 1,
    ];

    /**
     * 获取header数据集
     *
     * @param Environment $server
     * @return array
     */
    public static function createFromEnvironment(Environment $server)
    {
        $headers = [];
        foreach ($server as $key => $value) {
            //提取HTTP头
            if (isset(static::$special[$key]) || strpos($key, 'HTTP_') === 0) {
                $key = strtolower(str_replace('_', '-', $key));
                $key = (strpos($key, 'http-') === 0) ? substr($key, 5) : $key;
                $headers[$key] = explode(',', $value);
            }
        }

        return $headers;
    }
}