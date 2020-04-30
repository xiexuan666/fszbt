<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-25
 * Time: 19:40
 */

namespace app\ebapi\model\job;
use basic\ModelBasic;
use traits\ModelTrait;

class Job extends ModelBasic
{
    use  ModelTrait;
    public static function getList($data)
    {
        $keyword = $data['keyword'];
        $page = $data['page'];
        $limit = $data['limit'];
        $model = self::validWhere();
        
        if(!empty($keyword)) $model = $model->where('name|phone','LIKE',htmlspecialchars("%$keyword%"));

        $model = $model->order('is_top DESC, id DESC');

        $list = $model->page((int)$page,(int)$limit)->field('id,uid,name,phone,wechat,email,company,position,team_tag,description,image,slider_image,share_title,share_synopsis,ficti,visit,sort,status,add_time,is_top,is_hot,communication,interview,collection,is_default')->select();

        $list = count($list) ? $list->toArray() : [];
        return $list;
    }
    public static function validWhere()
    {
        return self::where('is_default',1);
    }
}