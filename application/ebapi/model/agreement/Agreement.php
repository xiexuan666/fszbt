<?php
namespace app\ebapi\model\agreement;

use traits\ModelTrait;
use basic\ModelBasic;

/**
 * Class 隐私协议
 * @package app\ebapi\model\article
 */
class Agreement extends ModelBasic
{

    use ModelTrait;

    /**
     * 获取一条
     * @param int $id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getOne($id = 0){
        if(!$id) return [];
        $list = self::where('is_show',1)->where('id',$id)->find();
        if($list){
            $list = $list->toArray();
            return $list;
        }
        else return [];
    }

    /**
     * 获取列表
     * @param $first
     * @param $limit
     * @param string $field
     * @return mixed
     */
    public static function getList($first, $limit, $field = 'id,title,image,visit,add_time')
    {
        $model=new self();
        $model = $model->field($field);
        $model = $model->where('is_show', 1);
        $model = $model->order('sort DESC,add_time DESC');
        if($limit)  $model = $model->limit($first, $limit);
        return $model->select();
    }
}
