<?php
namespace Ant\Http;

use RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Class ServerRequest
 * @package Ant\Http
 * @see http://www.php-fig.org/psr/psr-7/
 */
class Request extends Message implements RequestInterface
{
    /**
     * 请求资源
     *
     * @var string
     */
    protected $requestTarget;

    /**
     * http 请求方式
     *
     * @var string
     */
    protected $method;

    /**
     * Uri 实例
     *
     * @var \Psr\Http\Message\UriInterface
     */
    protected $uri;

    /**
     * cookie参数
     *
     * @var array
     */
    protected $cookieParams;

    /**
     * 查询参数
     *
     * @var array
     */
    protected $queryParams;

    /**
     * http上传文件 \Psr\Http\Message\UploadedFileInterface 实例
     *
     * @var array
     */
    protected $uploadFiles;

    /**
     * body 参数
     *
     * @var array|object|null
     */
    protected $bodyParsed;

    /**
     * body 解析器 根据subtype进行调用
     *
     * @var array
     */
    protected $bodyParsers = [];


    /**
     * 通过Tcp输入流解析Http请求
     *
     * @param string $receiveBuffer
     */
    public static function createFromTcpStream($receiveBuffer)
    {
        //Todo::待完善
        if (!is_string($receiveBuffer)) {
            throw new \InvalidArgumentException('Request must be string');
        }

        list($header, $body) = explode("\r\n\r\n", $receiveBuffer, 2);

        list($request_method, $request_uri, $server_protocol) = explode(' ', array_shift($headerData), 3);

        $headers = [];
        $bodyBoundary = '';
        $headerData = explode("\r\n",$header);

        foreach ($headerData as $content) {
            if (empty($content)) {
                continue;
            }
            list($name, $value) = explode(':', $content, 2);
            $name = strtolower($name);
            $value = trim($value);
            switch ($name) {
                case 'cookie':
                    parse_str(str_replace('; ', '&', $value), $_COOKIE);
                    break;
                case 'content-type':
                    // 判断是否为浏览器表单数据
                    if (preg_match('/boundary="?(\S+)"?/', $value, $match)) {
                        $headers[$name] = 'multipart/form-data';
                        $bodyBoundary = '--' . $match[1];
                    } else {
                        $headers[$name] = $value;
                    }
                    break;
                default:
                    $headers[$name] = $value;
                    break;
            }
        }

        if(!in_array($request_method,['GET','HEAD','OPTIONS'])){
            if(isset($headers['content-type']) && $headers['content-type'] === 'multipart/form-data'){
                //Todo::解析表单内容
                list($bodyParams,$uploadedFiles) = RequestBody::parseForm($body,$bodyBoundary);
            }else{
                $body = RequestBody::createFromTcpStream($body);
            }
        }
    }

    /**
     * 获取请求目标(资源)
     *
     * @return string
     */
    public function getRequestTarget()
    {
        return $this->requestTarget;
    }

    /**
     * 设置请求资源
     *
     * @param mixed $requestTarget
     * @return Request
     */
    public function withRequestTarget($requestTarget)
    {
        return $this->changeAttribute('requestTarget',$requestTarget);
    }

    /**
     * 获取http请求方式,支持重写请求方式
     *
     * @return string
     */
    public function getMethod()
    {
        if($this->method){
            return $this->method;
        }

        $method = $this->getMethod();

        // 尝试重写请求方法
        if ($method == 'POST') {
            $override = $this->getBodyParam('_method') ?: $this->getHeaderLine('x-http-method-override');
            if($override){
                $method = strtoupper($override);
            }
        }

        return $this->method = $method;
    }

    /**
     * 设置请求方式
     *
     * @param string $method
     * @return Request
     */
    public function withMethod($method)
    {
        return $this->changeAttribute('method',$method);
    }

    /**
     * 获取URI
     *
     * @return UriInterface
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * 设置uri
     *
     * 如果开启host保护
     * HOST为空,新的URI包含HOST,更新
     * HOST为空,新的URI不包含,不更新
     * HOST不为空,不更新
     *
     * @param UriInterface $uri
     * @param bool|false $preserveHost
     * @return Request
     */
    public function withUri(UriInterface $uri,$preserveHost = false)
    {
        if(!$preserveHost){
            $host = explode(',',$uri->getHost());
        }else{
            if( (!$this->hasHeader('host') || empty($this->getHeaderLine('host'))) && $uri->getHost() !== ''){
                $host = explode(',',$uri->getHost());
            }
        }

        if(empty($host)){
            $host = $this->getHeader('host');
        }

        return $this->changeAttribute(['headers','host'],$host);
    }

    /**
     * 获取cookie参数
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * 设置cookie参数
     *
     * @param array $cookies
     * @return Request
     */
    public function withCookieParams(array $cookies)
    {
        return $this->changeAttribute('cookieParams',$cookies);
    }

