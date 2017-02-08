<?php
namespace Ant\Foundation\Cgi;

use Ant\Http\Response;
use Ant\Routing\Router;
use Ant\Http\ServerRequest;
use Ant\Support\Collection;
use Ant\Container\Interfaces\ContainerInterface;
use Ant\Container\Interfaces\ServiceProviderInterface;

/**
 * 基础服务提供者
 *
 * Class BaseServiceProvider
 * @package Ant\Foundation\Cgi
 */
class BaseServiceProvider implements ServiceProviderInterface
{
    /**
     * 注册服务
     *
     * @param ContainerInterface $container
     */
    public function register(ContainerInterface $container)
    {
        /**
         * 注册服务容器
         */
        $container->instance('app',$container);

        /**
         * 注册 配置信息信息集
         */
        $container->instance('config',new Collection());

        /**
         * 注册 Http Request 处理类
         */
        $container->singleton('request',function() {
            return new ServerRequest;
        });

        /**
         * 注册 Http Response 类
         */
        $container->singleton('response',function() {
            return Response::prepare($this['request']);
        });

        /**
         * 注册 Ant Router 类
         */
        $container->singleton('router',function() {
            return new Router($this);
        });

        /**
         * 注册 Debug 对象
         */
        $container->bindIf('debug',\Ant\Foundation\Debug\ExceptionHandle::class);
    }
}