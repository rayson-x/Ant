<?php
namespace Ant;

use Ant\Http\Request;
use Ant\Http\RequestBody;
use Ant\Http\Response;
use Ant\Http\Uri;
use Ant\Routing\Router;
use Ant\Http\Environment;
use Ant\Http\ServerRequest;
use Ant\Container\Interfaces\ContainerInterface;
use Ant\Container\Interfaces\ServiceProviderInterface;

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
         * 注册 Http Request 处理类
         */
        $container->singleton('request',function(){
            return new ServerRequest;
        });

        /**
         * 注册 Http Response 类
         */
        $container->singleton('response',function(){
            return (new Response())->keepImmutability(false);
        });

        /**
         * 注册 Ant Router 类
         */
        $container->singleton('router',function(){
            return new Router($this);
        });

        /**
         * 注册 Debug 对象
         */
        $container->bindIf('debug',\Ant\Debug\ExceptionHandle::class);
    }
}