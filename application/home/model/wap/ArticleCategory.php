<?php
/**
 * @author: 招宝通
 */

namespace app\home\model\wap;

use think\Db;
use traits\ModelTrait;
use basic\ModelBasic;

/**
 * Class ArticleCategory
 * @package app\wap\model
 */
class ArticleCategory extends ModelBasic
{
    use ModelTrait;

    public static function cidByArticleList($cid, $first, $limit, $field = '*')
    {
        $model = new self();
        $model = Db::name('article');
        //if ($cid) $model->where("CONCAT(',',cid,',')", 'LIKE', "'%,$cid,%'");
        if ($cid) $model->where('cid',$cid);
        return  $model->field($field)->where('status', 1)->where('hide', 0)->order('sort DESC,add_time DESC')->limit($first, $limit)->select();

    }
}