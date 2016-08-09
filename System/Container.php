<?php
namespace Ant;

class Container{

    protected $callback = [
        //id  =>  callback
    ];

    /**
     * 容器注入口,在此注入脚本
     * @param $id
     * @param \Closure $callback
     */
    public function set($id,\Closure $callback){
        $this->callback[$id] = $callback->bindTo($this,$this);
    }

    /**
     * 返回被依赖实例
     * @param $id
     * @return mixed
     */
    public function get($id){
        $callback = $this->getCallback($id);
        $args = array_slice(func_get_args(),1);

        return call_user_func_array($callback,$args);
    }

    /**
     * 获取回调函数
     * @param $id
     */
    public function getCallback($id){
        if ($this->has($id)) {
            return $this->callback[$id];
        }

        throw new \UnexpectedValueException;
    }

    /**
     * 检查回调是否存在
     * @param $id
     * @return bool
     */
    public function has($id){
        return isset($this->callback[$id]);
    }
}
