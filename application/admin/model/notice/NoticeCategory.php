<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:24
 */

namespace app\admin\model\notice;

use traits\ModelTrait;
use app\admin\model\notice\Notice as NoticeModel;
use basic\ModelBasic;
use service\UtilService as Util;

/**
 * 知识分类 model
 * Class NoticeCategory
 * @package app\admin\model\notice
 */
class NoticeCategory extends ModelBasic
{
    use ModelTrait;

    /**
     * 获取系统分页数据   分类
     * @param array $where
     * @return array
     */
    public static function systemPage($where = array()){
        $model = new self;
        if($where['title'] !== '') $model = $model->where('title','LIKE',"%$where[title]%");
        if($where['status'] !== '') $model = $model->where('status',$where['status']);
        $model = $model->where('is_del',0);
        $model = $model->where('hidden',0);
        return self::page($model);
    }

    /**
     * 删除分类
     * @param $id
     * @return bool
     */
    public static function delNoticeCategory($id)
    {
        if(count(self::getNotice($id,'*'))>0)
            return self::setErrorInfo('请先删除改分类下的文章!');
        return self::edit(['is_del'=>1],$id,'id');
    }

    /**
     * 获取分类名称和id     field
     * @param $field
     * @return array
     */
    public  static function getField($field){
        return self::where('is_del','eq',0)->where('status','eq',1)->where('hidden','eq',0)->column($field);
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

    /**
     * 获取分类底下的文章
     * @param $id
     * @param $field
     * @return array
     */
    public static function getNotice($id,$field){
        $res = NoticeModel::where('status',1)->where('hide',0)->column($field,'id');
        $new_res = array();
        foreach ($res as $k=>$v){
            $cid_arr = explode(',',$v['cid']);
            if(in_array($id,$cid_arr)){
                $new_res[$k] = $res[$k];
            }
        }
        return $new_res;
    }
}