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

    /**
     * @param Collection $uri
     * @return Collection|static
     */
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

    /**
     * 初始化Uri类
     *
     * Uri constructor.
     * @param $scheme
     * @param $host
     * @param $uri
     * @param null $port
     * @param string $user
     * @param string $password
     */
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

    /**
     * 获取Scheme(连接方式)
     *
     * @return mixed
     */
    public function getScheme(){
        return $this->scheme;
    }

    /**
     * 返回[user-info@]host[:port] user-info，port为可选输出
     *
     * @return string
     */
    public function getAuthority(){
        $userInfo = $this->getUserInfo();
        $host = $this->getHost();
        $port = $this->getPort();

        return ($userInfo ? $userInfo."@" : ''). $host .($port !== null ? ':' . $port : '');
    }

    /**
     * 返回username[:password] password为可选
     *
     * @return string
     */
    public function getUserInfo(){
        return $this->user . ($this->password ? ':' . $this->password : '');
    }

    /**
     * 获取host
     *
     * @return string.
     */
    public function getHost(){
        return $this->host;
    }

    /**
     * 获取端口
     *
     * @return int|null
     */
    public function getPort(){
        $port = $this->port;
        if($port != null){
            return $port;
        }

        $scheme = $this->scheme;
        if(!$scheme){
            return null;
        }

        return $this->standardPort[$scheme];
    }

    /**
     * 获取脚本路径,如果有字符,需要编码后输出
     *
     * @return string
     */
    public function getPath(){
        return $this->path;
    }

    /**
     * 获取查询参数
     *
     * @return string
     */
    public function getQuery(){
        return $this->query;
    }

    /**
     * 获取 # 后的值
     *
     * @return string
     */
    public function getFragment(){
        return $this->fragment;
    }

    /**
     * 指定协议
     *
     * @param string $scheme
     * @return Uri
     */
    public function withScheme($scheme){
        $clone = clone $this;
        $clone->scheme = $scheme;

        return $clone;
    }

    /**
     * 指定userinfo
     *
     * @param string $user
     * @param null $password
     * @return Uri
     */
    public function withUserInfo($user, $password = null){
        $clone = clone $this;
        $clone->user = $user;
        $clone->password = $password;

        return $clone;
    }

    /**
     * 指定主机名
     *
     * @param string $host
     * @return Uri
     */
    public function withHost($host){
        $clone = clone $this;
        $clone->host = $host;

        return $clone;
    }

    /**
     * 指定端口
     *
     * @param int|null $port
     * @return Uri
     */
    public function withPort($port){
        $clone = clone $this;
        $clone->port = ($port === null ? null : (int) $port);

        return $clone;
    }

    /**
     * 指定Uri路径
     *
     * @param string $path
     * @return Uri
     */
    public function withPath($path){
        $clone = clone $this;
        $clone->path = $path ?: '/';

        return $clone;
    }

    /**
     * 指定query参数
     *
     * @param string $query The query string to use with the new instance
     * @return static A new instance with the specified query string
     * @throws \InvalidArgumentException for invalid query strings
     */
    public function withQuery($query){

        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    }

    public function withQueryArray(array $query){
        //将空格编码
        $query = http_build_query($query,'','&',PHP_QUERY_RFC3986);
    }

    /**
     * 指定 # 后的参数
     *
     * @param string $fragment
     * @return static
     */
    public function withFragment($fragment){
        if (!is_string($fragment)) {
            throw new \InvalidArgumentException('Invalid URI fragment');
        }

        $clone = clone $this;
        $clone->fragment = $fragment;

        return $clone;
    }

    /**
     * 输出完整链接
     *
     * @return string
     */
    public function __toString(){
        $uri = '';

        if ($scheme = $this->getScheme()) {
            $uri = $scheme.':';
        }

        if ($authority = $this->getAuthority()) {
            $uri .= '//'.$authority;
        } else {
            $uri = '';
        }

        $uri .= $this->getPath();

        if ($query = $this->getQuery()) {
            $uri .= '?'.$query;
        }

        $fragment = $this->getFragment();
        if ($fragment !== '') {
            $uri .= '#'.$fragment;
        }

        return $uri;
    }
}