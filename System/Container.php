<?php
namespace Ant;

use Closure;
use ArrayAccess;
use Ant\Traits\Singleton;
use Ant\Interfaces\ContainerInterface;

class Container implements ContainerInterface,ArrayAccess{
    
    use Singleton;
    /**
     * 已经实例化的服务.
     *
     * @var array
     */
    protected $resolved = [];

    /**
     * 已经绑定的服务.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * 服务实例.
     *
     * @var array
     */
    protected $instances = [];

    /**
     * 服务别名.
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * 服务扩展.
     *
     * @var array
     */
    protected $extenders = [];

    /**
     * 标签.
     *
     * @var array
     */
    protected $tags = [];

    /**
     * 实例服务时绑定的上下文.
     *
     * @var array
     */
    public $contextual = [];

    public function bound($abstract){

    }

    public function alias($abstract, $alias){

    }

    public function tag($abstracts, $tags){

    }

    public function tagged($tag){

    }

    public function bind($abstract, $concrete = null, $shared = false){

    }

    public function bindIf($abstract, $concrete = null, $shared = false){

    }

    public function singleton($abstract, $concrete = null){

    }

    public function extend($abstract, Closure $closure){

    }

    public function instance($abstract, $instance){

    }

    public function when($concrete){

    }

    public function make($abstract, array $parameters = []){

    }

    public function call($callback, array $parameters = [], $defaultMethod = null){

    }

    public function resolved($abstract){

    }

    public function offsetSet($offset,$value){

    }

    public function offsetExists($offset){

    }

    public function offsetGet($offset){

    }

    public function offsetUnset($offset){

    }
}
