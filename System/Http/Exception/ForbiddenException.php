<?php
namespace Ant\Http\Exception;

/**
 * 403错误,理解客户端请求后仍要拒绝请求时抛出
 *
 * Class ForbiddenException
 * @package Ant\Http\Exception
 */
class ForbiddenException extends HttpException
{
    /**
     * ForbiddenException constructor.
     * @param null $message
     * @param \Exception|null $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct($message = null,\Exception $previous = null, array $headers = [], $code = 0)
    {
        parent::__construct(403, $message, $previous, $headers, $code);
    }
}