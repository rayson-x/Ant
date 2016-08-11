<?php
namespace Ant\Http;

use \RuntimeException;
use \Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    protected $stream;

    protected $size;

    protected $isSeekable = false;

    protected $isReadable = false;

    protected $isWritable = false;

    //可用写模式
    protected $readMode = [
        'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
        'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
        'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
        'x+t' => true, 'c+t' => true, 'a+' => true,
    ];

    //可用读模式
    protected $writeMode = [
        'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
        'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
        'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
        'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true,
    ];

    public function __construct($stream){
        $this->stream = $stream;

        $meta = $this->getMetadata();
        $mode = $meta['mode'];
        $this->isSeekable = $meta['seekable'];
        $this->isReadable = $this->readMode[$mode] ? true : false;
        $this->isWritable = $this->writeMode[$mode] ? true : false;
    }

    /**
     * 检查是否是stream
     *
     * @return bool
     */
    protected function isAttached()
    {
        return is_resource($this->stream);
    }

    //从头输出一个完整的流
    public function __toString(){

    }

    //关闭流
    public function close(){

    }

    //分离流
    public function detach(){

    }

    /**
     * 获取流大小
     *
     * @return int | null
     */
    public function getSize(){
        if(!$this->isAttached())
            return null;

        $stat = fstat($this->stream);

        return isset($stat['size']) ? $stat['size'] : null;
    }

    /**
     * 返回文件指针位置
     *
     * @return int
     * @throws \Exception
     */
    public function tell(){
        if(!$this->isAttached() || ($position = ftell($this->stream)) === false){
            throw new \Exception('Unable to get position of stream');
        }

        return $position;
    }

    /**
     * 检查是否到到了文件结束位置
     *
     * @return bool
     */
    public function eof(){
        var_dump($this->getMetadata());
//        return $this->isAttached() ? feof($this->stream) : true;
    }

    /**
     * 检查是否可以定位
     *
     * @return bool
     */
    public function isSeekable(){
        return $this->isSeekable;
    }

    //在stream中定位
    public function seek($offset, $whence = SEEK_SET){
//        fseek($this->stream,$offset,$whence);
    }

    //倒回文件指针的位置
    public function rewind(){

    }

    /**
     * 检查是否可写
     *
     * @return bool
     */
    public function isWritable(){
        return $this->isWritable;
    }

    //写入字符串,返回写入长度
    public function write($string){

    }

    /**
     * 是否可读
     *
     * @return bool
     */
    public function isReadable(){
        return $this->isReadable;
    }

    /**
     * 读取指定长度数据流
     *
     * @param int $length
     * @return string
     */
    public function read($length){
        if (!$this->isReadable() || ($data = fread($this->stream, $length)) === false) {
            throw new RuntimeException('Could not read from stream');
        }

        return $data;
    }

    //获取剩余数据流
    public function getContents(){
//        return stream_get_contents($this->stream,5);
    }

    /**
     * 从封装协议文件指针中取得报头／元数据
     *
     * @param null $key
     * @return array|mixed|null
     */
    public function getMetadata($key = null){
        $meta = stream_get_meta_data($this->stream);

        if($key === null){
            return $meta;
        }

        return isset($meta[$key]) ? $meta[$key] : null;
    }
}