    /**
     * 获取查询参数
     *
     * @return array
     */
    public function getQueryParams()
    {
        if(is_array($this->queryParams)){
            return $this->queryParams;
        }

        if ($this->uri === null) {
            return [];
        }

        parse_str($this->uri->getQuery(),$this->queryParams);

        return $this->queryParams;
    }

    /**
     * 设置查询参数
     *
     * @param array $query
     * @return Request
     */
    public function withQueryParams(array $query)
    {
        return $this->changeAttribute('queryParams',$query);
    }

    /**
     * 向get中添加参数
     *
     * @param array $query
     * @return Request
     */
    public function withAddedQueryParams(array $query)
    {
        return $this->withQueryParams(array_merge($this->getQueryParams(),$query));
    }

    /**
     * 获取上传文件信息
     *
     * @return array
     */
    public function getUploadedFiles()
    {
        return $this->uploadFiles;
    }

    /**
     * 添加上传文件信息
     *
     * @param array $uploadedFiles
     * @return Request
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        return $this->changeAttribute('uploadFiles',$uploadedFiles);
    }

    /**
     * 获取body解析结果
     *
     * @return array|null|object
     */
    public function getParsedBody()
    {
        if(!empty($this->bodyParsed)){
            return $this->bodyParsed;
        }

        // "Content-Type" 为 "multipart/form-data" 时候 php://input 是无效的
        if($this->getMethod() === 'POST'
            && in_array($this->getContentType(),['multipart/form-data','application/x-www-form-urlencoded'])
        ){
            return $this->bodyParsed = $_POST;
        }

        if($this->body->getSize() === 0){
            return null;
        }

        list($type,$subtype) = explode('/',$this->getContentType(),2);

        if(in_array(strtolower($type),['application','text']) && isset($this->bodyParsers[$subtype])){
            //调用body解析函数
            $body = (string)$this->getBody();
            $parsed = call_user_func($this->bodyParsers[$subtype],$body);

            if (!(is_null($parsed) || is_object($parsed) || is_array($parsed))){
                throw new RuntimeException('Request body media type parser return value must be an array, an object, or null');
            }

            return $this->bodyParsed = $parsed;
        }

        return null;
    }

    /**
     * 设置body解析结果
     *
     * @param array|null|object $data
     * @return Request
     */
    public function withParsedBody($data)
    {
        if(!(is_null($data) || is_array($data) || is_object($data))){
            throw new InvalidArgumentException('Parsed body value must be an array, an object, or null');
        }

        return $this->changeAttribute('bodyParsed',$data);
    }

    /**
     * 检查请求方式
     *
     * @param $method
     * @return bool
     */
    public function isMethod($method)
    {
        return $this->getMethod() === $method;
    }

    /**
     * 查看是否是GET请求
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->isMethod('GET');
    }

    /**
     * 查看是否是POST请求
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->isMethod('POST');
    }

    /**
     * 查看是否是PUT请求
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->isMethod('PUT');
    }

    /**
     * 查看是否是DELETE请求
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->isMethod('DELETE');
    }

    /**
     * 检查是否是异步请求
     * 注意 : 主流JS框架发起AJAX都有此参数,如果是原生AJAX需要手动添加到http头
     *
     * @return bool
     */
    public function isAjax()
    {
        return strtolower($this->getHeaderLine('x-requested-with')) === 'xmlhttprequest';
    }

    /**
     * 获取body参数
     *
     * @param null $key
     * @return array|null|object
     */
    public function getBodyParam($key = null)
    {
        if($key === null){
            return $this->getParsedBody();
        }

        $params = $this->getParsedBody();

        if(is_array($params) && array_key_exists($key,$params)){
            return $params[$key];
        }elseif (is_object($params) && property_exists($params, $key)){
            return $params->$key;
        }

        return null;
    }

    /**
     * 获取content-type
     *
     * @return null
     */
    public function getContentType()
    {
        $result = $this->getHeader('content-type');

        $contentType = $result ? $result[0] : null;
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

            return strtolower($contentTypeParts[0]);
        }

        return null;
    }

    /**
     * 设置body解析器
     *
     * @param $subtype string
     * @param $parsers callable
     */
    public function setBodyParsers($subtype,$parsers)
    {
        if(!is_callable($parsers) && !function_exists($parsers)){
            throw new InvalidArgumentException('Body parsers must be a callable');
        }

        $this->bodyParsers[$subtype] = $parsers;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $input = sprintf(
            '%s %s HTTP/%s',
            $this->getMethod(),
            $this->getRequestTarget(),
            $this->getProtocolVersion()
        );
        $input .= PHP_EOL;
        foreach($this->getHeaders() as $name => $value){
            if (is_array($value)) {
                $value = implode(',', $value);
            }

            $name = implode('-',array_map('ucfirst',explode('-',$name)));
            $input .= sprintf('%s: %s',$name,$value).PHP_EOL;
        }
        $input .= PHP_EOL;
        $input .= (string)$this->getBody();

        return $input;
    }
}