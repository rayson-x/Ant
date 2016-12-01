<?php
namespace Ant\Http;

use RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Ant\Http\Interfaces\RequestInterface;

/**
 * Class Request
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
    protected $cookieParams = [];

    /**
     * 查询参数
     *
     * @var array
     */
    protected $queryParams = [];

    /**
     * http上传文件 \Psr\Http\Message\UploadedFileInterface 实例
     *
     * @var array
     */
    protected $uploadFiles = [];

    /**
     * body 参数
     *
     * @var array|object|null
     */
    protected $bodyParams;

    /**
     * body 解析器 根据subtype进行调用
     *
     * @var array
     */
    protected $bodyParsers = [];

    /**
     * 客户端请求的类型
     *
     * @var string|null
     */
    protected $acceptType = null;

    /**
     * 是否解析过
     *
     * @var bool
     */
    protected $bodyAlreadyParse = false;

    /**
     * 通过Tcp输入流解析Http请求
     *
     * @param string $receiveBuffer
     * @return static
     */
    public static function createFromRequestStr($receiveBuffer)
    {
        if (!is_string($receiveBuffer)) {
            throw new \InvalidArgumentException('Request must be string');
        }

        list($headerBuffer, $bodyBuffer) = explode("\r\n\r\n", $receiveBuffer, 2);
        $headerData = explode("\r\n",$headerBuffer);

        list($method, $requestTarget, $protocol) = explode(' ', array_shift($headerData), 3);
        $protocol = explode('/',$protocol,2)[1];

        $headers = [];
        foreach ($headerData as $content) {
            if (isset($content)) {
                list($name, $value) = explode(':', $content, 2);
                $headers[strtolower($name)] = explode(',',trim($value));
            }
        }

        $uri = (isset($headers['host']) ? 'http://'.$headers['host'][0] : '') .$requestTarget;

        $body = ($method == 'GET')
            ? new RequestBody(fopen('php://temp','r+'))
            : RequestBody::createFromString($bodyBuffer);

        return new static($method, $uri, $headers, $body, $protocol);
    }

    /**
     * Request constructor.
     * @param string $method                    Http动词
     * @param string $uri                       请求的Uri
     * @param array $headers                    Http头
     * @param StreamInterface|null $body        Body内容
     * @param string $protocol                  Http协议版本
     */
    public function __construct($method, $uri, array $headers = [], StreamInterface $body = null, $protocol = '1.1')
    {
        $this->method = $method;
        $this->uri = new Uri($uri);
        $this->headers = $headers;
        $this->body = $body ?: new Body();
        $this->protocolVersion = $protocol;

        $this->initParam();
    }

    /**
     * 初始化请求参数
     */
    protected function initParam()
    {
        //解析GET与Cookie参数
        parse_str($this->uri->getQuery(),$this->queryParams);
        parse_str(str_replace('; ', '&', $this->getHeaderLine('Cookie')), $this->cookieParams);

        //当请求方式为Post时,检查是否为表单提交,跟请求重写
        if ($this->method == 'POST') {
            //判断是否是表单
            if(
                in_array($this->getContentType(),['multipart/form-data','application/x-www-form-urlencoded']) &&
                preg_match('/boundary="?(\S+)"?/', $this->getHeaderLine('content-type'), $match)
            ){
                //获取Body分界符
                $this->parseForm( '--' . $match[1] . "\r\n");
            }

            $override = $this->getBodyParam('_method') ?: $this->getHeaderLine('x-http-method-override');
            if($override){
                $this->method = strtoupper($override);
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
        return $this->uri->getRequestTarget();
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
        return $this->method;
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
     * @param UriInterface $uri
     * @param bool|false $preserveHost
     * @return Request
     */
    public function withUri(UriInterface $uri,$preserveHost = false)
    {
        //如果开启host保护,原Host为空且新Uri包含Host时才更新
        if(!$preserveHost){
            $host = explode(',',$uri->getHost());
        }elseif((!$this->hasHeader('host') || empty($this->getHeaderLine('host'))) && $uri->getHost() !== ''){
            $host = explode(',',$uri->getHost());
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
        //解析成功直接返回解析结果
        if(!empty($this->bodyParams)){
            return $this->bodyParams;
        }

        //如果解析后的参数为空,不允许进行第二次解析
        if($this->bodyAlreadyParse){
            return null;
        }

        if($contentType = $this->getContentType()){
            //用自定义方法解析Body内容
            list($type,$subtype) = explode('/',$contentType,2);

            if(
                $this->body->getSize() !== 0
                && in_array(strtolower($type),['application','text'])
                && isset($this->bodyParsers[$subtype])
            ){
                //调用body解析函数
                $body = (string)$this->getBody();
                $parsed = call_user_func($this->bodyParsers[$subtype],$body);

                if (!(is_null($parsed) || is_object($parsed) || is_array($parsed))){
                    throw new RuntimeException(
                        'Request body media type parser return value must be an array, an object, or null'
                    );
                }

                return $this->bodyParams = $parsed;
            }
        }

        $this->bodyAlreadyParse = true;
        return null;
    }

    /**
     * 解析表单内容
     *
     * @param string $bodyBoundary Body分界符
     */
    protected function parseForm($bodyBoundary)
    {
        if(!$size = $this->getBody()->getSize()){
            $this->bodyParams = $_POST;
            $this->uploadFiles = UploadedFile::parseUploadedFiles($_FILES);
            return;
        }

        //将最后一行分界符剔除
        $body = substr((string)$this->getBody(), 0 ,$size - (strlen($bodyBoundary) + 4));

        foreach(explode($bodyBoundary,$body) as $buffer){
            if($buffer == ''){
                continue;
            }

            //将Body头信息跟内容拆分
            list($header, $bufferBody) = explode("\r\n\r\n", $buffer, 2);
            $bufferBody = substr($bufferBody, 0, -2);
            foreach (explode("\r\n", $header) as $item) {
                list($headerName, $headerData) = explode(":", $item, 2);
                $headerName = trim(strtolower($headerName));
                if($headerName == 'content-disposition'){
                    if (preg_match('/name=".*?"; filename="(.*?)"$/', $headerData, $match)) {
                        $file = new Stream(fopen('php://temp','w'));
                        $file->write($bufferBody);
                        $file->rewind();

                        $this->uploadFiles[$match[1]] = new UploadedFile([
                            'resources' => $file,
                            'name'      => $match[1],
                            'size'      => $file->getSize()
                        ]);
                        $uploadedFiles[$match[1]] = $file;
                    }elseif(preg_match('/name="(.*?)"$/', $headerData, $match)) {
                        $this->bodyParams[$match[1]] = $bufferBody;
                    }
                }
            }
        }
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
        if(is_null($key)){
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
     * 获取请求的body类型
     *
     * @return null|string
     */
    public function getContentType()
    {
        $result = $this->getHeader('Content-Type');
        $contentType = $result ? $result[0] : null;

        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

            $contentType = strtolower($contentTypeParts[0]);
        }

        return $contentType;
    }

    /**
     * 获取内容长度
     *
     * @return int|null
     */
    public function getContentLength()
    {
        $result = $this->getHeader('Content-Length');

        return $result ? (int)$result[0] : null;
    }

    /**
     * 获取请求的路由
     *
     * @return array
     */
    public function getRequestUri()
    {
        //获取请求资源的路径
        $requestScriptName = $this->getScriptName();
        $requestScriptDir = dirname($requestScriptName);
        $requestUri = $this->getUri()->getPath();

        //获取基础路径
        if (stripos($requestUri, $requestScriptName) === 0) {
            $basePath = $requestScriptName;
        } elseif ($requestScriptDir !== '/' && stripos($requestUri, $requestScriptDir) === 0) {
            $basePath = $requestScriptDir;
        }

        if(isset($basePath)) {
            //获取请求的路径
            $requestUri = '/'.trim(substr($requestUri, strlen($basePath)), '/');
        }

        //获取客户端需要的资源格式
        if(false !== ($pos = strrpos($requestUri,'.'))){
            $requestUri = strstr($requestUri, '.', true);
            $this->acceptType = substr($requestUri, $pos + 1);
        }

        return $requestUri;
    }

    /**
     * 解析客户端请求的数据格式
     *
     * @return string
     */
    public function getAcceptType()
    {
        if(is_null($this->acceptType)){
            $acceptTypes = [
                'application/json'  =>  'json',
                'text/xml'          =>  'xml',
                'application/xml'   =>  'xml',
                'text/html'         =>  'html',
            ];

            foreach($this->getHeader('accept') as $type){
                if(array_key_exists($type,$acceptTypes)){
                    $this->acceptType = $acceptTypes[$type];
                    break;
                }
            }
        }

        return $this->acceptType ?: 'html';
    }

    /**
     * 获取脚本路径
     *
     * @return string
     */
    protected function getScriptName()
    {
        //追踪栈
        $backtrace = debug_backtrace();
        //取得初始脚本路径
        $scriptPath = $backtrace[count($backtrace)-1]['file'];
        //获取脚本在网站根目录下的路径
        $intersect = array_intersect(explode('/',$this->getUri()->getPath()),explode(DIRECTORY_SEPARATOR,$scriptPath));
        $intersect[] = basename($scriptPath);

        return '/'.implode('/',$intersect);
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
     * Todo::将文件写入Body
     *
     * @return string
     */
    public function __toString()
    {
        //修改查询参数
        $this->uri = $this->uri->withQuery(http_build_query($this->getQueryParams()));

        if(!$this->hasHeader('host')){
            if(!$host = $this->getUri()->getHost()){
                // 请求的host不能为空
                throw new RuntimeException('Requested host cannot be empty');
            }
            $this->headers['host'] = $host;
        }

        if($cookie = $this->getCookieParams()){
            //设置Cookie
            $this->headers['cookie'] = http_build_query($this->getCookieParams());
        }

        if($size = $this->getBody()->getSize()){
            //设置Body长度
            $this->headers['content-length'] = [$size];
        }

        $input = sprintf(
            '%s %s HTTP/%s',
            $this->getMethod(),
            $this->getRequestTarget(),
            $this->getProtocolVersion()
        );

        $input .= PHP_EOL;
        $input .= $this->headerToString();

        $input .= PHP_EOL;
        $input .= (string)$this->getBody();

        return $input;
    }
}