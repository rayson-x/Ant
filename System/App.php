<?php
namespace Ant;

use Ant\Container\Container;

class App{
    use Middleware{
        Middleware::addMiddleware as setMiddleware;
    }

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

    public function addMiddleware($name,callable $callable)
    {
        $this->middleware[] = $name;
        $this->setMiddleware($name,$callable);
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

        try{
            $middleware = $this->getMiddleware($this->middleware);
            $this->execute($middleware,[$request,$response]);
        }catch(Exception $e){
            echo $e->getMessage()."<br>";
            foreach(explode("\n", $e->getTraceAsString()) as $index => $line ){
                echo "{$line} <br>";
            }
        }catch(\Error $e){
            echo " Error : {$e->getMessage()}";
            foreach(explode("\n", $e->getTraceAsString()) as $index => $line ){
                echo "{$line} <br>";
            }
        }catch(\Throwable $e){
            echo " Exception : {$e->getMessage()}";
        }

        $response->send();
    }
}