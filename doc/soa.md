Swoole Framework - SOA  
Tags： Swoole_Framework
------

最近由于业务关系某些模块需要分类，在此之前考虑过使用**鸟哥的Yar**，好处是在写服务端的同时，所做的注释可以在直接访问时，直接已文档形式呈现，而且在windows与Linux都有拓展支持，问题点就是接口文件暴露在外部，只能通过RSA之类加密来解决安全问题。最终选定了Swoole Framework的方案，因为是接口访问，所以在安全问题上可以很容易的使用iptables来解决，而且基于Swoole拓展的强大威力，Swoole的SOA不可小觑，经过半天测试现在简单整理成文章。

###1.下载框架框架
```
git clone https://github.com/swoole/framework.git
```

###2.创建服务端
`./soa_server.php`
```php
<?php
define('DEBUG', 'on');//开启debug模式，方便追踪问题
define("WEBPATH", str_replace("\\","/", __DIR__));//设定app服务器路径
require __DIR__ . '/../framework/libs/lib_config.php';//引用框架相关参数

$AppSvr = new Swoole\Protocol\SOAServer;
$AppSvr->setLogger(new \Swoole\Log\EchoLog(true)); //Logger
$AppSvr->addNameSpace('SW', __DIR__.'/models');//定义导入空间名为SW，读取models下所有文件

Swoole\Error::$echo_html = false;
$server = Swoole\Network\Server::autoCreate('0.0.0.0', 8888);//本地端口
$server->setProtocol($AppSvr);
$server->daemonize(); //作为守护进程
$server->run(array('worker_num' => 4, 'max_request' => 5000));
```
再看看models下的文件
`./models/Kaku/Chichi/Test.php`
```php
<?php
namespace SW\Kaku\Chichi;//注意空间名和路径直接关系，根据功能等划分目录

class Test{

    public function Search($args){
        $sql = "SELECT * FROM user WHERE 1 = 1 ";
        if(!empty($args['host'])){
            $sql .= " AND host = '{$args['host']}'";
        }
        if(!empty($args['user'])){
            $sql .= " AND user = '{$args['user']}'";
        }
    	return \SW\Db::Run($sql);//从swoole挖了一个函数出来，如下
    }

}
```
数据库查询
`./models/Db.php`
```php
<?php
namespace SW;

class Db{

	public function Run($sql){
		$config = array(
			'type'    => \Swoole\Database::TYPE_MYSQLi,
			'host'    => "127.0.0.1",
			'port'    => 3306,
			'dbms'    => 'mysql',
			'engine'  => 'MyISAM',
			'user'    => "root",
			'passwd'  => "",
			'name'    => "mysql",
			'charset' => "utf8",
			'setname' => true,
			);
		$db = new \Swoole\Database($config);
		$db->connect();
		$result = $db->query($sql);
		return $result->fetchall();
	}

}

```
这样服务端就准备完成了，执行以下命令即可运行
`php ./soa_server.php`

###3.客户端
`./soa_client.php`
```php
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
        
    
```
直接访问`./soa_client.php` 就可以看到结果了~

<https://github.com/kakuchichi/swoole_framework/src/soa/>  完整代码