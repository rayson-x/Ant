<?php
namespace Ant\Foundation\Http\Api;

use Ant\Middleware\Arguments;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Ant\Http\Exception\NotAcceptableException;
use Ant\Foundation\Http\Api\Exception\Handler;
use Ant\Foundation\Http\Api\Decorator\TextRenderer;
use Ant\Foundation\Http\Api\Decorator\RendererFactory;

/**
 * Api中间件
 *
 * Class Middleware
 * @package Ant\Foundation\Http\Api
 */
class Middleware
{
    protected $types = ['text','json','xml','javascript','js'];

    /**
     * 输出装饰与错误处理
     *
     * @param RequestInterface $req
     * @param ResponseInterface $res
     * @return ResponseInterface
     * @throws \Exception
     */
    public function __invoke(RequestInterface $req, ResponseInterface $res)
    {
        if (!$type = $this->getAcceptType($req)) {
            // 期望格式无法响应
            throw new NotAcceptableException(
                sprintf('Response type must be [%s]',implode(',', $this->types))
            );
        }

        try {
            // 获取内层应用程序的返回结果
            $result = yield new Arguments($req, $res);

            if (!$result instanceof ResponseInterface) {
                $res = RendererFactory::create($res, $type)
                    ->setPackage($result)
                    ->decorate();
            }
        } catch (\Exception $e) {
            // 文本格式的错误信息,交给上层应用程序处理
            if ($type == 'text') {
                throw $e;
            }
            // 将错误信息输出为指定格式
            $res = (new Handler())->render($e, $res, $type);
        }

        return $res;
    }

    /**
     * 获取客户端接受的类型
     *
     * @param RequestInterface $req
     * @return false|string
     */
    protected function getAcceptType(RequestInterface &$req)
    {
        $suffix = method_exists($req, "getRouteSuffix")
            ? $req->getRouteSuffix()
            : $this->parseRequestPath($req);

        if (in_array($suffix, $this->types)) {
            return $suffix;
        }

        // 如果客户端没有选择接受类型
        if (!$req->hasHeader('accept')) {
            return $this->types[0];
        }

        // 获取客户端可以接收的数据类型
        foreach ($req->getHeader('accept') as $acceptType) {
            // 服务端可以返回的类型
            foreach ($this->types as $type) {
                if (mb_strpos($acceptType, $type) !== false) {
                    return $type;
                }
            }
        }

        return false;
    }

    /**
     * 解析请求的资源
     *
     * @param RequestInterface $req
     * @return array
     */
    protected function parseRequestPath(RequestInterface &$req)
    {
        $suffix = null;
        // 获取请求资源的路径
        $routeUri = $req->getUri()->getPath();

        // 取得请求资源的格式(后缀)
        if (false !== ($pos = strrpos($routeUri,'.'))) {
            $suffix = substr($routeUri, $pos + 1);
            $routeUri = substr($routeUri, 0, $pos);
        }

        $req = $req->withUri($req->getUri()->withPath($routeUri));
        return $suffix;
    }
}