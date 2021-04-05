<?php

return [
    'secret'=>'abcdefghijk',//连接密钥 11位
    'server_ip'=>'workerman-chuantou.jc91715.top',//配置外网访问的那个连接，有端口的话带端口
    'server_port'=>'8483',//服务器监听端口
    'local_address' => 'jc91715.top:80',//本地代理的地址
    'client_worker_count'=> 5,//本地开启多少个进程
    'client_close_re_connect'=> true,//本地开启断线重连
    'after_re_connect'=> 2//每几秒重连
];