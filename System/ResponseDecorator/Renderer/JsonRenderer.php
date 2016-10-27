<?php
namespace Ant\ResponseDecorator\Renderer;

use Ant\ResponseDecorator\Renderer;

class JsonRenderer extends Renderer
{
    public function renderData()
    {
        $output = json_encode($this->wrapped);

        if ($output === false && json_last_error() !== JSON_ERROR_NONE) {
            throw new \UnexpectedValueException(json_last_error_msg(), json_last_error());
        }

        return $output;
    }
}