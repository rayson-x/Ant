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



$request = new Ant\Http\Request(new \Ant\Collection($_SERVER));
//$data = $request->getParsedBody();
//var_dump($data);
//echo $request->getRequestTarget();
//show($_POST);
//$Stream = new Ant\Http\Stream(fopen($_FILES[12412412]['tmp_name'],'r'));

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
//    echo "this is abc start \n";
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
//    return 1;
//};
//
//$middleware = new Ant\Middleware;
//$middleware->middleware([$abc,$qwe,$one]);


//try{
//    $pdo = new Ant\Database\Connector\Mysql($config);
//    $stat = $pdo->table('demo')
//        ->whereNotIn('name',['aulun','alex','ajax'])
////        ->where(['name'=>'alex'])
//        ->get();
//    print_r($stat);
//
//}catch(Exception $e){
//    foreach(explode("\n", $e->getTraceAsString()) as $index => $line ){
//        echo "{$line} <br>";
//    }
//}catch(Error $e){
//    echo " Error : {$e->getMessage()}";
//}catch(Throwable $e){
//    echo " Exception : {$e->getMessage()}";
//}
