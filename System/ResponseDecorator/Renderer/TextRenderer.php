<?php
namespace Ant\ResponseDecorator\Renderer;

use Ant\ResponseDecorator\Renderer;
use Psr\Http\Message\ResponseInterface;

class TextRenderer extends Renderer
{
    public function renderResponse(ResponseInterface $response)
    {
        if(!is_string($this->wrapped)){
            throw new \RuntimeException('Response content must be string');
        }

        return $response->getBody()->write($this->wrapped);
    }
}