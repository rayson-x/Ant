<?php
namespace Ant\Http;

class Body extends Stream
{
    public function __construct($stream = null)
    {
        if(is_null($stream)){
            $stream = fopen('php://temp','w+');
        }

        parent::__construct($stream);
    }


    /**
     * 通过字符串创建一个流
     *
     * @param string $data
     * @return static
     */
    public static function createFromString($data)
    {
        if(!is_string($data)){
            throw new \InvalidArgumentException("");
        }

        $stream = fopen("php://temp","w+");
        fwrite($stream,$data);
        rewind($stream);

        return new static($stream);
    }

}