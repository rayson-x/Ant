<?php
namespace Ant\Http;

use RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface{

    /**
     * @var array
     */
    protected $file;

    /**
     * @var bool
     */
    protected $moved = false;

    /**
     * @var null
     */
    protected $stream = null;

    /**
     * UploadedFile constructor.
     * @param $file
     */
    public function __construct($file)
    {
        if(!isset($file['tmp_name'])){
            throw new InvalidArgumentException('File is invalid or not upload file via POST');
        }

        $this->file = $file;
    }

    /**
     * 获取文件流
     *
     * @return StreamInterface Stream representation of the uploaded file.
     * @throws RuntimeException in cases when no stream is available.
     * @throws RuntimeException in cases when no stream can be created.
     */
    public function getStream()
    {
        if ($this->moved) {
            throw new RuntimeException('File was moved to other directory');
        }

        if ($this->stream === null) {
            $this->stream = new Stream(fopen($this->file['tmp_name'], 'r'));
        }

        return $this->stream;
    }

    /**
     * 移动文件到指定位置,可以移动到特定流上,第二次无法使用
     *
     * @param string $targetPath Path to which to move the uploaded file.
     * @throws \InvalidArgumentException if the $targetPath specified is invalid.
     * @throws \RuntimeException on any error during the move operation.
     * @throws \RuntimeException on the second or subsequent call to the method.
     */
    public function moveTo($targetPath)
    {
        if($this->moved){
            throw new RuntimeException('File was moved to other directory');
        }

        $targetIsStream = strpos($targetPath, '://') > 0;
        if (!$targetIsStream && !is_writable(dirname($targetPath))){
            throw new InvalidArgumentException('Upload target path is not writable');
        }

        if ($targetIsStream) {
            //处理流
            if (!copy($this->file['tmp_name'], $targetPath)) {
                throw new RuntimeException(sprintf('Error moving uploaded file %1s to %2s', $this->file['name'], $targetPath));
            }
            if (!unlink($this->file['tmp_name'])) {
                throw new RuntimeException(sprintf('Error removing uploaded file %1s', $this->file['name']));
            }
        } elseif ($this->isCgi()) {
            //处理post上传
            if (!is_uploaded_file($this->file['tmp_name'])) {
                throw new RuntimeException(sprintf('%1s is not a valid uploaded file', $this->file['name']));
            }

            if (!move_uploaded_file($this->file['tmp_name'], $targetPath)) {
                throw new RuntimeException(sprintf('Error moving uploaded file %1s to %2s', $this->file['name'], $targetPath));
            }
        } else {
            if (!rename($this->file['tmp_name'], $targetPath)) {
                throw new RuntimeException(sprintf('Error moving uploaded file %1s to %2s', $this->file['name'], $targetPath));
            }
        }

        $this->moved = true;
    }

    /**
     * @return int|null
     */
    public function getSize()
    {
        return isset($this->file['size']) ? $this->file['size'] : null;
    }

    /**
     * @return int One of PHP's UPLOAD_ERR_XXX constants
     */
    public function getError()
    {
        return $this->file['error'];
    }

    /**
     * @return string|null
     */
    public function getClientFilename()
    {
        return isset($this->file['name']) ? $this->file['name'] : null;
    }

    /**
     * @return string|null
     */
    public function getClientMediaType()
    {
        return isset($this->file['type']) ? $this->file['type'] : null;
    }

    /**
     * @return bool
     */
    public function isError(){
        return $this->file['error'] !== UPLOAD_ERR_OK;
    }

    /**
     * @return bool
     */
    public function isCgi(){
        return substr(PHP_SAPI,0,3) === 'cgi';
    }

}