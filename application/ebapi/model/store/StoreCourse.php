<?php
/**
 *
 * @author: 招宝通
 */

namespace app\ebapi\model\store;

use app\admin\model\store\StoreCourseAttrValue as StoreCourseAttrValuemodel;
use app\core\model\system\SystemUserLevel;
use app\core\model\user\UserLevel;
use app\ebapi\model\article\Article;
use app\ebapi\model\news\News;
use app\ebapi\model\knowledge\Knowledge;
use app\ebapi\model\supply\Supply;
use app\ebapi\model\company\Company;
use app\ebapi\model\store\Brand;
use app\ebapi\model\user\User;
use basic\ModelBasic;
use app\core\util\SystemConfigService;
use traits\ModelTrait;

class StoreCourse extends ModelBasic
{
    use  ModelTrait;

    protected function getSliderImageAttr($value)
    {
        return json_decode($value,true)?:[];
    }

    public static function getValidCourse($courseId,$field = 'add_time,start_time,stop_time,type,browse,cate_id,code_path,cost,description,ficti,give_integral,id,image,is_bargain,is_benefit,is_best,is_del,is_hot,is_new,is_postage,is_seckill,is_show,keyword,mer_id,mer_use,ot_price,postage,price,sales,slider_image,sort,stock,store_info,store_name,unit_name,vip_price,IFNULL(sales,0) + IFNULL(ficti,0) as fsales')
    {
         $Course=self::where('is_del',0)->where('is_show',1)->where('id',$courseId)->field($field)->find();
         if($Course) return $Course->toArray();
         else return false;
    }

    public static function validWhere()
    {
        return self::where('is_del',0)->where('is_show',1);
    }

    /**
     * @return int|string
     * @throws \think\Exception
     */
    public static function getCount()
    {
        return self::where('is_show',1)->where('start_time','LT',time())->where('stop_time','GT',time())->count();
    }

