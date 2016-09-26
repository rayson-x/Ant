<?php
namespace Ant\Support\Http;

use Ant\Http\UploadedFile as RequestUploadFiles;

class UploadedFile extends RequestUploadFiles
{
    /**
     * 加载上传文件,仅限POST上传
     *
     * @param $uploadedFiles
     * @return array
     */
    public static function parseUploadedFiles($uploadedFiles)
    {
        $parsed = [];
        foreach($uploadedFiles as $field => $uploadedFile){
            if(!isset($uploadedFile['error'])){
                continue;
            }

            $parsed[$field] = [];
            if(is_array($uploadedFile['error'])){
                //详见手册 [PHP多文件上传]
                $subArray = [];
                $count = count($uploadedFile['error']);
                $fileKey = array_keys($uploadedFile);
                for($fileIdx = 0;$fileIdx < $count;$fileIdx++){
                    foreach($fileKey as $key){
                        $subArray[$fileIdx][$key] = $uploadedFile[$key][$fileIdx];
                    }
                }
                $parsed[$field] = static::parseUploadedFiles($subArray);
            }else{
                $parsed[$field] = new static($uploadedFile);
            }
        }

        return $parsed;
    }
}