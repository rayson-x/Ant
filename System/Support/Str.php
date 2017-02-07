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

    /**
     * 把下划线以及横线分割的字符串变为首字母大写的分割方式
     *
     * @param $value
     * @return mixed
     */
    public static function studly($value)
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return str_replace(' ', '', $value);
    }

    /**
     * 驼峰式命名
     *
     * @param $value
     * @return string
     */
    public static function camel($value)
    {
        return lcfirst(static::studly($value));
    }

    /**
     * 更改字符串编码
     *
     * @param $value
     * @param string $encode
     * @return string
     */
    public static function changeEncoding($value, $encode = 'UTF-8')
    {
        return iconv(mb_detect_encoding($value), $encode, $value);
    }

    /**
     * 检查字符串开头是否与指定字符串一致
     *
     * @param $haystack
     * @param $needles
     * @return bool
     */
    public static function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && substr($haystack, 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查最后几位是否相同
     *
     * @param $haystack
     * @param $needles
     * @return bool
     */
    public static function endsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取字符串自然长度
     *
     * @param $value
     * @return int
     */
    public static function length($value)
    {
        return mb_strlen($value);
    }

    /**
     * 转换为大写字母
     *
     * @param $value
     * @param $encoding
     * @return mixed|string
     */
    public static function upper($value, $encoding = "UTF-8")
    {
        return mb_strtoupper($value, $encoding);
    }

    /**
     * 转换为小写字母
     *
     * @param $value
     * @param $encoding
     * @return mixed|string
     */
    public static function lower($value, $encoding = "UTF-8")
    {
        return mb_strtolower($value, $encoding);
    }

    /**
     * 生成随机字符串
     *
     * @param int $length
     * @return string
     */
    public static function random($length = 16)
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    /**
     * 查找字符串首次出现的位置,并替换它
     *
     * @param $search
     * @param $replace
     * @param $subject
     * @return mixed
     */
    public static function replaceFirst($search, $replace, $subject)
    {
        $position = strpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * 查找字符串最后一次出现的位置,并替换它
     *
     * @param $search
     * @param $replace
     * @param $subject
     * @return mixed
     */
    public static function replaceLast($search, $replace, $subject)
    {
        $position = strrpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * 查找指定字符串,按顺序依次替换
     *
     * @param $search
     * @param array $replace
     * @param $subject
     * @return mixed
     */
    public static function replaceArray($search, array $replace, $subject)
    {
        foreach ($replace as $value) {
            $subject = static::replaceFirst($search, $value, $subject);
        }

        return $subject;
    }
}