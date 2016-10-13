<?php
namespace Ant\Http;

use ArrayObject;
use JsonSerializable;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;

class Response extends Message implements ResponseInterface
{
    const SWITCHING_PROTOCOLS = 101;
    const PROCESSING = 102;
    const OK = 200;
    const CREATED = 201;
    const ACCEPTED = 202;
    const NON_AUTHORITATIVE_INFORMATION = 203;
    const NO_CONTENT = 204;
    const RESET_CONTENT = 205;
    const PARTIAL_CONTENT = 206;
    const MULTI_STATUS = 207;
    const ALREADY_REPORTED = 208;
    const MULTIPLE_CHOICES = 300;
    const MOVED_PERMANENTLY = 301;
    const FOUND = 302;
    const SEE_OTHER = 303;
    const NOT_MODIFIED = 304;
    const USE_PROXY = 305;
    const SWITCH_PROXY = 306;
    const TEMPORARY_REDIRECT = 307;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const PAYMENT_REQUIRED = 402;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;
    const NOT_ACCEPTABLE = 406;
    const PROXY_AUTHENTICATION_REQUIRED = 407;
    const REQUEST_TIMEOUT = 408;
    const CONFLICT = 409;
    const GONE = 410;
    const LENGTH_REQUIRED = 411;
    const PRECONDITION_FAILED = 412;
    const REQUEST_ENTITY_TOO_LARGE = 413;
    const REQUEST_URI_TOO_LONG = 414;
    const UNSUPPORTED_MEDIA_TYPE = 415;
    const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const EXPECTATION_FAILED = 417;
    const IM_A_TEAPOT = 418;
    const UNPROCESSABLE_ENTITY = 422;
    const LOCKED = 423;
    const FAILED_DEPENDENCY = 424;
    const UNORDERED_COLLECTION = 425;
    const UPGRADE_REQUIRED = 426;
    const PRECONDITION_REQUIRED = 428;
    const TOO_MANY_REQUESTS = 429;
    const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    const INTERNAL_SERVER_ERROR = 500;
    const NOT_IMPLEMENTED = 501;
    const BAD_GATEWAY = 502;
    const SERVICE_UNAVAILABLE = 503;
    const GATEWAY_TIMEOUT = 504;
    const HTTP_VERSION_NOT_SUPPORTED = 505;
    const VARIANT_ALSO_NEGOTIATES = 506;
    const INSUFFICIENT_STORAGE = 507;
    const LOOP_DETECTED = 508;
    const NETWORK_AUTHENTICATION_REQUIRED = 511;

    /**
     * http状态码与对应短语
     *
     * @var array
     */
    public static $httpReasonPhrase = [
        //1xx Informational 请求过程
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        //2xx Successful    请求成功
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        //3xx Redirection   请求重定向
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        //4xx Client Error  客户端请求出错
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        //5xx Server Error  服务器内部错误
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * 是否保持数据不变性
     *
     * @var bool
     */
    protected $immutability = false;

    /**
     * http状态码
     *
     * @var int
     */
    protected $code;

    /**
     * http短语
     *
     * @var string
     */
    protected $responsePhrase = '';

    /**
     * 响应的cookie
     *
     * @var array
     */
    protected $cookie = [];

    /**
     * @param int                   $code
     * @param null|array            $header
     * @param StreamInterface|null  $body
     */
    public function __construct($code = 200,$header = [],StreamInterface $body = null)
    {
        $this->code = $code;
        $this->headers = $header ?: [];
        $this->body = $body ? : new Body(fopen('php://temp', 'r+'));
    }

    /**
     * 获取http状态码
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->code;
    }

    /**
     * 设置http状态码
     *
     * @param int $code
     * @param string $reasonPhrase
     * @return $this
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        if(!is_integer($code) || $code < 100 || $code > 599){
            throw new InvalidArgumentException('Invalid HTTP status code');
        }

        $this->code = $code;
        $this->responsePhrase = (string) $reasonPhrase;

        return $this;
    }

    /**
     * 获取http响应短语
     *
     * @return null|string
     */
    public function getReasonPhrase()
    {
        if($this->responsePhrase){
            return $this->responsePhrase;
        }

        return $this->responsePhrase = static::getStatusPhrase($this->code);
    }

    /**
     * 向响应body写入内容
     *
     * @param $data
     * @return $this
     */
    public function write($data)
    {
        $this->getBody()->write($data);

        return $this;
    }

    /**
     * 设置响应内容
     *
     * @param $content
     */
    public function setContent($content)
    {
        if($this->shouldBeJson($content)){
            $this->setJson($content);
        }else{
            $this->write($content);
        }
    }

    /**
     * http重定向
     *
     * @param $url
     * @param $status 303
     * @return Response
     */
    public function redirect($url, $status = 303)
    {
        return $this->withStatus($status)
                    ->withHeader('Location', $url);
    }

    /**
     * 响应JSON数据
     *
     * @param $data
     * @param null $status
     * @param int $encodingOptions
     * @return Response
     */
    public function setJson($data,$status = null,$encodingOptions = 0)
    {
        $this->getBody()->rewind();
        $this->getBody()->write(safe_json_encode($data, $encodingOptions));

        $this->withHeader('Content-Type', 'application/json;charset=utf-8');
        if (isset($status)) {
            return $this->withStatus($status);
        }

        return $this;
    }

    /**
     * 设置Cookie
     *
     * @param string $name          cookie名称
     * @param string $value         cookie值
     * @param int $expire           超时时间
     * @param string $path          cookie作用目录
     * @param string $domain        cookie作用域名
     * @param bool $secure          是否https专属
     * @param bool|true $httponly   是否只有http可以使用cookie(启用后,JS将无法访问该cookie)
     */
    public function setCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = true)
    {
        $this->cookie[] = [$name, $value, $expire, $path, $domain, $secure, $httponly];
    }

