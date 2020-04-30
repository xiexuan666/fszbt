<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-11
 * Time: 08:28
 */

namespace app\ebapi\model\dealers;

use think\Db;
use traits\ModelTrait;
use basic\ModelBasic;

class Dealers extends ModelBasic
{
    use ModelTrait;

    /**
     * 获取人气企业
     * @param string $field
     * @return mixed
     */
    public static function getDealersListHot($field = '*'){
        $model = new self();
        $model = $model->field($field);
        $model = $model->where('status', 1);
        $model = $model->where('hide', 0);
        $model = $model->where('is_hot', 1);
        $model = $model->order('visit DESC,add_time DESC');
        return $model->select();
    }

    /**
     * 企业列表
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getDealersLoading($field = '*',$offset = 0,$limit = 0)
    {
        $model = self::where('status',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($offset,$limit);
        return $model->select();
    }

    public static function getDealersList($data,$uid)
    {
        $cId = $data['cid'];
        $keyword = $data['keyword'];
        $fictiOrder = $data['fictiOrder'];
        $visitOrder = $data['visitOrder'];
        $news = $data['news'];
        $page = $data['page'];
        $limit = $data['limit'];
        $model = self::validWhere();

        if($cId){
            $sids = StoreCategory::pidBySidList($cId)?:[];
            if($sids){
                $sidsr = [];
                foreach($sids as $v){
                    $sidsr[] = $v['id'];
                }
                $model=$model->where('cid','IN',$sidsr);
            }
        }

        if(!empty($keyword)) $model = $model->where('title|description','LIKE',htmlspecialchars("%$keyword%"));
        $baseOrder = '';
        if($fictiOrder) $baseOrder = $fictiOrder == 'desc' ? 'ficti DESC' : 'ficti ASC';
        if($visitOrder) $baseOrder = $visitOrder == 'desc' ? 'visit DESC' : 'visit ASC';
        if($news) $baseOrder = 'id DESC';
        if($baseOrder) $baseOrder .= ', ';

        $model = $model->order($baseOrder.'sort DESC, add_time DESC');
        $list = $model->page((int)$page,(int)$limit)->field('*')->select();

        $list = count($list) ? $list->toArray() : [];
        return $list;
    }
    public static function validWhere()
    {
        return self::where('status',1);
    }

    public static function getValidProduct($productId,$field = '*')
    {
        $Product=self::where('status',1)->where('is_show',1)->where('id',$productId)->field($field)->find();
        if($Product) return $Product->toArray();
        else return false;
    }
}