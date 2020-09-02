# SinglePHP-Ex 2.x


### 简介

[SinglePHP-Ex 2.x](https://github.com/geligaoli/SinglePHP-Ex) 是一个单文件PHP框架，提供了精简的MVC模式，简单系统的快速开发。看一眼代码和demo的内容，即可上手使用。

**基于SinglePHP-Ex的项目demo项目，请见 [Proejct](https://github.com/geligaoli/SinglePHP-Ex/tree/project) 分支。**

目前 [SinglePHP-Ex](https://github.com/geligaoli/SinglePHP-Ex) 由 geligaoli 开发维护，如果你希望参与到此项目中来，可以到[Github](https://github.com/geligaoli/SinglePHP-Ex)上Fork项目并提交Pull Request。

[SinglePHP-Ex](https://github.com/geligaoli/SinglePHP-Ex) 是参考了 [SinglePHP](https://github.com/leo108/SinglePHP) 为原型，并整合了 [PhpPoem](https://github.com/cleey/phppoem)、Thinkphp早期 部分代码。


#### 功能的增强有：

    加入了namespace的支持，默认namespace的路径和文件路径一致。采用psr-4标准。
    
    加入了composer的支持。保持了单文件php的简单，又可以composer安装组件。
    
    路由规则支持PATHINFO的伪静态方式，也同时支持普通QueryString的访问。

    加入View的include模板功能，根据文件时间来自动生成编译后的模板缓存文件。
    
    数据库操作改为PDO，可以在php7.x执行。支持建立多数据库连接。支持多种数据库的分页查询。
    
    加入数据库表Model，参考thinkphp，简化对单表的增删改查的操作。
    
    拦截php的异常错误，DEBUG状态下，在页面显示详细错误trace，方便调试。
    
    加入了命令行模式，方便写脚本用。

    整个框架不超过800行。简单明了。

#### composer 安装

环境要求PHP版本>=5.3，无其它库依赖。

    composer require "geligaoli/singlephp-ex:^2.0.4"

### 文档

#### nginx的pathinfo方式配置

假如项目部署在 /www/nginx/default目录下。设置open_basedir可提高安全性。

    root /www/nginx/default/Public;
    index index.html index.php;

    location / {
        try_files $uri $uri/ =404;
    }

    if (!-e $request_filename) {
        rewrite  ^/(.*)$  /index.php/$1  last;
    }

    location ~ \.php($|/) {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param PHP_ADMIN_VALUE "open_basedir=/www/nginx/default/:/usr/share/php:/tmp/:/proc/";
    }

fastcgi_params 文件中增加

    fastcgi_param  PATH_INFO          $fastcgi_path_info;
    fastcgi_param  SCRIPT_FILENAME    $document_root$fastcgi_script_name;
    fastcgi_param  PATH_TRANSLATED    $document_root$fastcgi_path_info;


#### View模板语法

    {$vo['info']}           // {$..}输出变量
    {:func($vo['info'])}    // {:..}调用函数
    {:Url("Index/Index")}   // 自动按PATH_MODE来生成url
                    
    <each "$list as $v"></each>     // 循环
    <if "$key==1"><elseif "$key==2"><else/></if>    // 判断
    
    <include "Public/Menu"/>        // 包含其它文件
    
    'APP_URL'       // 项目所在的URL根路径
    'MODULE_NAME'   // 当前的模块名
    'ACTION_NAME'   // 当前方法名
    
    <?php ?>        // 直接使用php语法

### Demo

在线演示：整理中。见 demo 目录下。

#### 目录结构

    ┌── App                                 #业务代码文件夹，可在配置中指定路径
    │   ├── Cache                           #缓存，该目录及以下 **需要写权限**
    │   │   ├── Tpl                         #编译后的view模板缓存
    │   │   └── File                        #文件缓存，暂未用
    │   ├── Controller                      #控制器文件夹
    │   │   └── IndexController.php
    │   ├── Lib                             #其他库文件
    │   │   └── Test.php
    │   ├── Log                             #日志文件夹，**需要写权限**
    │   ├── Model                           #数据库模型
    │   │   └── IndexModel.php
    │   ├── Service                         #Service服务
    │   │   └── IndexService.php
    │   ├── View                            #模板文件夹
    │   │   ├── Index                       #对应Index控制器
    │   │   │   └── Index.php
    │   │   └── Public
    │   │       ├── footer.php
    │   │       └── header.php
    │   ├── vendor                          #composer安装目录，包括SinglePHP-ex
    │   └── Functions.php                   #一些共用函数
    ├── composer.json                       #composer配置文件
    └── Public                              #网站根目录
        ├── index.php                       #入口文件
        ├── img                             #图片文件目录
        ├── js                              #javascript文件目录
        └── css                             #css样式表目录

#### 最简目录结构

    ┌── App                                 #业务代码文件夹，可在配置中指定路径
    │   ├── Controller                      #控制器文件夹
    │   │    └── IndexController.php
    │   └── vendor                          #composer安装目录，包括SinglePHP-ex
    ├── composer.json                       #composer配置文件
    └── Public                              #网站根目录
        └── index.php                       #入口文件
        
#### 采用单独文件部署的最简目录

    ┌── App                                 #业务代码文件夹，可在配置中指定路径
    │   └── Controller                      #控制器文件夹
    │        └── IndexController.php
    ├── SinglePHP.php                       #SinglePHP文件
    └── Public                              #网站根目录
        └── index.php                       #入口文件

同时修改SinglePHP.php，取消对autoload的注释
    
    //includeIfExist(APP_FULL_PATH.'/Functions.php');
    //spl_autoload_register(array('SinglePHP\SinglePHP', 'autoload'));


#### Hello World

只需增加3个文件，即可输出hello world。

入口文件：index.php

    <?php
    namespace App;
    
    define('APP_DEBUG',  TRUE);
    define('APP_FULL_PATH', dirname(__DIR__)."/App");
    
    require '../App/vendor/autoload.php';       //采用composer方式
    #require '../SinglePHP.php';                //采用单独文件部署方式
    use SinglePHP\SinglePHP;

    $config = array(
        'APP_PATH' => 'App',
        'APP_NAMESPACE' => __NAMESPACE__,  # 业务代码的主命名空间 namespace
        'CTL_NAMESPACE' => 'Controller',   # 控制器代码的命名空间 namespace
    );
    SinglePHP::getInstance($config)->run();     //跑起来啦
    

默认控制器：App/Controller/IndexController.php

    <?php
    namespace App\Controller;
    use SinglePHP\BaseController;

    class IndexController extends BaseController {   //控制器必须继承Controller类或其子类
        public function IndexAction(){               //默认Action
            $this->assign('content', 'Hello World'); //给模板变量赋值
            $this->display();                        //渲染吧
        }
    }
    
模板文件：App/View/Index/Index.php

    <?php echo $content; ?>
    或者
    <p>{$content}</p>
    
在浏览器访问index.php，应该会输出

    Hello World
    
#### 页面无输出的检查

请检查 Cache、Log 这两个目录及子目录是否存在且可写入。

      App                                 
      ├── Cache                           #缓存，该目录及以下 **需要写权限**
      │   └── Tpl                         #编译后的view模板缓存，**需要写权限**
      └── Log                             #日志文件夹，**需要写权限**



    
### 原 SinglePHP 简介

SinglePHP是一个单文件PHP框架，适用于简单系统的快速开发，提供了简单的路由方式，抛弃了坑爹的PHP模板，采用原生PHP语法来渲染页面,同时提供了widget功能，简单且实用。

目前SinglePHP由[leo108](http://leo108.com)开发维护，如果你希望参与到此项目中来，可以到[Github](https://github.com/leo108/SinglePHP)上Fork项目并提交Pull Request。


### 原 PhpPoem 简介

PhpPoem, 如诗一般简洁优美的PHP框架       
PhpPoem, a simple and beautiful php framework, php will be like poet.

Home: [http://phppoem.com/](http://phppoem.com/)  
Author: Cleey  

