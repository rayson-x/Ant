<?php
namespace Ant\Http;

class Body extends Stream
{
    public function __construct($stream = null)
    {
        if(!$stream){
            $stream = fopen('php://temp','w+');
        }

        parent::__construct($stream);
    }
}