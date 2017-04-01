<?php
namespace Ant\Foundation\Cgi;

use Ant\Http\Response;
use Ant\Routing\Router;
use Ant\Support\Collection;
use Ant\Http\ServerRequest;
use Ant\Foundation\Debug\ExceptionHandle;
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
        $container->instance('app', $container);

        /**
         * 注册 配置信息信息集
         */
        $container->singleton('config', Collection::class);

        /**
         * 注册 Http Request 处理类
         */
        $container->singleton('request', ServerRequest::class);

        /**
         * 注册 Http Response 类
         */
        $container->singleton('response', function ($container) {
            // 传入配置信息中的默认Http头
            return new Response(200, $container['config']->get("header", []));
        });

        /**
         * 注册 Ant Router 类
         */
        $container->singleton('router', Router::class);

        /**
         * 注册 Debug 对象
         */
        $container->bindIf('debug', ExceptionHandle::class);
    }
}