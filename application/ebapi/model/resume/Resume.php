<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-25
 * Time: 19:40
 */

namespace app\ebapi\model\resume;
use basic\ModelBasic;
use traits\ModelTrait;

class Resume extends ModelBasic
{
    use  ModelTrait;
    /**
     * 获取人才推荐
     * @param string $field
     * @return mixed
     */
    public static function getResumeList($field = '*'){
        $model = new self();
        $model = $model->field($field);
        $model = $model->where('is_top', 1);
        $model = $model->order('sort desc,id DESC,add_time DESC');
        return $model->select();
    }

    public static function getList($data)
    {
        $keyword = $data['keyword'];
        $page = $data['page'];
        $limit = $data['limit'];
        $model = self::validWhere();
        if(!empty($keyword)) $model = $model->where('name','LIKE',htmlspecialchars("%$keyword%"));
        $model = $model->order('sort desc,id DESC');
        $list = $model->page((int)$page,(int)$limit)->select();
        $list = count($list) ? $list->toArray() : [];
        return $list;
    }
    public static function validWhere()
    {
        return self::where('is_show',1);
    }
}