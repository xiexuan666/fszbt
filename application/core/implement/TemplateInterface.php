<?php
/**
 * 
 * User: 招宝通
 */

namespace app\core\implement;


/*
 * 模板消息接口类
 *
 * */

interface TemplateInterface
{
    public static function sendTemplate($openId,$tempCode,$dataKey,$formId=null,$link=null,$defaultColor=null);

    public static function getConstants($key=null);
}