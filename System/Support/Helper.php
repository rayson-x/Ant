<?php
if (!function_exists('isCgi')) {
    /**
     * 检测当前运行环境是否为Fast-Cgi模式
     *
     * @return bool
     */
    function isCgi()
    {
        return substr(PHP_SAPI,0,3) === 'cgi';
    }
}

if (!function_exists('debug')) {
    /**
     * 调试函数,用来打印信息,并结束应用
     */
    function debug()
    {
        ob_start();
        if ($args = func_get_args()) {
            call_user_func_array('var_dump',func_get_args());
        }

        echo !isCgi()
            ? ob_get_clean()
            : "<pre>".ob_get_clean()."</pre>";
        die;
    }
}

if (!function_exists('runtime')) {
    /**
     * 测试函数,用来检测从上一个点到下一个运行所消耗的时间
     */
    function runtime()
    {
        static $time = null;
        if($time == null){
            $time = microtime(true);
            return;
        }

        var_dump((((int)((microtime(true) - $time) * 10000))/10).'ms');
    }
}

if (!function_exists('safeMd5')) {
    /**
     * 将输入值加盐后进行MD5加密
     *
     * @param $data
     * @param string $salt
     * @return string
     */
    function safeMd5($data, $salt = '')
    {
        return md5($data.$salt);
    }
}

if (!function_exists('container')) {
    /**
     * 获取服务容器
     *
     * @param null $serviceName
     * @param array $parameters
     * @return \Ant\Foundation\Cgi\Application|object
     */
    function container($serviceName = null, $parameters = [])
    {
        if (is_null($serviceName)) {
            return Ant\Foundation\Cgi\Application::getInstance();
        }

        return Ant\Foundation\Cgi\Application::getInstance()->make($serviceName, $parameters);
    }
}

if (!function_exists('get')) {
    /**
     * 获取GET参数
     *
     * @param null $key
     * @return null|array
     */
    function get($key = null)
    {
        return container('request')->get($key);
    }
}

if (!function_exists('post')) {
    /**
     * 当为Post请求时,获取body参数
     *
     * @param null $key
     * @return null|array|object
     */
    function post($key = null)
    {
        return container('request')->post($key);
    }
}

if (!function_exists('body')) {
    /**
     * 获取解析完成后的Body参数
     *
     * @param null $key
     * @return null|array|object
     */
    function body($key = null)
    {
        return container('request')->getBodyParam($key);
    }
}

if (!function_exists('safeJsonEncode')) {
    /**
     * 保证json编码不会出错
     *
     * @param $input
     * @param int $options
     * @param int $depth
     * @return string
     */
    function safeJsonEncode($input, $options = 0, $depth = 512)
    {
        $value = json_encode($input, $options, $depth);

        if ($value === false && json_last_error() !== JSON_ERROR_NONE) {
            throw new \UnexpectedValueException(json_last_error_msg(), json_last_error());
        }

        return $value;
    }
}

if (!function_exists('safeJsonDecode')) {
    /**
     * 保证json解码不会出错
     *
     * @param $input
     * @param bool|true $assoc
     * @param int $depth
     * @param int $options
     * @return mixed
     */
    function safeJsonDecode($input, $assoc = true, $depth = 512, $options = 0)
    {
        $value = json_decode($input, $assoc, $depth, $options);

        if ($value === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \UnexpectedValueException(json_last_error_msg(), json_last_error());
        }

        return $value;
    }
}

if (!function_exists('numToLetter')) {
    /**
     * 将输入的数字转换成Excel对应的字母
     *
     * @param $num
     * @return string
     */
    function numToLetter($num)
    {
        static $map = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

        if ($num <= 26) {
            //26个字母以内
            return $map[$num - 1];
        }

        //之前的
        $a = ceil($num / 26) - 1;
        //最后一位
        $b = ($num % 26 == 0) ? 25 : $num % 26 - 1;

        return numToLetter($a).$map[$b];
    }
}