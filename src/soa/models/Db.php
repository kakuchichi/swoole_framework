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