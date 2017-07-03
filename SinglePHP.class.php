<?php
/**
 * 获取和设置配置参数 支持批量定义
 * 如果$key是关联型数组，则会按K-V的形式写入配置
 * 如果$key是数字索引数组，则返回对应的配置数组
 * @param string|array $key 配置变量
 * @param array|null $value 配置值
 * @return array|null
 */
function Config($key, $value=null){
    static $_config = array();
    $args = func_num_args();
    if($args == 1){
        if(is_string($key)){  //如果传入的key是字符串
            return isset($_config[$key])?$_config[$key]:null;
        }
        if(is_array($key)){
            if(array_keys($key) !== range(0, count($key) - 1)){  //如果传入的key是关联数组
                $_config = array_merge($_config, $key);
            }else{
                $ret = array();
                foreach ($key as $k) {
                    $ret[$k] = isset($_config[$k])?$_config[$k]:null;
                }
                return $ret;
            }
        }
    }else{
        if(is_string($key)){
            $_config[$key] = $value;
        }else{
            halt('传入参数不正确');
        }
    }
    return null;
}

/**
 * 调用Widget
 * @param string $name widget名
 * @param array $data 传递给widget的变量列表，key为变量名，value为变量值
 * @return void
 */
function Widget($name, $data = array()){
    $fullName = $name.'Widget';
    if(!class_exists($fullName)){
        halt('Widget '.$name.'不存在');
    }
    $widget = new $fullName();
    $widget->invoke($data);
}

/**
 * 终止程序运行
 * @param string|array $err 终止原因 or Error Array
 * @return void
 */
function halt($err) {
    $e = array();
    if (APP_DEBUG || IS_CLI) {
        if (is_array($err)) {
            $e = $err;
        } else {
            $trace = debug_backtrace();
            $e['message'] = $err;
            $e['file'] = $trace[0]['file'];
            $e['line'] = $trace[0]['line'];
            ob_start();
            debug_print_backtrace();
            $e['trace'] = ob_get_clean();
        }
        
        if (IS_CLI) {
            exit((DIRECTORY_SEPARATOR=='\\' ? iconv('UTF-8', 'gbk', $e['message']) : $e['message'])
                . ' File: ' . $e['file'] . '(' . $e['line'] . ') ' . $e['trace']);
        }
    } else {
        $e['message'] = is_array($err) ? $err['message'] : $err;
    }
    Log::fatal($e['message'].' debug_backtrace:'.$e['trace']);
    
    header("Content-Type:text/html; charset=utf-8");
    echo nl2br(htmlspecialchars(print_r($e, true), ENT_QUOTES)); // . '<pre>' . '</pre>';
    exit;
}

/**
 * 获取数据库实例
 * @return DB
 */
function db(){
    $dbConf = Config(array('DB_TYPE','DB_HOST','DB_PORT','DB_USER','DB_PWD','DB_NAME','DB_CHARSET'));
    return DB::getInstance($dbConf);
}

// /**
//  * 创建Model实例
//  * @param unknown $model
//  */
// function Model($model) {
//     $modelClass = $model.'Model';
//     return new $modelClass($model);
// }

/**
 * 如果文件存在就include进来
 * @param string $path 文件路径
 * @return void
 */
function includeIfExist($path){
    if(file_exists($path)){
        include $path;
    }
}

/**
 * 总控类
 */