    /**
     * 开始响应
     */
    public function send()
    {
        //响应头
        if(!headers_sent()){
            header(sprintf(
                'HTTP/%s %s %s',
                $this->getProtocolVersion(),
                $this->getStatusCode(),
                $this->getReasonPhrase()
            ));

            foreach($this->getHeaders() as $name => $value){
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                $name = implode('-',array_map('ucfirst',explode('-',$name)));
                header(sprintf('%s: %s',$name,$value));
            }

            foreach($this->cookie as list($name, $value, $expire, $path, $domain, $secure, $httponly)){
                setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
            }
        }

        //响应body内容
        if(!$this->isEmpty()){
            echo (string) $this->getBody();
        }else{
            echo '';
        }
    }

    /**
     * 根据状态码判断响应是否空内容
     *
     * @return bool
     */
    public function isEmpty()
    {
        return in_array($this->getStatusCode(),[204,205,304]);
    }

    /**
     * 是否为临时响应
     *
     * @return bool
     */
    public function isInformational()
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }

    /**
     * 是否成功
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**
     * 是否进行重定向
     *
     * @return bool
     */
    public function isRedirect()
    {
        return in_array($this->getStatusCode(), [301, 302, 303, 307]);
    }

    /**
     * 是否为重定向响应
     *
     * @return bool
     */
    public function isRedirection()
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
     * 是否为拒绝请求
     *
     * @return bool
     */
    public function isForbidden()
    {
        return $this->getStatusCode() === 403;
    }

    /**
     * 是否找到页面
     *
     * @return bool
     */
    public function isNotFound()
    {
        return $this->getStatusCode() === 404;
    }

    /**
     * 是否为客户端错误
     *
     * @return bool
     */
    public function isClientError()
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * 是否为服务端错误
     *
     * @return bool
     */
    public function isServerError()
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }

    /**
     * @param $code
     * @return null
     */
    public static function getStatusPhrase($code) {
        return isset(self::$httpReasonPhrase[$code])
            ? self::$httpReasonPhrase[$code]
            : null;
    }

    /**
     * 是否序列化为json格式
     *
     * @param $content
     * @return bool
     */
    protected function shouldBeJson($content)
    {
        //默认将实现了json序列化方法的对象进行JSON序列化
        return $content instanceof JsonSerializable ||
        $content instanceof ArrayObject ||
        is_array($content);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $output = sprintf(
            'HTTP/%s %s %s',
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        );
        $output .= PHP_EOL;
        foreach ($this->getHeaders() as $name => $values) {
            $output .= sprintf('%s: %s', $name, $this->getHeaderLine($name)) . PHP_EOL;
        }
        $output .= PHP_EOL;
        $output .= (string)$this->getBody();

        return $output;
    }
}