<?php
/**
 *
 * @author: 招宝通
 */

namespace app\ebapi\model\store;


use basic\ModelBasic;
use think\Db;
use traits\ModelTrait;

class StoreCourseAttr extends ModelBasic
{

    use ModelTrait;

    protected function getAttrValuesAttr($value)
    {
        return explode(',',$value);
    }

    public static function storeCourseAttrValueDb()
    {
        return Db::name('StoreCourseAttrValue');
    }


    /**
     * 获取商品属性数据
     * @param $courseId
     * @return array
     */
    public static function getCourseAttrDetail($courseId)
    {
        $attrDetail = self::where('course_id',$courseId)->order('attr_values asc')->select()->toArray()?:[];
        $_values = self::storeCourseAttrValueDb()->where('course_id',$courseId)->select();
        $values = [];
        foreach ($_values as $value){
            $values[$value['suk']] = $value;
        }
        foreach ($attrDetail as $k=>$v){
            $attr = $v['attr_values'];
//            unset($courseAttr[$k]['attr_values']);
            foreach ($attr as $kk=>$vv){
                $attrDetail[$k]['attr_value'][$kk]['attr'] =  $vv;
                $attrDetail[$k]['attr_value'][$kk]['check'] =  false;
            }
        }
        return [$attrDetail,$values];
    }

    public static function uniqueByStock($unique)
    {
        return self::storeCourseAttrValueDb()->where('unique',$unique)->value('stock')?:0;
    }

    public static function uniqueByAttrInfo($unique, $field = '*')
    {
        return self::storeCourseAttrValueDb()->field($field)->where('unique',$unique)->find();
    }

    public static function issetCourseUnique($courseId,$unique)
    {
        $res = self::be(['course_id'=>$courseId]);
        if($unique){
            return $res && self::storeCourseAttrValueDb()->where('course_id',$courseId)->where('unique',$unique)->count() > 0;
        }else{
            return !$res;
        }
    }

}