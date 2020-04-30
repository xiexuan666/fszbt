<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:24
 */

namespace app\admin\model\welfare;

use traits\ModelTrait;
use basic\ModelBasic;
use service\UtilService as Util;

/**
 * 福利配置 model
 * Class Welfare
 * @package app\admin\model\welfare
 */
class Welfare extends ModelBasic
{
    use ModelTrait;

    /**
     * 获取系统分页数据   分类
     * @param array $where
     * @return array
     */
    public static function getList($where = array()){

        $data = ($data = self::systemPage($where,true)->page((int)$where['page'],(int)$where['limit'])->select()) && count($data) ? $data->toArray() :[];
        $count=self::systemPage($where,true)->count();
        return compact('count','data');

    }

    /**
     * @param $where
     * @return array
     */
    public static function systemPage($where,$isAjax=false){
        $model = new self;
        if($where['status'] != '')  $model = $model->where('status',$where['status']);
        if($where['title'] != '') $model = $model->where('title','LIKE',"%$where[title]%");
        if($isAjax===true){
            if(isset($where['order']) && $where['order']!=''){
                $model=$model->order(self::setOrder($where['order']));
            }else{
                $model=$model->order('sort desc,id desc');
            }
            return $model;
        }
        return self::page($model,function ($item){

        },$where);
    }

    /**
     * 删除福利配置
     * @param $id
     * @return bool
     */
    public static function delWelfare($id)
    {
        return self::where('id',$id)->delete();
    }

    /**
     * 获取福利配置名称和id     field
     * @param $field
     * @return array
     */
    public  static function getField($field){
        return self::where('is_del','eq',0)->where('status','eq',1)->where('hidden','eq',0)->column($field);
    }

    /**
     * 福利配置排序列表
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