<?php

class test{
    public $string = '';

    public function __construct($string){
        $this->string = $string;
    }

}


//$a = new test('123123');
//$b = new test('asdfsafa');
//$c = new test('qweq456');
//$d = new test('sadfdasfsdafasfasdfxx');
//
//$spl = new SplObjectStorage();
//$spl->attach($a);
//
//$demo = new SplObjectStorage;
//
//$demo[$a] = 1;
//$demo[$b] = 2;
//$demo[$c] = 3;
//$demo[$d] = 4;
//
//foreach($demo as $key => $value){
//    echo "{$key} : $value".PHP_EOL;
//}
//
//echo serialize($demo);
//

/*
 SplObjectStorage

 通过ArrayAccess接口以对象作为key与一个value绑定
 通过Iterator接口将已绑定的对象作为value输出，对应的key为数字
 */
