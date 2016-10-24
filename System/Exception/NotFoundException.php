<?php
namespace Ant\Exception;

/**
 * 404 Not Found Exception
 *
 * Class NotFoundException
 * @package Ant\Http\Exception
 */
class NotFoundException extends HttpException
{
    public function __construct($message = null, \Exception $previous = null, array $headers = array(), $code = 0)
    {
        parent::__construct(404,$message,$previous,$headers,$code);
    }
}