# swoole framework SOA 服务端详解

Tags： Swoole_Framework/soa

---

swoole framework中实现SOA简单说就是通过对指定文件夹内文件进行载入，通过命名空间与call_user_func函数实现，客户端与服务器之间通信基于swoole拓展的server与client实现。框架中websocket,app server等个别配置同理。

###创建服务端
`./soa_server.php`
```php
<?php
define('DEBUG', 'on');//开启debug模式，方便追踪问题
define("WEBPATH", str_replace("\\","/", __DIR__));//设定app服务器路径
require __DIR__ . '/../framework/libs/lib_config.php';//引用框架相关参数

$AppSvr = new Swoole\Protocol\SOAServer;
$AppSvr->setLogger(new \Swoole\Log\EchoLog(true)); //Logger
$AppSvr->addNameSpace('SW', __DIR__.'/models');//定义导入空间名为SW，载入models下所有文件

Swoole\Error::$echo_html = false;
$server = Swoole\Network\Server::autoCreate('0.0.0.0', 8888);//本地端口
$server->setProtocol($AppSvr);
$server->daemonize(); //作为守护进程
$server->run(array('worker_num' => 4, 'max_request' => 5000));
```
###1.应用服务
```
$AppSvr = new Swoole\Protocol\SOAServer;
$AppSvr->setLogger(new \Swoole\Log\EchoLog(true)); //Logger
$AppSvr->addNameSpace('SW', __DIR__.'/models');//定义导入空间名为SW，读取models下所有文件
```
SOAServer类结构，其中几个主要函数
```
<?php
namespace Swoole\Protocol;

use Swoole;
/**
 * Class Server
 * @package Swoole\Network
 */
class SOAServer extends Base implements Swoole\IFace\Protocol
{
    //检验数据是否合法
    function _packetReform($data)
    {
    }
    //结合_packetReform与task验证包头尾后执行，将结果send回客户端
    function onReceive($serv, $fd, $from_id, $data)
    {
        //self::STX self::ETX为自定义协议头尾标示
        $this->server->send($fd, pack('n', self::STX).serialize($retData).pack('n', self::ETX));
    }
    //将指定地址中所以文件自动载入
    function addNameSpace($name, $path)
    {
        Swoole\Loader::setRootNS($name, $path);
    }
    //执行客户端发来的函数请求
    function task($client_id, $data)
    {
        $ret = call_user_func($request['call'], $request['params']);
    }
}
```
再看看怎么将指定目录载入的
```
<?php
namespace Swoole;

/**
 * Swoole库加载器
 * @author Tianfeng.Han
 * @package SwooleSystem
 * @subpackage base
 *
 */
class Loader
{
	/**
	 * 自动载入类
	 * @param $class
	 */
	static function autoload($class)
	{
		$root = explode('\\', trim($class, '\\'), 2);
		if (count($root) > 1 and isset(self::$nsPath[$root[0]]))
		{
            include self::$nsPath[$root[0]].'/'.str_replace('\\', '/', $root[1]).'.php';
		}
	}
	/**
	 * 设置根命名空间
	 * @param $root
	 * @param $path
	 */
	static function setRootNS($root, $path)
	{
		self::$nsPath[$root] = $path;
	}
}
```

以上应用服务就配置好了
###2.服务端配置
```
$server = Swoole\Network\Server::autoCreate('0.0.0.0', 8888);//本地端口
$server->setProtocol($AppSvr);
$server->daemonize(); //作为守护进程
$server->run(array('worker_num' => 4, 'max_request' => 5000));
```
主要函数
```
<?php
namespace Swoole\Network;
use Swoole;

/**
 * Class Server
 * @package Swoole\Network
 */
class Server extends Swoole\Server implements Swoole\Server\Driver
{
    //服务器监听指定端口，如果没有实例化，重新将self重新建立实例，应该是为了综合其他调用方法这么设计的，默认使用swoole扩展,其次是libevent,最后是select(支持windows)
    static function autoCreate($host, $port, $ssl = false)
    {
        if (class_exists('\\swoole_server', false))
        {
            return new self($host, $port, $ssl);
        }
        elseif (function_exists('event_base_new'))
        {
            return new EventTCP($host, $port, $ssl);
        }
        else
        {
            return new SelectTCP($host, $port, $ssl);
        }
    }

    function __construct($host, $port, $ssl = false)
    {
        $this->sw = new \swoole_server($host, $port, self::$sw_mode, $flag);
    }
    //进程守护
    function daemonize()
    {
        $this->swooleSetting['daemonize'] = 1;
    }
    //将相应函数加入指定swoole_server函数中，$this->protocol是由implements实现接口的，即将之前AppSvr中的函数引用至此，见下一段代码
    function run($setting = array())
    {
        //合并设置项目
        $this->swooleSetting = array_merge($this->swooleSetting, $setting);
        if (!empty($this->swooleSetting['pid_file']))
        {
            $this->pid_file = $this->swooleSetting['pid_file'];
        }
        //全部设置项目进行配置服务端
        $this->sw->set($this->swooleSetting);
        $this->sw->on('Start', array($this, 'onMasterStart'));
        $this->sw->on('ManagerStop', array($this, 'onManagerStop'));
        $this->sw->on('WorkerStart', array($this->protocol, 'onStart'));
        $this->sw->on('Connect', array($this->protocol, 'onConnect'));
        $this->sw->on('Receive', array($this->protocol, 'onReceive'));
        $this->sw->on('Close', array($this->protocol, 'onClose'));
        $this->sw->on('WorkerStop', array($this->protocol, 'onShutdown'));
    }
```
`setProtocol()函数`在此
```
<?php
namespace Swoole;
use Swoole;

abstract class Server implements Server\Driver
{

	/**
	 * 应用协议
     * @param $protocol Swoole\Protocol\Base
	 * @return null
	 */
	function setProtocol($protocol)
	{
        if (!($protocol instanceof Swoole\IFace\Protocol))
        {
             throw new \Exception("The protocol is not instanceof \\Swoole\\IFace\\Protocol");
        }
		$this->protocol = $protocol;
        $protocol->server = $this;
	}

}
```
所以
```php
<?php
define('DEBUG', 'on');//开启debug模式，方便追踪问题
define("WEBPATH", str_replace("\\","/", __DIR__));//设定app服务器路径
require __DIR__ . '/../framework/libs/lib_config.php';//引用框架相关参数
//实例应用服务
$AppSvr = new Swoole\Protocol\SOAServer;
$AppSvr->setLogger(new \Swoole\Log\EchoLog(true)); //Logger
//定义导入空间名为SW，载入models下所有文件
$AppSvr->addNameSpace('SW', __DIR__.'/models');

Swoole\Error::$echo_html = false;
//实例swoole_server,优先swoole拓展
$server = Swoole\Network\Server::autoCreate('0.0.0.0', 8888);//本地端口
//server载入AppSvr相关函数
$server->setProtocol($AppSvr);
//作为守护进程
$server->daemonize(); 
//配置额外设置项后运行服务端
$server->run(array('worker_num' => 4, 'max_request' => 5000));
```
这么一个流程，服务端就运行好了。



