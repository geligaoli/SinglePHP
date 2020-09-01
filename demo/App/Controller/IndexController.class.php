<?php

namespace App\Controller;

use App\Functions;
use App\Lib\Test as TestClass;
use SinglePHP\BaseController;
use SinglePHP\Log;

class IndexController extends BaseController {
    public function IndexAction(){
        $this->assign('title', 'SinglePHP-Ex');
        $this->display();
    }
    public function UrlAction(){
        echo 'url测试成功';
    }
    public function RedirectAction(){
        $this->redirect('http://www.baidu.com'); //302跳转到百度
    }
    public function AjaxAction(){
        $ret = array(
            'result' => true,
            'data'   => 123,
        );
        $this->json($ret);                //将$ret格式化为json字符串后输出到浏览器
    }
    public function FunctionAction(){
        echo Functions\Now();
    }
    public function AutoLoadAction(){
        $t = new TestClass();
        echo $t->hello();
    }
    public function LogAction(){
        Log::fatal('something');
        Log::warn('something');
        Log::notice('something');
        Log::debug('something');
        echo '请到Log文件夹查看效果';
    }
    public function DatabaseAction() {
        $db = \SinglePHP\db();
        $db->beginTransaction();
        echo nl2br(htmlspecialchars(print_r($db->select("select * from tbl_order"), true)));
        $db->rollBack();
    }
}
