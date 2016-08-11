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

//php://temp 是一个类似文件包装器的数据流，允许读写临时数据
//$stream = fopen('php://temp', 'w+');
//stream_copy_to_stream(fopen('php://input', 'r'), $stream);
//rewind($stream);
$stream = fopen('php://input','r');
var_dump(stream_get_meta_data($stream));
fclose($stream);
//$streams = new Ant\Http\Stream($stream);
//echo $streams->read(26);
//echo $streams->tell();
//$streams->seek(26);
//$streams->eof();
//var_dump($streams->eof());
//echo $stream->getContents();


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
