<?php
namespace Ant;

use Ant\Http\Response;
use Ant\Http\Request;
use Ant\Support\Http\Environment;
use Ant\Interfaces\ContainerInterface;
use Ant\Interfaces\ServiceProviderInterface;

class BaseServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container)
    {
        /**
         * 按照顺序注册服务
         */
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
         * 注册Server数据集
         *
         * @return Environment;
         */
        $container->bindIf([Environment::class => 'environment'],function(){
            return new Environment($_SERVER);
        });

        /**
         * 注册 Http Request 处理类
         *
         * @return Request
         */
        $container->bindIf([Request::class => 'request'],function(){
            return \Ant\Support\Http\Request::createRequestFromEnvironment($this['environment']);
        },true);

        /**
         * 注册 Http Response 类
         */
        $container->bindIf([Response::class => 'response'],function(){
            return new Response();
        },true);

        $container->singleton([\SuperClosure\Serializer::class => 'serialize']);
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
         *
         * @return Request
         */
        $container->extend(Request::class,function($request){
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
        });
    }
}