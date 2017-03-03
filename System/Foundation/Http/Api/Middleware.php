<?php
namespace Ant\Foundation\Http\Api;

use Ant\Http\Request;
use Ant\Http\Response;
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
     * @param Request $req
     * @param Response $res
     * @return Response|\Psr\Http\Message\MessageInterface
     * @throws \Exception
     */
    public function __invoke(Request $req, Response $res)
    {
        if (!$type = $req->accepts(...$this->types)) {
            // 期望格式无法响应
            throw new NotAcceptableException(
                sprintf('Response type must be [%s]',implode(',', $this->types))
            );
        }

        // 获取装饰器
        $renderer = RendererFactory::create($type);

        try {
            // 获取内层应用程序的返回结果
            $result = yield;

            if (!$result instanceof Response) {
                $res = $renderer
                    ->setPackage($result)
                    ->decorate($res);
            }
        } catch (\Exception $e) {
            // 文本格式的错误信息,交给上层应用程序处理
            if ($renderer instanceof TextRenderer) {
                throw $e;
            }
            // 将错误信息输出为指定格式
            $res = (new Handler())->render($e, $res, $renderer);
        }

        return $res;
    }
}