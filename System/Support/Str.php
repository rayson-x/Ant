<?php
namespace Ant\Support;

class Str
{
    /**
     * Url安全的Base64编码
     *
     * @param $string
     * @return mixed|string
     */
    public static function base64UrlEncode($string)
    {
        $data = base64_encode($string);
        $data = str_replace(['+','/','='],['-','_',''],$data);
        return $data;
    }

    /**
     * Url安全的Base64解码
     *
     * @param $string
     * @return string
     */
    public static function base64UrlDecode($string)
    {
        $data = str_replace(['-','_'],['+','/'],$string);
        $mod4 = mb_strlen($data) % 4;
        if ($mod4) {
            $data .= mb_substr('====', $mod4);
        }
        return base64_decode($data);
    }


    /**
     * 检查字符串中是否包含指定字符
     *
     * @param $haystack
     * @param $needles
     * @return bool
     */
    public static function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}