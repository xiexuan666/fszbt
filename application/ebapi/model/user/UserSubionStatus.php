<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-08-04
 * Time: 11:34
 */

namespace app\ebapi\model\user;

use basic\ModelBasic;
use traits\ModelTrait;

class UserSubionStatus extends ModelBasic
{
    use ModelTrait;

    public static function status($oid,$change_type,$change_message,$change_time = null)
    {
        if($change_time == null) $change_time = time();
        return self::set(compact('oid','change_type','change_message','change_time'));
    }

    public static function getTime($oid,$change_type)
    {
        return self::where('oid',$oid)->where('change_type',$change_type)->value('change_time');
    }
}