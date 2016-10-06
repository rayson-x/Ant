<?php
namespace Ant\Middleware;

use Closure;
use Generator;
use Exception;

/**
 * 管道模式,并非责任链模式
 * 此模式中除非打断调用链,不然每个回调都必将执行
 *
 * Class Middleware
 * @package Ant\Middleware
 */
class Middleware
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
     * @return null|mixed
     */
    public function then(Closure $destination)
    {
        try{
            $stack = [];
            $arguments = $this->arguments;
            foreach ($this->handlers as $handler) {
                $generator = $handler(...$arguments);

                if ($generator instanceof Generator) {
                    $stack[] = $generator;

                    $yieldValue = $generator->current();
                    if ($yieldValue === false) {
                        break;
                    }elseif($yieldValue instanceof Arguments){
                        //替换传递参数
                        $arguments = $yieldValue->toArray();
                    }
                }
            }

            $result = $destination(...$arguments);
            $isSend = ($result !== null);
            //7.0之前使用第二次协同返回数据,7.0之后通过getReturn返回数据
            $getReturnValue = version_compare(PHP_VERSION, '7.0.0', '>=');
            //回调函数栈
            while ($generator = array_pop($stack)) {
                /* @var $generator Generator */
                if ($isSend) {
                    $generator->send($result);
                }else{
                    $generator->next();
                }

                if ($getReturnValue) {
                    $result = $generator->getReturn();
                    $isSend = ($result !== null);
                }elseif(null === $result = $generator->current()){
                    $isSend = false;
                }
            }

            return $result;
        }catch(Exception $e){
            $exceptionHandle = function($e){
                throw $e;
            };

            if(isset($stack)){
                //将异常交给中间件进行处理
                $exceptionHandle = $this->createExceptionHandle($stack,$exceptionHandle);
            }

            $exceptionHandle($e);
        }
    }

    /**
     * 以递归的方式形成负责处理异常的责任链
     *
     * @param $stack
     * @param $lastHandle
     * @return Closure
     */
    protected function createExceptionHandle($stack,$lastHandle)
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
                }catch(Exception $e) {
                    //将异常交给外层中间件
                    $stack($e);
                }
            };
        },$lastHandle);
    }
}