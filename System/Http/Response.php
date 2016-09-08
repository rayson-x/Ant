<?php
namespace Ant\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;

class Response extends Message implements ResponseInterface{
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
    protected $code = 200;

    /**
     * http短语
     *
     * @var string
     */
    protected $responsePhrase = '';

    /**
     * @param int $code
     * @param Header|null $header
     * @param StreamInterface|null $body
     */
    public function __construct($code = 200,Header $header = null,StreamInterface $body = null)
    {
        $this->code = $code;
        $this->headers = $header ? : new Header();
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

        return $this->responsePhrase = StatusPhrase::getStatusPhrase($this->code);
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
        $this->getBody()->write($json = json_encode($data, $encodingOptions));

        if ($json === false) {
            throw new \RuntimeException(json_last_error_msg(), json_last_error());
        }

        $this->withHeader('Content-Type', 'application/json;charset=utf-8');
        if (isset($status)) {
            return $this->withStatus($status);
        }
        return $this;
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

                header(sprintf('%s: %s',strtolower($name),$value));
            }
        }

        //响应body内容
        if(!$this->isEmpty()){
            echo (string) $this->getBody();
        }else{
            echo '';
        }
    }

    //////////////////////////此处借鉴slim框架响应类////////////////////////////

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