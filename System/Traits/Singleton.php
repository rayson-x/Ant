<?php
namespace Ant\Traits;

use Ant\Exception;

/**
 * 单例模式
 *
 * Class Singleton
 * @package Ant\Traits
 */
trait Singleton
{
    /**
     * @var self
     */
    protected static $instance;

    /**
     * 禁止外部实例化
     */
    protected function __construct()
    {
    }

    /**
     * 禁止克隆
     *
     * @throws Exception
     */
    public function __clone()
    {
        throw new Exception('Cloning '.__CLASS__.' is not allowed');
    }

    /**
     * 禁止序列化
     *
     * @throws Exception
     */
    public function __sleep()
    {
        throw new Exception('Serialize '.__CLASS__.' is not allowed');
    }

    /**
     * 获取实例
     *
     * @return \Ant\Container\Container|Singleton
     */
    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * 重置实例
     */
    public static function resetInstance()
    {
        unset(static::$instance);
    }
}

