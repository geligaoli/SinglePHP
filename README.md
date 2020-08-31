# SinglePHP-Ex


### 简介

SinglePHP-Ex是一个单文件PHP框架，提供了简单的MVC方式，简单系统的快速开发。整个框架不超过1000行。

SinglePHP-Ex是采用 [SinglePHP](https://github.com/leo108/SinglePHP) 为基础，并整合了 [PhpPoem](https://github.com/cleey/phppoem) 部分代码。

功能的增强有：

    加入View的include模板功能，根据文件时间来自动生成编译后的模板缓存文件。
    
    数据库操作改为PDO，可以在php7.1执行。（DB与原SinglePHP不兼容）。
    
    拦截php的异常错误，DEBUG状态下，在页面显示错误trace，方便调试。
    
    加入数据库表Model，简化对单表的增删改查的操作。
    
    加入了命令行模式，方便写脚本用。


目前 [SinglePHP-Ex](https://github.com/geligaoli/SinglePHP-Ex) 由 geligaoli 开发维护，如果你希望参与到此项目中来，可以到[Github](https://github.com/geligaoli/SinglePHP-Ex)上Fork项目并提交Pull Request。

### 文档

整理中。

### Demo

在线演示：整理中。

### 目录结构

    ├── App                                 #业务代码文件夹，可在配置中指定路径
    │   ├── Cache                           #缓存，**需要写权限**
    │   │   ├── Tpl                         #编译后的view模板缓存
    │   │   └── File                        #文件缓存，暂未用
    │   ├── Controller                      #控制器文件夹
    │   │   └── IndexController.class.php
    │   ├── Lib                             #外部库
    │   ├── Log                             #日志文件夹，**需要写权限**
    │   ├── Model                           #数据库模型
    │   │   └── IndexModel.class.php
    │   ├── View                            #模板文件夹
    │   │   ├── Index                       #对应Index控制器
    │   │   │   └── Index.php
    │   │   └── Public
    │   │       ├── footer.php
    │   │       └── header.php
    │   └── common.php                      #一些共用函数
    ├── SinglePHP.class.php                 #SinglePHP核心文件
    └── Public                              #网站根目录
        ├── index.php                       #入口文件
        ├── js                              #javascript文件
        └── css                             #css样式表


### Hello World

只需增加3个文件，即可输出hello world。

入口文件：index.php

    <?php
    include '../SinglePHP.class.php';         //包含核心文件
    $config = array('APP_PATH' => '../App/'); //指定业务目录为App
    SinglePHP::getInstance($config)->run();   //撒丫子跑起来啦
    

默认控制器：App/Controller/IndexController.class.php

    <?php
    class IndexController extends Controller {       //控制器必须继承Controller类或其子类
        public function IndexAction(){               //默认Action
            $this->assign('content', 'Hello World'); //给模板变量赋值
            $this->display();                        //渲染吧骚年
        }
    }
    
模板文件：App/View/Index/Index.php

    <?php echo $content;
    or
    ${content}
    
在浏览器访问index.php，应该会输出

    Hello World
    
    
    
### 原 SinglePHP 简介

SinglePHP是一个单文件PHP框架，适用于简单系统的快速开发，提供了简单的路由方式，抛弃了坑爹的PHP模板，采用原生PHP语法来渲染页面,同时提供了widget功能，简单且实用。

目前SinglePHP由[leo108](http://leo108.com)开发维护，如果你希望参与到此项目中来，可以到[Github](https://github.com/leo108/SinglePHP)上Fork项目并提交Pull Request。

### 文档

中文: [http://leo108.github.io/SinglePHP/](http://leo108.github.io/SinglePHP/)

English: [http://leo108.github.io/SinglePHP/en/](http://leo108.github.io/SinglePHP/en/) (Not Finished Yet)


### 原 PhpPoem 简介

PhpPoem, 如诗一般简洁优美的PHP框架       
PhpPoem, a simple and beautiful php framework, php will be like poet.


Home: http://phppoem.com/  
Author: Cleey  
QQ群: 137951449


压力测试    
服务器配置为 16G 16核，php5.3.3开启opcache，使用压测工具ab，结果如下：   
   
PhpPoem 2.0 并发 7500 持续10s，结果  7836.84 req/s ：   
   
ab -c7500 -t10 test.com   
   
Requests per second:    7836.84 [#/sec] (mean)   
Time per request:       957.019 [ms] (mean)   
Time per request:       0.128 [ms] (mean, across all concurrent requests)   
Transfer rate:          1642.15 [Kbytes/sec] received   
