<?php
namespace Ant\Http;

/**
 * Class Request
 * @package Ant\Http
 * ���ܸı�ʵ�������з��������뱣֤����ʵ�����ܱ��ı�,ʹ�����Ǳ��ֵ�ǰ��Ϣ���ڲ�״̬,������һ�������ı�״̬��ʵ��.
 */
class Request extends Message implements \Psr\Http\Message\ServerRequestInterface{
    /**
     * @var string ������Դ
     */
    protected $requestTarget;
    /**
     * @var string http ����ʽ
     */
    protected $method;
    /**
     * @var \Psr\Http\Message\UriInterface ʵ��
     */
    protected $uri;
    /**
     * @var array
     */
    protected $serverParams;
    /**
     * @var array cookie����
     */
    protected $cookieParams;
    /**
     * @var array ��ѯ����
     */
    protected $queryParams;
    /**
     * @var array ÿһ��Ԫ�ض�Ϊ\Psr\Http\Message\UploadedFileInterface ʵ��
     */
    protected $uploadFiles;
    /**
     * @var array|object|null �������
     */
    protected $bodyParsers;
    /**
     * @var array ����
     */
    protected $attributes = [];

    public function initialize(){
        $server = $_SERVER;
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = '';
        $cookie = $_COOKIE;
        $files = '';
        $body = '';

        $headers = [];
        foreach ($server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $key           = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$key] = explode(',', $value);
            }
        }

    }

    /**
     * @param array $headers                            http ͷ��Ϣ
     * @param string $method                            http ����ʽ
     * @param \Psr\Http\Message\UriInterface $uri       uri
     * @param array $server                             ����������
     * @param array $cookie                             cookie����
     * @param array $files                              �ϴ��ļ�
     * @param \Psr\Http\Message\StreamInterface $body   http��������
     */
    public function __construct(
        array $headers,
        $method,
        \Psr\Http\Message\UriInterface $uri,
        array $server,
        array $cookie,
        array $files = [],
        \Psr\Http\Message\StreamInterface $body
    ){

    }

    //��ȡ����Ŀ��(��Դ)
    public function getRequestTarget(){

    }

    //����������Դ,�豣�����ݲ�����
    public function withRequestTarget($requestTarget){

    }

    //��ȡ����ʽ
    public function getMethod(){

    }

    //��������ʽ,�豣�����ݲ�����
    public function withMethod($method){

    }

    //��ȡuri,����\Psr\Http\Message\UriInterfaceʵ��
    public function getUri(){

    }

    //����uri,��Ҫ�������ݲ�����
    public function withUri(\Psr\Http\Message\UriInterface $uri,$preserveHost = false){

    }

    //��������������
    public function getServerParams(){

    }

    //����Cookie����,����Array
    public function getCookieParams(){

    }

    //����cookie����,�������ݲ���
    public function withCookieParams(array $cookies){

    }

    //��ȡ��ѯ����(Ҳ����GET),����Array
    public function getQueryParams(){
        //TODO::parse_str
    }

    //��Ӳ�ѯ����,�������ݲ�����
    public function withQueryParams(array $query){

    }

    //��ȡ�ϴ��ļ���Ϣ,�践������
    public function getUploadedFiles(){

    }

    //����ϴ��ļ���Ϣ
    public function withUploadedFiles(array $uploadedFiles){

    }

    //�������������ṩ�κβ���
    public function getParsedBody(){

    }

    //��������ֻ��Ϊnull,����,����,�������ݲ�����
    public function withParsedBody($data){

    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return mixed[] Attributes derived from the request.
     */
    public function getAttributes(){

    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null){

    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return self
     */
    public function withAttribute($name, $value){

    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return self
     */
    public function withoutAttribute($name){

    }

}