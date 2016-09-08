<?php
class test{
    public $string;

    public $instances;

    public function __construct($string = ''){
        $this->string = $string;
    }

    public function extend($server, Closure $closure)
    {
        if(isset($this->instances[$server])){
            $returnValue = call_user_func($closure,$this->instances[$server],$this);
            if($returnValue !== null){
                $this->instances[$server] = $returnValue;
            }
        }
    }
}
$obj = new class{
    private $demo = 123;

    public function get(){
        return $this->demo;
    }

    public function update(){
        $this->demo = 456;
    }
};
$test = new test();
$test->instances['demo'] = $obj;
var_dump($test->instances['demo']->get());

$test->extend('demo',function($obj){
    $obj->update();

    return new class{
        public function get(){
            return 'this is new obj';
        }
    };
});

var_dump($test->instances['demo']->get());


//$array = [
//    'mu'=>[
//        0,0,0,1
//    ],
//    'you'=>[
//        0,0,0,1
//    ],
//    'wa'=>[
//        0,0,0,1
//    ],
//];
//
//$array = array_map(function($array){
//    return array(end($array));
//},$array);
//
//var_dump($array);
/*
 SplObjectStorage

 通过ArrayAccess接口以对象作为key与一个value绑定
 通过Iterator接口将已绑定的对象作为value输出，对应的key为数字
 */
