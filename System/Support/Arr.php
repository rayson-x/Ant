<?php
namespace Ant\Support;

use ArrayAccess;

class Arr
{
    /**
     * 检查是否为数组
     *
     * @param $value
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * 将多维数组进行降维
     *
     * @param $array
     * @return array
     */
    public static function collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->toArray();
            } elseif (! is_array($values)) {
                continue;
            }

            $results = array_merge($results, $values);
        }

        return $results;
    }

    /**
     * @param $array
     * @param $depth
     * @return mixed
     */
    public static function flatten($array, $depth = INF)
    {
        $array = $array instanceof Collection ? $array->toArray() : $array;

        return array_reduce($array, function ($result, $item) use ($depth) {
            $item = $item instanceof Collection ? $item->toArray() : $item;

            if (! is_array($item)) {
                return array_merge($result, [$item]);
            } elseif ($depth === 1) {
                return array_merge($result, array_values($item));
            } else {
                return array_merge($result, static::flatten($item, $depth - 1));
            }
        }, []);
    }

    /**
     * 检查key是否存在在数组中
     *
     * @param array|ArrayAccess $array
     * @param mixed $key
     * @return bool
     */
    public static function exists($array, $key)
    {
        if($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key,$array);
    }

    /**
     * 从多维数组中获取值
     *
     * @param $array
     * @param $path
     * @param null $default
     * @return mixed
     */
    public static function get($array, $path, $default = null)
    {
        if (is_null($path)) {
            return $array;
        }

        foreach (explode('.',$path) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * @param array $array
     * @param $path
     * @param $value
     */
    public static function set(&$array, $path, $value)
    {
        if (is_string($path)) {
            $path = explode('.',$path);
        }

        $lastKey = array_pop($path);

        foreach ($path as $key) {
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[$lastKey] = $value;
    }

    /**
     * @param array $array
     * @param $path
     * @param $value
     */
    public static function push(&$array, $path, $value)
    {
        if (is_string($path)) {
            $path = explode('.',$path);
        }

        foreach ($path as $key) {
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[] = $value;
    }

    /**
     * 检查key是否存在
     *
     * @param $array
     * @param $keys
     * @return bool
     */
    public static function has($array, $keys)
    {
        if (is_null($keys)) {
            return false;
        }

        $keys = (array) $keys;

        if (! $array) {
            return false;
        }

        if ($keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $array;

            if (static::exists($array, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 删除数组中的一个元素
     *
     * @param $array
     * @param $keys
     */
    public static function forget(&$array, $keys)
    {
        $original = &$array;

        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * 从数组中取出指定的值
     *
     * @param array|ArrayAccess $array
     * @param array $keys
     * @return array
     */
    public static function take($array, array $keys)
    {
        $result = [];
        foreach($keys as $key) {
            if(static::exists($array, $key)){
                $result[] = $array[$key];
            }
        }

        return $result;
    }

    /**
     * 从数组获取指定数据
     *
     * @param array $array
     * @param $keys
     * @return array
     */
    public static function getKeywordsFromArray($array,array $keys)
    {
        $result = [];
        $array = is_array($array) ? $array : [];
        foreach($keys as $key){
            if(!array_key_exists($key,$array)){
                // 缺少必要参数
                throw new \InvalidArgumentException("{$key} does not exist");
            }

            $result[$key] = $array[$key];
        }

        return $result;
    }

    /**
     * 检查非法字段
     *
     * @param $array
     * @param array $keys
     * @return array
     */
    public static function checkIllegalKeywords($array,array $keys)
    {
        if(!static::accessible($array)){
            return false;
        }

        foreach($keys as $key){
            if(array_key_exists($key,$array)){
                // 非法参数
                return false;
            }
        }

        return true;
    }

    /**
     * 分离key跟value
     *
     * @param array $array
     * @return array
     */
    public static function detach(array $array)
    {
        return [array_keys($array),array_values($array)];
    }

    /**
     * 处理数组中指定元素
     *
     * @param array $array
     * @param array $elements
     * @param string $func
     * @return array
     */
    public static function handleElement(array $array, array $elements, $func = 'rawurlencode')
    {
        if(!is_callable($func)) {
            throw new \InvalidArgumentException(
                "\\Ant\\Support\\Arr::handleElement() expects parameter 3 to be a valid callback"
            );
        }

        foreach($elements as $key) {
            if(static::exists($array, $key)) {
                $array[$key] = $func($array[$key]);
            }
        }

        return $array;
    }

    /**
     * 将数组中的每一个元素通过递归回调一次
     *
     * @param array $array      要被回调的数组
     * @param callable $call    回调函数
     * @param int $depth        递归深度
     * @return array
     */
    public static function recursiveCall(array $array, callable $call, $depth = 512)
    {
        if($depth-- <= 0){
            return $array;
        }

        foreach($array as $key => $value){
            if(is_array($value)){
                $array[$key] = static::recursiveCall($value, $call, $depth);
            }else{
                $array[$key] = call_user_func($call,$value);
            }
        }

        return $array;
    }
}