<?php
namespace Ant\Http\Exception;

/**
 * 405异常,请求方式错误时抛出
 *
 * Class MethodNotAllowedException
 * @package Ant\Http\Exception
 */
class MethodNotAllowedException extends HttpException
{
    /**
     * 抛出405异常
     *
     * @param array $allowed
     * @param null $message
     * @param \Exception|null $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct(
        array $allowed,
        $message = null,
        \Exception $previous = null,
        array $headers = [],
        $code = 0
    ){
        $allowed = ['allowed' => implode(',',$allowed)];
        $headers = array_merge($headers,$allowed);

        parent::__construct(405,$message,$previous,$headers,$code);
    }
}