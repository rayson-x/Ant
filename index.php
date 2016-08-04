<?php
define('EXT','.php');
include 'System\Autoloader.php';

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
\Ant\Autoloader::register();
\Ant\Autoloader::addNamespace('Ant\\Database','System'.DIRECTORY_SEPARATOR.'Database');

$config = [
    'dsn'=>'mysql:dbname=test;host=127.0.0.1',
    'user'=>'root',
    'password'=>'123456',
];


class IoC{
    public $callback = [];

    public function set($id,\Closure $func){
        //绑定给匿名函数的一个对象
        $this->callback[$id] = $func->bindTo($this);
    }

    public function get($id){
        return call_user_func($this->callback[$id]);
    }

}



//
//$pdo = new Ant\Database\Connector\Mysql($config);
//
//$stat = $pdo->table('info')
//    ->whereNotIn('name',['aulun','alex','ajax'])
//    ->get();
////    ->select(['a.name','a.sex','a.age'])
////    ->where(['name'=>'in','age'=>'<>'],[['aulun','alex','ajax'],18])
////    ->where('age = ?','18')
////    ->orWhere(['sex'=>'in'],[['woman','man']])
////    ->where(['name'=>'ajax','age'=>'18'])
////    ->order('age','DESC')
////    ->alias('a')
////    ->get();
//
//show($stat->getAll());
//
