<?php
//$app = require "bootstrap.php";
//
//$app->run();

require "vendor/autoload.php";
$buffer = file_get_contents('http://www.baidu.com');

$response = \Ant\Http\Response::createFromRequestResult(get_headers('http://www.baidu.com'));

echo $response;