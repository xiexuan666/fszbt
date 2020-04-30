<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-11
 * Time: 08:28
 */

namespace app\ebapi\model\job;

use think\Db;
use traits\ModelTrait;
use basic\ModelBasic;

class Job extends ModelBasic
{
    use ModelTrait;

    /**
     * 获取职位推荐
     * @param string $field
     * @return mixed
     */
    public static function getJobList($field = 'id,uid,cid,title,name,author,phone,address,region,experience,education,salary,welfare,tag,scale,image,slider_image,synopsis,is_show,is_top,is_new,is_hot,sort,visit,ficti,description,province,city,district,is_default'){
        $model = new self();
        $model = $model->field($field);
        $model = $model->where('is_show', 1);
        $model = $model->where('is_hot', 1);
        $model = $model->order('sort DESC,add_time DESC');
        return $model->select();
    }

    public static function getList($data,$uid)
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
                $model=$model->where('cate_id','IN',$sidsr);
            }
        }
        $model=$model->where('status',1);

        if(!empty($keyword)) $model = $model->where('title|synopsis','LIKE',htmlspecialchars("%$keyword%"));
        $baseOrder = '';
        if($fictiOrder) $baseOrder = $fictiOrder == 'desc' ? 'ficti DESC' : 'ficti ASC';
        if($visitOrder) $baseOrder = $visitOrder == 'desc' ? 'visit DESC' : 'visit ASC';
        if($news) $baseOrder = 'id DESC';
        if($baseOrder) $baseOrder .= ', ';


        $model = $model->order($baseOrder.'sort DESC, add_time DESC');
        $list = $model->page((int)$page,(int)$limit)->field('id,uid,cid,title,name,author,phone,address,region,experience,education,salary,welfare,tag,scale,image,slider_image,synopsis,is_show,is_top,is_new,is_hot,sort,visit,ficti,description,province,city,district,is_default')->select();

        $list = count($list) ? $list->toArray() : [];
        return $list;
    }
    public static function validWhere()
    {
        return self::where('is_show',1)->where('mer_id',0);
    }

    public static function getDetails($productId,$field = 'id,uid,cid,title,name,author,phone,address,region,experience,education,salary,welfare,tag,scale,image,slider_image,synopsis,is_show,is_top,is_new,is_hot,sort,visit,ficti,description,province,city,district,is_default')
    {
        $Product=self::where('is_show',1)->where('id',$productId)->field($field)->find();
        if($Product) return $Product->toArray();
        else return false;
    }

    public static function setDefaultJob($id,$uid)
    {
        self::beginTrans();
        $res1 = self::where('uid',$uid)->update(['is_default'=>0]);
        $res2 = self::where('id',$id)->where('uid',$uid)->update(['is_default'=>1]);
        $res =$res1 !== false && $res2 !== false;
        self::checkTrans($res);
        return $res;
    }

}