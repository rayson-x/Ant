<?php
namespace Ant\Exception;

/**
 * 405 Method Not Allowed Exception
 *
 * Class MethodNotAllowedException
 * @package Ant\Http\Exception
 */
class MethodNotAllowedException extends HttpException
{
    //Todo::返回allowed
    public function __construct($message = null, \Exception $previous = null, array $headers = array(), $code = 0)
    {
        parent::__construct(405,$message,$previous,$headers,$code);
    }
}