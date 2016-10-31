<?php
namespace Ant\Routing\Renderer;

use Psr\Http\Message\ResponseInterface;

class HtmlRenderer extends Renderer
{
    public function renderResponse(ResponseInterface $response)
    {
        if(!is_string($this->wrapped) && !is_integer($this->wrapped)){
            throw new \RuntimeException('Response content must be string');
        }

        $response->getBody()->write($this->wrapped);

        return $response;
    }
}