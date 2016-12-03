<?php
namespace Ant\Middleware;

use Closure;
use Generator;
use Exception;

/**
 * Class Middleware
 * @package Ant\Middleware
 */
class Pipeline
{
    /**
     * 默认加载的中间件
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * 执行时传递给每个中间件的参数
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * 7.0之前使用第二次协同返回数据,7.0之后通过getReturn返回数据
     *
     * @var bool
     */
    protected $getReturnValue = false;

    /**
     * Middleware constructor.
     */
    public function __construct()
    {
        $this->getReturnValue = version_compare(PHP_VERSION, '7.0.0', '>=');
    }

    /**
     * 设置在中间件中传输的参数
     *
     * @return self $this
     */
    public function send()
    {
        $this->arguments = func_get_args();

        return $this;
    }

    /**
     * 设置经过的中间件
     *
     * @param $handle
     * @return $this
     */
    public function through($handle)
    {
        $this->handlers = is_array($handle) ? $handle : func_get_args();

        return $this;
    }

    /**
     * 设定中间件运行终点,并执行
     *
     * @param Closure $destination
     * @return mixed
     * @throws Exception
     */
    public function then(Closure $destination)
    {
        //初始化参数
        $stack = [];
        $arguments = $this->arguments;

        try{
            foreach ($this->handlers as $handler) {
                $generator = call_user_func_array($handler,$arguments);

                if ($generator instanceof Generator) {
                    //将协同函数添加到函数栈
                    $stack[] = $generator;

                    $yieldValue = $generator->current();
                    if ($yieldValue === false) {
                        //打断中间件执行流程
                        return null;
                    }elseif($yieldValue instanceof Arguments){
                        //替换传递参数
                        $arguments = $yieldValue->toArray();
                    }
                }
            }

            $result = $destination(...$arguments);
            $isSend = ($result !== null);
            //回调函数栈
            while ($generator = array_pop($stack)) {
                /* @var $generator Generator */
                if ($isSend) {
                    $generator->send($result);
                }else{
                    $generator->next();
                }

                //尝试用协同返回数据进行替换,如果无返回则继续使用之前结果
                if ($this->getReturnValue) {
                    $result = $generator->getReturn() ?: $result;
                }else{
                    $result = $generator->current() ?: $result;
                }

                $isSend = ($result !== null);
            }
        }catch(Exception $e){
            $tryCatch = $this->exceptionHandle($stack,function($e){
                //如果无法处理,交给上层应用处理
                throw $e;
            });

            $result = $tryCatch($e);
        }

        return $result;
    }

    /**
     * 处理异常
     *
     * @param $stack
     * @param $throw
     * @return Closure
     */
    protected function exceptionHandle($stack, $throw)
    {
        //此处的异常处理是以责任链的方式完成
        //出现异常之后开始回调中间件函数栈
        //如果内层中间件无法处理异常
        //那么外层中间件会尝试捕获这个异常
        //如果一直无法处理,异常将会抛到最顶层来处理
        //如果处理了这个异常,那么异常回调链将会被打断
        //程序会返回至中间件启动的位置
        return array_reduce($stack,function(Closure $stack,Generator $generator){
            return function(Exception $exception)use($stack,$generator){
                try{
                    //将异常交给内层中间件
                    $generator->throw($exception);

                    return $this->getReturnValue
                        ? $generator->getReturn()
                        : $generator->current();
                }catch(Exception $e) {
                    //将异常交给外层中间件
                    return $stack($e);
                }
            };
        },$throw);
    }
}