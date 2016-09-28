<?php
namespace Ant\Support;

use Iterator;

//TODO::待优化,更灵活的设置key值与分隔符
class JsonFileIterator implements Iterator
{
    protected $offset = -1;

    protected $data = [];

    protected $file;

    public function __construct($file)
    {
        if(!file_exists($file) || !is_readable($file)){
            throw new \InvalidArgumentException('The file must exist or be read');
        }

        $this->file = fopen($file,'r');
    }

    public function rewind()
    {
        rewind($this->file);
        $this->next();
    }

    public function valid()
    {
        return !$this->eof();
    }

    public function eof()
    {
        return empty($this->file) || feof($this->file);
    }

    public function next()
    {
        $this->data = json_decode(stream_get_line($this->file,1024,"\n"),true);

        $this->offset = $this->data['method'].$this->data['uri'];
    }

    public function current()
    {
        return $this->data;
    }

    public function key()
    {
        return $this->offset;
    }
}