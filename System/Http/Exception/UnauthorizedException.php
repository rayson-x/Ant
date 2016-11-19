<?php
namespace Ant\Http\Exception;

/**
 * 401异常,客户端权限不足时抛出
 *
 * Class UnauthorizedException
 * @package Ant\Http\Exception
 */
class UnauthorizedException extends HttpException
{
    /**
     * UnauthorizedException constructor.
     * @param null $message
     * @param \Exception|null $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct($message = null,\Exception $previous = null, array $headers = [], $code = 0)
    {
        parent::__construct(401, $message, $previous, $headers, $code);
    }
}