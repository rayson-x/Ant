<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2016/10/28
 * Time: 12:44
 */

namespace Ant\Routing\Renderer;

use Psr\Http\Message\ResponseInterface;

class FileRenderer extends Renderer
{
    public function renderResponse(ResponseInterface $response)
    {
        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="example.txt"',
            'Content-Transfer-Encoding' => 'binary',
        ];

        foreach($headers as $name => $value){
            $response = $response->withAddedHeader($name,$value);
        }

        if(!is_string($this->wrapped) && !is_integer($this->wrapped)){
            throw new \RuntimeException('Response content must be string');
        }

        $response->getBody()->write($this->wrapped);

        return $response;
    }
}