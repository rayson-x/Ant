<?php
namespace Ant\ResponseDecorator\Renderer;

use Ant\ResponseDecorator\Renderer;
use Psr\Http\Message\ResponseInterface;

class XmlRenderer extends  Renderer
{
    public function renderResponse(ResponseInterface $response)
    {
        $response->getBody()->write($this->toXml());
        $response->withAddedHeader('Content-Type', 'application/xml;charset=utf-8');

        return $response;
    }

    // Todo::XML转换
    public function toXml()
    {
        $output = $this->wrapped;

        $doc = new \DOMDocument();

        foreach ($output as $key => $val) {
            $doc->appendChild($doc->createElement($key, $val));
        }

        return $doc->saveXML();
    }
}