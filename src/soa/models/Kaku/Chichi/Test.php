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