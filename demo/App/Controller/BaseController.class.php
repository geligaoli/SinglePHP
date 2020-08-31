<?php
if (!defined('APP_FULL_PATH')) exit();

class BaseController extends Controller{
    protected function _init(){
        header("Content-Type:text/html; charset=utf-8");
    }
} 
