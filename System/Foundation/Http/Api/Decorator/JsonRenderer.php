<?php
namespace Ant\Foundation\Http\Api\Decorator;

use Psr\Http\Message\MessageInterface as PsrMessage;

class JsonRenderer extends Renderer
{
    public $type = 'application/json';

    public function decorate(PsrMessage $http)
    {
        $http->getBody()->write($this->toJson());
        return $http->withHeader('Content-Type', $this->getType());
    }

    /**
     * @return string
     */
    public function toJson()
    {
        $output = json_encode($this->package);

        if ($output === false && json_last_error() !== JSON_ERROR_NONE) {
            throw new \UnexpectedValueException(json_last_error_msg(), json_last_error());
        }

        return $output;
    }
}