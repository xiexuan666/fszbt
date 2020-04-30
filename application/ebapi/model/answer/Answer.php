<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-11
 * Time: 08:29
 */

namespace app\ebapi\model\answer;

use think\Db;
use traits\ModelTrait;
use basic\ModelBasic;


class Answer extends ModelBasic
{
    use ModelTrait;

    protected function getImageInputAttr($value)
    {
        return explode(',',$value)?:[];
    }


    /**
     * 获取一条新闻
     * @param int $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public static function getOne($id = 0){
        if(!$id) return [];
        $list = self::where('status',1)->where('hide',0)->where('id',$id)->order('id desc')->find();
        if($list){
            $list = $list->toArray();
            return $list;
        }
        else return [];
    }

    /**
     * 获取某个分类底下的文章
     * @param $cid
     * @param $first
     * @param $limit
     * @param string $field
     * @return mixed
     */
    public static function cidByList($cid, $first, $limit, $field = 'id,title,image,slider_image,visit,add_time,synopsis,url')
    {
        $model=new self();
        if ($cid) $model->where("`cid` LIKE '$cid,%' OR `cid` LIKE '%,$cid,%' OR `cid` LIKE '%,$cid' OR `cid`=$cid ");
        $model = $model->field($field);
        $model = $model->where('status', 1);
        $model = $model->where('hide', 0);
        $model = $model->order('sort DESC,add_time DESC');
        if($limit)  $model = $model->limit($first, $limit);
        return $model->select();
    }

    /**
     * 获取热门文章
     * @param string $field
     * @return mixed
     */
    public static function getListHot($field = 'id,title,image,slider_image,visit,add_time,synopsis,url'){
        $model = new self();
        $model = $model->field($field);
        $model = $model->where('status', 1);
        $model = $model->where('hide', 0);
        $model = $model->where('is_hot', 1);
        $model = $model->order('sort DESC,add_time DESC');
        return $model->select();
    }

    /**
     * 获取轮播文章
     * @param string $field
     * @return mixed
     */
    public static function getListBanner($field = 'id,title,image,slider_image,visit,add_time,synopsis,url'){
        $model = new self();
        $model = $model->field($field);
        $model = $model->where('status', 1);
        $model = $model->where('hide', 0);
        $model = $model->where('is_banner', 1);
        $model = $model->order('sort DESC,add_time DESC');
        return $model->select();
    }

    /**
     * 获取知识推荐
     * @param string $field
     * @return mixed
     */
    public static function getAnswerList(){
        $model = new self();
        $model = $model->where('status', 1);
        $model = $model->where('hide', 0);
        $model = $model->where('is_best', 1);
        $model = $model->order('sort DESC,add_time DESC');
        return $model->select();
    }

}