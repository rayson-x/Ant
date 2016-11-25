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

        $uri = new Uri((isset($headers['host']) ? 'http://'.$headers['host'][0] : '') .$requestTarget);

        $body = in_array($method,['GET','OPTIONS'])
            ? new RequestBody(fopen('php://temp','r+'))
            : RequestBody::createFromTcpStream($bodyBuffer);

        return new static($method, $requestTarget, $protocol, $uri, $headers, $body);
    }

    /**
     * Request constructor.
     * @param $method
     * @param $requestTarget
     * @param $protocol
     * @param UriInterface $uri
     * @param array $headers
     * @param StreamInterface $body
     */
    public function __construct(
        $method,
        $requestTarget,
        $protocol,
        UriInterface $uri,
        array $headers = [],
        StreamInterface $body = null
    ){
        $this->method = $method;
        $this->requestTarget = $requestTarget;
        $this->protocolVersion = $protocol;
        $this->headers = $headers;
        $this->uri = $uri;
        $this->body = $body;

        //当请求方式为Post时,
        if ($this->method == 'POST') {
            //判断是否是表单
            if($this->getContentType() == 'multipart/form-data'){
                $this->parseForm();
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
        if(!empty($this->queryParams)){
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
        //解析成功直接返回解析结果
        if(!empty($this->bodyParams)){
            return $this->bodyParams;
        }

        //为空返回null
        if($this->body->getSize() === 0){
            return null;
        }

        //用自定义方法解析Body内容
        list($type,$subtype) = explode('/',$this->getContentType(),2);

        if(in_array(strtolower($type),['application','text']) && isset($this->bodyParsers[$subtype])){
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

        return null;
    }

    /**
     * 解析表单内容
     *
     * @return array
     */
    protected function parseForm()
    {
        if (!preg_match('/boundary="?(\S+)"?/', $this->getHeaderLine('content-type'), $match)) {
            return;
        }

        //获取Body分界符
        $bodyBoundary = '--' . $match[1] . "\r\n";
        //将最后一行分界符剔除
        $body = substr((string)$this->getBody(), 0 ,$this->getBody()->getSize() - (strlen($bodyBoundary) + 4));
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
        //Todo::将文件写入Body
        $input = sprintf(
            '%s %s HTTP/%s',
            $this->getMethod(),
            $this->getRequestTarget(),
            $this->getProtocolVersion()
        );
        $input .= PHP_EOL;

        if(!$this->hasHeader('host')){
            if(!$host = $this->getUri()->getHost()){
                // 请求的host不能为空
                throw new RuntimeException('Requested host cannot be empty');
            }
            $this->headers['host'] = $host;
        }

        $input .= $this->headerToString();

        foreach($this->getCookieParams() as $cookieName => $cookieValue){
            $input .= sprintf('%s: %s',$cookieName,$cookieValue).PHP_EOL;
        }

        $input .= PHP_EOL;
        $input .= (string)$this->getBody();

        return $input;
    }
}