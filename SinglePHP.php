<?php
/**
 * SinglePHP-Ex 单php文件精简框架。
 * https://github.com/geligaoli/SinglePHP-Ex
 * @author geligaoli
 * @version 2020-09-01
 */
namespace SinglePHP;
/**
 * 获取和设置配置参数 支持批量定义
 * 如果$key是关联型数组，则会按K-V的形式写入配置
 * 如果$key是数字索引数组，则返回对应的配置数组
 * @param string|array $key 配置变量
 * @param array|null $value 配置值
 * @return array|null
 */
function Config($key, $value=null) {
    static $_config = array();
    if(func_num_args() == 1) {
        if(is_string($key))     //如果传入的key是字符串
            return value($_config, $key);
        if(is_array($key)) {
            if(array_keys($key) !== range(0, count($key) - 1))      //如果传入的key是关联数组
                $_config = array_merge($_config, $key);
            else {
                $ret = array();
                foreach ($key as $k)
                    $ret[$k] = value($_config, $k);
                return $ret;
            }
        }
    } else {
        if(is_string($key))
            $_config[$key] = $value;
        else
            Halt('传入参数不正确');
    }
    return null;
}
/**
 * 按配置生成url
 * @param string $ModuleAction
 * @param array $param
 * @return string
 */
function Url($ModuleAction, $param=array()) {
    if (strcasecmp(Config('PATH_MODE'),'NORMAL') === 0) {
        $pathInfoArr = explode('/',trim($ModuleAction,'/'));
        $moduleName = valempty($pathInfoArr, 0, 'Index');
        $actionName = valempty($pathInfoArr, 1, 'Index');
        $url = 'c='.$moduleName.'&a='.$actionName;
        if (is_string($param))
            $url .= '&' . $param;
        else {
            foreach($param as $k => $v)
                $url .= '&'. $k .'='. urlencode($v);
        }
        return $_SERVER['SCRIPT_NAME'] .'?'. $url;
    } else { // pathinfo
        $url = trim($ModuleAction, '/');
        if (is_string($param))
            $url .= '/' . strtr($param, '&=', '//');
        else {
            foreach($param as $k => $v)
                $url .= '/'. $k .'/'. urlencode($v);
        }
        return APP_URL .'/'. $url .'.'. ltrim(Config("URL_HTML_SUFFIX"), '.');
    }
}
/**
 * 终止程序运行
 * @param string|array|Error $err 终止原因 or Error Array
 */
function Halt($err) {
    $e = array();
    if (is_array($err))
        $e = $err;
    elseif (is_string($err))
        $e['message'] = $err;
    else {
        $e['message'] = $err->getMessage();
        $e['file'] = $err->getFile();
        $e['line'] = $err->getLine();
        $e['trace'] = $err->getTraceAsString();
    }
    Log::fatal($e['message'].' debug_trace:'. $e['trace']);

    if (IS_CLI) exit ($e['message'] . ' File: ' . $e['file'] . '(' . $e['line'] . ') ' . $e['trace']);
    if (!APP_DEBUG) $e = $e['message'];

    header("Content-Type: text/html; charset=utf-8");
    exit (var_dump($e)); // . '<pre>' . '</pre>';
}
/**
 * 获取数据库实例。多数据库可仿照建立
 * @return DB
 * @throws \Exception
 */
function db() {
    $dbConf = Config(array('DB_DSN','DB_USER','DB_PWD','DB_OPTIONS','TBL_PREFIX'));
    return DB::getInstance($dbConf);
}
/**
 * 如果文件存在就include进来
 * @param string $file 文件路径
 */
function includeIfExist($file) {
    if(file_exists($file))
        include_once $file;
}
function sp_output($data, $type) {
    header('Content-Type: '.$type.'; charset=utf-8');
    header('Content-Length: '. strlen($data));
    exit ($data);
}
function sp_tojson($data) {
    return is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
}
function value($array, $key, $default=null) {
    return $array!=null && isset($array[$key]) ? $array[$key] : $default;
}
function valempty($array, $key, $default=null) {
    return $array!=null && isset($array[$key]) && !empty($array[$key]) ? $array[$key] : $default;
}

/**
 * 总控类
 */
