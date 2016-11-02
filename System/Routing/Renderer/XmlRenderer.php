<?php
namespace Ant\Routing\Renderer;

use Psr\Http\Message\ResponseInterface;

class XmlRenderer extends  Renderer
{
    public function renderResponse(ResponseInterface $response)
    {
        $response->getBody()->write($this->toXml());
        return $response->withAddedHeader('Content-Type', 'application/xml;charset=utf-8');
    }

    /**
     * 输出XML格式数据
     *
     * @return string
     */
    protected function toXml()
    {
        $sxe = new \SimpleXMLElement('<xml/>');
        $this->addChildToElement($sxe,$this->wrapped);

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
        if(is_string($data) || is_integer($data)){
            $data = ['item' => $data];
        }

        foreach($data as $key => $val){
            if(is_array($val)){
                $childElement = $element->addChild($key);
                $this->addChildToElement($childElement,$val);
            }else{
                $element->addChild($key,$val);
            }
        }
    }
}