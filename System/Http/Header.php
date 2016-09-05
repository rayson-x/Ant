<?php
namespace Ant\Http;

use Ant\Collection;

class Header extends Collection{

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
     * @param Collection $server
     * @return static
     */
    public static function createFromCollection(Collection $server)
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

        return new static($headers);
    }
}