class SinglePHP {
    /**
     * 单例
     * @var SinglePHP
     */
    private static $_instance;
    /**
     * 构造函数，初始化配置
     * @param array $conf
     */
    private function __construct($conf){
        Config($conf);
    }
    private function __clone(){}
    /**
     * 获取单例
     * @param array $conf
     * @return SinglePHP
     */
    public static function getInstance($conf){
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self($conf);
        }
        return self::$_instance;
    }
    /**
     * 运行应用实例
     * @access public
     * @return void
     */
    public function run(){
        register_shutdown_function('\SinglePHP::appFatal'); // 错误和异常处理
        set_error_handler('\SinglePHP::appError');
        set_exception_handler('\SinglePHP::appException');
        
        defined('APP_DEBUG') || define('APP_DEBUG',false);
        define('__APP__',PHP_FILE);
        
        define('APP_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), "/"));
        define('IS_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) ? true : false);
        define('IS_CLI',  PHP_SAPI=='cli'? 1 : 0);
        define('SINGLE_PHP_DEFINE', "1");
        define('APP_FULL_PATH', getcwd().'/'.Config('APP_PATH').'/');
        
        date_default_timezone_set("Asia/Shanghai");
        
        if(Config('USE_SESSION') == true) session_start();
        includeIfExist(APP_FULL_PATH.'/common.php');
        $pathMod = Config('PATH_MOD');
        $pathMod = empty($pathMod)?'NORMAL':$pathMod;
        spl_autoload_register(array('SinglePHP', 'autoload'));
        
        if (IS_CLI) {   // 命令行模式
            Config('PATH_MOD', 'PATH_INFO');
            $tmp = parse_url($_SERVER['argv'][1]);
            $_SERVER['PATH_INFO'] = $tmp['path'];
            $tmp = explode('&', $tmp['query']);
            foreach ($tmp as $one) {
                list($k, $v) = explode('=', $one);
                $_GET[$k] = $v;
            }
        }
        if(strcmp(strtoupper($pathMod),'NORMAL') === 0 || !isset($_SERVER['PATH_INFO'])){
            define("MODULE_NAME", isset($_GET['c'])?$_GET['c']:'Index');
            define("ACTION_NAME", isset($_GET['a'])?$_GET['a']:'Index');
        }else{
            $pathInfo = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:'';
            $pathInfoArr = explode('/',trim($pathInfo,'/'));
            define("MODULE_NAME", (isset($pathInfoArr[0]) && $pathInfoArr[0] !== '') ? $pathInfoArr[0]: 'Index');
            define("ACTION_NAME", isset($pathInfoArr[1]) ? $pathInfoArr[1]: 'Index');
        }
        if(!class_exists(MODULE_NAME.'Controller')){
            halt('控制器'.MODULE_NAME.'不存在');
        }
        $controllerClass = MODULE_NAME.'Controller';
        $controller = new $controllerClass();
        if(!method_exists($controller, ACTION_NAME.'Action')){
            halt('方法'.ACTION_NAME.'不存在');
        }
        call_user_func(array($controller, ACTION_NAME.'Action'));
    }

    /**
     * 自动加载函数
     * @param string $class 类名
     */
    public static function autoload($class){
        if(substr($class,-10)=='Controller'){
            includeIfExist(APP_FULL_PATH.'/Controller/'.$class.'.class.php');
        }elseif(substr($class,-5)=='Model'){
            includeIfExist(APP_FULL_PATH.'/Model/'.$class.'.class.php');
        }elseif(substr($class,-6)=='Widget'){
            includeIfExist(APP_FULL_PATH.'/Widget/'.$class.'.class.php');
        }else{
            includeIfExist(APP_FULL_PATH.'/Lib/'.$class.'.class.php');
        }
    }
    // 接受PHP内部回调异常处理
    static function appException($e) {
        $err = array();
        $err['message'] = $e->getMessage();
        $trace = $e->getTrace();
        if ('E' == $trace[0]['function']) {
            $err['file'] = $trace[0]['file'];
            $err['line'] = $trace[0]['line'];
        } else {
            $err['file'] = $e->getFile();
            $err['line'] = $e->getLine();
        }
        $err['trace'] = $e->getTraceAsString();
        halt($err);
    }
    // 自定义错误处理
    static function appError($errno, $errstr, $errfile, $errline) {
        $haltArr = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
        if (in_array($errno, $haltArr)) {
            $errStr = "Errno: $errno $errstr File: $errfile lineno: $errline.";
            halt($errStr);
        }
    }
    // 致命错误捕获
    static function appFatal() {
        $e = error_get_last();
        $haltArr = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
        if ($e && in_array($e['type'], $haltArr)) {
            halt($e);
        }
    }
}

/**
 * 控制器类
 */
class Controller {
    /**
     * 视图实例
     * @var View
     */
    private $_view;

    /**
     * 构造函数，初始化视图实例，调用hook
     */
    public function __construct(){
        $this->_view = new View();
        $this->_init();
    }

    /**
     * 前置hook
     */
    protected function _init(){}
    /**
     * 渲染模板并输出
     * @param null|string $tpl 模板文件路径
     * 参数为相对于App/View/文件的相对路径，不包含后缀名，例如index/index
     * 如果参数为空，则默认使用$controller/$action.php
     * 如果参数不包含"/"，则默认使用$controller/$tpl
     * @return void
     */
    protected function display($tpl=''){
        if($tpl === ''){
            $tpl = MODULE_NAME. '/' . ACTION_NAME;
        }elseif(strpos($tpl, '/') === false){
            $tpl = MODULE_NAME. '/' . $tpl;
        }
        $this->_view->display($tpl);
    }
    /**
     * 为视图引擎设置一个模板变量
     * @param string $name 要在模板中使用的变量名
     * @param mixed $value 模板中该变量名对应的值
     * @return void
     */
    protected function assign($name,$value){
        $this->_view->assign($name,$value);
    }
    /**
     * 将数据用json格式输出至浏览器，并停止执行代码
     * @param array $data 要输出的数据
     */
    protected function ajax($data){
        $jsondata = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Length: '. strlen($jsondata));
        echo ($jsondata);
        exit;
    }
    protected function xml($xmlstr){
        header('Content-Type: text/xml; charset=utf-8');
        header('Content-Length: '. strlen($xmlstr));
        echo ($xmlstr);
        exit;
    }
    /**
     * 重定向至指定url
     * @param string $url 要跳转的url
     * @param void
     */
    protected function redirect($url){
        header("Location: $url");
        exit;
    }
}

/**
 * 视图类
 */
class View {
    /**
     * 视图文件目录
     * @var string
     */
    private $_tplDir;
    /**
     * 编译后模板缓存目录
     * @var string
     */
    private $_tplCacheDir;
    /**
     * 视图文件路径
     * @var string
     */
    private $_viewPath;
    /**
     * 视图变量列表
     * @var array
     */
    private $_data = array();
    /**
     * 给tplInclude用的变量列表
     * @var array
     */
    private static $tmpData;

    /**
     * @param string $tplDir
     */
    public function __construct($tplDir=''){
        if($tplDir == ''){
            $this->_tplDir = './'.Config('APP_PATH').'/View/';
        }else{
            $this->_tplDir = $tplDir;
        }
        $this->_tplCacheDir = './'.Config('APP_PATH').'/Cache/Tpl/';
    }
    /**
     * 为视图引擎设置一个模板变量
     * @param string $key 要在模板中使用的变量名
     * @param mixed $value 模板中该变量名对应的值
     * @return void
     */
    public function assign($key, $value) {
        $this->_data[$key] = $value;
    }
    /**
     * 渲染模板并输出
     * 2017-06-25 加入模板缓存
     * @param null|string $tplFile 模板文件路径，相对于App/View/文件的相对路径，不包含后缀名，例如index/index
     * @return void
     */
    public function display($tplFile) {
        $this->_viewPath = $this->_tplDir . $tplFile . '.php';
        $cacheTplFile = $this->_tplCacheDir . md5($tplFile) . ".php";
        
        if(!is_file($cacheTplFile) || filemtime($this->_viewPath) > filemtime($cacheTplFile))
            file_put_contents($cacheTplFile, $this->compiler($this->_viewPath));
        
        unset($tplFile);
        extract($this->_data);
        #include $this->_viewPath;
        include $cacheTplFile;
    }
    /**
     * 用于在模板文件中包含其他模板
     * @param string $path 相对于View目录的路径
     * @param array $data 传递给子模板的变量列表，key为变量名，value为变量值
     * @return void
     */
    public static function tplInclude($path, $data=array()){
        self::$tmpData = array(
            'path' => APP_FULL_PATH . '/View/' . $path . '.php',
            'data' => $data,
        );
        unset($path);
        unset($data);
        extract(self::$tmpData['data']);
        include self::$tmpData['path'];
    }
    /**
     * 编译模板
     */
    protected function compiler($tplfile) {
        $content = file_get_contents($tplfile);
        // 添加安全代码 代表入口文件进入的
        $content = '<?php if (!defined(\'SINGLE_PHP_DEFINE\')) exit();?>' . $content;
        $content = preg_replace(
            array(
                '/{\$([\w\[\]\'"\$]+)}/s', // 匹配 {$vo['info']}
                '/{\:([^\}]+)}/s', // 匹配 {:func($vo['info'])}
                '/<each[ ]+[\'"](.+)[\'"][ ]*>/', // 匹配 <each "$list as $v"></each>
                '/<if[ ]*[\'"](.+)[\'"][ ]*>/', // 匹配 <if "$key == 1"></if>
                '/<elseif[ ]*[\'"](.+)[\'"][ ]*>/',
            ),
            array(
                '<?php echo $\\1;?>',
                '<?php echo \\1;?>',
                '<?php foreach( \\1 ){ ?>',
                '<?php if( \\1 ){ ?>',
                '<?php }elseif( \\1 ){ ?>',
            ),
            $content);
        $content = str_replace(array('</if>', '<else />', '</each>', 'APP_URL'), array('<?php } ?>', '<?php }else{ ?>', '<?php } ?>', APP_URL), $content);
        // 匹配 <include "Public/Menu"/>
        $content = preg_replace_callback(
            '/<include[ ]+[\'"](.+)[\'"][ ]*\/>/',
            function ($matches) {return $this->compiler(file_get_contents($this->_tplDir . $matches[1]. '.php'));},
            $content);
        return $content;
    }
    
}

/**
 * Widget类
 * 使用时需继承此类，重写invoke方法，并在invoke方法中调用display
 */
class Widget {
    /**
     * 视图实例
     * @var View
     */
    protected $_view;
    /**
     * Widget名
     * @var string
     */
    protected $_widgetName;

    /**
     * 构造函数，初始化视图实例
     */
    public function __construct(){
        $this->_widgetName = get_class($this);
        $dir = APP_FULL_PATH . '/Widget/Tpl/';
        $this->_view = new View($dir);
    }

    /**
     * 处理逻辑
     * @param mixed $data 参数
     */
    public function invoke($data){}

    /**
     * 渲染模板
     * @param string $tpl 模板路径，如果为空则用类名作为模板名
     */
    protected function display($tpl=''){
        if($tpl == ''){
            $tpl = $this->_widgetName;
        }
        $this->_view->display($tpl);
    }

    /**
     * 为视图引擎设置一个模板变量
     * @param string $name 要在模板中使用的变量名
     * @param mixed $value 模板中该变量名对应的值
     * @return void
     */
    protected function assign($name,$value){
        $this->_view->assign($name,$value);
    }
}

/**
 * 数据库操作类
 * 使用方法： 
 *      $db = M();
 *      $db->query('select * from table');
 * 
 * 2015-06-25 数据库操作改为PDO，可以用于php7
 * 或者使用 Medoo，支持多种数据库
 */
class DB {
    /**
     * PDO 设置
     * @var array
     */
    protected $options = array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::MYSQL_ATTR_MULTI_STATEMENTS => false);
    /**
     * 数据库链接
     * @var resource
     */
    private $_db;
    /**
     * 保存最后一条sql
     * @var string
     */
    private $_lastSql;
    /**
     * 实例数组
     * @var array
     */
    private static $_instance = array();
    /**
     * 构造函数
     * @param array $dbConf 配置数组
     */
    private function __construct($dbConf){
        if(empty($dbConf['DB_CHARSET'])){
            $dbConf['DB_CHARSET'] = 'utf8';
        }
        $this->options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '" . Config("DB_CHARSET") . "'";
        $dsn = Config("DB_TYPE").":host=".Config("DB_HOST").";port=".Config("DB_PORT").
            ";dbname=".Config("DB_NAME").";charset=".Config("DB_CHARSET");
        
        try {
            $this->_db = new \PDO($dsn, Config("DB_USER"), Config("DB_PWD"), $this->options) or die('数据库连接创建失败');
        } catch (\PDOException $e) {    // 避免泄露密码等
            throw new \ExtException($e->getMessage());
        }
    }
    /**
     * 获取DB类
     * @param array $dbConf 配置数组
     * @return DB
     */
    static public function getInstance($dbConf){
        if(!isset($dbConf['DB_PORT'])){
            $dbConf['DB_PORT'] = '3306';
        }
        $key = $dbConf['DB_HOST'].':'.$dbConf['DB_PORT'];
        if(!isset(self::$_instance[$key]) || !(self::$_instance[$key] instanceof self)){
            self::$_instance[$key] = new self($dbConf);
        }
        return self::$_instance[$key];
    }
    public function beginTransaction() {$this->_db->beginTransaction();}
    public function commit() {$this->_db->commit();}
    public function rollBack() {$this->_db->rollBack();}
    /**
     * 转义字符串
     * @param string $str 要转义的字符串
     * @return string 转义后的字符串
     */
    public function escape($str) {return $this->_db->quote($str);}
    public function close() {$this->_db= NULL;}

    public function select($sql, $bind=array()) {return $this->execute($sql, $bind, 'select');}
    public function insert($sql, $bind=array()) {return $this->execute($sql, $bind, 'insert');}
    public function update($sql, $bind=array()) {return $this->execute($sql, $bind, 'update');}
    public function delete($sql, $bind=array()) {return $this->execute($sql, $bind, 'delete');}
    
    /**
     * 执行sql语句
     * @param string $sql 要执行的sql
     * @param array $bind 执行中的参数
     * @return bool|int|array 执行成功返回数组、数量、自增id，失败返回false
     */
    private function execute($sql, $bind=array(), $flag = '') {
        $this->_lastSql = $sql;
        try {
            $pre = $this->_db->prepare($sql);
            if (!$pre) {
                $this->error($this->_db, $sql);
            }
            foreach ($bind as $k => $v) {
                $pre->bindValue($k, $v);
            }
            $re = $pre->execute();
            if (!$re) {
                $this->error($pre, $sql);
            }
            switch ($flag) {
                case 'insert':return $this->_db->lastInsertId();
                break;
                case 'update':return $pre->rowCount();
                break;
                case 'delete':return $pre->rowCount();
                break;
                case 'select':return $pre->fetchAll(\PDO::FETCH_ASSOC);
                break;
                default:break;
            }
        } catch (\PDOException $e) {
            $this->error($e, $sql);
        }
    }
    private function error($e, $sql) {
        throw new \ExtException(implode(', ', $e->errorInfo) . "\n[SQL]：" . $sql);
    }
    function __destruct() {
        $this->_db = null;
    }
    /**
     * 获取上一次查询的sql
     * @return string sql
     */
    public function getLastSql(){
        return $this->_lastSql;
    }
}

