<?php
    define('DEBUG', 'off');
    define("WEBPATH", str_replace("\\","/", __DIR__));
    require __DIR__ . '/../framework/libs/lib_config.php';
    $cloud = new \Swoole\Client\SOA;
    $cloud->addServers(array('0.0.0.0:8888'));
    $args = array(
            'host' => 'localhost',
            'user' => NULL,//为空时需定义，否则会提示错误
        );
    $ret1 = $cloud->task("SW\\Kaku\\Chichi\\Test::Search", $args);
    $ret2 = $cloud->task("SW\\Kaku\\Chichi\\Test::Search", $args);
    $n = $cloud->wait(0.5);//执行并设置500ms超时
    if($n === 2){
        var_dump($ret1->data,$ret2->data['data']);//全部成功后输出
    }
    else{
        exit();
    }
