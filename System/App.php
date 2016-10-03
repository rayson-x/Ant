<?php
namespace Ant;

use Ant\Container\Container;
use Ant\Middleware\Middleware;
use Ant\Http\Request as HttpRequest;
use Ant\Http\Response as HttpResponse;
use Ant\Http\Exception as HttpException;
use Ant\Interfaces\ServiceProviderInterface;
use Psr\Http\Message\RequestInterface;
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
     * 应用内核
     *
     * @var \Closure
     */
    protected $applicationKernel;

    /**
     * App constructor.
     * @param string $path
     */
    public function __construct($path = '/')
    {
        $this->container = Container::getInstance();

        $this->register(BaseServiceProvider::class);

        $this->registerNamespace('App',$path);
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

    /**
     * 注册命名空间
     *
     * @param $namespace
     * @param $path
     * @param null $className
     * @return mixed
     */
    public function registerNamespace($namespace, $path, $className = null)
    {
        $namespace = trim($namespace, '\\');
        $path = rtrim($path, '/\\');

        $loader = function ($className, $returnFileName = false) use ($namespace, $path) {
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

            if ($returnFileName) {
                return $filename;
            } else {
                if (!file_exists($filename)) {
                    return false;
                }

                require $filename;

                return class_exists($className, false) || interface_exists($className, false);
            }
        };

        if ($className === null) {
            spl_autoload_register($loader);
        } else {
            return $loader($className, true);
        }
    }

    /**
     * @param callable $handler
     * @return $this
     */
    public function registerExceptionHandler(callable $handler)
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
            /* @var $response \Ant\Http\Response */
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
     * @param \Closure $kernel
     */
    public function  setApplicationKernel(\Closure $kernel)
    {
        //TODO::函数名,变量类型,提供更多变的方式
        $this->applicationKernel = $kernel;
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

        $response = $this->process($request,$response);

        $response->send();
    }

    /**
     * 处理一个请求
     *
     * @param $request
     * @param $response
     * @return \Ant\Http\Response
     */
    public function process($request,$response)
    {
        ob_start();
        $level = ob_get_level();

        try{
            $result = (new Middleware)
                ->send($request,$response)
                ->through($this->middleware)
                ->then($this->applicationKernel);
        }catch(\Exception $exception){
            $result = call_user_func($this->getExceptionHandler(),$exception,$request,$response);
        }catch(\Throwable $error){
            $result = call_user_func($this->getExceptionHandler(),$error,$request,$response);
        }

        if(! $result instanceof HttpResponse){
            // 将高嵌套级别的缓冲区的内容清除
            while(ob_get_level() > $level){
                ob_get_clean();
            }

            // 将输出内容写入响应body
            $result = $response->write(ob_get_clean());
        }

        return $result;
    }
}