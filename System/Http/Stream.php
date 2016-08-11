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

    //����дģʽ
    protected $readMode = [
        'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
        'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
        'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
        'x+t' => true, 'c+t' => true, 'a+' => true,
    ];

    //���ö�ģʽ
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
     * ����Ƿ���stream
     *
     * @return bool
     */
    protected function isAttached()
    {
        return is_resource($this->stream);
    }

    //��ͷ���һ����������
    public function __toString(){

    }

    //�ر���
    public function close(){

    }

    //������
    public function detach(){

    }

    /**
     * ��ȡ����С
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
     * �����ļ�ָ��λ��
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
     * ����Ƿ񵽵����ļ�����λ��
     *
     * @return bool
     */
    public function eof(){
        var_dump($this->getMetadata());
//        return $this->isAttached() ? feof($this->stream) : true;
    }

    /**
     * ����Ƿ���Զ�λ
     *
     * @return bool
     */
    public function isSeekable(){
        return $this->isSeekable;
    }

    //��stream�ж�λ
    public function seek($offset, $whence = SEEK_SET){
//        fseek($this->stream,$offset,$whence);
    }

    //�����ļ�ָ���λ��
    public function rewind(){

    }

    /**
     * ����Ƿ��д
     *
     * @return bool
     */
    public function isWritable(){
        return $this->isWritable;
    }

    //д���ַ���,����д�볤��
    public function write($string){

    }

    /**
     * �Ƿ�ɶ�
     *
     * @return bool
     */
    public function isReadable(){
        return $this->isReadable;
    }

    /**
     * ��ȡָ������������
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

    //��ȡʣ��������
    public function getContents(){
//        return stream_get_contents($this->stream,5);
    }

    /**
     * �ӷ�װЭ���ļ�ָ����ȡ�ñ�ͷ��Ԫ����
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