<?php

class TestContainer extends PHPUnit_Framework_TestCase{

    public function testSingleton()
    {
        $container = Ant\Container::getInstance();
        $this->assertInstanceOf(Ant\Container::class,$container);

        return $container;
    }

    /**
     * 通过闭包的方式获取实例
     *
     * @depends testSingleton
     * @param $c \Ant\Container
     */
    public function testGetsInstanceFromClosure($c)
    {
        $c->bind('environment',function(){
            return Ant\Http\Environment::mock();
        });
        $c->bind([Ant\Http\Request::class => 'request'],function($c){
            return new Ant\Http\Request($c->make('environment'));
        });
        //是否绑定
        $this->assertTrue($c->bound(Ant\Http\Request::class));

        $request = $c->make('request');
        $this->assertInstanceOf(Ant\Http\Request::class,$request);

        //是否实例
        $this->assertTrue($c->resolved(Ant\Http\Request::class));
        //重置容器
        $c->reset();
    }

    /**
     * 通过绑定实例名称的方式获取实例
     *
     * @depends testSingleton
     * @param $c \Ant\Container
     */
    public function testGetsInstanceFromServiceName($c)
    {
        $c->bind('collection',Ant\Collection::class);
        $collection = $c->make('collection');
        $this->assertInstanceOf(Ant\Collection::class,$collection);
        $c->reset();
    }

    /**
     * 实例未绑定至容器的服务
     *
     * @depends testSingleton
     * @param $c \Ant\Container
     */
    public function testInstanceNotBindingService($c)
    {
        $collection = $c->make(Ant\Collection::class,[['name'=>'ben','age'=>18]]);
        $this->assertInstanceOf(Ant\Collection::class,$collection);
        $this->assertEquals('ben',$collection['name']);
        $this->assertEquals(18,$collection['age']);
        $c->reset();
    }

    /**
     * 测试异常
     *
     * @expectedException ReflectionException
     * @expectedException RuntimeException
     * @depends testSingleton
     * @param $c \Ant\Container
     */
    public function testNotInstantiable($c)
    {
        //当类不存在时出现的抛出的异常
        $c->make('aaa');

        //当类无法进行实例时抛出的异常
        $c->make(Ant\Containter::class);
    }

    /**
     * 用数组的方式来操作容器
     *
     * @depends testSingleton
     * @param $c \Ant\Container
     */
    public function testContainerAccessArray($c)
    {
        $c['environment'] = function(){
            return Ant\Http\Environment::mock();
        };
        $c['request'] = function($c){
            return new Ant\Http\Request($c->make('environment'));
        };
        $request = $c['request'];
        $this->assertInstanceOf(Ant\Http\Request::class,$request);
        $c->reset();

        //-------------------测试分割线-------------------//

        $c['collection'] = Ant\Collection::class;
        $collection = $c['collection'];
        $this->assertInstanceOf(Ant\Collection::class,$collection);
        $c->reset();

        //-------------------测试分割线-------------------//

        $collection = $c[Ant\Collection::class];
        $this->assertInstanceOf(Ant\Collection::class,$collection);
        $c->reset();
    }

    /**
     * 别名与服务名冲突
     *
     * @depends testSingleton
     * @param $c \Ant\Container
     */
    public function testAliasAndServiceNameConflict($c)
    {
        $c->bind(Ant\Middleware::class,function(){
            return new Ant\Middleware();
        });
        //绑定别名
        $c->alias(Ant\Middleware::class,'request');
        $this->assertInstanceOf(Ant\Middleware::class,$c->make('request'));

        //当别名与服务名冲突
        $c->bind('request',function(){
            return new Ant\Http\Request(Ant\Http\Environment::mock());
        });
        //别名将会被销毁
        $this->assertNotInstanceOf(Ant\Middleware::class,$c->make('request'));
        $this->assertInstanceOf(Ant\Http\Request::class,$c->make('request'));
        $c->reset();
    }

