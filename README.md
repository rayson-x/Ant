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

## nginx配置
```
server {
    listen 80;
    server_name ant.com;
    root /path;
    index index.php;s

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
           include fastcgi.conf;
           fastcgi_pass 127.0.0.1:9000;
           fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
    }
}
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

$app->run();
```