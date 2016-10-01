<?php
namespace Ant;

use Ant\Container\Container;
use Ant\Middleware\Middleware;
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
     * App constructor.
     * @param string $namespace
     * @param string $path
     */
    public function __construct($path = '/')
    {
        $this->container = Container::getInstance();

        $this->register(BaseServiceProvider::class);

        //注册应用程序命名空间
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
     * @param null $classname
     * @return mixed
     */
    public function registerNamespace($namespace, $path, $classname = null)
    {
        $namespace = trim($namespace, '\\');
        $path = rtrim($path, '/\\');

        $loader = function ($classname, $return_filename = false) use ($namespace, $path) {
            if (class_exists($classname, false) || interface_exists($classname, false)) {
                return true;
            }

            $classname = trim($classname, '\\');

            if ($namespace && stripos($classname, $namespace) !== 0) {
                return false;
            } else {
                $filename = trim(substr($classname, strlen($namespace)), '\\');
            }

            $filename = $path.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $filename).'.php';

            if ($return_filename) {
                return $filename;
            } else {
                if (!file_exists($filename)) {
                    return false;
                }

                require $filename;

                return class_exists($classname, false) || interface_exists($classname, false);
            }
        };

        if ($classname === null) {
            spl_autoload_register($loader);
        } else {
            return $loader($classname, true);
        }
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
            /* @var $response \Ant\Http\Response */
            if ($exception instanceof \Ant\Http\Exception) {
                $status = $exception->getCode();
            } else {
                $status = 500;
            }

            $response->withStatus($status);

            foreach (exceptionHandle($exception) as $key => $value) {
                $response->write($value."<br>");
            }
        };
    }

    /**
     * 启动框架
     */
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
     * @param $request
     * @param $response
     */
    public function process($request,$response)
    {
        try{
            $this->withArguments([$request,$response]);

            $this->execute();
        }catch(\Exception $exception){
            call_user_func($this->getExceptionHandler(),$exception,$request,$response);
        }catch(\Throwable $error){
            call_user_func($this->getExceptionHandler(),$error,$request,$response);
        }
    }
}