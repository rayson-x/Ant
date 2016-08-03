<?php
/**
 * Class Autoloader
 * @package Ant
 *
 * 遵循PSR-0，PSR-4进行自动加载
 * 核心文件使用类库映射,加快读取速度
 * 应用目录使用命名空间前缀进行加载
 */
namespace Ant;

class Autoloader
{
    protected static $classMap = [];//类库映射
    protected static $prefixes = [];//命名空间前缀映射

    /**
     * 注册自动加载
     */
    public static function register()
    {
        spl_autoload_register('Ant\\Autoloader::loadClass');
//        self::addClassMap(include __DIR__.'/../config/classMap.php');
    }

    /**
     * 注册类库映射
     * @param $classMap
     * @param string $map
     */
    public static function addClassMap($classMap, $map = '')
    {
        //命名空间映射
        if (is_array($classMap)) {
            self::$classMap = array_merge(self::$classMap, $classMap);
        } else {
            self::$classMap[$classMap] = $map;
        }
    }

    /**
     * 注册命名空间
     * @param $prefix               命名空间前缀
     * @param $base_dir             前缀所映射的路径,可以映射多目录
     * @param bool|false $prepend   前缀是否优先读取(推荐将经常加载的目录优先读取)
     */
    public static function addNamespace($prefix, $base_dir = '', $prepend = false)
    {
        if(is_array($prefix)){
            self::$prefixes = array_merge(self::$prefixes, $prefix);
        }else{
            // 标准化命名空间前缀
            $prefix = trim($prefix, '\\') . '\\';

            // 将斜杠变为PHP内置文件夹分割符
            $base_dir = rtrim($base_dir, '/') . DIRECTORY_SEPARATOR;

            // 初始化命名空间前缀数组
            if (isset(self::$prefixes[$prefix]) === false) {
                self::$prefixes[$prefix] = [];
            }

            // 添加到命名空间映射
            $prepend
                ? array_unshift(self::$prefixes[$prefix], $base_dir)
                : array_push(self::$prefixes[$prefix], $base_dir);
        }
    }

    /**
     * 开始加载
     * @param $class    命名空间
     * @return bool
     */
    public static function loadClass($class)
    {
        //如果存在类库映射，直接加载
        if(!empty(self::$classMap[$class]))
            return self::requireFile(self::$classMap[$class]);

        $prefix = $class;
        // 通过前缀名找到映射文件名
        while (false !== $pos = strrpos($prefix, '\\')) {

            // 保留尾随命名空间分隔的前缀
            $prefix = substr($class, 0, $pos + 1);

            // 其余的是相对的类名
            $relative_class = substr($class, $pos + 1);

            // 检查是否有此命名空间前缀的基本目录
            if (isset(self::$prefixes[$prefix]) === false) {
                /* 删除尾随命名空间分隔，作为下一次迭代参数 */
                $prefix = rtrim($prefix, '\\');
                continue;
            }

            /* 尝试加载类名映射的映射文件名 */
            $mapped_file = self::loadMappedFile($prefix, $relative_class);

            if ($mapped_file)
                return $mapped_file;
        }

        return false;
    }

    /**
     * 映射基本目录
     * @param $prefix           命名前缀
     * @param $relative_class   类名
     * @return bool
     */
    protected static function loadMappedFile($prefix, $relative_class)
    {
        foreach (self::$prefixes[$prefix] as $base_dir) {
            //使用基本目录替换命名空间前缀,添加PHP后缀
            $file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . EXT;

            // 尝试加载文件
            if (self::requireFile($file))
                return true;
        }

        return false;
    }

    /**
     * 引入文件
     * @param $file     文件路径
     * @return bool
     */
    protected static function requireFile($file)
    {
        // 如果存在,进行加载
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }
}