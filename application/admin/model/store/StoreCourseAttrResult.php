<?php
/**
 *
 * @author: 招宝通
 */

namespace app\admin\model\store;


use basic\ModelBasic;
use traits\ModelTrait;

class StoreCourseAttrResult extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['change_time'];

    protected static function setChangeTimeAttr($value)
    {
        return time();
    }

    protected static function setResultAttr($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    public static function setResult($result,$course_id)
    {
        $result = self::setResultAttr($result);
        $change_time = self::setChangeTimeAttr(0);
        return self::insert(compact('course_id','result','change_time'),true);
    }

    public static function getResult($courseId)
    {
        return json_decode(self::where('course_id',$courseId)->value('result'),true) ?: [];
    }

    public static function clearResult($courseId)
    {
        return self::del($courseId);
    }

}