<?php
namespace Ant\Http\Message;

use Psr\Http\Message\MessageInterface;

class HtmlRenderer extends Renderer
{
    public function decorate(MessageInterface $http)
    {
        if(!is_string($this->package) && !is_integer($this->package)){
            throw new \RuntimeException('Response content must be string');
        }

        $http->getBody()->write($this->package);

        return $http;
    }
}