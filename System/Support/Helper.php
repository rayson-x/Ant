<?php
use Ant\App;
use Ant\Support\Arr;

/**
 * 打印信息
 */
function debug()
{
    echo "<pre>";
    var_dump(...func_get_args());
    echo "</pre>";
    die;
}

/**
 * URL安全的字符串base64编码
 *
 * @param $string
 * @return mixed|string
 */
function base64UrlEncode($string) {
    $data = base64_encode($string);
    $data = str_replace(array('+','/','='),array('-','_',''),$data);
    return $data;
}

/**
 * URL安全的字符串base64解码
 *
 * @param $string
 * @return string
 */
function base64UrlDecode($string) {
    $data = str_replace(array('-','_'),array('+','/'),$string);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    return base64_decode($data);
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
 * @param bool|false $assoc
 * @param int $depth
 * @param int $options
 * @return mixed
 */
function safeJsonDecode($json, $assoc = false, $depth = 512, $options = 0)
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