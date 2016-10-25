<?php
namespace Ant;

use Ant\Http\Request;
use Ant\Http\Response;
use Ant\Routing\Router;
use Ant\Http\Environment;
use Ant\Interfaces\Container\ContainerInterface;
use Ant\Interfaces\Container\ServiceProviderInterface;

class BaseServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container)
    {
        $this->registerClass($container);
        $this->registerServiceExtend($container);
    }

    /**
     * 注册服务实例
     *
     * @param ContainerInterface $container
     */
    protected function registerClass(ContainerInterface $container)
    {
        /**
         * 注册服务容器
         */
        $container->instance('app',$container);

        /**
         * 注册环境
         */
        $container->bind('environment',function(){
            return new Environment($_SERVER);
        });

        /**
         * 注册 Http Request 处理类
         */
        $container->singleton('request',function(){
            return Request::createRequestFromEnvironment($this['environment']);
        });

        /**
         * 注册 Http Response 类
         */
        $container->singleton('response',function(){
            $res = new Response();
            $res->setImmutability(false);
            return $res;
        });

        /**
         * 注册 Ant Router 类
         */
        $container->singleton('router',function(){
            return new Router($this);
        });
    }

    /**
     * 注册服务扩展
     *
     * @param ContainerInterface $container
     */
    protected function registerServiceExtend(ContainerInterface $container)
    {
        /**
         * 扩展 Http Request 处理类
         */
        $container->extend('request',function($request){
            /* @var $request Request */
            $request->setBodyParsers('json',function($input){
                return safe_json_decode($input,true);
            });

            $request->setBodyParsers('xml',function($input){
                $backup = libxml_disable_entity_loader(true);
                $result = simplexml_load_string($input);
                libxml_disable_entity_loader($backup);
                return $result;
            });

            $request->setBodyParsers('x-www-form-urlencoded',function($input){
                parse_str($input,$data);
                return $data;
            });

            return $request;
        });
    }
}