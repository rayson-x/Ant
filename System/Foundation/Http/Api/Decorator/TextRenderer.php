<?php
namespace Ant\Foundation\Http\Api\Decorator;

use Psr\Http\Message\MessageInterface as PsrMessage;

class TextRenderer extends Renderer
{
    public $type = 'text/html';

    public function decorate(PsrMessage $http)
    {
        if (!is_string($this->package) && !is_integer($this->package)) {
            throw new \RuntimeException('Response content must be string');
        }

        $http->getBody()->write($this->package);

        return !$http->hasHeader("content-type")
            ? $http->withHeader('Content-Type', $this->getType())
            : $http;
    }
}