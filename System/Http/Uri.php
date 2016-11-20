<?php
namespace Ant\Http;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    /**
     * 标准端口
     *
     * @var array
     */
    protected $standardPort = [
        'http'  =>  80,
        'https' =>  443,
    ];

    /**
     * 连接方式
     *
     * @var string
     */
    protected $scheme;

    /**
     * 请求主机地址
     *
     * @var string
     */
    protected $host = null;

    /**
     * 请求的端口号
     *
     * @var null|int
     */
    protected $port = null;

    /**
     * http用户
     *
     * @var string
     */
    protected $user = null;

    /**
     * http用户连接密码
     *
     * @var string
     */
    protected $password = null;

    /**
     * 请求资源路径
     *
     * @var string
     */
    protected $path = null;

    /**
     * 查询参数
     *
     * @var string
     */
    protected $query = null;

    /**
     * 分段
     *
     * @var string
     */
    protected $fragment = null;

    /**
     * @param Environment $env
     * @return static
     */
    public static function createFromEnvironment(Environment $env)
    {
        $scheme = ($env['HTTPS'] == 'on') ? 'https' : 'http';
        $userInfo = $env['PHP_AUTH_USER'].($env['PHP_AUTH_PW'] ? ':' . $env['PHP_AUTH_PW'] : '');

        //HTTP_HOST在http1.0下可以返回空
        if($httpHost = $env['HTTP_HOST'] ?: false){
            if(strpos($httpHost,':')){
                list($host,$port) = explode(':',$httpHost,2);
                $port = (int)$port;
            }else{
                $host = $httpHost;
                $port = null;
            }
        }else{
            $host = $env['SERVER_NAME'] ?: $env['SERVER_ADDR'];
            $port = $env['SERVER_PORT'];
        }

        $uri = $scheme.':';
        $uri .= '//'.($userInfo ? $userInfo."@" : ''). $host .($port !== null ? ':' . $port : '');
        $uri .= $env['REQUEST_URI'];

        return new static($uri);
    }

    /**
     * 初始化Uri类
     *
     * Uri constructor.
     * @param $uri
     */
    public function __construct($uri)
    {
        foreach(parse_url($uri) as $key => $value){
            $this->$key = $value;
        }
    }

    /**
     * 获取应用协议
     *
     * @return mixed
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * 指定连接方式
     *
     * @param string $scheme
     * @return Uri
     */
    public function withScheme($scheme)
    {
        $clone = clone $this;
        $clone->scheme = $scheme;

        return $clone;
    }

    /**
     * 获取授权信息,返回[user-info@]host[:port] user-info
     *
     * @return string
     */
    public function getAuthority()
    {
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
    public function getUserInfo()
    {
        return $this->user . ($this->password ? ':' . $this->password : '');
    }

    /**
     * 指定userInfo
     *
     * @param string $user
     * @param null $password
     * @return Uri
     */
    public function withUserInfo($user, $password = null)
    {
        $clone = clone $this;
        $clone->user = $user;
        $clone->password = $password;

        return $clone;
    }

    /**
     * 获取host
     *
     * @return string.
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * 指定主机名
     *
     * @param string $host
     * @return Uri
     */
    public function withHost($host)
    {
        $clone = clone $this;
        $clone->host = $host;

        return $clone;
    }

    /**
     * 获取端口
     *
     * @return int|null
     */
    public function getPort()
    {
        if($this->port){
            return $this->port;
        }

        if(!$this->scheme){
            return null;
        }

        return $this->port = $this->standardPort[$this->scheme];
    }

    /**
     * 指定端口
     *
     * @param int|null $port
     * @return Uri
     */
    public function withPort($port)
    {
        $port = $this->filterPort($port);
        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    /**
     * 过滤端口，确保参数为null或者int
     *
     * @param $port
     * @return int|null
     */
    protected function filterPort($port)
    {
        if (is_null($port) || (is_integer($port) && ($port >= 1 && $port <= 65535))) {
            return $port;
        }

        throw new InvalidArgumentException('Uri port must be null or an integer between 1 and 65535 (inclusive)');
    }

    /**
     * 获取脚本路径
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path ?: '/';
    }

    /**
     * 指定Uri路径
     *
     * @param string $path
     * @return Uri
     */
    public function withPath($path)
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException('Uri path must be a string');
        }

        $clone = clone $this;
        $clone->path = $path;

        return $clone;
    }

    /**
     * 获取查询参数
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * 指定query参数
     *
     * @param mixed
     * @return static
     * @throws \InvalidArgumentException for invalid query strings
     */
    public function withQuery($query)
    {
        if(is_array($query)){
            $query = http_build_query($query,'','&',PHP_QUERY_RFC3986);
        }

        if (!is_string($query) && !method_exists($query, '__toString')) {
            throw new InvalidArgumentException('Uri query must be a string');
        }

        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    }

    /**
     * 获取 # 后的值
     *
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * 指定 # 后的参数
     *
     * @param string $fragment
     * @return static
     */
    public function withFragment($fragment)
    {
        if (!is_string($fragment)) {
            throw new \InvalidArgumentException('Uri fragment must be a string');
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
    public function __toString()
    {
        $uri = '';

        if ($scheme = $this->getScheme()) {
            $uri = $scheme.':';
        }

        if ($authority = $this->getAuthority()) {
            $uri .= '//'.$authority;
        } else {
            $uri = '';
        }

        $uri .= '/'.$this->getPath();

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