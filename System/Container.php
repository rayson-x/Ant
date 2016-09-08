<?php
namespace Ant;

use Closure;
use ArrayAccess;
use ReflectionClass;
use Ant\Traits\Singleton;

/**
 * IoC容器,只要将服务注册到容器中,在有依赖关系时,容器便会会自动载入服务
 * 注意 : 在追求性能的时候,推荐注册服务时候绑定闭包函数,不然容器会通过反射API去加载服务所需要的类,这样会造成不必要的损耗
 *
 * Class Container
 * @package Ant
 */
class Container implements ArrayAccess{
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
     * 正在生成的服务栈
     *
     * @var array
     */
    protected $buildStack = [];

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

    /**
     * 服务是否与容器绑定
     *
     * @param string $server
     * @return bool
     */
    public function bound($server)
    {
        $server = $this->normalize($server);

        return isset($this->bindings[$server]) || isset($this->instances[$server]);
    }

    /**
     * 服务是否已被实例
     *
     * @param string $server
     * @return bool
     */
    public function resolved($server)
    {
        $server = $this->normalize($server);

        return isset($this->resolved[$server]) || isset($this->instances[$server]);
    }

    /**
     * 给服务定义一个别名
     *
     * @param string $server
     * @param string $alias
     */
    public function alias($server, $alias)
    {
        $this->aliases[$alias] = $this->normalize($server);
    }

    /**
     * 服务是否有别名
     *
     * @param $name
     * @return bool
     */
    public function isAlias($name)
    {
        return isset($this->aliases[$this->normalize($name)]);
    }

    /**
     * 通过数组设置服务别名
     *
     * @param array $array
     */
    public function setAliasFromArray(array $array)
    {
        list($server,$alias) = [key($array),current($array)];

        $this->alias($server,$alias);

        return $alias;
    }
    /**
     * 通过服务别名获取服务名称
     *
     * @param $name
     * @return string
     */
    public function getAlias($name)
    {
        return isset($this->aliases[$name]) ? $this->aliases[$name] : $name;
    }

    /**
     * 将服务打上标签
     *
     * @param array|string $servers
     * @param array|mixed $tags
     */
    public function tag($servers, $tags)
    {
        $tags = is_array($tags) ? $tags :array_slice(func_get_args(),1);

        foreach($tags as $tag){
            if (! isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }

            foreach ((array) $servers as $server) {
                $this->tags[$tag][] = $this->normalize($server);
            }
        }
    }

    public function tagged($tag)
    {

    }

    /**
     * @param string $server        服务名称
     * @param null $concrete        服务具体实例过程
     * @param bool|false $shared    是否共享
     *
     * @example
     * 手动实例 :
     * $container->bind('foo',function($c){
     *     return new foo($c->make('bar'));
     * })
     * 自动实例 :
     * $container->bind('foo');
     * $container->bind('foo','bar');
     */
    public function bind($server, $concrete = null, $shared = false)
    {
        $server = $this->normalize($server);
        $concrete = $this->normalize($concrete);

        if(is_array($server)){
            $server = $this->setAliasFromArray($server);
        }

        //如果已经绑定,删除之前所有的服务实例
        $this->removeStaleInstances($server);

        if(is_null($concrete)){
            $concrete = $server;
        }

        $this->bindings[$server] = compact('concrete', 'shared');
    }

    /**
     * 绑定未绑定服务,如果已绑定就放弃
     *
     * @param $server
     * @param null $concrete
     * @param bool|false $shared
     */
    public function bindIf($server, $concrete = null, $shared = false)
    {
        if(!$this->bound($server)){
            $this->bind($server,$concrete,$shared);
        }
    }

    /**
     * 绑定一个单例
     *
     * @param $server
     * @param null $concrete
     */
    public function singleton($server, $concrete = null)
    {
        $this->bind($server,$concrete,true);
    }

    /**
     * 绑定一个实例
     *
     * @param $server
     * @param $instance
     */
    public function instance($server, $instance)
    {
        $server = $this->normalize($server);

        if(is_array($server)){
            $server = $this->setAliasFromArray($server);
        }

        //如果将服务实例绑定到容器中
        //那么只能通过 instances 获取服务
        unset($this->aliases[$server]);

        $this->instances[$server] = $instance;
    }

    /**
     * 扩展容器中一个服务
     *
     * @param $server
     * @param Closure $closure
     */
    public function extend($server, Closure $closure)
    {
        $server = $this->normalize($server);

        if(isset($this->instances[$server])){
            $returnValue = call_user_func($closure,$this->instances[$server],$this);
            if($returnValue !== null){
                $this->instances[$server] = $returnValue;
            }
        }else{
            $this->extenders[$server][] = $closure;
        }
    }

