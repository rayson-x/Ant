<?php
namespace Ant;

use Ant\Http\Response;
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
        $this->container->registerService(new BaseServiceProvider());
    }

    /**
     * 设置异常处理方式
     *
     * @param callable $handler
     * @return $this
     */
    public function setExceptionHandler(callable $handler)
    {
        $this->exceptionHandler = $handler;

        return $this;
    }

    /**
     * 获取异常处理方式
     *
     * @return callable|\Closure
     */
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

            foreach (exceptionHandle($exception) as $key => $value) {
                //写入响应主体
                $response->write("{$value} <br>");
            }
        };
    }

    public function run()
    {
        $request = $this->container['request'];
        $response = $this->container['response'];
        /* @var $response Response*/

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