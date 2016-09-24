<?php
namespace Ant\Middleware;

use Generator;

trait Middleware{
    /**
     * 默认加载的中间件
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * 执行时传递给每个中间件的参数
     *
     * @var array|callable
     */
    protected $arguments;

    public function addMiddleware(callable $handler)
    {
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * 设置中间件传输参数
     *
     * @param $arguments
     */
    public function withArguments($arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * 获取传递给每个中间件的参数
     *
     * @param array $arguments
     * @return array
     */
    public function getArguments($arguments = [])
    {
        if(is_callable($this->arguments)){
            return call_user_func($this->arguments,$arguments);
        }
        if(is_array($this->arguments)){
            return $this->arguments;
        }

        return array_merge((array)$this->arguments ,$arguments);
    }

    /**
     * 执行中间件
     *
     * @param array $handlers
     * @param array $arguments
     * @return null|void
     */
    public function execute($handlers = [] , $arguments = [])
    {
        $handlers = $handlers ?: $this->handlers;
        if (!$handlers) {
            return ;
        }

        //函数栈
        $stack = [];
        $result = null;
        foreach ($handlers as $handler) {
            //获取中间件参数
            $args = $this->getArguments($arguments);
            // 每次循环之前重置，只能保存最后一个处理程序的返回值
            $result = null;
            $generator = call_user_func_array($handler, $args);

            if ($generator instanceof Generator) {
                $stack[] = $generator;

                $yieldValue = $generator->current();

                if ($yieldValue === false) {
                    break;
                }
            } elseif ($generator !== null) {
                // 回调参数
                $result = $generator;
            }
        }

        $return = ($result !== null);

        $getReturnValue = version_compare(PHP_VERSION, '7.0.0', '>=');
        //重入函数栈
        while ($generator = array_pop($stack)) {
            /* @var $generator Generator */
            if ($return) {
                $generator->send($result);
            }else{
                $generator->next();
            }

            if ($getReturnValue) {
                $result = $generator->getReturn();
                $return = ($result !== null);
            }else{
                $return = false;
            }
        }

        return $result;
    }

    /**
     * 重置中间件
     */
    public function reset()
    {
        $this->handlers = [];
    }
}