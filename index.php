<?php
define('EXT','.php');
include 'vendor\autoload.php';
//\Ant\Autoloader::register();
//\Ant\Autoloader::addNamespace('Ant\\Database','System'.DIRECTORY_SEPARATOR.'Database');

function show($msg){
    echo "<pre>";
    var_dump($msg);
    echo "</pre>";
}
function debug(){
    echo "<pre>";
    var_dump(func_get_args());
    echo "</pre>";
    die;
}

$config = [
    'dsn'=>'mysql:dbname=test;host=127.0.0.1',
    'user'=>'root',
    'password'=>'123456',
];

try{

}catch(Exception $e){
    echo $e->getMessage();
    foreach(explode("\n", $e->getTraceAsString()) as $index => $line ){
        echo "{$line} <br>";
    }
}catch(Error $e){
    echo " Error : {$e->getMessage()}";
}catch(Throwable $e){
    echo " Exception : {$e->getMessage()}";
}

function exceptionHandle(Throwable $exception){
    if($exception->getPrevious()){
        return exceptionHandle($exception->getPrevious());
    }

    $headers = [];
    $headers['Exception'] = sprintf('%s(%d) %s',get_class($exception),$exception->getCode(),$exception->getMessage());

    foreach(explode("\n",$exception->getTraceAsString()) as $index => $line){
        $key           = sprintf('X-Exception-Trace-%02d', $index);
        $headers[$key] = $line;
    }

    return $headers;
}
function test($func){
    for($i=0;$i<100000;$i++){
        call_user_func($func);
    }
}

$c = Ant\Container::getInstance();
$c->bind(B::class,function(){
    return new B;
});
$c->bind(C::class,function(){
   return new C;
});
$c->bind(D::class,function(){
   return new D;
});
$c->bind(E::class,function(){
    return new E;
});
$c->bind(F::class,function(){
    return new F;
});

//$c->tag([B::class,C::class,D::class,E::class,F::class],'test');
//
//$c->bind(A::class,function($c){
//    list($b,$c,$d,$e,$f) = $c->tagged('test');
//    return new A($b,$c,$d,$e,$f);
//});

//$c->bind(A::class,function($c){
//    return new A($c->make(B::class),$c->make(C::class),$c->make(D::class),$c->make(E::class),$c->make(F::class));
//});

//$c->bind(A::class,function($c){
//    return new A(new B,new C,new D,new E,new F);
//});

test(function()use($c){
    $c->make(A::class);
});
class A{
    public function __construct(B $b,C $c,D $d,E $e,F $f)
    {
//        echo 'this is class A';
    }
}
class B{
}
class C{
}
class D{
}
class E{
}
class F{}