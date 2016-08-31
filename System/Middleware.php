<?php
namespace Ant;

use UnexpectedValueException;

class Middleware{

    protected $handlers;

    public function set($handler)
    {
        if(!is_callable($handler)){
            throw new UnexpectedValueException('Middleware must be a callable');
        }

        $this->handlers[] = $handler;

        return $this;
    }

    public function reset()
    {
        $this->handlers = [];
    }

    public function middleware($arguments = [],$handlers = [])
    {
        $handlers = $handlers ?: $this->handlers;

        if (!$handlers) {
            return;
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
                //重入协程参数
                $result = $generator;
            }
        }

        $return = ($result !== null);
        while ($generator = array_pop($stack)) {
            if ($return) {
                $generator->send($result);
                continue;
            }

            $generator->next();
        }
    }
}