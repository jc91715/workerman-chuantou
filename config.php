<?php

return [
    'secret'=>'abcdefghijk',//连接密钥
    'server_ip'=>'127.0.0.1',//服务器ip
    'server_port'=>'8483',//服务器端口
    'local_address' => 'jc91715.top:80',//本地地址
    'client_worker_count'=> 50,//本地开启多少个进程
    'client_close_re_connect'=> true,//本地开启断线重连
    'after_re_connect'=> 2//每几秒重连
];