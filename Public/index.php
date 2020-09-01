<?php
namespace App;

define('APP_DEBUG',  TRUE);
define('APP_FULL_PATH', dirname(__DIR__)."/App");

require '../App/vendor/autoload.php';

use SinglePHP\SinglePHP;
use SinglePHP\Log;
use function SinglePHP\Config;

$conf = array(
    'APP_PATH'          => 'App',          # 业务代码文件夹
    'LOG_LEVEL'         => Log::DEBUG,     # 调试级别
    'USE_SESSION'       => true,           # 是否开启session，默认false

    'PATH_MODE'         => 'PATHINFO',     # 路由方式，支持 NORMAL 和 PATHINFO ，默认NORMAL
    'URL_HTML_SUFFIX'   => '.html',        # url伪静态后缀　(PATHINFO路由)

    'APP_NAMESPACE'     => __NAMESPACE__,  # 业务代码的主命名空间 namespace
    'CTL_NAMESPACE'     => 'Controller',   # 控制器代码的命名空间 namespace

    'DB_DSN'      => "mysql:host=127.0.0.1;port=3306;dbname=test;charset=utf8",           # 数据库DSN
    'DB_OPTIONS'  => array(
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
        \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"),        # 数据库选项
    'DB_USER'     => 'root',         # 数据库用户名
    'DB_PWD'      => 'root',         # 数据库密码
    'TBL_PREFIX'  => 'tbl_',         # 数据库表前缀
);

SinglePHP::getInstance($conf)->run();
