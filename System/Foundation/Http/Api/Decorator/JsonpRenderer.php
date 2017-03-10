<?php
namespace Ant\Foundation\Http\Api\Decorator;

/**
 * Class JsonpRenderer
 * @package Ant\Foundation\Http\Api\Decorator
 */
class JsonpRenderer extends JsonRenderer
{
    public $type = 'application/javascript';

    /**
     * @var string
     */
    public $getName = 'callback';

    /**
     * @var string
     */
    public $callName = 'callback';

    /**
     * {@inheritDoc}
     */
    public function decorate()
    {
        $callName = isset($_GET[$this->getName]) ? $_GET[$this->getName] : $this->callName;

        $this->response->getBody()->write(
            "{$callName}({$this->toJson()});"
        );

        return $this->response->withHeader('Content-Type', $this->getType());
    }
}