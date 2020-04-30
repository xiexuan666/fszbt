<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-15
 * Time: 18:12
 */

namespace app\ebapi\model\welfare;

use traits\ModelTrait;
use basic\ModelBasic;
use service\UtilService as Util;

class Welfare extends ModelBasic
{
    use ModelTrait;

    /**
     * TODO 获取福利配置
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getList(){
        return self::where('status',1)->order('sort DESC')->field('id,title,image')->select();
    }

    public static function getDatas($id){
        return self::where('status',1)->where('id',$id)->field('id,title,image')->find();
    }

    /**
     * TODO  获取福利配置字段
     * @param $id $id 编号
     * @param string $field $field 字段
     * @return mixed|string
     */
    public static function getCategoryField($id,$field = 'title'){
        if(!$id) return '';
        return self::where('id',$id)->value($field);
    }

    /**
     * TODO 福利配置排序列表
     * @param null $model
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getTierList($model = null)
    {
        if($model === null) $model = new self();
        return Util::sortListTier($model->select()->toArray());
    }
}