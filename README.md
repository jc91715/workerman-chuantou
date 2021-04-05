

workerman 极简内网穿透

可通过公网域名访问内网资源

## 使用方法



配置
config.php
```
return [
    'secret'=>'abcdefghijk',//连接密钥 11位
    'server_ip'=>'workerman-chuantou.jc91715.top',//配置外网访问的那个连接，有端口的话带端口。要80端口访问的话，用nginx在做一次代理，看下面例子
    'server_port'=>'8483',//服务器监听端口，换成自己的服务器端口
    'local_address' => '127.0.0.1:80',//换成自己的本地代理的地址
    'client_worker_count'=> 5,//本地开启多少个进程,就有多少个代理连接
    'client_close_re_connect'=> true,//本地开启断线重连
    'after_re_connect'=> 2//每几秒重连
];

```
nginx 80端口访问
```
server {
    listen 80;
    server_name  workerman-chuantou.jc91715.top;
    location / {
      proxy_pass http://workerman-chuantou.jc91715.top:8483;
      proxy_set_header    Host             $host;#保留代理之前的host
      proxy_set_header    X-Real-IP        $remote_addr;#保留代理之前的真实客户端ip
      proxy_set_header    X-Forwarded-For  $proxy_add_x_forwarded_for;
      proxy_set_header    HTTP_X_FORWARDED_FOR $remote_addr;#在多级代理的情况下，记录每次代理之前的客户端真实ip
#      proxy_redirect      default;#指定修改被代理服务器返回的响应头中的location头域跟refresh头域数值

    }
    access_log /var/log/nginx/workerman-chuantou.jc91715.top.access.log;
    error_log  /var/log/nginx/workerman-chuantou.jc91715.top.error.log;
}
```


启动服务端

可以先不加-d 运行，等调试通过，再用-d参数

```
php server.php start -d 
```

启动客户端
可以先不加-d 运行，等调试通过，再用-d参数
```
php client.php start -d 
```

访问 你配置的连接，我配置了一个nginx代理 那么我访问的是[workerman-chuantou.jc91715.top](http://workerman-chuantou.jc91715.top),配置里的 `server_ip` 也应该是 `workerman-chuantou.jc91715.top`





