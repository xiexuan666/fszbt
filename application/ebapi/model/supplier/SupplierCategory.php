<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-09-20
 * Time: 11:31
 */

namespace app\ebapi\model\supplier;

use traits\ModelTrait;
use basic\ModelBasic;
use service\UtilService as Util;

class SupplierCategory extends ModelBasic
{
    use ModelTrait;

    /**
     * TODO 获取文章分类
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getCategory(){
        return self::where('is_show',1)->where('pid',0)->order('sort DESC')->select();
    }

    public static function pidBySidList($pid)
    {
        return self::where('pid',$pid)->order('sort DESC')->select();
    }

    /**
     * TODO  获取分类字段
     * @param $id $id 编号
     * @param string $field $field 字段
     * @return mixed|string
     */
    public static function getCategoryField($id,$field = 'cate_name'){
        if(!$id) return '';
        return self::where('id',$id)->value($field);
    }

    /**
     * 分级排序列表
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