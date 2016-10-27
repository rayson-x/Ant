<?php
namespace Ant;

use Ant\Http\Body;
use Ant\Traits\Singleton;
use Ant\Container\Container;
use Ant\Middleware\Middleware;
use Ant\Http\Request as HttpRequest;
use Ant\Http\Response as HttpResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Ant\Exception\MethodNotAllowedException;
use Ant\Interfaces\Container\ServiceProviderInterface;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\Debug\Exception\FatalErrorException;

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
     * 支持的http请求方式,如果不在下列方法内会抛出异常
     *
     * @var array
     */
    protected $methods = [
        'GET' => 1,             // 请求资源
        'PUT' => 1,             // 创建资源
        'POST' => 1,            // 创建或者完整的更新了资源
        'DELETE' => 1,          // 删除资源
        'HEAD' => 1,            // 只获取某个资源的头部信息
        'PATCH' => 1,           // 局部更新资源
        'OPTIONS ' => 1         // 获取资源支持的HTTP方法
    ];

    /**
     * App constructor.
     *
     * @param string $path 项目路径
     */
    public function __construct($path = null)
    {
        $this->basePath = trim($path);
        $this->registerError();
        $this->bootstrapContainer();
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
     * 初始化应用容器.
     */
    protected function bootstrapContainer()
    {
        static::setInstance($this);
        $this->registerService(new BaseServiceProvider);
        $this->registerContainerAliases();
    }

    /**
     * 注册服务别名
     */
    protected function registerContainerAliases()
    {
        $aliases = [
            \Ant\App::class                                     => 'app',
            \Ant\Container\Container::class                     => 'app',
            \Ant\Interfaces\ContainerInterface::class           => 'app',
            \Ant\Routing\Router::class                          => 'router',
            \Psr\Http\Message\ServerRequestInterface::class     => 'request',
            \Ant\Http\Request::class                            => 'request',
            \Psr\Http\Message\ResponseInterface::class          => 'response',
            \Ant\Http\Response::class                           => 'response',
        ];

        foreach($aliases as $alias => $serviceName){
            $this->alias($serviceName,$alias);
        }
    }

    /**
     * 注册错误信息
     */
    public function registerError()
    {
        error_reporting(E_ALL);

        set_error_handler(function($level, $message, $file = '', $line = 0){
            throw new \ErrorException($message, 0, $level, $file, $line);
        });

        register_shutdown_function(function () {
            if (!is_null($error = error_get_last()) &&
                in_array($error['type'],[E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])
            ){
                throw new FatalErrorException(
                    $error['message'], $error['type'], 0, $error['file'], $error['line']
                );
            }
        });

        set_exception_handler(function($e){
            $this->handleUncaughtException($e)->send();
        });
    }

    /**
     * 处理未捕获异常
     *
     * @param \Exception $exception
     * @return HttpResponse
     */
    protected function handleUncaughtException($exception)
    {
        // 此处是为了兼容PHP7
        // PHP7中错误可以跟异常都实现了Throwable接口
        // 所以错误也会跟异常一起被捕获
        // 此处将捕获到的错误转换成异常
        if ($exception instanceof \Error) {
            $exception = new FatalThrowableError($exception);
        }

        $handle = $this->make(\Ant\Debug\ExceptionHandle::class);
        $response = $this['response']->replaceBody(fopen('php://temp','w+'));

        return $handle->render($exception,$response,true);
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
    protected function getExceptionHandler()
    {
        if(is_callable($this->exceptionHandler)){
            return $this->exceptionHandler;
        }

        // 如果开发者没有处理异常
        // 异常将会交由框架进行处理
        return function($exception){
            return $this->handleUncaughtException($exception);
        };
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
            $this->filterMethod($request);
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
     * 过滤非法请求方式
     *
     * @param \Ant\Http\Request $request
     */
    protected function filterMethod(\Ant\Http\Request $request)
    {
        $method = strtoupper($request->getMethod());

        if(!array_key_exists($method,$this->methods)){
            throw new MethodNotAllowedException(array_keys($this->methods),sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }
    }

    /**
     * 解析响应结果
     *
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