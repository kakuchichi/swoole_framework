<?php
define('DEBUG', 'on');//开启debug模式，方便追踪问题
define("WEBPATH", str_replace("\\","/", __DIR__));//设定app服务器路径
require __DIR__ . '/../framework/libs/lib_config.php';//引用框架相关参数
$AppSvr = new Swoole\Protocol\SOAServer;
$AppSvr->setLogger(new \Swoole\Log\EchoLog(true)); //Logger
$AppSvr->addNameSpace('SW', __DIR__.'/models');//定义导入空间名为Kaku，读取models下所有文件
Swoole\Error::$echo_html = false;
$server = Swoole\Network\Server::autoCreate('0.0.0.0', 8888);//本地端口
$server->setProtocol($AppSvr);
//$server->daemonize(); //作为守护进程
$server->run(array('worker_num' => 4, 'max_request' => 5000));