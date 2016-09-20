<?php
namespace Ant\Container;

use Closure;
use ArrayAccess;
use ReflectionClass;
use ReflectionParameter;
use Ant\Traits\Singleton;
use Ant\Interfaces\ContainerInterface;
use Ant\Interfaces\ServiceProviderInterface;

/**
 * IoC容器,只要将服务注册到容器中,在有依赖关系时,容器便会会自动载入服务
 * 注意 : 当一个实例的构造函数需要大量参数时,推荐通过闭包函数生成实例,这样可以大幅度提升效率
 * 在实例需要6个参数时,手动生成比自动生成快了接近一倍
 *
 * Class Container
 * @package Ant
 */
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
     * @param string $serviceName
     * @return bool
     */
    public function bound($serviceName)
    {
        $serviceName = $this->normalize($serviceName);

        return isset($this->bindings[$serviceName]) || isset($this->instances[$serviceName]);
    }

    /**
     * 服务是否已被实例
     *
     * @param string $serviceName
     * @return bool
     */
    public function resolved($serviceName)
    {
        $serviceName = $this->normalize($serviceName);

        return isset($this->resolved[$serviceName]) || isset($this->instances[$serviceName]);
    }

    /**
     * 是否共享一个实例
     *
     * @param $serviceName
     * @return bool
     */
    protected function isShared($serviceName)
    {
        $serviceName = $this->normalize($serviceName);

        if (isset($this->instances[$serviceName])) {
            return true;
        }

        if (! isset($this->bindings[$serviceName]['shared'])) {
            return false;
        }

        return $this->bindings[$serviceName]['shared'] === true;
    }

    /**
     * 给服务定义一个别名
     *
     * @param string $serviceName
     * @param string $alias
     */
    public function alias($serviceName, $alias)
    {
        $this->aliases[$alias] = $this->normalize($serviceName);
    }

    /**
     * 服务是否有别名
     *
     * @param $name
     * @return bool
     */
    protected function isAlias($name)
    {
        return isset($this->aliases[$this->normalize($name)]);
    }

    /**
     * 通过数组设置服务别名
     *
     * @param array $array
     * @return mixed
     */
    protected function setAliasFromArray(array $array)
    {
        list($serviceName,$alias) = [key($array),current($array)];

        $this->alias($serviceName,$alias);

        return $serviceName;
    }

    /**
     * 通过服务别名获取服务原名
     *
     * @param $name
     * @return string
     */
    protected function getServiceNameFromAlias($name)
    {
        return isset($this->aliases[$name]) ? $this->aliases[$name] : $name;
    }

    /**
     * 将服务打上标签
     *
     * @param array|string $serviceGroup
     * @param array|string $tags
     */
    public function tag($serviceGroup, $tags)
    {
        $tags = is_array($tags) ? $tags :array_slice(func_get_args(),1);

        foreach($tags as $tag){
            if (! isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }

            foreach ((array) $serviceGroup as $serviceName) {
                $this->tags[$tag][] = $this->normalize($serviceName);
            }
        }
    }

    /**
     * 获取一个标签里的所有服务
     *
     * @param $tag
     * @return array
     */
    public function tagged($tag)
    {
        $results = [];

        if (isset($this->tags[$tag])) {
            foreach ($this->tags[$tag] as $serviceName) {
                $results[] = $this->make($serviceName);
            }
        }

        return $results;
    }


    /**
     * 绑定服务到容器
     *
     * @param array|string $serviceName
     * @param mixed $concrete
     * @param bool $shared
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
    public function bind($serviceName, $concrete = null, $shared = false)
    {
        $serviceName = $this->normalize($serviceName);
        $concrete = $this->normalize($concrete);

        if(is_array($serviceName)){
            $serviceName = $this->setAliasFromArray($serviceName);
        }

        //如果已经绑定,删除之前所有的服务实例
        $this->removeStaleInstances($serviceName);

        if($concrete instanceof Closure){
            $concrete = $concrete->bindTo($this);
        }elseif(is_null($concrete)){
            $concrete = $serviceName;
        }

        $this->bindings[$serviceName] = compact('concrete', 'shared');
    }

    /**
     * 绑定未绑定服务,如果已绑定就放弃
     *
     * @param string|array $serviceName
     * @param mixed $concrete
     * @param bool|false $shared
     */
    public function bindIf($serviceName, $concrete = null, $shared = false)
    {
        $key = is_array($serviceName) ? key($serviceName) : $serviceName;

        if(!$this->bound($key)){
            $this->bind($serviceName,$concrete,$shared);
        }
    }

    /**
     * 绑定一个单例
     *
     * @param string|array $serviceName
     * @param mixed $concrete
     */
    public function singleton($serviceName, $concrete = null)
    {
        $this->bind($serviceName,$concrete,true);
    }

    /**
     * 绑定一个实例
     *
     * @param string|array $serviceName
     * @param mixed $instance
     */
    public function instance($serviceName, $instance)
    {
        $serviceName = $this->normalize($serviceName);

        if(is_array($serviceName)){
            $serviceName = $this->setAliasFromArray($serviceName);
        }

        unset($this->aliases[$serviceName]);

        $this->instances[$serviceName] = $instance;
    }

    /**
     * 扩展容器中一个服务
     *
     * @param string|array $serviceName
     * @param Closure $closure
     */
    public function extend($serviceName, Closure $closure)
    {
        $serviceName = $this->normalize($serviceName);

        if(isset($this->instances[$serviceName])){
            $returnValue = call_user_func($closure,$this->instances[$serviceName],$this);
            if($returnValue !== null){
                $this->instances[$serviceName] = $returnValue;
            }
        }else{
            $this->extenders[$serviceName][] = $closure;
        }
    }

    /**
     * 获取服务扩展
     *
     * @param $serviceName
     * @return array
     */
    protected function getExtenders($serviceName)
    {
        if(isset($this->extenders[$serviceName])){
            return $this->extenders[$serviceName];
        }

        return [];
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
     * 获取当前服务所绑定的上下文
     *
     * @param $serviceName
     * @return mixed
     */
    protected function getContextualConcrete($serviceName)
    {
        if (isset($this->contextual[end($this->buildStack)][$serviceName])) {
            $concrete = $this->contextual[end($this->buildStack)][$serviceName];
            return ($concrete instanceof Closure)
                ? call_user_func($concrete, $this)
                : $concrete;
        }
    }

    /**
     * 获取具体实现方式
     *
     * @param $serviceName
     * @return mixed
     */
    protected function getConcrete($serviceName)
    {
        if (! isset($this->bindings[$serviceName])) {
            return $serviceName;
        }

        return $this->bindings[$serviceName]['concrete'];
    }

    /**
     * 通过服务名称获取服务
     *
     * @param string $serviceName
     * @param array $parameters
     * @return mixed
     */
    public function make($serviceName, array $parameters = [])
    {
        //获取服务名称
        $serviceName = $this->getServiceNameFromAlias($serviceName);

        if(isset($this->instances[$serviceName])){
            return $this->instances[$serviceName];
        }

        //获取服务实现方式
        $concrete = $this->getConcrete($serviceName);

        $serviceObject = $this->build($concrete,$parameters);

        //扩展服务
        foreach ($this->getExtenders($serviceName) as $extender) {
            $returnValue = $extender($serviceObject, $this);
            if(!is_null($returnValue)){
                $serviceObject = $extender($serviceObject, $this);
            }
        }

        //是否保存为全局唯一实例
        if ($this->isShared($serviceName)) {
            $this->instances[$serviceName] = $serviceObject;
        }

        $this->resolved[$serviceName] = true;

        return $serviceObject;
    }

    /**
     * 通过类名或者是闭包函数生成服务实例
     *
     * @param $concrete string|Closure  类名或者是返回一个对象的闭包函数
     * @param array $parameters         构造函数参数或者闭包函数参数
     * @return object                   服务实例
     */
    public function build($concrete, array $parameters = [])
    {
        if ($concrete instanceof Closure) {
            return call_user_func_array($concrete,$parameters);
        }
        //通过反射机制实现实例
        $reflection = new ReflectionClass($concrete);

        //检查是否可以实例化
        if(!$reflection->isInstantiable()){
            if (! empty($this->buildStack)) {
                $previous = implode(', ', $this->buildStack);

                $message = "Target [$concrete] is not instantiable while building [$previous].";
            } else {
                $message = "Target [$concrete] is not instantiable.";
            }

            throw new ContainerValueNotFoundException($message);
        }

        $construct = $reflection->getConstructor();
        if(is_null($construct)){
            //没有构造函数,直接返回实例
            return $reflection->newInstance();
        }

        //将生成中的实例入栈
        $this->buildStack[] = $concrete;

        //获取构造函数需要参数
        $dependencies = $construct->getParameters();
        $parameters = $this->keyParametersByArgument($dependencies,$parameters);
        $instanceArgs = $this->getDependencies($dependencies,$parameters);

        //完成,将生成中的实例出栈
        array_pop($this->buildStack);

        //返回实例
        return $reflection->newInstanceArgs($instanceArgs);
    }

    /**
     * 将方法依赖参数与用户传入参数进行匹配
     *
     * @param $dependencies
     * @param $parameters
     * @return mixed
     */
    protected function keyParametersByArgument($dependencies, $parameters)
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
     * @param $parameters
     * @param $primitives
     * @return mixed
     */
    protected function getDependencies($parameters,$primitives)
    {
        $dependencies = [];

        foreach($parameters as $parameter){
            if(array_key_exists($parameter->name,$primitives)){
                //使用用户给定的值
                $dependencies[] = $primitives[$parameter->name];
            }elseif(is_null($parameter->getClass())){
                //获取参数
                $dependencies[] = $this->resolveNotClass($parameter);
            }else{
                //获取依赖的服务实例
                $dependencies[] = $this->resolveClass($parameter);
            }
        }

        return $dependencies;
    }

    /**
     * 获取上下文绑定参数
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     */
    protected function resolveNotClass(ReflectionParameter $parameter)
    {
        if (! is_null($concrete = $this->getContextualConcrete('$'.$parameter->name))) {
            return $concrete;
        }

        if($parameter->isDefaultValueAvailable()){
            return $parameter->getDefaultValue();
        }

        throw new ContainerValueNotFoundException("Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}");
    }

    /**
     * 获取显性依赖实例
     *
     * @param ReflectionParameter $parameter
     * @return mixed|object
     * @throws \Exception
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try{
            $dependencyClass = $parameter->getClass()->getName();
            if(!is_null($object = $this->getContextualConcrete($dependencyClass))){
                //优先使用用户提供实例
                if($object instanceof $dependencyClass){
                    return $object;
                }
                //TODO::实例用户提供的类
            }

            return $this->make($dependencyClass);
        }catch(ContainerValueNotFoundException $e){
            if($parameter->isOptional()){
                return $parameter->getDefaultValue();
            }

            throw $e;
        }
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset,$value)
    {
        if(is_object($value) && !($value instanceof Closure)){
            $this->instance($offset,$value);
        }else{
            $this->bind($offset,$value);
        }
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->bound($offset);
    }

    /**
     * @param mixed $offset
     * @return object
     */
    public function offsetGet($offset)
    {
        return $this->make($offset);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->forgetService($offset);
    }

    /**
     * 规范服务名称
     *
     * @param  mixed  $serviceName
     * @return mixed
     */
    protected function normalize($serviceName)
    {
        return is_string($serviceName) ? ltrim($serviceName, '\\') : $serviceName;
    }

    /**
     * 清空实例
     *
     * @param $serviceName
     */
    protected function removeStaleInstances($serviceName)
    {
        //如果服务原名与服务别名重复,会出现无法加载服务的情况
        unset($this->instances[$serviceName],$this->aliases[$serviceName]);
    }

    /**
     * 从容器中移除一个服务
     *
     * @param $name
     */
    public function forgetService($name){
        $name = $this->normalize($name);
        //获取服务原名
        $serviceName = $this->getServiceNameFromAlias($name);

        unset($this->bindings[$serviceName], $this->instances[$serviceName], $this->resolved[$serviceName]);
    }

    /**
     * 服务提供者
     *
     * @param ServiceProviderInterface $serviceProvider
     */
    public function registerService(ServiceProviderInterface $serviceProvider)
    {
        $serviceProvider->register($this);
    }

    /**
     * 重置容器
     */
    public function reset(){
        $this->aliases = [];
        $this->resolved = [];
        $this->bindings = [];
        $this->instances = [];
        $this->extenders = [];
        $this->bindings = [];
        $this->tags = [];
    }
}