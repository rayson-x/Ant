<?php
use Ant\Container\Container;
use Psr\Http\Message\ServerRequestInterface;

function newRequest(ServerRequestInterface $request)
{
    Container::getInstance()->instance('newRequest',$request);
}
