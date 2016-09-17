<?php
namespace Ant;

use Ant\Http\Request;
use Ant\Http\Response;
use Ant\Http\Environment;
use Ant\Interfaces\ContainerInterface;
use Ant\Interfaces\ServiceProviderInterface;

class BaseServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container)
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
     * @param ContainerInterface $container
     */
    protected function registerServiceNeedArguments(ContainerInterface $container)
    {

    }

    /**
     * 注册各种数据类型的服务
     *
     * @param ContainerInterface $container
     */
    protected function registerOtherTypesService(ContainerInterface $container)
    {
        /**
         * 将中间件参数托管至此服务
         * 通过修改此服务实例来达到
         * 修改调用时传递给每个中间件的参数
         */
        $container->bind('arguments',function(){
            /* @var $this ContainerInterface */
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