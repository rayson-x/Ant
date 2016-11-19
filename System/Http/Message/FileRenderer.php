<?php
namespace Ant\Http\Message;

use Psr\Http\Message\MessageInterface;

class FileRenderer extends Renderer
{
    public function decorate(MessageInterface $http)
    {
        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="example.txt"',
            'Content-Transfer-Encoding' => 'binary',
        ];

        foreach($headers as $name => $value){
            $http = $http->withAddedHeader($name,$value);
        }

        if(!is_string($this->package) && !is_integer($this->package)){
            throw new \RuntimeException('Response content must be string');
        }

        $http->getBody()->write($this->package);

        return $http;
    }
}