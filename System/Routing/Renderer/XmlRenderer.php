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
        if(!$this->checkType($data)){
            $data = ['item' => $data];
        }

<<<<<<< HEAD
        foreach($data as $key => $val) {
            if($this->checkType($val)) {
=======
        foreach($data as $key => $val){
            if(!is_string($val) && !is_int($val)){
>>>>>>> d91386f783508015f798ccb70687cd5001601348
                $childElement = $element->addChild($key);
                $this->addChildToElement($childElement,$val);
            }else{
                $element->addChild($key,$val);
            }
        }
    }

    /**
     * 检查数据类型
     *
     * @return bool
     */
    protected function checkType($data)
    {
        if(is_array($data) || is_object($data)){
            return true;
        }

        return false;
    }
}