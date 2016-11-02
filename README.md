# Ant-Framework

Ant框架是一款轻量,快速的php框架

## 依赖
	PHP7.0 +

## 特点
* Middleware 中间件
* PSR-7
* RESTFul
* 依赖注入容器

## 安装
```
git clone git@github.com:sssxxxzzz/Ant.git

cd Ant

composer install
```

## 快速开始
```php
include 'vendor/autoload.php';
/* 初始化框架 */
$app = new Ant\App(realpath(__DIR__));

/* 获取路由器 */
$router = $app['router'];

/* 注册路由 */
$router->get('/',function(){
    return 'Hello World';
});
```