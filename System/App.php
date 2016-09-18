<?php
namespace Ant;

use Ant\Container\Container;
use Ant\Http\Response;
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


    public function setExceptionHandler(callable $handler)
    {
        $this->exceptionHandler = $handler;

        return $this;
    }

    public function getExceptionHandler(){
        if(is_callable($this->exceptionHandler)){
            return $this->exceptionHandler;
        }

        return function($exception,$request,$response){
            /* @var $response Response*/
            if ($exception instanceof \Ant\Http\Exception) {
                $status = $exception->getCode();
            } else {
                $status = 500;
            }

            $response->withStatus($status);

//            foreach (exceptionHandle($exception) as $key => $value) {
//                $response->withHeader($key, $value);
//            }
        };
    }

    public function run()
    {
        $request = $this->container['request'];
        $response = $this->container['response'];

        /* 将中间件参数交给服务容器维护 */
        $this->withArguments(function()use($request,$response){
            static $init = false;
            if($init){
                return $this->container['arguments']->all();
            }

            $init = true;
            return $this->container->make('arguments',[$request,$response])->all();
        });

        try{
            $this->execute();
        }catch(\Exception $exception){
            call_user_func($this->getExceptionHandler(),$exception,$request,$response);
        }catch(\Throwable $error){
            call_user_func($this->getExceptionHandler(),$error,$request,$response);
        }

        $response->send();
    }
}