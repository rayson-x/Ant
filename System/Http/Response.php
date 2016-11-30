<?php
namespace Ant\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Ant\Http\Interfaces\ResponseInterface;

/**
 * Todo::添加静态方法,通过http响应内容获取Response类
 *
 * Class Response
 * @package Ant\Http
 */
class Response extends Message implements ResponseInterface
{
    /**
     * http状态码与对应短语
     *
     * @var array
     */
    public $httpReasonPhrase = [
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
    protected $cookies = [];

    public static function createFromRequestResult(array $header, $bodyBuffer)
    {
        // TODO: Implement createFromRequestResult() method.
    }

    public static function createFromResponseStr($receiveBuffer)
    {
        // TODO: Implement createFromResponseStr() method.
    }

    /**
     * @param int                   $code
     * @param null|array            $header
     * @param StreamInterface|null  $body
     */
    public function __construct($code = 200, $header = [], StreamInterface $body = null)
    {
        $this->code = $code;
        $this->headers = $header ?: [];
        $this->body = $body ? : new Body();
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

        $this->responsePhrase = (string) $reasonPhrase;

        return $this->changeAttribute('code',$code);
    }

    /**
     * 获取http响应短语
     *
     * @return null|string
     */
    public function getReasonPhrase()
    {
        if(empty($this->responsePhrase)){
            $this->responsePhrase = isset($this->httpReasonPhrase[$this->code])
                ? $this->httpReasonPhrase[$this->code]
                : null;
        }

        return $this->responsePhrase;
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
     * @return $this
     */
    public function setCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false)
    {
        $this->cookies[] = [$name, $value, $expire, $path, $domain , $secure, $httponly];

        return $this;
    }

    /**
     * @return array
     */
    public function getCookies()
    {
        return $this->cookies;
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
        $output .= $this->headerToString();
        $output .= $this->getCookieHeader();
        $output .= PHP_EOL;
        $output .= (string)$this->getBody();

        return $output;
    }

    /**
     * @return string
     */
    protected function getCookieHeader()
    {
        $result = [];

        foreach($this->getCookies() as list($name, $value, $expire, $path, $domain , $secure, $httponly)){
            $value .= urlencode($name) . '=' . urlencode($value);

            if (isset($domain)) {
                $value .= '; domain=' . $domain;
            }

            if (isset($path)) {
                $value .= '; path=' . $path;
            }

            if (isset($expire)) {
                if (is_string($expire)) {
                    $timestamp = strtotime($expire);
                } else {
                    $timestamp = (int)$expire;
                }
                if ($timestamp !== 0) {
                    $value .= '; expires=' . gmdate('D, d-M-Y H:i:s e', $timestamp);
                }
            }

            if (isset($secure) && $secure) {
                $value .= '; secure';
            }

            if (isset($httponly) && $httponly) {
                $value .= '; HostOnly';
            }

            $result[] = 'Set-Cookie: %s'.$value;
        }

        return implode(PHP_EOL,$result);
    }
}