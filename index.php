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

Ant\Http\Uri::createFromCollection(new Ant\Collection($_SERVER));


//if($_SERVER['REQUEST_METHOD'] == 'POST'){
//    try{
//        $stream = fopen('php://input','r');
//        $stream = new Ant\Http\Stream($stream);
//        echo $hello = $stream->read(5)."\n";
//        $stream->seek(1,SEEK_CUR);
//        $stream->write($hello);
//        $stream->rewind();
//        echo $stream->getContents();
//        var_dump($stream->eof());
//    }catch(\InvalidArgumentException $e){
//        echo $e->getMessage();
//    }catch (Exception $e){
//        echo $e->getMessage();
//    }
//}else{
//    httpRequest();
//}
//
//function httpRequest(){
//    $opts = array(
//        'http'=>array(
//            'method' => "POST",
//            'header' => "Cache-Control: no-cache\r\n".
//                "Content-type: application/x-www-form-urlencoded\r\n" ,
//            'content'=> 'hello world',
//        )
//    );
//
//    $context = stream_context_create($opts);
//
//    $fp = fopen('http://www.bfb100qj.com/app/index.php?i=8&c=entry&id=7&do=detail&m=hx_zhongchou','r',false,$context);
//    fpassthru($fp);
//    fclose($fp);
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
//$middleware->middleware([$abc,$qwe,$aas]);


//try{
//    $pdo = new Ant\Database\Connector\Mysql($config);
//    $stat = $pdo->table('demo')
////        ->whereNotIn('name',['aulun','alex','ajax'])
//        ->where(['name'=>'alex'])
//        ->get();
//    print_r($stat);
//}catch(Exception $e){
//    foreach(explode("\n", $e->getTraceAsString()) as $index => $line ){
//        echo "{$line} <br>";
//    }
//}catch(Error $e){
//    echo " Error : {$e->getMessage()}";
//}catch(Throwable $e){
//    echo " Exception : {$e->getMessage()}";
//}
