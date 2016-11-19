<?php
namespace Ant\Http\Exception;

/**
 * 400异常,当客户端请求的参数不正确时抛出
 *
 * Class BadRequestException
 * @package Ant\Http\Exception
 */
class BadRequestException extends HttpException
{
    /**
     * BadRequestException constructor.
     * @param null $message
     * @param \Exception|null $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct($message = null,\Exception $previous = null, array $headers = [], $code = 0)
    {
        parent::__construct(400, $message, $previous, $headers, $code);
    }
}