    /**
     * 创建实例时从外部传入参数
     *
     * @depends testSingleton
     * @param $c \Ant\Container
     */
    public function testAfferentParameters($c)
    {
        $array = ['name'=>'ben','age'=>18];
        $c->bind(Ant\Collection::class,function($c,$array){
            return new Ant\Collection($array);
        });
        $collection = $c->make(Ant\Collection::class,[$array]);
        $this->assertEquals('ben',$collection['name']);
        $this->assertEquals(18,$collection['age']);
        unset($collection);
        $c->reset();

        //-------------------测试分割线-------------------//

        $c->bind(Ant\Collection::class);
        $collection = $c->make(Ant\Collection::class,['items' => $array]);
        $this->assertEquals('ben',$collection['name']);
        $this->assertEquals(18,$collection['age']);
        $c->reset();
    }

    /**
     * 绑定上下文
     *
     * @depends testSingleton
     * @param $c \Ant\Container
     */
    public function testContextualBindingBuilder($c)
    {
        //每次获取Ant\Http\Environment
        //都会向构造函数的$items参数传入数组
        $c->when(Ant\Http\Environment::class)->needs('$items')->give([
            'REQUEST_METHOD'       => 'POST',
            'REQUEST_URI'          => '/Test/TestContainer.php?test=abc',
        ]);

        //每次通过容器实例Ant\Http\Request，都会传入闭包返回的实例
        $c->when(Ant\Http\Request::class)->needs(Ant\Http\Environment::class)->give(function($c){
            return $c->make(Ant\Http\Environment::class);
        });

        $request = $c->make(Ant\Http\Request::class);

        $this->assertEquals('abc',$request->get('test'));
        $this->assertEquals('/Test/TestContainer.php',$request->getUri()->getPath());
    }

    /**
     * 绑定一个全局唯一的实例(单例)
     *
     * @depends testSingleton
     * @param $c \Ant\Container
     */
    public function testBindGlobalUniqueInstance($c)
    {
        $array = ['name'=>'ben','age'=>18];
        $c->bind(Ant\Collection::class,function($c,$array){
            return new Ant\Collection($array);
        });
        $firstCollection = $c->make(Ant\Collection::class,[$array]);
        //改变实例
        $firstCollection['sex'] = 'male';
        $secondCollection = $c->make(Ant\Collection::class,[$array]);
        //容器中实例并未被改变
        $this->assertNull($secondCollection['sex']);
        $this->assertNotEquals($firstCollection,$secondCollection);
        unset($firstCollection,$secondCollection);

        //-------------------测试分割线-------------------//

        //绑定一个全局唯一实例
        $c->singleton(Ant\Collection::class,function($c,$array){
            return new Ant\Collection($array);
        });
        $firstCollection = $c->make(Ant\Collection::class,[$array]);
        //改变实例
        $firstCollection['sex'] = 'male';
        $secondCollection = $c->make(Ant\Collection::class,[$array]);
        //第一次返回与第二次返回都为同一实例
        $this->assertEquals('male',$secondCollection['sex']);
        $this->assertEquals($firstCollection,$secondCollection);

        $c->reset();
    }

    /**
     * 获取被依赖接口的默认实例
     *
     * @depends testSingleton
     * @param $c \Ant\Container
     */
    public function testGetsTheDefaultInstanceOfTheDependentInterface($c)
    {
    }

    /**
     * 扩展服务,在下次获取服务时会把服务实例传给闭包
     *
     * @depends testSingleton
     * @param $c \Ant\Container
     */
    public function testExtendService($c)
    {
        $c->extend(Ant\Collection::class,function($collection){
            $collection['name'] = 'ben';
            $collection['age'] = 18;
            $collection['sex'] = 'male';
        });
        $collection = $c->make(Ant\Collection::class);

        $this->assertEquals('ben',$collection['name']);
        $this->assertEquals(18,$collection['age']);
        $this->assertEquals('male',$collection['sex']);
    }
}