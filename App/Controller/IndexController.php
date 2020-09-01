<?php

namespace App\Controller;

use App\Functions;
use App\Lib\MyLog;
use App\Lib\Test as TestClass;
use App\Model\OrderModel;
use App\Service\OrderService;
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
        $t = new TestClass(new MyLog());
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
        $orderModel = new OrderModel();
        $result1 = $orderModel->getpk1();

        $orderService = new OrderService();
        $result3 = $orderService->getLast3Order();

        $db = \SinglePHP\db();
        $db->beginTransaction();
        $result = $db->autocount()->page(1, 2)->select("select * from tbl_order");
        //echo nl2br(htmlspecialchars(print_r($result, true), ENT_HTML5));
        $lastsql = $db->getLastSql();
        $db->rollBack();

        $this->assign("result1", $result1);
        $this->assign("result3", $result3);
        $this->assign("result", $result);
        $this->assign("sql", $lastsql);
        $this->display();
    }
}