class SinglePHP {
    private static $_instance = null;               // 单例
    private static $appNamespace = 'App';           // 应用的主namespace
    private static $ctlNamespace = 'Controller';    // 控制器的namespace
    /**
     * 构造函数，初始化配置
     * @param array $conf
     */
    private function __construct($conf) {
        Config($conf);
        if (isset($conf["APP_NAMESPACE"])) self::$appNamespace = $conf["APP_NAMESPACE"];
        if (isset($conf["CTL_NAMESPACE"])) self::$ctlNamespace = $conf["CTL_NAMESPACE"];
    }
    /**
     * 获取单例
     * @param array $conf
     * @return SinglePHP
     */
    public static function getInstance($conf) {
        if(self::$_instance == null)
            self::$_instance = new self($conf);
        return self::$_instance;
    }
    /**
     * 运行应用实例
     * @access public
     * @return void
     */
    public function run() {
        register_shutdown_function(array('SinglePHP\SinglePHP', 'appFatal')); // 错误和异常处理
        set_error_handler(array('SinglePHP\SinglePHP', 'appError'));
        set_exception_handler(array('SinglePHP\SinglePHP', 'appException'));
        date_default_timezone_set("Asia/Shanghai");

        defined('APP_DEBUG') || define('APP_DEBUG',false);
        defined('APP_FULL_PATH') || define('APP_FULL_PATH', __DIR__ . DIRECTORY_SEPARATOR . Config('APP_PATH'));
        define('APP_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), "/"));
        define('IS_AJAX', (strtolower(value($_SERVER,'HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest') ? true : false);
        define('IS_CLI',  PHP_SAPI=='cli'? 1 : 0);

        if(Config('USE_SESSION') == true) \session_start();
        includeIfExist(APP_FULL_PATH.'/Functions.php');
        //spl_autoload_register(array('SinglePHP\SinglePHP', 'autoload'));

        if (IS_CLI) {   // 命令行模式
            Config('PATH_MODE', 'PATH_INFO');
            $tmp = parse_url($_SERVER['argv'][1]);
            $_SERVER['PATH_INFO'] = $tmp['path'];
            $tmp = explode('&', $tmp['query']);
            foreach ($tmp as $one) {
                list($k, $v) = explode('=', $one);
                $_GET[$k] = $v;
            }
        }
        // 路径解析
        $pathInfo = value($_SERVER, 'PATH_INFO', '');
        if ($pathInfo === '') {
            $moduleName = value($_GET, 'c', 'Index');
            $actionName = value($_GET, 'a', 'Index');
        } else {
            $pathInfo = preg_replace('/\.(' . ltrim(Config("URL_HTML_SUFFIX"), '.') . ')$/i', '', $pathInfo);
            $pathInfoArr = explode('/',trim($pathInfo,'/'));
            $moduleName = valempty($pathInfoArr, 0, 'Index');
            $actionName = $this->parseActionName($pathInfoArr);
        }
        $this->callActionMethod($moduleName, $actionName);
    }
    /**
     * 解析Action名及QS参数
     * @param array $pathInfoArr
     * @return string
     */
    protected function parseActionName($pathInfoArr) {
        $actionName = valempty($pathInfoArr, 1, 'Index');
        $queryParam = array();
        for ($idx=2; $idx<count($pathInfoArr); $idx++,$idx++)
            $queryParam[$pathInfoArr[$idx]] = value($pathInfoArr, $idx+1, '');
        $_GET = array_merge($_GET, $queryParam);
        $_REQUEST = array_merge($_REQUEST, $queryParam);
        return $actionName;
    }
    /**
     * 解析执行模块及方法
     * @param string $moduleName
     * @param string $actionName
     */
    protected function callActionMethod($moduleName, $actionName) {
        define("MODULE_NAME", $moduleName);

        $controllerClass = "\\".self::$appNamespace."\\".self::$ctlNamespace."\\".MODULE_NAME.'Controller';
        if(!class_exists($controllerClass) && !preg_match('/^[A-Za-z][\w|\.]*$/', MODULE_NAME))
            Halt('控制器 '.$controllerClass.' 不存在');
        $controller = new $controllerClass();

        $isRestful = $controller instanceof RestfulController;
        define("ACTION_NAME", $isRestful ? ucfirst(strtolower($this->httpmethod())) : $actionName); // Get Post Put Patch Delete Options

        if(!method_exists($controller, ACTION_NAME.'Action'))
            Halt('方法 '.ACTION_NAME.'Action 不存在');

        $result = $controller->{ACTION_NAME.'Action'}();  // call_user_func(array($controller, ACTION_NAME.'Action'));
        if ($result != NULL && $isRestful)
            sp_output(sp_tojson($result), "application/json");
    }
    /**
     * 获取http的method
     * @return string
     */
    protected function httpmethod() {
        if (isset($_POST['_method']))       // 用post方式模拟restful方法
            return $_POST['_method'];
        elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']))
            return $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
        else
            return $_SERVER['REQUEST_METHOD']?: 'GET';
    }
    /**
     * 自动加载函数
     * @param string $class 类名
     */
    public static function autoload($class) {
        if ($class[0]===self::$appNamespace[0] && strncmp($class, self::$appNamespace."\\", strlen(self::$appNamespace)+1)===0) {
            $classfile = strtr(substr($class, strlen(self::$appNamespace)), "\\", "/");
            includeIfExist(APP_FULL_PATH.$classfile.'.php'); // 默认Namespace路径和文件路径一致
        } else
            includeIfExist(APP_FULL_PATH.'/vendor/autoload.php');  //composer安装的类库
    }
    // 接受PHP内部回调异常处理
    static function appException($error) {
        Halt($error);
    }
    // 自定义错误处理
    static function appError($errno, $errstr, $errfile, $errline) {
        $haltArr = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
        if (in_array($errno, $haltArr))
            Halt("Errno: $errno $errstr File: $errfile line: $errline.");
    }
    // 致命错误捕获
    static function appFatal() {
        $error = error_get_last(); // last error with keys "type", "message", "file" and "line". Returns &null; if no error.
        $haltArr = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
        if ($error && in_array($error['type'], $haltArr))
            Halt($error);
    }
}

/**
 * 控制器类
 */
class Controller {
    private $_view;     // 视图实例
    /**
     * 构造函数，初始化视图实例，调用hook
     */
    public function __construct() {
        $this->_view = new View();
        $this->_init();
    }
    protected function _init() {}
    /**
     * 渲染模板并输出
     * @param null|string $tpl 模板文件路径
     * 参数为相对于App/View/文件的相对路径，不包含后缀名，例如index/index
     * 如果参数为空，则默认使用$controller/$action.php
     * 如果参数不包含"/"，则默认使用$controller/$tpl
     */
    protected function display($tpl='') {
        if($tpl === '')
            $tpl = MODULE_NAME. '/' . ACTION_NAME;
        elseif(strpos($tpl, '/') === false)
            $tpl = MODULE_NAME. '/' . $tpl;
        $this->_view->display($tpl);
    }
    /**
     * 为视图引擎设置一个模板变量
     * @param string $name 要在模板中使用的变量名
     * @param mixed $value 模板中该变量名对应的值
     */
    protected function assign($name, $value) {
        $this->_view->assign($name, $value);
    }
    /**
     * 将数据用json格式输出至浏览器，并停止执行代码
     * @param array|string|object $data 要输出的数据
     */
    protected function json($json) {
        sp_output(sp_tojson($json), "application/json");
    }
    protected function xml($xmlstr) {
        sp_output($xmlstr, "text/xml");
    }
    protected function text($textstr) {
        sp_output($textstr, "text/plain");
    }
    protected function redirect($url) {
        header("Location: $url");
        exit();
    }
}
class BaseController extends Controller{
    protected function _init(){
        header("Content-Type: text/html; charset=utf-8");
    }
}
class RestfulController {
    // 获取post、put传的json对象
    protected function read() {
        return json_decode( file_get_contents('php://input') );
    }
}

/**
 * 视图类
 */
class View {
    private $_tplDir;           /** 视图文件目录 */
    private $_tplCacheDir;      /** 编译后模板缓存目录 */
    private $_viewPath;         /** 视图文件路径 */
    private $_data = array();   /** 视图变量列表 */
    /**
     * @param string $tplDir 模板目录
     */
    public function __construct($tplDir='') {
        $this->_tplDir = $tplDir ?: APP_FULL_PATH.'/View/';
        $this->_tplCacheDir = APP_FULL_PATH.'/Cache/Tpl/';
    }
    /**
     * 为视图引擎设置一个模板变量
     * @param string $key 要在模板中使用的变量名
     * @param mixed $value 模板中该变量名对应的值
     */
    public function assign($key, $value) {
        $this->_data[$key] = $value;
    }
    /**
     * 渲染模板并输出
     * 2017-06-25 加入模板缓存, 注意<include文件变化不会主动更新，可以清除缓存，或更新包含文件。
     * @param null|string $tplFile 模板文件路径，相对于App/View/文件的相对路径，不包含后缀名，例如index/index
     */
    public function display($tplFile) {
        $this->_viewPath = $this->_tplDir . $tplFile . '.php';
        $cacheTplFile = $this->_tplCacheDir . md5($tplFile) . ".php";
        if(!is_file($cacheTplFile) || filemtime($this->_viewPath) > filemtime($cacheTplFile))
            file_put_contents($cacheTplFile, $this->compiler($this->_viewPath));
        unset($tplFile);
        extract($this->_data);
        include $cacheTplFile;
    }
    /**
     * 编译模板文件
     */
    protected function compiler($tplfile, $flag=true) {
        $content = file_get_contents($tplfile);
        // 添加安全代码 代表入口文件进入的
        if ($flag)
            $content = '<?php if (!defined(\'APP_FULL_PATH\')) exit();?>' . $content;
        $content = preg_replace(
            array(
                '/{\$([^\}]+)}/s', // 匹配 {$vo['info']}  '/{\$([\w\[\]\'"\$]+)}/s'
                '/{\:Url([^\}]+)}/s', // 匹配 {:Url("")}, 纯简化
                '/{\:([^\}]+)}/s', // 匹配 {:func($vo['info'])}
                '/<each[ ]+[\'"](.+?)[\'"][ ]*>/', // 匹配 <each "$list as $v"></each>
                '/<if[ ]*[\'"](.+?)[\'"][ ]*>/', // 匹配 <if "$key == 1"></if>
                '/<elseif[ ]*[\'"](.+?)[\'"][ ]*>/',
            ),
            array(
                '<?php echo $\\1;?>',
                '<?php echo \\SinglePHP\\Url\\1;?>',
                '<?php echo \\1;?>',
                '<?php foreach( \\1 ){ ?>',
                '<?php if( \\1 ){ ?>',
                '<?php }elseif( \\1 ){ ?>',
            ),
            $content);
        $content = str_replace(array('</if>', '<else/>', '</each>', 'APP_URL', 'MODULE_NAME', 'ACTION_NAME'),
            array('<?php } ?>', '<?php }else{ ?>', '<?php } ?>', APP_URL, MODULE_NAME, ACTION_NAME), $content);
        // 匹配 <include "Public/Menu"/>
        $content = preg_replace_callback('/<include[ ]+[\'"](.+)[\'"][ ]*\/>/',
            function ($matches) {return $this->compiler($this->_tplDir . $matches[1]. '.php', false);}, $content);
        return $content;
    }
}

/**
 * 数据库操作类
 * 使用方法：
 *      $db = db();
 *      $db->query('select * from table');
 * 2015-06-25 数据库操作改为PDO，可以用于php7. 或者使用 Medoo，支持多种数据库
 */
class DB {
    private static $_instance = array();    /** 实例数组 */
    private $_db;                           /** 数据库链接 */
    private $_db_type;                      /** 数据库类型 */
    private $_lastSql;                      /** 保存最后一条sql */
    public  $_tbl_prefix = '';              /** 表名前缀 */
    private $autocount=false, $pagesize=20, $pageno=-1, $totalrows=-1; /** 是否自动计算总数，页数，页大小，总条数 */
    /**
     * DB 构造函数
     * @param array $dbConf 配置数组
     * @throws \Exception
     */
    private function __construct($dbConf) {
        try {
            $this->_db = new \PDO($dbConf['DB_DSN'], $dbConf["DB_USER"], $dbConf["DB_PWD"], $dbConf['DB_OPTIONS']) or exit ('数据库连接创建失败');
            $this->_db_type = strtolower(strstr($dbConf["DB_DSN"], ':', true));
            $this->_tbl_prefix = value($dbConf, "TBL_PREFIX", "");
        } catch (\PDOException $e) {    // 避免泄露密码等
            throw new \Exception($e->getMessage());
        }
    }
    /** 获取DB类
     * @param array $dbConf 配置数组
     * @return DB
     * @throws \Exception
     */
    static public function getInstance($dbConf) {
        $key = sp_tojson($dbConf);
        if(!isset(self::$_instance[$key]) || !(self::$_instance[$key] instanceof self))
            self::$_instance[$key] = new self($dbConf);
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

    public function select($sql, $bind=array()) {
        if ($this->pageno > 0) {
            $pagers = array();
            if ($this->autocount) {
                $sqlcount = preg_replace("/select[\s(].+?[\s)]from([\s(])/is", "SELECT COUNT(*) AS num FROM $1", $sql, 1);
                $total = $this->execute($sqlcount, $bind, 'select');
                $this->totalrows = empty($total) ? 0 : $total[0]["num"];
            }
            if ($this->totalrows != 0) {
                if (in_array($this->_db_type, ['oci', 'sqlsrv', 'firebird'])) { // oracle12c mssql2008 firebird3
                    if ($this->pageno == 1)
                        $sql .= ' FETCH FIRST '. $this->pagesize .' ROWS ONLY';
                    else
                        $sql .= ' OFFSET '. ($this->pagesize*($this->pageno-1)) .' ROWS FETCH NEXT '. $this->pagesize .' ROWS ONLY';
                } else {                                                // mysql sqlite pgsql HSQLDB H2
                    if ($this->pageno == 1)
                        $sql .= ' LIMIT '. $this->pagesize;
                    else
                        $sql .= ' LIMIT '. $this->pagesize .' OFFSET '. ($this->pagesize*($this->pageno-1));
                }
                $pagers = $this->execute($sql, $bind, 'select');
            }
            $this->autocount=false; $this->pagesize=20; $this->pageno=-1; $this->totalrows=-1;
            return $pagers;
        } else
            return $this->execute($sql, $bind, 'select');
    }
    public function insert($sql, $bind=array()) {return $this->execute($sql, $bind, 'insert');}
    public function update($sql, $bind=array()) {return $this->execute($sql, $bind, 'update');}
    public function delete($sql, $bind=array()) {return $this->execute($sql, $bind, 'delete');}

    /** 执行sql语句
     * @param string $sql 要执行的sql
     * @param array $bind 执行中的参数
     * @return bool|int|array 执行成功返回数组、数量、自增id，失败返回false
     * @throws \Exception
     */
    private function execute($sql, $bind=array(), $flag = '') {
        $this->_lastSql = $sql;
        try {
            $stmt = $this->_db->prepare($sql);
            if (! $stmt)
                $this->error($this->_db, $sql);
            foreach ($bind as $k => $v)
                $stmt->bindValue($k, $v);
            if (! $stmt->execute())
                $this->error($stmt, $sql);

            switch ($flag) {
                case 'insert': {
                    if ("pgsql" == $this->_db_type) {   // id SERIAL PRIMARY KEY,
                        if(preg_match("/^INSERT[\t\n ]+INTO[\t\n ]+([a-z0-9\_\-]+)/is", $sql, $tablename))
                            return $this->_db->lastInsertId($tablename[1] .'_id_seq');
                    }
                    return $this->_db->lastInsertId();
                } break;
                case 'update':
                case 'delete':return $stmt->rowCount(); break;
                case 'select':return $stmt->fetchAll(\PDO::FETCH_ASSOC); break;
                default:break;
            }
        } catch (\PDOException $e) {
            $this->error($e, $sql);
        }
    }
    private function error($e, $sql) {
        throw new \Exception(implode(', ', $e->errorInfo) . "\n[SQL]：" . $sql);
    }
    function __destruct() {
        $this->_db = null;
    }
    public function getLastSql() {
        return $this->_lastSql;
    }
    public function totalrows() {
        return $this->totalrows;
    }
    public function autocount() {
        $this->autocount = true;
        return $this;
    }
    /** 分页参数
     * @param number $pageno
     * @param number $pagesize
     * @return DB
     */
    public function page($pageno, $pagesize=20) {
        $this->pageno=$pageno; $this->pagesize=$pagesize;
        return $this;
    }
}

/**
 * 数据库模型, 简化增删改查
 * $model = new UserModel("tablename");
 * $model->where("sqlwhere conditon", array(vvv))->get();
 */
class Model {
    protected $_db = null;          // 数据库连接
    protected $_table = '';         // 表名
    protected $_pk = '';            // 主键名
    protected $_where = '';         // where语句
    protected $_bind = array();     // 参数数组

    function __construct($tbl_name='', $db_name='', $pk="id", $db=null) {
        $this->_initialize();
        if($this->_db==null) $this->_db = $db ?: db();
        $this->_table = (empty($db_name) ? "" : $db_name.'.') . $this->_db->_tbl_prefix . ($this->_table ?: $tbl_name);
        if(empty($this->_pk)) $this->_pk = $pk;
    }
    // 回调方法 初始化模型
    protected function _initialize() {}

    /** where条件
     * @param string|array $sqlwhere     sql条件|或查询数组
     * @param array  $bind               参数数组
     * @return Model $this
     * @throws \Exception
     */
    public function where($sqlwhere, $bind=array()) {
        if (is_array($sqlwhere)) {
            $item = array();
            $this->_bind = array();
            foreach ($sqlwhere as $k => $v) {
                if (substr($k,0,1)=='_') continue;
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
            $this->_where = ' (' . implode(" AND ", $item) . ') ';
            $this->_where .= value($sqlwhere, "_sql", ""); // 其他如order group等语句
        } else {
            $this->_where = $sqlwhere;
            $this->_bind = $bind;
        }
        return $this;
    }
    /** 获取一条记录
     * @param null|number $id
     * @return boolean|array
     * @throws \Exception
     */
    public function get($id=null) {
        if ($id != null)
            $this->where(array($this->_pk => $id));
        $info = $this->select();
        return count($info)>0 ? $info[0] : $info;
    }
    /** 获取多条记录
     * @return boolean|array
     */
    public function select() {
        $_sql = 'SELECT * FROM ' . $this->_table ." WHERE ". $this->_where;
        $info = $this->_db->select($_sql, $this->_bind);
        $this->clean();
        return $info;
    }
    /** 更新数据
     * @param array $data
     * @return boolean|number
     * @throws \Exception
     */
    public function update($data) {
        if (isset($data[$this->_pk])) {
            $this->where($this->_pk."=:".$this->_pk, array(":".$this->_pk => $data[$this->_pk]));
            unset($data[$this->_pk]);
        }
        if (empty($this->_where))
            return false;

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
     * @param null|number $id
     * @return boolean|number
     * @throws \Exception
     */
    public function delete($id=null) {
        if ($id != null)
            $this->where(array($this->_pk => $id));
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
    }
}

/**
 * 日志类
 * 使用方法：Log::error('error msg');
 * 保存路径为 App/Log，按天存放
 */
class Log {
    const DEBUG = 1, NOTICE = 2, WARN = 3, ERROR = 4, FATAL = 5;
    /**
     * 打印日志
     * @param string $msg 日志内容
     * @param string $level 日志等级
     */
    protected static function write($msg, $level=Log::NOTICE) {
        if(null != Config('LOG_LEVEL') && Config('LOG_LEVEL') <= $level) {
            $msg = date('[ Y-m-d H:i:s ]')." [{$level}] ".$msg."\r\n";
            $logPath = APP_FULL_PATH.'/Log/'.date('Ymd').'.log';
            file_put_contents($logPath, $msg, FILE_APPEND);
        }
    }
    /** 打印fatal日志
     * @param string $msg 日志信息
     */
    public static function fatal($msg) {
        self::write($msg, Log::FATAL);
    }
    public static function error($msg) {
        self::write($msg, Log::ERROR);
    }
    public static function warn($msg) {
        self::write($msg, Log::WARN);
    }
    public static function notice($msg) {
        self::write($msg, Log::NOTICE);
    }
    public static function debug($msg) {
        self::write($msg, Log::DEBUG);
    }
}
