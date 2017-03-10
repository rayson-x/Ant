<?php
namespace Ant\Foundation\Http\Api\Decorator;

/**
 * Class JsonRenderer
 * @package Ant\Foundation\Http\Api\Decorator
 */
class JsonRenderer extends Renderer
{
    public $type = 'application/json';

    /**
     * {@inheritDoc}
     */
    public function decorate()
    {
        $response = $this->response;

        $response->getBody()->write($this->toJson());
        return $response->withHeader('Content-Type', $this->getType());
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