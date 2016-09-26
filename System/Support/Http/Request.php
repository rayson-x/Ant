<?php
namespace Ant\Support\Http;

use Ant\Http\RequestBody;
use Ant\Http\Request as HttpRequest;

class Request extends HttpRequest
{
    /**
     * 通过上下文环境创建一个request
     *
     * @param Environment $server
     * @return static
     */
    public static function createRequestFromEnvironment(Environment $server)
    {
        $uri = Uri::createFromEnvironment($server);
        $headers = Header::createFromEnvironment($server);
        $cookieParams = $_COOKIE;
        $serverParams = $server->all();
        $body = new RequestBody();
        $uploadFiles = UploadedFile::parseUploadedFiles($_FILES);

        return new static($uri,$headers,$cookieParams,$serverParams,$body,$uploadFiles);
    }
}