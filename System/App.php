<?php
namespace Ant;

use Exception;
use Ant\Container\Container;
use Ant\Middleware\Middleware;

class App{
    use Middleware;

    /**
     * 加载的中间件
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * 服务容器
     *
     * @var Container
     */
    protected $container;

    /**
     * 异常处理函数
     *
     * @var callable
     */
    protected $exceptionHandler;

    /**
     * App constructor.
     */
    public function __construct()
    {
        $this->container = Container::getInstance();
    }

    /**
     * 注册服务
     */
    public function registerService()
    {
        $this->container->registerService(new BaseServiceProvider());
    }

    /**
     * php7错误跟异常都继承于Throwable,可以用try...catch的方式来捕获程序中的错误
     */
    public function setErrorHandler(callable $errorHandler)
    {
        if(version_compare(PHP_VERSION, '7.0.0', '<')){
            set_error_handler($errorHandler);
        }
    }

    public function setExceptionHandler(callable $handler)
    {
        $this->exceptionHandler = $handler;

        return $this;
    }

    public function getExceptionHandler(){
        if(is_callable($this->exceptionHandler)){
            return $this->exceptionHandler;
        }

        return function($exception){
            
        };
    }

    public function run()
    {
        $request = $this->container['request'];
        $response = $this->container['response'];

        $this->withArguments(function()use($request,$response){
            return $this->container->make('arguments',[$request,$response])->all();
        });

        try{
            $this->execute();
        }catch(Exception $e){
            call_user_func($this->getExceptionHandler(),$e);
        }catch(\Throwable $e){
            call_user_func($this->getExceptionHandler(),$e);
        }

        $response->send();
    }
}