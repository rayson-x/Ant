<?php
namespace Ant\Foundation\Http\Api\Decorator;

use Psr\Http\Message\MessageInterface as PsrMessage;

class FileRenderer extends Renderer
{
    public $type = 'application/octet-stream';

    public $fileName = 'example.txt';

    public function decorate(PsrMessage $http)
    {
        if (!is_string($this->package) && !is_integer($this->package)) {
            throw new \RuntimeException('Response content must be string');
        }

        $headers = [
            'Content-Type' => $this->type,
            "Content-Disposition" => "attachment; filename=\"{$this->fileName}\"",
            'Content-Transfer-Encoding' => 'binary',
        ];

        foreach ($headers as $name => $value) {
            $http = $http->withHeader($name,$value);
        }

        $http->getBody()->write($this->package);

        return $http;
    }
}