<?php
namespace app\ebapi\model\news;

use think\Db;
use traits\ModelTrait;
use basic\ModelBasic;

/**
 * Class Article
 * @package app\ebapi\model\article
 */
class News extends ModelBasic
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
    public static function getArticleOne($id = 0){
        if(!$id) return [];
        $list = self::where('is_show',1)->where('id',$id)->order('id desc')->find();
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
    public static function cidByArticleList($cid, $first, $limit, $field = 'id,title,image,slider_image,visit,add_time,synopsis,url')
    {
        $model=new self();
        if ($cid) $model->where("`cid` LIKE '$cid,%' OR `cid` LIKE '%,$cid,%' OR `cid` LIKE '%,$cid' OR `cid`=$cid ");
        $model = $model->field($field);
        $model = $model->where('is_show', 1);
        $model = $model->order('sort DESC,add_time DESC');
        if($limit)  $model = $model->limit($first, $limit);
        return $model->select();
    }

    /**
     * 获取热门文章
     * @param string $field
     * @return mixed
     */
    public static function getArticleListHot($field = 'id,title,image,slider_image,visit,add_time,synopsis,url'){
        $model = new self();
        $model = $model->field($field);
        $model = $model->where('is_show', 1);
        $model = $model->order('sort DESC,add_time DESC');
        return $model->select();
    }

    /**
     * 获取轮播文章
     * @param string $field
     * @return mixed
     */
    public static function getArticleListBanner($field = 'id,title,image,slider_image,visit,add_time,synopsis,url'){
        $model = new self();
        $model = $model->field($field);
        $model = $model->where('is_show', 1);
        $model = $model->where('is_banner', 1);
        $model = $model->order('sort DESC,add_time DESC');
        return $model->select();
    }
}
