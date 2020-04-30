<?php
/**
 * 
 * User: 招宝通
 */

namespace app\core\lib;

/*
 * 错误处理基类
 * */
class BaseException
{
    //错误提示
    public $msg='系统错误';
    //HTTP状态码
    public $code=500;
    //自定义错误代码
    public $errorCode=400;


}