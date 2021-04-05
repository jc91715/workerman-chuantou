<?php
use \Workerman\Worker;
use \Workerman\Connection\AsyncTcpConnection;
require_once './vendor/autoload.php';

$config = require_once './config.php';

$worker = new Worker();

$worker->count = $config['client_worker_count'];
$worker->onWorkerStart = function($worker)
{
    global $config;

    $con = new AsyncTcpConnection('tcp://'.$config['server_ip'].':'.$config['server_port']);//连接服务端
    
    $con->onConnect = function($connection) {
        echo "connection server success\n";
        global $config;
        $connection->send($config['secret']);//注册代理
    };
    $con->onMessage = function($connection, $buffer) {
        // $addr = "docker.for.mac.host.internal";
        global $config;
        $addr =  $config['local_address'];
        echo "$addr\n";
        $buffer = str_replace('Host: '.$config['server_ip'],'Host: '.$addr,$buffer);//将HOST设为目标值
        echo "$buffer\n";

        // echo "$url_data";
        // Async TCP connection.
        $remote_connection = new AsyncTcpConnection("tcp://$addr");
        
        $remote_connection->onConnect = function($remote_connection)use($buffer){
            $remote_connection->send($buffer);
        };
        $remote_connection->onMessage = function($remote_connection,$msg)use($connection){
            $connection->send($msg);
        };
        $remote_connection->onClose = function($remote_connection){
            echo "remote_connection closed\n";
        };
        $remote_connection->onBufferFull  = function ($remote_connection) use ($connection) {
            $connection->pauseRecv();
        };
        $remote_connection->onBufferDrain = function ($remote_connection) use ($connection) {
            $connection->resumeRecv();
        };
        $connection->onBufferFull  = function ($connection) use ($remote_connection) {
            $remote_connection->pauseRecv();
        };
        $connection->onBufferDrain = function ($connection) use ($remote_connection) {
            $remote_connection->resumeRecv();
        };
        $remote_connection->connect();

    };

    $con->onClose = function($con) {
        // 如果连接断开，则在1秒后重连
        global $config;
        echo "connection server closed\n";
        if($config['client_close_re_connect']){//断线重连
            $con->reConnect($config['after_re_connect']);
        }
    };
    $con->connect();
};

Worker::runAll();