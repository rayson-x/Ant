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

//echo Ant\Http\Uri::createFromCollection(new Ant\Collection($_SERVER));




//try{
//
////    $files = Ant\Http\UploadedFile::parseUploadedFiles($_FILES);
//
////    echo $files['123'][1]->getSize();
//
////    echo $files['asd']->getSize();
//}catch(Exception $e){
//    echo $e->getMessage()."<br>";
//    foreach(explode("\n", $e->getTraceAsString()) as $index => $line ){
//        echo "{$line} <br>";
//    }
//}catch(Error $e){
//    echo " Error : {$e->getMessage()}";
//}catch(Throwable $e){
//    echo " Exception : {$e->getMessage()}";
//}



//$abc = function(){
//    if(empty($_SESSION['isLogin'])){
//        throw new Exception('not login');
//    }
//    yield;
//    echo "this is abc end \n";
//};
//
//$qwe = function (){
//    echo "this is qwe start \n";
//    $a = yield;
//    echo $a."\n";
//    echo "this is qwe end \n";
//};
//$one = function (){
//    throw new Exception(123);
//};
//
//try{
//    $middleware = new Ant\Middleware;
//    $middleware->middleware([$abc,$qwe,$one]);
//}catch(Exception $e){
//    $exception = exceptionHandle($e);
//    foreach($exception as $key => $value){
//        echo $value.PHP_EOL;
//    }
//}catch(Error $e){
//    $exception = exceptionHandle($e);
//    foreach($exception as $key => $value){
//        echo $value.PHP_EOL;
//    }
//}catch(Throwable $e){
//    $exception = exceptionHandle($e);
//    foreach($exception as $key => $value){
//        echo $value.PHP_EOL;
//    }
//}
//
//function exceptionHandle(Throwable $exception){
//    if($exception->getPrevious()){
//        return exceptionHandle($exception->getPrevious());
//    }
//
//    $headers = [];
//    $headers['Exception'] = sprintf('%s(%d) %s',get_class($exception),$exception->getCode(),$exception->getMessage());
//
//    foreach(explode("\n",$exception->getTraceAsString()) as $index => $line){
//        $key           = sprintf('X-Exception-Trace-%02d', $index);
//        $headers[$key] = $line;
//    }
//
//    return $headers;
//}

try{
    $pdo = new Ant\Database\Connector\Mysql($config);

    $table = $pdo->getTables();
//    $name = 'power';
//    $stat = $pdo->select('demo')
//        ->whereSub('id','IN',function(){
//            $this->table = 'users';
//            $this->columns('id')->where(['name'=>'power']);
//        })
//        ->orWhere(['score'=>'>='],[85])
//        ->get();

    show($table);
}catch(Exception $e){
    foreach(explode("\n", $e->getTraceAsString()) as $index => $line ){
        echo "{$line} <br>";
    }
}catch(Error $e){
    echo " Error : {$e->getMessage()}";
}catch(Throwable $e){
    echo " Exception : {$e->getMessage()}";
}


