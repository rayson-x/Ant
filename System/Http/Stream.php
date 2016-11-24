<?php
namespace Ant\Http;

use RuntimeException;
use UnexpectedValueException;
use Psr\Http\Message\StreamInterface;

/**
 * Class Stream
 * @package Ant\Http
 *
 * @example
 *
 * 必须用stream_copy_to_stream将input流拷贝到另一个流上,不然无法使用fstat函数
 * $stream = fopen('php://temp', 'w+');
 * stream_copy_to_stream(fopen('php://input', 'r'), $stream);
 * rewind($stream);
 * $stream = new Ant\Http\Stream($stream);
 */
class Stream implements StreamInterface
{
    /**
     * @var resource
     */
    protected $stream;

    /**
     * @var bool 是否可以定位
     */
    protected $isSeekable = false;

    /**
     * @var bool 是否可读
     */
    protected $isReadable = false;

    /**
     * @var bool 是否可写
     */
    protected $isWritable = false;

    /**
     * 可用写模式
     *
     * @var array
     */
    protected $readMode = [
        'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
        'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
        'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
        'x+t' => true, 'c+t' => true, 'a+' => true,
    ];

    /**
     * 可用读模式
     *
     * @var array
     */
    protected $writeMode = [
        'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
        'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
        'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
        'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true,
    ];

    /**
     * 处理一个stream资源
     *
     * Stream constructor.
     * @param $stream resource 只接受资源类型
     */
    public function __construct($stream)
    {
        if(!is_resource($stream)){
            throw new UnexpectedValueException(__METHOD__ . ' argument must be a valid PHP resource');
        }
        $this->stream = $stream;

        $meta = $this->getMetadata();
        $mode = $meta['mode'];
        $this->isSeekable = $meta['seekable'];
        $this->isReadable = isset($this->readMode[$mode]) ?: false;
        $this->isWritable = isset($this->writeMode[$mode]) ?: false;
    }


    /**
     * 从stream读取所有数据到一个字符串
     *
     * @return string
     */
    public function __toString()
    {
        if (!$this->isAttached()) {
            return '';
        }

        try {
            $this->rewind();
            return $this->getContents();
        } catch (RuntimeException $e) {
            //如果不可读返回空字符串
            return '';
        }
    }

    /**
     * 关闭stream
     *
     * @return void
     */
    public function close()
    {
        $stream = $this->detach();

        if(is_resource($stream)){
            fclose($stream);
        }
    }

    /**
     * 将stream分离
     *
     * @return null|resource
     */
    public function detach()
    {
        if(!$this->isAttached()){
            return null;
        }

        $oldResource = $this->stream;
        $this->stream = null;
        $this->isSeekable = null;
        $this->isReadable = null;
        $this->isWritable = null;

        return $oldResource;
    }

    /**
     * 获取stream大小
     *
     * @return int|null
     */
    public function getSize()
    {
        if(!$this->isAttached()){
            return null;
        }

        $stat = fstat($this->stream);

        return isset($stat['size']) ? $stat['size'] : null;
    }

    /**
     * 返回stream指针位置
     *
     * @return int
     * @throws \RuntimeException
     */
    public function tell()
    {
        if(($position = ftell($this->stream)) === false){
            throw new RuntimeException('Unable to get position of stream');
        }

        return $position;
    }

    /**
     * 检查是否到到了stream结束位置
     *
     * @return bool
     */
    public function eof()
    {
        //如果不是资源就返回true
        return !$this->isAttached() || feof($this->stream);
    }

    /**
     * 检查是否可以定位
     *
     * @return bool
     */
    public function isSeekable()
    {
        return $this->isSeekable;
    }

    /**
     * 在stream中定位
     *
     * @param int $offset
     * @param int $whence
     * @throws RuntimeException.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if(!$this->isSeekable() || fseek($this->stream,$offset,$whence) === -1){
            throw new RuntimeException('Could not seek in stream');
        }
    }

    /**
     * 是否可读
     *
     * @return bool
     */
    public function isReadable()
    {
        return $this->isReadable;
    }

    /**
     * 读取指定长度数据流
     *
     * @param int $length
     * @return string
     * @throws RuntimeException.
     */
    public function read($length)
    {
        if (!$this->isReadable() || ($data = stream_get_contents($this->stream, $length,$this->tell())) === false){
            throw new RuntimeException('Could not read from stream');
        }

        return $data;
    }

    /**
     * 将stream指针的位置 设置为stream的开头
     *
     * @throws RuntimeException.
     */
    public function rewind()
    {
        if(!$this->isSeekable() || rewind($this->stream) === false){
            throw new RuntimeException('Could not rewind in stream');
        }
    }

    /**
     * 检查是否可写
     *
     * @return bool
     */
    public function isWritable()
    {
        return $this->isWritable;
    }

    /**
     * 写入字符串,返回写入长度
     *
     * @param $content $string
     * @return int
     */
    public function write($content)
    {
        if (null !== $content &&
            !is_string($content) &&
            !is_numeric($content) &&
            !method_exists($content,'__toString'))
        {
            //参数错误
            throw new UnexpectedValueException(
                sprintf('The Response content must be a string or object implementing __toString(), "%s" given.', gettype($content))
            );
        }

        if (!$this->isWritable() || ($written = fwrite($this->stream, $content)) === false) {
            //写入失败
            throw new RuntimeException('Could not write to stream');
        }

        return $written;
    }


    /**
     * 获取剩余数据流
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws RuntimeException if unable to read. (无法读取？为空还是读取失败？)
     */
    public function getContents()
    {
        return $this->read(-1);
    }

    /**
     * 从封装协议文件指针中取得报头/元数据
     *
     * @param null $key
     * @return array|mixed|null
     */
    public function getMetadata($key = null)
    {
        $meta = stream_get_meta_data($this->stream);

        if($key === null){
            return $meta;
        }

        return isset($meta[$key]) ? $meta[$key] : null;
    }

    /**
     * 检查是否是stream
     *
     * @return bool
     */
    public function isAttached()
    {
        return is_resource($this->stream);
    }
}