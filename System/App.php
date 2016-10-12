<?php
namespace Ant;

use Ant\Container\Container;
use Ant\Middleware\Middleware;
use Ant\Http\Request as HttpRequest;
use Ant\Http\Response as HttpResponse;
use Ant\Http\Exception as HttpException;
use Ant\Interfaces\Container\ServiceProviderInterface;use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class App
 * @package Ant
 *
 * 重载了服务容器的方法
 * @method Container bound($serviceName)
 * @method Container resolved($serviceName)
 * @method Container alias($serviceName, $alias)
 * @method Container tag($serviceGroup, $tags)
 * @method Container tagged($tag)
 * @method Container bind($serviceName, $concrete = null, $shared = false)
 * @method Container bindIf($serviceName, $concrete = null, $shared = false)
 * @method Container singleton($serviceName, $concrete = null)
 * @method Container instance($serviceName, $instance)
 * @method Container extend($serviceName, \Closure $closure)
 * @method Container when($concrete)
 * @method Container make($serviceName, array $parameters = [])
 * @method Container build($concrete, array $parameters = [])
 * @method Container call($callback,$parameters = [],$defaultMethod = null)
 * @method Container callClass($callback,$parameters = [],$defaultMethod = null)
 * @method Container forgetService($name)
 * @method Container registerService(ServiceProviderInterface $serviceProvider)
 */
class App
{
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
     * 已注册服务提供者
     *
     * @var array
     */
    protected $loadedProviders = [];

    /**
     * 异常处理函数
     *
     * @var callable
     */
    protected $exceptionHandler;

    /**
     * 项目路径
     *
     * @var string
     */
    protected $basePath = '';

    /**
     * App constructor.
     * @param string $path 项目路径
     */
    public function __construct($path = null)
    {
        $this->container = Container::getInstance();

        $this->register(new BaseServiceProvider);

        $this->registerError();

        $this->basePath = trim($path);

        $this->registerNamespace('App',$this->basePath.DIRECTORY_SEPARATOR.'app');
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method,$args)
    {
        return $this->container->$method(...$args);
    }

    /**
     * 注册服务提供者
     *
     * @param $provider
     */
    public function register($provider)
    {
        if(is_string($provider)){
            $provider = new $provider;
        }

        if (array_key_exists($providerName = get_class($provider), $this->loadedProviders)) {
            return;
        }

        $this->loadedProviders[$providerName] = true;

        //TODO::遇到类型错误的服务容器在 抛出异常,跳过此服务 二者选一
        if($provider instanceof ServiceProviderInterface){
            $this->container->registerService($provider);
        }
    }

    public function registerError()
    {
        set_error_handler(function($level, $message, $file = '', $line = 0){
            throw new \ErrorException($message, 0, $level, $file, $line);
        });
        error_reporting(-1);
    }

    /**
     * 注册命名空间
     *
     * @param $namespace
     * @param $path
     */
    public function registerNamespace($namespace, $path)
    {
        $namespace = trim($namespace, '\\');
        $path = rtrim($path, '/\\');

        spl_autoload_register(function ($className) use ($namespace, $path) {
            if (class_exists($className, false) || interface_exists($className, false)) {
                return true;
            }

            $className = trim($className, '\\');

            if ($namespace && stripos($className, $namespace) !== 0) {
                return false;
            } else {
                $filename = trim(substr($className, strlen($namespace)), '\\');
            }

            $filename = $path.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $filename).'.php';

            if (!file_exists($filename)) {
                return false;
            }

            require $filename;

            return class_exists($className, false) || interface_exists($className, false);
        });
    }

    /**
     * 注册自定义异常处理方式
     *
     * @param callable $handler
     * @return $this
     */
    public function registerExceptionHandler(callable $handler)
    {
        $this->exceptionHandler = $handler;

        return $this;
    }

    /**
     * 获取异常处理方法
     *
     * @return callable|\Closure
     */
    public function getExceptionHandler(){
        if(is_callable($this->exceptionHandler)){
            return $this->exceptionHandler;
        }

        return function($exception,$request,$response){
            /* @var $response HttpResponse */
            if ($exception instanceof HttpException) {
                $status = $exception->getCode();
            } else {
                $status = 500;
            }

            $response->withStatus($status);
            foreach (exceptionHandle($exception) as $key => $value) {
                $response->write($value."<br>");
            }

            return $response;
        };
    }

    /**
     * 获取路由器
     *
     * @return \Ant\Routing\Router
     */
    public function createRouter()
    {
        return $this->container->make('router');
    }
    
    /**
     * 添加应用中间件
     *
     * @param callable $middleware
     */
    public function addMiddleware(callable $middleware)
    {
        $this->middleware[] = $middleware;
    }

    /**
     * 启动框架
     */
    public function run()
    {
        $request = $this->container['request'];
        $response = $this->container['response'];

        $result = $this->process($request,$response);

        if(!$result instanceof HttpResponse){
            $response->write($result);
        }

        $response->send();
    }

    /**
     * 处理一个请求
     *
     * @param $request
     * @param $response
     * @return \Ant\Http\Response
     */
    protected function process($request,$response)
    {
        //TODO::将缓冲区在响应解析完毕之后输出
        try{
            $result = $this->sendThroughMiddleware([$request,$response],$this->middleware,function(){
                return $this->parseResponse(
                    $this->container['router']->run(...func_get_args())
                );
            });
        }catch(\Exception $exception){
            $result = call_user_func($this->getExceptionHandler(),$exception,$request,$response);
        }catch(\Throwable $error){
            $result = call_user_func($this->getExceptionHandler(),$error,$request,$response);
        }

        return $result;
    }

    protected function parseResponse($result)
    {
        if(!$result instanceof HttpResponse){
            return $this->container['response']->setContent($result);
        }

        return $result;
    }

    /**
     * 发送请求与响应通过中间件到达回调函数
     *
     * @param array $args
     * @param array $handlers
     * @param \Closure $then
     * @return mixed
     */
    protected function sendThroughMiddleware(array $args,array $handlers,\Closure $then)
    {
        if(count($handlers) > 0){
            return (new Middleware)
                ->send(...$args)
                ->through($handlers)
                ->then($then);
        }

        return $then(...$args);
    }
}