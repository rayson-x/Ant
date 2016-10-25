<?php
namespace Ant;

use Ant\Traits\Singleton;
use Ant\Container\Container;
use Ant\Middleware\Middleware;
use Ant\Exception\HttpException;
use Ant\Http\Request as HttpRequest;
use Ant\Http\Response as HttpResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Ant\Interfaces\Container\ServiceProviderInterface;

/**
 * Class App
 * @package Ant
 */
class App extends Container
{
    use Singleton;

    /**
     * 加载的中间件
     *
     * @var array
     */
    protected $middleware = [];

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
     *
     * @param string $path 项目路径
     */
    public function __construct($path = null)
    {
        static::setInstance($this);
        $this->basePath = trim($path);
        $this->registerError();
        $this->registerService(new BaseServiceProvider);
        $this->registerNamespace('App',$this->basePath.DIRECTORY_SEPARATOR.'app');
    }

    /**
     * 注册服务提供者,如果服务提供者不符合规范将会跳过
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

        if($provider instanceof ServiceProviderInterface){
            $this->registerService($provider);
        }
    }

    /**
     * 注册全局容器
     */
    public function registerInstance()
    {
        $this->instance('app',$this);
        $this->alias('app',Container::class);
        $this->alias('app',App::class);
    }

    /**
     * 注册错误信息
     */
    public function registerError()
    {
        set_error_handler(function($level, $message, $file = '', $line = 0){
            throw new \ErrorException($message, 0, $level, $file, $line);
        });
        error_reporting(E_ALL);
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
                // 获取HTTP状态码
                $status = $exception->getStatusCode();

                // 将头信息写入响应头
                foreach($exception->getHeaders() as $name => $value){
                    $response->withAddedHeader($name,$value);
                }
            } else {
                $status = 500;
            }
            $response->withStatus($status);

            foreach (exceptionHandle($exception) as $key => $value) {
                $response->write($value.(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
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
        return $this['router'];
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
        $request = $this['request'];
        $response = $this['response'];

        $result = $this->parseResponse(
            $this->process($request,$response)
        );

        $result->send();
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
        try{
            $result = $this->sendThroughMiddleware([$request,$response],$this->middleware,function(){
                return $this->parseResponse(
                    $this['router']->run(...func_get_args())
                );
            });
        }catch(\Exception $exception){
            $result = call_user_func($this->getExceptionHandler(),$exception,$request,$response);
        }catch(\Throwable $error){
            $result = call_user_func($this->getExceptionHandler(),$error,$request,$response);
        }

        return $result;
    }

    /**
     * @param $result
     * @return HttpResponse
     */
    protected function parseResponse($result)
    {
        if(!$result instanceof HttpResponse){
            $this['response']->setContent($result);
            $result = $this['response'];
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