    public static function getCourseList($data,$uid)
    {
        $merId      = $data['mer_id'];
        $sId        = $data['sid'];
        $cId        = $data['cid'];
        $keyword    = $data['keyword'];
        $priceOrder = $data['priceOrder'];
        $salesOrder = $data['salesOrder'];
        $news       = $data['news'];
        //$type       = $data['type'];
        $page       = $data['page'];
        $limit      = $data['limit'];
        $model      = self::validWhere();



        if($sId){
            $course_ids = self::getDb('store_course_cate')->where('cate_id',$sId)->column('course_id');

            if(count($course_ids)){
                $model = $model->where('id',"in",$course_ids);
            } else {
                $model = $model->where('cate_id',-1);
            }
        } else if($cId) {
            $sids = StoreCategory::pidBySidList($cId)?:[];
            if($sids){
                $sidsr = [];
                foreach($sids as $v){
                    $sidsr[] = $v['id'];
                }
                $model = $model->where('cate_id','IN',$sidsr);
            }
        }

        if(!empty($keyword))    $model = $model->where('keyword|store_name','LIKE',htmlspecialchars("%$keyword%"));
        if($news!=0)            $model = $model->where('is_new',1);
        //if($type!=0)            $model = $model->where('type',$type);
        if($merId!=0)           $model = $model->where('mer_id',$merId);
        $baseOrder = '';

        if($priceOrder) $baseOrder = $priceOrder == 'desc' ? 'price DESC' : 'price ASC';
        if($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC';//虚拟销量
        if($baseOrder) $baseOrder .= ', ';
        $model = $model->order($baseOrder.'sort DESC, add_time DESC');

        $list = $model->page((int)$page,(int)$limit)->field('id,store_name,store_info,cate_id,image,IFNULL(sales,0) + IFNULL(ficti,0) as sales,price,vip_price,stock,start_time,stop_time,ficti')->select();

        $list=count($list) ? $list->toArray() : [];
        foreach ($list as $item=>$value){
            $count = StoreOrder::where('course_id',$value['id'])->where('paid',1)->sum('total_num');
            $list[$item]['count'] = $count;
            $list[$item]['start_time'] = date('Y年m月d日 H:i',$value['start_time']);
            $list[$item]['stop_time'] = date('Y年m月d日 H:i',$value['stop_time']);;
        }
        return self::setLevelPrice($list,$uid);
    }
    /*
     * 分类搜索
     * @param string $value
     * @return array
     * */
    public static function getSearchStorePage($keyword,$uid)
    {
        $data = [];
        $model = self::validWhere();
        if(strlen(trim($keyword))) $model = $model->where('store_name|keyword','LIKE',"%$keyword%");
        $list = $model->field('id,store_name,cate_id,image,IFNULL(sales,0) + IFNULL(ficti,0) as sales,price,stock')->select();
        $storeCourse = self::setLevelPrice($list,$uid);
        if(count($storeCourse)){
            $data['is_store_course'] =  1;
            $data['storeCourseData'] =  $storeCourse;
        }

        $article = Article::where('title|tag','LIKE',"%$keyword%")->field('id,title,image,slider_image,add_time')->select();
        if(count($article)){
            foreach ($article as $item=>$value){
                $article[$item]['slider_image'] = json_decode($value['slider_image']);
                $article[$item]['add_time'] = date('Y-m-d',$value['add_time']);
            }
            $data['is_article'] =  1;
            $data['articleData'] =  $article;
        }

        $news = News::where('title','LIKE',"%$keyword%")->field('id,title,image')->select();
        if(count($news)){
            $data['is_news'] =  1;
            $data['newsData'] =  $news;
        }

        $knowledge = Knowledge::where('title','LIKE',"%$keyword%")->field('id,uid,title,image,slider_image,add_time')->select();
        if(count($knowledge)){
            foreach ($knowledge as $item=>$value){
                $knowledge[$item]['user'] = User::where('uid',$value['uid'])->field('uid,avatar')->find();
                $knowledge[$item]['slider_image'] = json_decode($value['slider_image']);
                $knowledge[$item]['add_time'] = date('Y-m-d',$value['add_time']);
            }
            $data['is_knowledge'] =  1;
            $data['knowledgeData'] =  $knowledge;
        }

        $supply = Supply::where('title','LIKE',"%$keyword%")->field('id,title,image')->select();
        if(count($supply)){
            $data['is_supply'] =  1;
            $data['supplyData'] =  $supply;
        }

        $newcourse = NewCourse::where('name','LIKE',"%$keyword%")->field('id,name,image')->select();
        if(count($newcourse)){
            $data['is_newcourse'] =  1;
            $data['newcourseData'] =  $newcourse;
        }

        $company = Company::where('title','LIKE',"%$keyword%")->field('id,title,logo')->select();
        if(count($company)){
            $data['is_company'] =  1;
            $data['companytData'] =  $company;
        }

        $brand = Brand::where('name','LIKE',"%$keyword%")->field('id,name,logo')->select();
        if(count($brand)){
            $data['is_brand'] =  1;
            $data['brandData'] =  $brand;
        }

        return $data;
    }
    /**
     * 新品产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getNewCourse($field = '*',$limit = 0,$uid=0)
    {
        $model = self::where('is_new',1)->where('is_del',0)
            ->where('stock','>',0)->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($limit);
        $list=$model->select();
        $list=count($list) ? $list->toArray() : [];
        return self::setLevelPrice($list,$uid);
    }

    /**
     * 热卖产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getHotCourse($field = '*',$limit = 0,$uid=0)
    {
        $model = self::where('is_hot',1)->where('is_del',0)
            ->where('stock','>',0)->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($limit);
        return self::setLevelPrice($model->select(),$uid);
    }

    /**
     * 热卖产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getHotCourseLoading($field = '*',$offset = 0,$limit = 0)
    {
        $model = self::where('is_hot',1)->where('is_del',0)
            ->where('stock','>',0)->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($offset,$limit);
        return $model->select();
    }

    /**
     * 精品产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getBestCourse($field = '*',$limit = 0,$uid=0)
    {
        $model = self::where('is_best',1)->where('is_del',0)
            ->where('stock','>',0)->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($limit);
        return self::setLevelPrice($model->select(),$uid);
    }

    /*
     * 设置会员价格
     * @param object | array $list 产品列表
     * @param int $uid 用户uid
     * @return array
     * */
    public static function setLevelPrice($list,$uid,$isSingle=false)
    {
        if(is_object($list)) $list=count($list) ? $list->toArray() : [];
        $levelId=UserLevel::getUserLevel($uid);
        if($levelId){
            $discount=UserLevel::getUserLevelInfo($levelId,'discount');
            $discount=bcsub(1,bcdiv($discount,100,2),2);
        }else{
            $discount=SystemUserLevel::getLevelDiscount();
            $discount=bcsub(1,bcdiv($discount,100,2),2);
        }
        //如果不是数组直接执行减去会员优惠金额
        if(!is_array($list))
            //不是会员原价返回
            if($levelId)
                //如果$isSingle==true 返回优惠后的总金额，否则返回优惠的金额
                return $isSingle ? bcsub($list,bcmul($discount,$list,2),2) : bcmul($discount,$list,2);
            else
                return $isSingle ? $list : 0;
        //当$list为数组时$isSingle==true为一维数组 ，否则为二维
       /* if($isSingle)
            $list['vip_price']=isset($list['price']) ? bcsub($list['price'],bcmul($discount,$list['price'],2),2) : 0;
        else
            foreach ($list as &$item){
                $item['vip_price']=isset($item['price']) ? bcsub($item['price'],bcmul($discount,$item['price'],2),2) : 0;
            }*/
        return $list;
    }


    /**
     * 优惠产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getBenefitCourse($field = '*',$limit = 0)
    {
        $model = self::where('is_benefit',1)
            ->where('is_del',0)->where('stock','>',0)
            ->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($limit);
        return $model->select();
    }

    public static function cateIdBySimilarityCourse($cateId,$field='*',$limit = 0)
    {
        $pid = StoreCategory::cateIdByPid($cateId)?:$cateId;
        $cateList = StoreCategory::pidByCategory($pid,'id') ?:[];
        $cid = [$pid];
        foreach ($cateList as $cate){
            $cid[] = $cate['id'];
        }
        $model = self::where('cate_id','IN',$cid)->where('is_show',1)->where('is_del',0)
            ->field($field)->order('sort DESC,id DESC');
        if($limit) $model->limit($limit);
        return $model->select();
    }

    public static function isValidCourse($courseId)
    {
        return self::be(['id'=>$courseId,'is_del'=>0,'is_show'=>1]) > 0;
    }

    public static function getCourseStock($courseId,$uniqueId = '')
    {
        return  $uniqueId == '' ?
            self::where('id',$courseId)->value('stock')?:0
            : StoreCourseAttr::uniqueByStock($uniqueId);
    }

    public static function decCourseStock($num,$courseId)
    {
        $res = false !== self::where('id',$courseId)->inc('sales',$num)->inc('people',1)->update();
        return $res;
    }

    /*
     * 减少销量,增加库存
     * @param int $num 增加库存数量
     * @param int $courseId 产品id
     * @param string $unique 属性唯一值
     * @return boolean
     * */
    public static function incCourseStock($num,$courseId,$unique = '')
    {
        if($unique){
            $res = false !== StoreCourseAttrValuemodel::incCourseAttrStock($courseId,$unique,$num);
            $res = $res && self::where('id',$courseId)->setDec('sales',$num);
        }else{
            $res = false !== self::where('id',$courseId)->inc('stock',$num)->dec('sales',$num)->update();
        }
        return $res;
    }

    public static function getPacketPrice($storeInfo,$courseValue)
    {
        $store_brokerage_ratio=SystemConfigService::get('store_brokerage_ratio');
        $store_brokerage_ratio=bcdiv($store_brokerage_ratio,100,2);
        if(count($courseValue)){
            $Maxkey=self::getArrayMax($courseValue,'price');
            $Minkey=self::getArrayMin($courseValue,'price');

            if(isset($courseValue[$Maxkey])){
                $value=$courseValue[$Maxkey];
                if($value['cost'] > $value['price'])
                    $maxPrice=0;
                else
                    $maxPrice=bcmul($store_brokerage_ratio,bcsub($value['price'],$value['cost']),0);
                unset($value);
            }else $maxPrice=0;

            if(isset($courseValue[$Minkey])){
                $value=$courseValue[$Minkey];
                if($value['cost'] > $value['price'])
                    $minPrice=0;
                else
                    $minPrice=bcmul($store_brokerage_ratio,bcsub($value['price'],$value['cost']),0);
                unset($value);
            }else $minPrice=0;
            if($minPrice==0 && $maxPrice==0)
                return 0;
            else
                return $minPrice.'~'.$maxPrice;
        }else{
            if($storeInfo['cost'] < $storeInfo['price'])
                return bcmul($store_brokerage_ratio,bcsub($storeInfo['price'],$storeInfo['cost']),0);
            else
                return 0;
        }
    }
    /*
     * 获取二维数组中最大的值
     * */
    public static function getArrayMax($arr,$field)
    {
        $temp=[];
        foreach ($arr as $k=>$v){
            $temp[]=$v[$field];
        }
        $maxNumber=max($temp);
        foreach ($arr as $k=>$v){
            if($maxNumber==$v[$field]) return $k;
        }
        return 0;
    }
    /*
     * 获取二维数组中最小的值
     * */
    public static function getArrayMin($arr,$field)
    {
        $temp=[];
        foreach ($arr as $k=>$v){
            $temp[]=$v[$field];
        }
        $minNumber=min($temp);
        foreach ($arr as $k=>$v){
            if($minNumber==$v[$field]) return $k;
        }
        return 0;
    }

}