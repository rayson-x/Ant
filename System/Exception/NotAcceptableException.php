<?php
namespace Ant\Exception;

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