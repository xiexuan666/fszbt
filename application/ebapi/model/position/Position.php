<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-25
 * Time: 18:01
 */

namespace app\ebapi\model\position;
use basic\ModelBasic;
use traits\ModelTrait;

class Position extends ModelBasic
{
    use  ModelTrait;
    public static function pidBy($pid,$field = '*',$limit = 0)
    {
        $model = self::where('pid',$pid)->where('is_show',1)->order('sort desc,id asc')->field($field);
        if($limit) $model->limit($limit);
        return $model->select();
    }
    public static function pidByList($pid)
    {
        return self::where('pid',$pid)->field('id,name,pid')->select();
    }
}