<?php
namespace Ant\Http;

/**
 * Class Request
 * @package Ant\Http
 * 可能改变实例的所有方法都必须保证请求实例不能被改变,使得它们保持当前消息的内部状态,并返回一个包含改变状态的实例.
 */
class Request extends Message implements \Psr\Http\Message\ServerRequestInterface{

    //获取请求目标(资源)
    public function getRequestTarget(){

    }

    //设置请求资源,需保持数据不变性
    public function withRequestTarget($requestTarget){

    }

    //获取请求方式
    public function getMethod(){

    }

    //设置请求方式,需保持数据不变性
    public function withMethod($method){

    }

    //获取uri,返回\Psr\Http\Message\UriInterface实例
    public function getUri(){

    }

    //设置uri,需要保持数据不变性
    public function withUri(\Psr\Http\Message\UriInterface $uri,$preserveHost = false){

    }

    //检索服务器参数
    public function getServerParams(){

    }

    //检索Cookie参数,返回Array
    public function getCookieParams(){

    }

    //设置cookie参数,保持数据不变
    public function withCookieParams(array $cookies){

    }

    //获取查询参数(也就是GET),返回Array
    public function getQueryParams(){
        //TODO::parse_str
    }

    //添加查询参数,保持数据不变性
    public function withQueryParams(array $query){

    }

    //获取上传文件信息,需返回数组
    public function getUploadedFiles(){

    }

    //添加上传文件信息
    public function withUploadedFiles(array $uploadedFiles){

    }

    //检索请求主体提供任何参数
    public function getParsedBody(){

    }

    //输入数据只能为null,数组,对象,保持数据不变性
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