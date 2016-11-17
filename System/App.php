<?php
namespace Ant;

use Ant\Http\Body;
use Ant\Traits\Singleton;
use Ant\Container\Container;
use Ant\Middleware\Middleware;
use Ant\Http\Request as HttpRequest;
use Ant\Http\Response as HttpResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
        'GET',             // 请求资源
        'PUT',             // 创建资源
        'POST',            // 创建或者完整的更新了资源
        'DELETE',          // 删除资源
        'HEAD',            // 只获取某个资源的头部信息
        'PATCH',           // 局部更新资源
        'OPTIONS'          // 获取资源支持的HTTP方法
    ];

    /**
     * App constructor.
     *
     * @param string $path 项目路径
     */
    public function __construct($path = null)
    {
        $this->basePath = rtrim($path,DIRECTORY_SEPARATOR);
        $this->registerError();
        $this->bootstrapContainer();
        $this->registerNamespace('App',$this->basePath.DIRECTORY_SEPARATOR.'App');
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
            \Ant\Interfaces\Container\ContainerInterface::class => 'app',
            \Ant\Routing\Router::class                          => 'router',
            \Psr\Http\Message\ServerRequestInterface::class     => 'request',
            \Ant\Http\Request::class                            => 'request',
            \Psr\Http\Message\ResponseInterface::class          => 'response',
            \Ant\Http\Response::class                           => 'response',
            \Ant\Debug\ExceptionHandle::class                   => 'debug',
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
            $this->send($this->handleUncaughtException($e));
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

        $handle = $this->make('debug');
        $response = $this['response']->withBody(new Body(fopen('php://temp','w+')));

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
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        spl_autoload_register(function ($className) use ($namespace, $path) {
            // 如果已经存在,直接返回
            if (class_exists($className, false) || interface_exists($className, false)) {
                return true;
            }

            $className = trim($className, '\\');

            // 检查类是否存在于此命名空间之下
            if ($namespace && stripos($className, $namespace) !== 0) {
                return false;
            }

            // 根据命名空间截取出文件路径
            $filename = trim(substr($className, strlen($namespace)), '\\');
            // 拼接路径
            $filename = $path.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $filename).'.php';

            if (!file_exists($filename)) {
                return false;
            }
            // 引入文件
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

        $result = $this->process($request,$response);

        $this->send($result);
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
                return $this['router']->dispatch(...func_get_args());
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
     * @param ServerRequestInterface $request
     */
    protected function filterMethod(ServerRequestInterface $request)
    {
        $method = strtoupper($request->getMethod());

        if(!in_array($method,$this->methods)){
            throw new MethodNotAllowedException($this->methods,sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }
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

    /**
     * 向客户端发送数据
     *
     * @param mixed $result
     */
    public function send($result)
    {
        $response = $this->handleResult($result);
        $this->sendHeader($response)->sendContent($response);

        if (function_exists("fastcgi_finish_request")) {
            fastcgi_finish_request();
        }elseif('cli' != PHP_SAPI){
            $this->closeOutputBuffers(0,true);
        }
    }

    /**
     * 解析响应结果
     *
     * @param $result
     * @return HttpResponse
     */
    protected function handleResult($result)
    {
        if(!$result instanceof HttpResponse){
            $result = $this['response'];
        }

        return $result;
    }

    /**
     * 发送头信息
     *
     * @return $this
     */
    public function sendHeader(HttpResponse $response)
    {
        if(!headers_sent()){
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            foreach($response->getHeaders() as $name => $value){
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                $name = implode('-',array_map('ucfirst',explode('-',$name)));
                header(sprintf('%s: %s',$name,$value));
            }

            foreach($response->getCookies() as list($name, $value, $expire, $path, $domain , $secure, $httponly)){
                setcookie($name, $value, $expire, $path, $domain , $secure, $httponly);
            }
        }

        return $this;
    }

    /**
     * 发送消息主体
     *
     * @return $this
     */
    public function sendContent(HttpResponse $response)
    {
        if(!$response->isEmpty()){
            echo (string) $response->getBody();
        }else{
            echo '';
        }

        return $this;
    }

    /**
     * 关闭并输出缓冲区
     *
     * @param $targetLevel
     * @param $flush
     */
    public function closeOutputBuffers($targetLevel, $flush)
    {
        $status = ob_get_status(true);
        $level = count($status);
        $flags = PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE);

        while ($level-- > $targetLevel
            && ($s = $status[$level])
            && (!isset($s['del']) ? !isset($s['flags']) || $flags === ($s['flags'] & $flags) : $s['del'])
        ){
            if ($flush) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
        }
    }
}