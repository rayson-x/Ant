<?php
namespace Ant\Http\Message;

use Psr\Http\Message\MessageInterface;

/**
 * Class JsonpRenderer
 * @package Ant\Http\Response\Renderer
 */
class JsonpRenderer extends JsonRenderer
{
    /**
     * @var string
     */
    protected $getName = 'callback';

    /**
     * @var string
     */
    protected $callName = 'callback';

    public function decorate(MessageInterface $http)
    {
        $callName = isset($_GET[$this->getName]) ? $_GET[$this->getName] : $this->callName;

        $http->getBody()->write(
            "function {$callName}(){return '{$this->toJson()}'};"
        );

        return $http->withAddedHeader('Content-Type', 'application/json'.$this->getCharset($http));
    }
}