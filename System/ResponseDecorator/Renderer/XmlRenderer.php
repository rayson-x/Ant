<?php
namespace Ant\ResponseDecorator\Renderer;

use Ant\ResponseDecorator\Renderer;

class XmlRenderer extends  Renderer
{
    public function renderData()
    {
        return $this->toXml();
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