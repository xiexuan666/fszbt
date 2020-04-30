<?php
/**
 *
 * @author: 招宝通
 */

namespace app\ebapi\model\store;

use traits\ModelTrait;
use basic\ModelBasic;
use think\Cache;

class StoreBrand extends ModelBasic
{
    use  ModelTrait;
    public static function pidByBrand($pid,$field = '*',$limit = 0,$where=null)
    {
        $merId = 0;
        if($where != null) $merId = $where['mer_id'];
        $model      = self::validWhere();
        if($merId) $model = $model->where('mer_id',$merId);
        $model = $model->where('pid',$pid);
        $model = $model->order('sort desc,id desc');
        $model = $model->field($field);
        if($limit) $model->limit($limit);
        return $model->select();
    }

    public static function pidBySidList($pid)
    {
        return self::where('pid',$pid)->field('id,mer_id,cate_name,pid')->select();
    }

    public static function cateIdByPid($cateId)
    {
        return self::where('id',$cateId)->value('pid');
    }

    public static function validWhere()
    {
        return self::where('is_show',1);
    }

    /*
     * 获取一级和二级分类
     * @return array
     * */
    public static function getProductBrand($where)
    {
        $parentBrand = self::pidByBrand(0, 'id,mer_id,cate_name,pic')->toArray();
        foreach ($parentBrand as $k => $brand) {
            $brand['child'] = self::pidByBrand($brand['id'], 'id,mer_id,cate_name,pic',0,$where)->toArray();
            $parentBrand[$k] = $brand;
        }
        return $parentBrand;
    }

    /**
     * TODO  获取首页展示的二级分类  排序默认降序
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function byIndexList($limit = 4,$field = 'id,cate_name,pid,pic'){
        return self::where('pid','>',0)->where('is_show',1)->field($field)->order('sort DESC')->limit($limit)->select();
    }

}