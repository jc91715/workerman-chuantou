<?php
use \Workerman\Worker;
use \Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;

$config = require_once './config.php';


require_once './vendor/autoload.php';

$worker = new Worker('tcp://0.0.0.0:'.$config['server_port']);//中间的通道

$worker->count = 1;

$worker->onWorkerStart = function($worker){  
};

$worker->onConnect = function($connection){
    
};

$worker->onMessage = function  ($connection, $msg){

    if(!isset($connection->is_proxy)){//初始化代理参数
        $connection->is_proxy = false;
        $connection->is_busy = false;
        $connection->proxy_connect = null;
        echo "proxy:{$connection->id} params init success\n";
    }

    if($connection->is_proxy&&$connection->is_busy){//说明正在代理
        echo "proxy:{$connection->id}-{$connection->proxy_connect->id}\n\n";
        $connection->proxy_connect->send($msg);
        return ;
    }
    $secret = substr($msg,0,11);
    global $config;
    if ($secret == $config['secret']) {//说明是客户端注册
        $connection->is_proxy = true; //标记该连接是代理连接
    } else {//用户请求
        if(!$connection->is_proxy&&$connection->proxy_connect){//该用户连接已有代理了直接复用
            $connection->proxy_connect->send($msg);
            return ;
        }
        $proxy= null;//寻找是否有代理连接
        foreach($connection->worker->connections as $proxyConnect)
        {
            if($proxyConnect->is_proxy){
                if(!$proxyConnect->is_busy){
                    $proxyConnect->is_busy = true;
                    $proxy = $proxyConnect;
                    $connection->proxy_connect = $proxy;//将代理连接放到自身中

                    break;
                }else{
                    echo "proxy:{$proxyConnect->id} the proxyConnect is busy\n";
                }
            } 
        }

        if(empty($proxy)){//代理连接用完了，直接404
            $connection->send("HTTP/1.1 404 Connection Established\r\n\r\n");
            $connection->close();
        }else{
            $proxy->proxy_connect = $connection;
            $proxy->send($msg);
            $connection->onClose = function($connection)use($proxy){//用户连接关闭后，空出代理连接
                echo "user connect closed\n";
                $proxy->is_busy = false;
                $proxy->proxy_connect = null;
                $connection->proxy_connect = null;
            };
            // $connection->close();
            $proxy->onBufferFull  = function ($proxy) use ($connection) {
                $connection->pauseRecv();
            };
            $proxy->onBufferDrain = function ($proxy) use ($connection) {
                $connection->resumeRecv();
            };
            $connection->onBufferFull  = function ($connection) use ($proxy) {
                $proxy->pauseRecv();
            };
            $connection->onBufferDrain = function ($connection) use ($proxy) {
                $proxy->resumeRecv();
            };
        }
    }
};
$worker->onClose = function($connection){//代理连接关闭后走这个
    echo "proxy connection closed:{$connection->id}\n";
};
Worker::runAll();