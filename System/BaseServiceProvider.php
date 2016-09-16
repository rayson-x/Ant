<?php
namespace Ant;

use Ant\Http\Request;
use Ant\Http\Response;
use Ant\Http\Environment;
use Ant\Container\Container;
use Ant\Interfaces\ServiceProviderInterface;

class BaseServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        /**
         * 按照顺序注册服务
         */
        $this->registerOtherTypesService($container);
        $this->registerServiceNeedArguments($container);
        $this->registerClass($container);
        $this->registerServiceExtend($container);
    }

    /**
     * 注册服务实例
     *
     * @param Container $container
     */
    protected function registerClass(Container $container)
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
            return new Request($this['environment']);
        },true);

        /**
         * 注册 Http Response 类
         */
        $container->bindIf([Response::class => 'response'],function(){
            return new Response();
        },true);
    }

    /**
     * 注册服务扩展
     *
     * @param Container $container
     */
    protected function registerServiceExtend(Container $container)
    {
        /**
         * 扩展 Http Request 处理类
         *
         * @return Request
         */
        $container->extend(Request::class,function($request){
            /* @var $request Request */
            if(method_exists($request,'setBodyParsers')){
                $request->setBodyParsers('json',function($input){
                    return json_decode($input,true);
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
            }
        });
    }

    /**
     * 给服务注册依赖参数
     *
     * @param Container $container
     */
    protected function registerServiceNeedArguments(Container $container)
    {

    }

    /**
     * 注册各种数据类型的服务
     *
     * @param Container $container
     */
    protected function registerOtherTypesService(Container $container)
    {
        /**
         * 中间件参数统一进行管理
         */
        $container->bind('arguments',function(){
            /* @var $this Container */
            static $arguments = null;
            if(is_null($arguments)){
                $arguments = new Collection();
            }
            if(isset($this['newRequest'])){
                $arguments[0] = $this['newRequest'];
                $this->forgetService('newRequest');
            }
            if(isset($this['newResponse'])) {
                $arguments[1] = $this['newResponse'];
                $this->forgetService('newResponse');
            }

            foreach(func_get_args() as $arg){
                $arguments[$arguments->count()] = $arg;
            }

            return $arguments;
        });
    }
}