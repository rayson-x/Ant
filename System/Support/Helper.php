<?php
use Ant\App;
use Ant\Support\Arr;

/**
 * 打印信息
 */
function debug()
{
    echo "<pre>";
    if($args = func_get_args()){
        call_user_func_array('var_dump',$args);
    }
    echo "</pre>";
    die;
}

function runtime()
{
    static $time = null;
    if($time == null){
        $time = microtime(true);
        return;
    }

    var_dump((((int)((microtime(true) - $time) * 10000))/10).'ms');
}

function safeMd5($data,$salt = '')
{
    return md5($data.$salt);
}

function setIn(&$array,$path,$value)
{
    Arr::setIn($array,$path,$value);
}

function container($serviceName = null, $parameters = [])
{
    if (is_null($serviceName)) {
        return App::getInstance();
    }

    return App::getInstance()->make($serviceName, $parameters);
}

function get($key = null)
{
    return container('request')->get($key);
}

function post($key = null)
{
    return container('request')->post($key);
}

function body($key = null)
{
    return container('request')->getBodyParam($key);
}

/**
 * 保证json编码不会出错
 * @param $value
 * @param int $options
 * @param int $depth
 * @return string
 */
function safeJsonEncode($value, $options = 0, $depth = 512)
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
 * @param bool|true $assoc
 * @param int $depth
 * @param int $options
 * @return mixed
 */
function safeJsonDecode($json, $assoc = true, $depth = 512, $options = 0)
{
    $value = json_decode($json, $assoc, $depth, $options);

    if ($value === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new \UnexpectedValueException(json_last_error_msg(), json_last_error());
    }

    return $value;
}

/**
 * 将输入的数字转换成Excel对应的字母
 *
 * @param $num
 * @return string
 */
function numToLetter($num)
{
    static $map = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

    if($num <= 26){
        //26个字母以内
        return $map[$num - 1];
    }

    //之前的
    $a = ceil($num / 26) - 1;
    //最后一位
    $b = ($num % 26 == 0) ? 25 : $num % 26 - 1;

    return numToLetter($a).$map[$b];
}