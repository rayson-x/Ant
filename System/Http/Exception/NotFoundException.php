<?php
namespace Ant\Http\Exception;

/**
 * 404异常，当请求的资源不存在时抛出
 *
 * Class NotFoundException
 * @package Ant\Http\Exception
 */
class NotFoundException extends HttpException
{
    /**
     * NotFoundException constructor.
     * @param null $message
     * @param \Exception|null $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct($message = null, \Exception $previous = null, array $headers = array(), $code = 0)
    {
        parent::__construct(404,$message,$previous,$headers,$code);
    }
}