<?php
namespace Ant\Debug;

use Ant\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;

class ExceptionHandle
{
    /**
     * @param $e
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function render($e,ResponseInterface $response)
    {
        if($e instanceof HttpException){
            $fe = FlattenException::create($e,$e->getStatusCode(),$e->getHeaders());
        }else{
            $fe = FlattenException::create($e);
        }
        //Todo::Debug开关
        $handler = new SymfonyExceptionHandler(true);

        foreach($fe->getHeaders() as $name => $value){
            $response->withAddedHeader($name,$value);
        }

        $response->withStatus($fe->getStatusCode());

        $response->getBody()->write(
            $this->decorate($handler->getContent($fe), $handler->getStylesheet($fe))
        );

        return $response;
    }

    /**
     * 获取响应内容.
     *
     * @param  string  $content
     * @param  string  $css
     * @return string
     */
    protected function decorate($content, $css)
    {
        return <<<EOF
<!DOCTYPE html>
<html>
    <head>
        <meta name="robots" content="noindex,nofollow" />
        <style>
            $css
        </style>
    </head>
    <body>
        $content
    </body>
</html>
EOF;
    }
}