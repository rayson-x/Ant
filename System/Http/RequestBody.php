<?php
namespace Ant\Http;

class RequestBody extends Body
{
    /**
     * 获取请求内容
     */
    public static function createFromCgi()
    {
        $stream = fopen('php://temp','w+');
        stream_copy_to_stream(fopen('php://input','r'),$stream);
        rewind($stream);

        return new static($stream);
    }

    /**
     * 通过Tcp流获取请求内容
     *
     * @param string $data
     * @return static
     */
    public static function createFromTcpStream($data)
    {
        if(!is_string($data)){
            throw new \InvalidArgumentException("");
        }

        $stream = fopen("php://temp","w+");
        fwrite($stream,$data);
        rewind($stream);

        return new static($stream);
    }

    /**
     * 解析表单内容
     *
     * @param string $body              请求主体
     * @param string $bodyBoundary      body分割标示
     * @return array
     */
    public static function parseForm($body,$bodyBoundary)
    {
        //将最后一行分界符剔除
        $body = substr($body, 0 ,strlen($body) - (strlen($bodyBoundary) + 4));
        $bodyParams = [];
        $uploadedFiles = [];
        foreach(explode($bodyBoundary . "\r\n", $body) as $buffer){
            if($buffer === ''){
                continue;
            }

            list($header, $bufferBody) = explode("\r\n\r\n", $buffer, 2);
            $bufferBody = substr($bufferBody, 0, -2);
            foreach (explode("\r\n", $header) as $item) {
                list($name, $value) = explode(": ", $item);
                $name = strtolower($name);
                if($name == 'content-disposition'){
                    if (preg_match('/name=".*?"; filename="(.*?)"$/', $value, $match)) {
                        //Todo::处理文件
                    }elseif(preg_match('/name="(.*?)"$/', $value, $match)) {
                        $bodyParams[$match[1]] = $bufferBody;
                    }
                }
            }
        }

        return [$bodyParams,$uploadedFiles];
    }
}