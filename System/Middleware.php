<?php
namespace Ant;

//use UnexpectedValueException;
use Ant\Container\Container;
use InvalidArgumentException;

trait Middleware{

    //TODO::中间容器,管理全局中间件

    protected $handlers;

    public function addMiddleware($name,callable $handler)
    {
        if(!is_string($name) || method_exists($name,'__toString')){
            throw new InvalidArgumentException('Middleware name must be a string');
        }

        $this->handlers[$name] = $handler;

        return $this;
    }

    public function getMiddleware(array $names)
    {
        return array_filter($this->handlers,function($name)use($names){
            if(in_array($name,$names)){
                return true;
            }

            return false;
        },ARRAY_FILTER_USE_KEY);
    }

    public function reset()
    {
        $this->handlers = [];
    }

    /**
     * 获取容器
     *
     * @return Container
     */
    public function getContainer()
    {
        if(! $this->container instanceof Container){
            $this->container = Container::getInstance();
        }

        return $this->container;
    }

    /**
     * 执行中间件
     *
     * @param array $arguments
     * @param array $handlers
     * @return null|void
     */
    public function execute($handlers = [] , $arguments = [])
    {
        $handlers = $handlers ?: $this->handlers;
        $arguments = $this->getContainer()->make('arguments',$arguments)->all();
        if (!$handlers) {
            return ;
        }

        //函数栈
        $stack = [];
        $result = null;
        foreach ($handlers as $handler) {
            // 每次循环之前重置，只能保存最后一个处理程序的返回值
            $result = null;
            $generator = call_user_func_array($handler, $arguments);

            if ($generator instanceof \Generator) {
                $stack[] = $generator;

                $yieldValue = $generator->current();

                if ($yieldValue === false) {
                    break;
                }
            } elseif ($generator !== null) {
                // 回调参数
                $result = $generator;
            }

            //获取
            $arguments = $this->getContainer()->make('arguments')->all();
        }

        $return = ($result !== null);

        $getReturnValueSwitch = version_compare(PHP_VERSION, '7.0.0', '>=');
        //重入函数栈
        while ($generator = array_pop($stack)) {
            if ($return) {
                $generator->send($result);
            }else{
                $generator->next();
            }

            if ($getReturnValueSwitch) {
                $result = $generator->getReturn();
                $return = ($result !== null);
            }else{
                $return = false;
            }
        }

        return $result;
    }
}