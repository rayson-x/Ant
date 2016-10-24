<?php
namespace Ant\Exception;

use RuntimeException;

/**
 * Http Exception
 *
 * Class Exception
 * @package Ant\Http
 */
class HttpException extends RuntimeException
{
    protected $statusCode;
    protected $headers;

    /**
     * Exception constructor.
     *
     * @param int|string $statusCode
     * @param null $message
     * @param \Exception|null $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct($statusCode, $message = null, \Exception $previous = null, array $headers = array(), $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取Http状态码
     *
     * @return int|string
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * 获取Http头信息
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}