<?php

namespace app\core\logic;

use app\core\util\MiniProgramService;
use think\Request;

/**
 *
 * User: 招宝通
 */
class Pay
{
    public static function notify(){
        $request=Request::instance();
        switch (strtolower($request->param('notify_type','wenxin'))){
            case 'wenxin':
                break;
            case 'routine': //小程序支付回调
                MiniProgramService::handleNotify();
                break;
            case 'alipay':
                break;
            default:
                echo 121;
                break;
        }
    }
}