<?php
namespace Ant\Foundation\Http\Api\Decorator;

use Psr\Http\Message\MessageInterface as PsrMessage;

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

    public function decorate(PsrMessage $http)
    {
        $callName = isset($_GET[$this->getName]) ? $_GET[$this->getName] : $this->callName;

        $http->getBody()->write(
            "{$callName}({$this->toJson()});"
        );

        return $http->withHeader('Content-Type', $this->getType());
    }
}