<?php
namespace Ant\Http\Message;

use Psr\Http\Message\MessageInterface;

class JsonRenderer extends Renderer
{
    public function decorate(MessageInterface $http)
    {
        $http->getBody()->write($this->toJson());
        return $http->withHeader('Content-Type', 'application/json'.$this->getCharset($http));
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