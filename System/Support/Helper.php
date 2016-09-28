<?php
use Ant\Support\ArrayHandle;
use Ant\Container\Container;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 设置一个新中间件参数
 * @param ServerRequestInterface $request
 */
function newRequest(ServerRequestInterface $request)
{
    Container::getInstance()->instance('newRequest',$request);
}

/**
 * 打印信息
 * @param $msg
 */
function show($msg)
{
    echo "<pre>";
    var_dump($msg);
    echo "</pre>";
}

function ArraySetIn(&$array,$path,$value)
{
    if(is_string($path)){
        $path = explode('.',$path);
    }

    ArrayHandle::setIn($array,$path,$value);
}

function container($serviceName = null, $parameters = [])
{
    if (is_null($serviceName)) {
        return Container::getInstance();
    }

    return Container::getInstance()->make($serviceName, $parameters);
}

function serializeClosure(Closure $closure)
{
    return container('serialize')->serialize($closure);
}

function unserializeClosure($closure)
{
    return container('serialize')->unserialize($closure);
}

/**
 * 保证json编码不会出错
 * @param $value
 * @param int $options
 * @param int $depth
 * @return string
 */
function safe_json_encode($value, $options = 0, $depth = 512)
{
    $value = json_encode($value, $options, $depth);

    if ($value === false && json_last_error() !== JSON_ERROR_NONE) {
        throw new \UnexpectedValueException(json_last_error_msg(), json_last_error());
    }

    return $value;
}

/**
 * 保证json解码不会出错
 * @param $json
 * @param bool|false $assoc
 * @param int $depth
 * @param int $options
 * @return mixed
 */
function safe_json_decode($json, $assoc = false, $depth = 512, $options = 0)
{
    $value = json_decode($json, $assoc, $depth, $options);

    if ($value === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new \UnexpectedValueException(json_last_error_msg(), json_last_error());
    }

    return $value;
}

/**
 * 检查指定字符串是否包含另一个字符串
 * @param $haystack
 * @param $needles
 * @return bool
 */
function contains($haystack, $needles)
{
    foreach ((array) $needles as $needle) {
        if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
            return true;
        }
    }

    return false;
}

function test(){
    static $time = false;
    if(!$time){
        $time = microtime(true);
    }elseif(isset($time)){
        container('response')->withHeader('x-test-time',(microtime(true) - $time) * 1000);
    }
}

/**
 * 获取错误信息
 * @param $exception
 * @return array
 */
function exceptionHandle($exception){
    if($exception->getPrevious()){
        return exceptionHandle($exception->getPrevious());
    }

    $exceptionInfo = [];
    $exceptionInfo['Exception'] = sprintf($exception->getLine().'%s(%d) %s',get_class($exception),$exception->getCode(),$exception->getMessage());

    foreach(explode("\n",$exception->getTraceAsString()) as $index => $line){
        $key           = sprintf('X-Exception-Trace-%02d', $index);
        $exceptionInfo[$key] = $line;
    }

    return $exceptionInfo;
}