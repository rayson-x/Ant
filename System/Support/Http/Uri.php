<?php
namespace Ant\Support\Http;

use Ant\Http\Uri as RequestUri;

class Uri extends RequestUri
{
    /**
     * @param Environment $server
     * @return static
     */
    public static function createFromEnvironment(Environment $server)
    {
        $scheme = (empty($scheme) || $server->get('HTTPS') === 'off') ? 'http' : 'https';
        $user = $server->get('PHP_AUTH_USER','');
        $password = $server->get('PHP_AUTH_PW','');

        //HTTP_HOST在http1.0下可以返回空
        if($httpHost = $server->get('HTTP_HOST',false)){
            if(strpos($httpHost,':')){
                list($host,$port) = explode(':',$httpHost,2);
                $port = intval($port);
            }else{
                $host = $httpHost;
                $port = null;
            }
        }else{
            $host = $server->get('SERVER_NAME')?:$server->get('SERVER_ADDR');
            $port = $server->get('SERVER_PORT');
        }

        return new static($scheme,$host,$server->get('REQUEST_URI','/'),$port,$user,$password);
    }
}