/**
 * 数据库模型
 * $model = new UserModel("tablename");
 * $model->where("sqlwhere conditon", array(vvv))->find();
 * @author
 */
class Model{
    protected $_db = null;          // 数据库连接
    protected $_table = '';         // 表名
    protected $_pk = '';            // 主键名
    protected $_where = '';         // where语句
    protected $_bind = array();     // 参数数组
    protected $_order = '';
    protected $_dbname = '';
    
    function __construct($tbl_name='', $db_name='', $pk="id") {
        $this->_initialize();
        if(empty($this->_table)) {
            $this->_table = (empty($db_name) ? "" : $db_name.'.') . Config("TBL_PREFIX") . $tbl_name;
        } else {
            $this->_table = $this->_dbname .'.'. Config("TBL_PREFIX") . $this->_table;
        }
        if(empty($this->_pk)) $this->_pk = $pk;
        $this->_db = db();
    }
    // 回调方法 初始化模型
    protected function _initialize() {}
    /**
     * where条件
     * @param string $sqlwhere     sql条件
     * @param array  $bind         参数数组
     * @return Model
     */
    public function where($sqlwhere, $bind=array()) {
        if (is_array($sqlwhere)) {
            $item = array();
            $this->_bind = array();
            foreach ($sqlwhere as $k => $v) {
                if (is_array($v)) {
                    $exp = strtoupper($v[0]); //  in like
                    if (preg_match('/^(NOT IN|IN)$/', $exp)) {
                        if (is_string($v[1])) $v[1] = explode(',', $v[1]);
                        $vals = implode(',', $this->_db->quote($v[1]));
                        $item[] = "$k $exp ($vals)";
                    } elseif (preg_match('/^(=|!=|<|<>|<=|>|>=)$/', $exp)) {
                        $k1 = count($this->_bind);
                        $item[] = "$k $exp :$k1";
                        $this->_bind[":$k1"] = $v[1];
                    } elseif (preg_match('/^(BETWEEN|NOT BETWEEN)$/', $exp)) {
                        $tmp = is_string($v[1]) ? explode(',', $v[1]) : $v[1];
                        $k1 = count($this->_bind);
                        $k2 = $k1 + 1;
                        $item[] = "($k $exp :$k1 AND :$k2)";
                        $this->_bind[":$k1"] = $tmp[0];
                        $this->_bind[":$k2"] = $tmp[1];
                    } elseif (preg_match('/^(LIKE|NOT LIKE)$/', $exp)) {
                        $wyk = ':' . count($this->_bind);
                        $item[] = "$k $exp $wyk";
                        $this->_bind[$wyk] = $v[1];
                    } else {
                        throw new \Exception("exp error", 1);
                    }
                } else {
                    $wyk = ':' . count($this->_bind);
                    $item[] = "$k=$wyk";
                    $this->_bind[$wyk] = $v;
                }
            }
            $this->_where = '(' . implode(" AND ", $item) . ')';
        } else {
            $this->_where = $sqlwhere;
            $this->_bind = $bind;
        }
        return $this;
    }
    public function order($order) {
        $this->_order = $order;
        return $this;
    }
    /** 获取一条记录
     * @param unknown $id
     * @return boolean|array
     */
    public function find($id=null) {
        if ($id != null)
            $this->where($this->_pk."=?", array($id));
        $info = $this->select();
        return count($info)>0 ? $info[0] : $info;
    }
    /** 获取多条记录
     * @return boolean|array
     */
    public function select() {
        $_sql = 'SELECT * FROM ' . $this->_table ." WHERE ". $this->_where;
        if (! empty($this->_order)) $_sql .= " ORDER BY ". $this->_order;
        $info = $this->_db->select($_sql, $this->_bind);
        $this->clean();
        return $info;
    }
    /** 更新数据
     * @param array $data
     * @return boolean|number
     */
    public function update($data) {
        if (isset($data[$this->_pk])) {
            $this->where($this->_pk."=:".$this->_pk, array(":".$this->_pk => $data[$this->_pk]));
            unset($data[$this->_pk]);
        }
        if (empty($this->_where)) {
            return false;
        }
        $keys = ''; $_bind = array();
        foreach ($data as $k => $v) {
            $keys .= "$k=:$k,";
            $_bind[":$k"] = $v;
        }
        $keys = substr($keys, 0, -1);
        $this->_bind = array_merge($this->_bind, $_bind);
            
        $_sql = 'UPDATE ' . $this->_table . " SET {$keys} WHERE ". $this->_where;
        $info = $this->_db->update($_sql, $this->_bind);
        $this->clean();
        return $info;
    }
    /** 删除数据
     * @param unknown $id
     * @return boolean|number
     */
    public function delete($id=null) {
        if ($id != null)
            $this->where($this->_pk."=?", array($id));
        $_sql = 'DELETE FROM ' . $this->_table ." WHERE ". $this->_where;
        $info = $this->_db->delete($_sql, $this->_bind);
        $this->clean();
        return $info;
    }
    /** 插入数组，字段名=>值
     * @param array $data
     * @return boolean|number
     */
    public function insert($data) {
        $keys = ''; $vals = ''; $_bind = array();
        foreach ($data as $k => $v) {
            if (is_null($v)) continue;
            $keys .= "$k,";
            $vals .= ":$k,";
            $_bind[":$k"] = $v;
        }
        $keys = substr($keys, 0, -1);
        $vals = substr($vals, 0, -1);
        $_sql = 'INSERT INTO ' . $this->_table . " ($keys) VALUES ($vals)";
        return $this->_db->insert($_sql, $_bind);
    }
    private function clean() {
        $this->_where = "";
        $this->_bind = array();
        $this->_order = "";
    }
}

