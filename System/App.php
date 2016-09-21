<?php
namespace Ant;

use Ant\Http\Response;
use Ant\Container\Container;
use Ant\Middleware\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
     * @param callable $handler
     * @return $this
     */
    public function setExceptionHandler(callable $handler)
    {
        $this->exceptionHandler = $handler;

        return $this;
    }

    /**
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

//            foreach (exceptionHandle($exception) as $key => $value) {
//                $response->withHeader($key, $value);
//            }
        };
    }

    public function run()
    {
        $request = $this->container['request'];
        $response = $this->container['response'];

        $this->process($request,$response);

        $response->send();
    }

    /**
     * 处理一个请求
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    public function process(RequestInterface $request,ResponseInterface $response)
    {
        // 将中间件参数交给服务容器维护
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
    }
}