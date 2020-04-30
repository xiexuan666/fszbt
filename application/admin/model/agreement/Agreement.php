<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:24
 */

namespace app\admin\model\agreement;

use app\admin\model\system\SystemAdmin;
use traits\ModelTrait;
use basic\ModelBasic;
use think\Db;

/**
 * 协议管理 Model
 * Class News
 */
class Agreement extends ModelBasic
{
    use ModelTrait;

    /**
     * 协议管理
     * @param array $where
     * @return array
     */
    public static function getList($where = array()){
        $model = new self;
        if($where['title']) $model = $model->where('title','LIKE',"%$where[title]%");
        $model = $model->order('is_show desc,sort desc');
        $model = $model->page((int)$where['page'],(int)$where['limit']);
        $data = ($data = $model->select()) && count($data) ? $data->toArray():[];
        foreach ($data as &$item){
            $item['add_time']=date('Y-m-d',$item['add_time']);
        }
        $count = $model->count();
        return compact('count','data');
    }
}