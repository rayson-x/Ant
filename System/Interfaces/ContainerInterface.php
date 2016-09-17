<?php
namespace Ant\Interfaces;

use Closure;

interface ContainerInterface
{
    /**
     * 检查服务是否绑定
     *
     * @param $serviceName
     * @return bool
     */
    public function bound($serviceName);

    /**
     * 检查服务是否解决
     *
     * @param $serviceName
     * @return bool
     */
    public function resolved($serviceName);

    /**
     * 绑定服务到容器
     *
     * @param string|array $serviceName         服务名称
     * @param mixed $concrete                   具体实现
     * @param bool $shared                      是否共享,即全局唯一
     */
    public function bind($serviceName, $concrete = null, $shared = false);

    /**
     * 绑定未绑定服务,如果已绑定就放弃
     *
     * @param string|array $serviceName
     * @param mixed $concrete
     * @param bool $shared
     */
    public function bindIf($serviceName, $concrete = null, $shared = false);

    /**
     * 绑定一个单例
     *
     * @param string|array $serviceName
     * @param mixed $concrete
     */
    public function singleton($serviceName, $concrete = null);

    /**
     * 绑定一个实例
     *
     * @param string|array $serviceName
     * @param mixed $instance
     */
    public function instance($serviceName, $instance);

    /**
     * 扩展容器中一个服务
     *
     * @param string|array $serviceName
     * @param Closure $closure
     */
    public function extend($serviceName, Closure $closure);

    /**
     * 给服务定义一个别名
     *
     * @param string $serviceName
     * @param string $alias
     */
    public function alias($serviceName, $alias);

    /**
     * 通过服务名称获取服务
     *
     * @param string $serviceName   服务名称或者服务别名
     * @param array $parameters     生成服务所需要的参数
     * @return mixed                返回服务(通常为object,但是也允许其他类型)
     */
    public function make($serviceName, array $parameters = []);

    /**
     * 将服务打上标签
     *
     * @param array|string $serviceGroup
     * @param array|string $tags
     */
    public function tag($serviceGroup, $tags);

    /**
     * 获取一个标签里的所有服务
     *
     * @param $tag
     * @return array
     */
    public function tagged($tag);
    
    /**
     * 从容器中移除一个服务
     *
     * @param $name
     */
    public function forgetService($name);
}