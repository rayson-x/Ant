<?php
namespace Ant\Http\Exception;

/**
 * 406异常,当无法响应客户端请求的内容格式时抛出
 *
 * Class NotAcceptableException
 * @package Ant\Http\Exception
 */
class NotAcceptableException extends HttpException
{
    /**
     * NotAcceptableException constructor.
     *
     * @param null $message
     * @param \Exception|null $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct($message = null,\Exception $previous = null, array $headers = [], $code = 0)
    {
        parent::__construct(406, $message, $previous, $headers, $code);
    }
}