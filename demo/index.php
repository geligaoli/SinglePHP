<?php
define('APP_DEBUG',  FALSE);

include '../SinglePHP.class.php';
$config = array(
    'APP_PATH'    => '../App/',      #APP业务代码文件夹
    
    'DB_TYPE'     => 'mysql',        #数据库类型
    'DB_HOST'     => '127.0.0.1',    #数据库主机地址
    'DB_PORT'     => '3306',         #数据库端口，默认为3306
    'DB_USER'     => 'root',         #数据库用户名
    'DB_PWD'      => 'root',         #数据库密码
    'DB_NAME'     => 'test',         #数据库名
    'DB_CHARSET'  => 'utf8',         #数据库编码，默认utf8
    'TBL_PREFIX'  => 'tbl_',         #数据库表前缀

    'PATH_MOD'    => 'PATHINFO',     #路由方式，支持NORMAL和PATHINFO，默认NORMAL
    'USE_SESSION' => true,           #是否开启session，默认false
);
SinglePHP::getInstance($config)->run();
