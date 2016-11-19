<?php
namespace Ant\Support;

class ArrayHandle
{
    /**
     * 从多维数组中获取值
     *
     * @param array $target
     * @param $path
     * @return array|bool
     */
    public static function getIn(array $target,$path)
    {
        foreach((array) $path as $key){
            if (!array_key_exists($key,$target)) {
                return false;
            }

            $target = &$target[$key];
        }

        return $target;
    }

    /**
     * @param array $target
     * @param $path
     * @param $value
     * @param bool|false $push
     */
    public static function setIn(array &$target,$path,$value,$push = false)
    {
        $last_key = array_pop($path);

        foreach ($path as $key) {
            if (!array_key_exists($key, $target)) {
                $target[$key] = [];
            }

            $target = &$target[$key];

            if (!is_array($target)) {
                throw new \RuntimeException('Cannot use a scalar value as an array');
            }
        }

        if ($push) {
            if (!array_key_exists($last_key, $target)) {
                $target[$last_key] = [];
            } elseif (!is_array($target[$last_key])) {
                throw new \RuntimeException('Cannot use a scalar value as an array');
            }

            array_push($target[$last_key], $value);
        } else {
            $target[$last_key] = $value;
        }
    }

    /**
     * @param array $target
     * @param $path
     * @param $value
     */
    public static function pushIn(array &$target,$path,$value)
    {
        static::setIn($target,$path,$value,true);
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
        $array = is_array($array) ? $array : [];
        foreach($keys as $key){
            if(array_key_exists($key,$array)){
                // 非法参数
                throw new \InvalidArgumentException("[$key] is illegal fields");
            }
        }

        return $array;
    }
}