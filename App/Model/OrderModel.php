<?php

namespace App\Model;

use SinglePHP\Model;

class OrderModel extends Model {
    protected $_table = 'order';         // 表名

    public function getpk1() {
        return $this->get(10);
    }
}