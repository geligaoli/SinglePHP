<?php

namespace App\Service;

use App\Model\OrderModel;

class OrderService {
    private $orderModel;

    function __construct() {
        $this->orderModel = new OrderModel();
    }

    public function getLast3Order() {
        $where = [];
        $where['id'] = array(">", 10);
        $where['ipaddr'] = '127000000001';
        $where['_sql'] = 'order by id desc limit 3';
        return $this->orderModel->where($where)->select();
    }


}