<?php
use Ant\Container\Container;
use Psr\Http\Message\ServerRequestInterface;

function newRequest(ServerRequestInterface $request)
{
    Container::getInstance()->instance('newRequest',$request);
}

function show($msg)
{
    echo "<pre>";
    var_dump($msg);
    echo "</pre>";
}

function safe_json_encode($value, $options = 0, $depth = 512)
{
    $value = json_encode($value, $options, $depth);

    if ($value === false && json_last_error() !== JSON_ERROR_NONE) {
        throw new \UnexpectedValueException(json_last_error_msg(), json_last_error());
    }

    return $value;
}

function safe_json_decode($json, $assoc = false, $depth = 512, $options = 0)
{
    $value = json_decode($json, $assoc, $depth, $options);

    if ($value === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new \UnexpectedValueException(json_last_error_msg(), json_last_error());
    }

    return $value;
}

function exceptionHandle($exception){
    if($exception->getPrevious()){
        return exceptionHandle($exception->getPrevious());
    }

    $headers = [];
    $headers['Exception'] = sprintf('%s(%d) %s',get_class($exception),$exception->getCode(),$exception->getMessage());

    foreach(explode("\n",$exception->getTraceAsString()) as $index => $line){
        $key           = sprintf('X-Exception-Trace-%02d', $index);
        $headers[$key] = $line;
    }

    return $headers;
}