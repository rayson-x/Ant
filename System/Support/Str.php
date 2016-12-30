<?php
namespace Ant\Support;

class Str
{
    /**
     * URL��ȫ���ַ���base64����
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
     * URL��ȫ���ַ���base64����
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
     * ���ָ���ַ����Ƿ������һ���ַ���
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