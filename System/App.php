<?php
namespace Ant;

class App{
    /**
     * 异常处理函数
     * @var callable
     */
    protected $exceptionHandler;


    public function setExceptionHandler(callable $handler)
    {
        $this->exceptionHandler = $handler;

        return $this;
    }

    public function getExceptionHandler(){
        if(is_callable($this->exceptionHandler)){
            return $this->exceptionHandler;
        }

        return function($exception){

        };
    }
}