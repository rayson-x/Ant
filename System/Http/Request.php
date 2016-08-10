<?php
namespace Ant\Http;

/**
 * Class Request
 * @package Ant\Http
 * ���ܸı�ʵ�������з��������뱣֤����ʵ�����ܱ��ı�,ʹ�����Ǳ��ֵ�ǰ��Ϣ���ڲ�״̬,������һ�������ı�״̬��ʵ��.
 */
class Request extends Message implements \Psr\Http\Message\ServerRequestInterface{

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