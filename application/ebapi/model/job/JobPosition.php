<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-25
 * Time: 19:41
 */

namespace app\ebapi\model\job;
use app\ebapi\model\position\Position;
use basic\ModelBasic;
use traits\ModelTrait;

class JobPosition extends ModelBasic
{
    use  ModelTrait;

    /**
     * 获取职位推荐
     * @param string $field
     * @return mixed
     */
    public static function getJobList($field = 'id,uid,name,skills,age_for,education,salary,address,description,position,industry,province,city,district'){
        $model = new self();
        $model = $model->field($field);
        $model = $model->where('is_top', 1);
        $model = $model->order('id desc');
        return $model->select();
    }

    public static function getList($data)
    {
        $keyword = $data['keyword'];
        $merId = $data['mer_id'];
        $page = $data['page'];
        $limit = $data['limit'];
        $model = self::validWhere();

        if(!empty($keyword)){
            $position = Position::where('is_show',1)->where('name','LIKE',htmlspecialchars("%$keyword%"))->select();
            $cid = [];
            foreach ($position as $item=>$value){
                $cid[$item] = $value['id'];
            }
            $model = $model->where('position','in',$cid);
        }

        if(!empty($merId))   $model = $model->where('uid',$merId);
        $model = $model->where('is_show',1);
        $model = $model->order('sort desc,id desc');

        $list = $model->page((int)$page,(int)$limit)->field('id,uid,name,skills,age_for,education,salary,address,description,position,industry,province,city,district,add_time')->select();

        $list = count($list) ? $list->toArray() : [];
        return $list;
    }
    public static function validWhere()
    {
        return self::where('uid','<>',0)->where('is_show',1);
    }
}