<?php
namespace Ant\Debug;

use Exception;
use Ant\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;

/**
 * Class ExceptionHandle
 * @package Ant\Debug
 */
class ExceptionHandle
{
    /**
     * @param Exception $exeption
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function render(Exception $exeption, ResponseInterface $response, $debug = true)
    {
        if($exeption instanceof HttpException){
            $fe = FlattenException::create($exeption,$exeption->getStatusCode(),$exeption->getHeaders());
        }else{
            $fe = FlattenException::create($exeption);
        }

        $handler = new SymfonyExceptionHandler($debug);

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