    /**
     * 绑定上下文参数到具体服务上,绑定之后每次实例都会加载绑定参数
     *
     * @param $concrete
     * @return ContextualBindingBuilder
     *
     * @example
     * //类Foo构造函数需要$array参数,传入[1,2,3]
     * $c->when(Foo::class)->needs('$array')->give([1,2,3]);
     *
     * //类Foo构造函数需要Bar::class实例,传入实例Bar::class
     * $c->when(Foo::class)->needs(Bar::class)->give(Bar::class);
     */
    public function when($concrete)
    {
        $concrete = $this->normalize($concrete);

        return new ContextualBindingBuilder($this,$concrete);
    }

    /**
     * @param $concrete string
     * @param $need string
     * @param $implementation string|Closure
     *
     * @example
     * addContextualBinding(具体服务,需要的参数,值)
     * addContextualBinding(Foo::class,'$array',[1,2,3])
     * addContextualBinding(Foo::class,Bar::class,Bar::class)
     */
    public function addContextualBinding($concrete,$need,$implementation)
    {
        $this->contextual[$this->normalize($concrete)][$this->normalize($need)] = $implementation;
    }

    /**
     * 获取生成中的服务所绑定的上下文
     *
     * @param $server
     * @return mixed
     */
    public function getContextualConcrete($server)
    {
        if (isset($this->contextual[end($this->buildStack)][$server])) {
            return $this->contextual[end($this->buildStack)][$server];
        }
    }

    public function getConcrete($server)
    {
        if (! isset($this->bindings[$server])) {
            return $server;
        }

        return $this->bindings[$server]['concrete'];
    }

    public function make($server, array $parameters = [])
    {
        $server = $this->getAlias($server);

        if(isset($this->instances[$server])){
            return $this->instances[$server];
        }


    }

    /**
     * 生成服务实例
     *
     * @param $concrete string|Closure
     * @param array $parameters
     * @return object
     */
    public function build($concrete, array $parameters = [])
    {
        if ($concrete instanceof Closure) {
            array_unshift($parameters,$this);
            return call_user_func_array($concrete,$parameters);
        }

        $reflection = new ReflectionClass($concrete);

        //检查是否可以实例化
        if(!$reflection->isInstantiable()){
            //TODO::抛出异常
        }

        //将正在实例的服务分为一组
        $this->buildStack[] = $concrete;

        $construct = $reflection->getConstructor();
        if(is_null($construct)){
            array_pop($this->buildStack);

            return $reflection->newInstance();
        }

        //获取实例需要参数
        $dependencies = $construct->getParameters();

        $parameters = $this->keyParametersByArgument($dependencies,$parameters);

        $instanceArgs = $this->getDependencies($dependencies,$parameters);

        array_pop($this->buildStack);

        return $reflection->newInstanceArgs($instanceArgs);
    }

    /**
     * 将方法依赖参数与用户传入参数进行匹配
     *
     * @param $dependencies
     * @param $parameters
     * @return mixed
     */
    public function keyParametersByArgument($dependencies, $parameters)
    {
        foreach($parameters as $key => $value){
            //通过数组索引进行匹配
            if(is_numeric($key)){
                unset($parameters[$key]);

                $parameters[$dependencies[$key]->name] = $value;
            }
        }

        return $parameters;
    }

    /**
     * 获取实例依赖参数
     *
     * @param $dependencies
     * @param $parameters
     * @return mixed
     */
    public function getDependencies($parameters,$primitives)
    {
        $dependencies = [];

        foreach($parameters as $parameter){
            if(array_key_exists($parameters->name,$primitives)){
                //使用用户给定的值
                $dependencies[] = $primitives[$parameter->name];
            }elseif(is_null($parameter->getClass())){
                //获取上下文参数
                //TODO::获取绑定参数
            }else{
                //获取依赖的服务实例
                //TODO::获取实例
            }
        }

        return $dependencies;
    }

    public function call($callback, array $parameters = [], $defaultMethod = null)
    {

    }

    public function offsetSet($offset,$value)
    {

    }

    public function offsetExists($offset)
    {

    }

    public function offsetGet($offset)
    {

    }

    public function offsetUnset($offset)
    {

    }

    /**
     * 规范服务名称
     *
     * @param  mixed  $service
     * @return mixed
     */
    protected function normalize($service)
    {
        return is_string($service) ? ltrim($service, '\\') : $service;
    }

    protected function removeStaleInstances($server)
    {
        unset($this->instances[$server], $this->aliases[$server]);
    }
}
