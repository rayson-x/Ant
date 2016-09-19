<?php
//class test{
//    public $string = '123123';
//
//    public $func;
//
//    public function __construct($string = ''){
//        $this->string = $string;
//    }
//
//    public function set($func){
//        $this->func = $func;
//    }
//
//    public function get(){
//        $args = [];
//        array_unshift($args,$this);
//        var_dump(call_user_func_array($this->func,$args));
//    }
//}
//$test = new test('123123');
//$test->set(function($test){
//   return $test->string;
//});
//
//for($i=0;$i<100000;$i++){
//    $test->get();
//}

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

