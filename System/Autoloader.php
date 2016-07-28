<?php
namespace Ant;

class Autoloader
{
    protected static $classMap = [];//类库映射
    protected static $prefixes = [];//命名空间前缀映射

    /**
     * 注册惰性加载
     */
    public static function register()
    {
        spl_autoload_register('Ant\\Autoloader::loadClass');
        self::addClassMap(include __DIR__.'/../config/classMap.php');
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
     * @param bool|false $prepend   前缀是否优先读取
     */
    public static function addNamespace($prefix, $base_dir = '', $prepend = false)
    {
        if(is_array($prefix)){
            self::$prefixes = array_merge(self::$prefixes, $prefix);
        }else{
            // 标准化命名空间前缀
            $prefix = trim($prefix, '\\') . '\\';

            // 后面有个分隔符正常化的基本目录
            $base_dir = rtrim($base_dir, '/') . DIRECTORY_SEPARATOR;
            //$base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';

            // 初始化命名空间前缀数组
            if (isset(self::$prefixes[$prefix]) === false) {
                self::$prefixes[$prefix] = [];
            }

            // 保留命名空间前缀的基本目录
            if ($prepend) {
                array_unshift(self::$prefixes[$prefix], $base_dir);
            } else {
                array_push(self::$prefixes[$prefix], $base_dir);
            }
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
        if(!empty(self::$classMap[$class])) return self::requireFile(self::$classMap[$class]);
        $prefix = $class;

        // 通过前缀名找到映射文件名
        while (false !== $pos = strrpos($prefix, '\\')) {

            // 保留尾随命名空间分隔的前缀
            $prefix = substr($class, 0, $pos + 1);

            // 其余的是相对的类名
            $relative_class = substr($class, $pos + 1);

            /* 尝试加载类名映射的映射文件名 */
            $mapped_file = self::loadMappedFile($prefix, $relative_class);
            if ($mapped_file) {
                return $mapped_file;
            }

            /* 删除尾随命名空间分隔，作为下一次迭代参数 */
            $prefix = rtrim($prefix, '\\');
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
        // 检查是否有此命名空间前缀的基本目录
        if (isset(self::$prefixes[$prefix]) === false) {
            return false;
        }

        // 通过基本目录寻找这个命名空间前缀
        foreach (self::$prefixes[$prefix] as $base_dir) {

            //使用基本目录替换空间前缀
            //替换目录分隔符分隔空间
            //在相关的类名，追加与.PHP
            $file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . EXT;

            // 如果映射文件存在，加载它
            if (self::requireFile($file)) {
                return true;
            }
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
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }
}