/**
 * 日志类
 * 使用方法：Log::fatal('error msg');
 * 保存路径为 App/Log，按天存放
 * fatal和warning会记录在.log.wf文件中
 */
class Log{
    /**
     * 打日志，支持SAE环境
     * @param string $msg 日志内容
     * @param string $level 日志等级
     * @param bool $wf 是否为错误日志
     */
    protected static function write($msg, $level='DEBUG', $wf=false){
        if($wf || (null != Config('LOG_LEVEL') && in_array($level, Config('LOG_LEVEL')))) {
            if(function_exists('sae_debug')){ //如果是SAE，则使用sae_debug函数打日志
                $msg = "[{$level}]".$msg;
                sae_set_display_errors(false);
                sae_debug(trim($msg));
                sae_set_display_errors(true);
            }else{
                $msg = date('[ Y-m-d H:i:s ]')." [{$level}] ".$msg."\r\n";
                $logPath = APP_FULL_PATH.'/Log/'.date('Ymd').'.log';
                if($wf){
                    $logPath .= '.wf';
                }
                file_put_contents($logPath, $msg, FILE_APPEND);
            }
        }
    }
    /**
     * 打印fatal日志
     * @param string $msg 日志信息
     */
    public static function fatal($msg){
        self::write($msg, 'FATAL', true);
    }
    /**
     * 打印fatal日志
     * @param string $msg 日志信息
     */
    public static function error($msg){
        self::write($msg, 'ERROR', true);
    }
    /**
     * 打印warning日志
     * @param string $msg 日志信息
     */
    public static function warn($msg){
        self::write($msg, 'WARN', true);
    }
    /**
     * 打印notice日志
     * @param string $msg 日志信息
     */
    public static function notice($msg){
        self::write($msg, 'NOTICE');
    }
    /**
     * 打印debug日志
     * @param string $msg 日志信息
     */
    public static function debug($msg){
        self::write($msg, 'DEBUG');
    }
}

/**
 * ExtException类，记录额外的异常信息
 */
class ExtException extends Exception{
    /**
     * @var array
     */
    protected $extra;

    /**
     * @param string $message
     * @param array $extra
     * @param int $code
     * @param null $previous
     */
    public function __construct($message = "", $extra = array(), $code = 0, $previous = null){
        $this->extra = $extra;
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取额外的异常信息
     * @return array
     */
    public function getExtra(){
        return $this->extra;
    }
}
