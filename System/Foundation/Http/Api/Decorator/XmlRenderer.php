<?php
namespace Ant\Foundation\Http\Api\Decorator;

/**
 * Class XmlRenderer
 * @package Ant\Foundation\Http\Api\Decorator
 */
class XmlRenderer extends Renderer
{
    public $type = 'text/xml';

    /**
     * {@inheritDoc}
     */
    public function decorate()
    {
        $this->response->getBody()->write($this->toXml());

        return $this->response->withHeader('Content-Type', $this->getType());
    }

    /**
     * 输出XML格式数据
     *
     * @return string
     */
    protected function toXml()
    {
        $sxe = new \SimpleXMLElement('<xml/>');
        $this->addChildToElement($sxe,$this->package);

        return $sxe->asXML();
    }

    /**
     * 添加子节点
     *
     * @param \SimpleXMLElement $element
     * @param array|object $data
     */
    protected function addChildToElement(\SimpleXMLElement $element, $data)
    {
        if (!is_array($data) && !is_object($data)) {
            throw new \RuntimeException('Response content must be array or object');
        }

        foreach ($data as $key => $val) {
            if (is_array($val) || is_object($val)) {
                $childElement = $element->addChild($key);
                $this->addChildToElement($childElement,$val);
            } else {
                $element->addChild($key,$val);
            }
        }
    }
}