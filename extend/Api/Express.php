<?php
/**
 *
 * @author: 招宝通
 */

namespace Api;


use service\HttpService;
use app\core\util\SystemConfigService;

class Express
{
    protected static $api = [
        'query'=>'https://wuliu.market.alicloudapi.com/kdi'
    ];

    public static function query($no,$type = '')
    {
        $appCode = SystemConfigService::config('system_express_app_code');
        if(!$appCode) return false;
        $res = HttpService::getRequest(self::$api['query'],compact('no','type'),['Authorization:APPCODE '.$appCode]);
        $result = json_decode($res,true)?:false;
        return $result;
    }

}