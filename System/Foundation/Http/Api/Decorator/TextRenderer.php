<?php
namespace Ant\Foundation\Http\Api\Decorator;

/**
 * Class TextRenderer
 * @package Ant\Foundation\Http\Api\Decorator
 */
class TextRenderer extends Renderer
{
    public $type = 'text/html';

    /**
     * {@inheritDoc}
     */
    public function decorate()
    {
        if (!is_string($this->package) && !is_integer($this->package)) {
            throw new \RuntimeException('Response content must be string');
        }

        $response = $this->response;
        $response->getBody()->write($this->package);

        return !$response->hasHeader("content-type")
            ? $response->withHeader('Content-Type', $this->getType())
            : $response;
    }
}