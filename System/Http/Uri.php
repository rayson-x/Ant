<?php
namespace Ant\Http;

use \Psr\Http\Message\UriInterface;
use \Ant\Collection;

class Uri implements UriInterface{

    protected $standardPort = [
        'http'  =>  80,
        'https' =>  443,
    ];

    protected $scheme;
    protected $host;
    protected $port;
    protected $user;
    protected $password;
    protected $path;
    protected $query;
    protected $fragment;

    public static function createFromCollection(Collection $uri){
        $scheme = (empty($scheme) || $uri->get('HTTPS') === 'off') ? 'http' : 'https';
        $user = $uri->get('PHP_AUTH_USER','');
        $password = $uri->get('PHP_AUTH_PW','');

        //HTTP_HOST在http1.0下可以返回空
        if($httpHost = $uri->get('HTTP_HOST',false)){
            if(strpos($httpHost,':')){
                list($host,$port) = explode(':',$httpHost,2);
                $port = intval($port);
            }else{
                $host = $httpHost;
                $port = null;
            }
        }else{
            $host = $uri->get('SERVER_NAME')?:$uri->get('SERVER_ADDR');
            $port = $uri->get('SERVER_PORT');
        }

        $uri = new static($scheme,$host,$uri->get('REQUEST_URI','/'),$port,$user,$password);

        return $uri;
    }

    public function __construct($scheme,$host,$uri,$port = null,$user = '',$password = '')
    {
        $parsed = [];
        if ($uri) {
            $parsed = parse_url($uri) ?: [];
        }
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->path = isset($parsed['path']) ? $parsed['path'] : '/';
        $this->query = isset($parsed['query']) ? $parsed['query'] : '';
        $this->fragment = isset($parsed['fragment']) ? $parsed['fragment'] : '';
    }

    //获取Scheme(连接方式)
    public function getScheme(){

    }

    //返回[user-info@]host[:port] user-info，port为可选输出
    public function getAuthority(){

    }

    //返回username[:password] password为可选
    public function getUserInfo(){

    }

    //获取host
    public function getHost(){

    }

    //获取端口
    public function getPort(){

    }

    //获取脚本路径,如果有字符,需要编码后输出
    public function getPath(){

    }

    //获取查询参数
    public function getQuery(){

    }

    //
    public function getFragment(){

    }

    public function withScheme($scheme){

    }

    public function withUserInfo($user, $password = null){

    }

    public function withHost($host){

    }

    public function withPort($port){

    }

    public function withPath($path){

    }

    public function withQuery($query){

    }


    public function withFragment($fragment){

    }

    public function __toString(){

    }
}