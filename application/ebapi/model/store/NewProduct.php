<?php
/**
 *
 * @author: 招宝通
 */

namespace app\ebapi\model\store;

use app\admin\model\store\NewProductAttrValue as NewProductAttrValuemodel;
use app\core\model\system\SystemUserLevel;
use app\core\model\user\UserLevel;
use basic\ModelBasic;
use app\core\util\SystemConfigService;
use traits\ModelTrait;

class NewProduct extends ModelBasic
{
    use  ModelTrait;

    protected function getPosterImageAttr($value)
    {
        return json_decode($value,true)?:[];
    }

    protected function getSliderImageAttr($value)
    {
        return json_decode($value,true)?:[];
    }

    public static function getValidProduct($productId,$field = '*')
    {
         $Product=self::where('is_del',0)->where('is_show',1)->where('id',$productId)->field($field)->find();
         if($Product) return $Product->toArray();
         else return false;
    }

    public static function validWhere()
    {
        return self::where('is_del',0)->where('is_show',1);
    }

    public static function getProductList($data,$uid)
    {
        $cId        = $data['cid'];
        $merId      = $data['mer_id'];
        $keyword    = $data['keyword'];
        $is_news    = $data['is_news'];
        $is_hot     = $data['is_hot'];
        $page       = $data['page'];
        $limit      = $data['limit'];
        $model      = self::validWhere();

        if($cId){
            $sids = NewCategory::IdBy($cId);
            if($sids){
                $sidsr = [];
                foreach($sids as $v){
                    $sidsr[] = $v['id'];
                }
            }
            $model = $model->where('cate_id','IN',$sidsr);
        }

        if(!empty($keyword))    $model = $model->where('name','LIKE',htmlspecialchars("%$keyword%"));
        if($merId)              $model = $model->where('mer_id',$merId);

        $baseOrder = '';
        if($is_news)    $baseOrder = 'add_time DESC';
        if($is_hot)     $baseOrder = 'browse DESC';
        if($baseOrder)  $baseOrder .= ', ';
        $model =    $model->order($baseOrder.'sort DESC');
        $list =     $model->page((int)$page,(int)$limit)->field('*')->select();

        $list = count($list) ? $list->toArray() : [];
        foreach ($list as $item=>$value){
            $cate_nam = NewCategory::where(array('id'=>$value['cate_id']))->find();
            $list[$item]['cate_name'] = $cate_nam['cate_name'];
            $list[$item]['add_time'] = date('Y-m-d',$value['add_time']);
        }
        return $list;
    }

    /*
     * 分类搜索
     * @param string $value
     * @return array
     * */
    public static function getSearchStorePage($keyword,$uid)
    {
        $model = self::validWhere();
        if(strlen(trim($keyword))) $model = $model->where('store_name|keyword','LIKE',"%$keyword%");
        $list = $model->field('id,store_name,cate_id,is_pay,pay_time,pay_price,image,IFNULL(sales,0) + IFNULL(ficti,0) as sales,price,stock')->select();
        return self::setLevelPrice($list,$uid);
    }
    /**
     * 新品产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getNewProduct($field = '*',$limit = 0,$uid=0)
    {
        $model = self::where('is_del',0)
            ->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($limit);
        $list = $model->select();
        $list = count($list) ? $list->toArray() : [];
        foreach ($list as $item=>$value){
            $cate_nam = NewCategory::where(array('id'=>$value['cate_id']))->find();
            $list[$item]['cate_name'] = $cate_nam['cate_name'];
            $list[$item]['add_time'] = date('Y-m-d',$value['add_time']);
        }
        return $list;
    }

    /**
     * 热卖产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getHotProduct($field = '*',$limit = 0,$uid=0)
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
    public static function getHotProductLoading($field = '*',$offset = 0,$limit = 0)
    {
        $model = self::where('is_hot',1)->where('is_show',1)->field($field)
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
    public static function getBestProduct($field = '*',$limit = 0,$uid=0)
    {
        $model = self::where('is_best',1)->where('is_del',0)
            ->where('stock','>',0)->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($limit);
        return self::setLevelPrice($model->select(),$uid);
    }


    /**
     * 优惠产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getBenefitProduct($field = '*',$limit = 0)
    {
        $model = self::where('is_benefit',1)
            ->where('is_del',0)->where('stock','>',0)
            ->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($limit);
        return $model->select();
    }

    public static function cateIdBySimilarityProduct($cateId,$field='*',$limit = 0)
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

    public static function isValidProduct($productId)
    {
        return self::be(['id'=>$productId,'is_del'=>0,'is_show'=>1]) > 0;
    }

    public static function getProductStock($productId,$uniqueId = '')
    {
        return  $uniqueId == '' ?
            self::where('id',$productId)->value('stock')?:0
            : NewProductAttr::uniqueByStock($uniqueId);
    }

    public static function decProductStock($num,$productId,$unique = '')
    {
        if($unique){
            $res = false !== NewProductAttrValuemodel::decProductAttrStock($productId,$unique,$num);
            $res = $res && self::where('id',$productId)->setInc('sales',$num);
        }else{
            $res = false !== self::where('id',$productId)->dec('stock',$num)->inc('sales',$num)->update();
        }
        return $res;
    }

    /*
     * 减少销量,增加库存
     * @param int $num 增加库存数量
     * @param int $productId 产品id
     * @param string $unique 属性唯一值
     * @return boolean
     * */
    public static function incProductStock($num,$productId,$unique = '')
    {
        if($unique){
            $res = false !== NewProductAttrValuemodel::incProductAttrStock($productId,$unique,$num);
            $res = $res && self::where('id',$productId)->setDec('sales',$num);
        }else{
            $res = false !== self::where('id',$productId)->inc('stock',$num)->dec('sales',$num)->update();
        }
        return $res;
    }

    public static function getPacketPrice($storeInfo,$productValue)
    {
        $store_brokerage_ratio=SystemConfigService::get('store_brokerage_ratio');
        $store_brokerage_ratio=bcdiv($store_brokerage_ratio,100,2);
        if(count($productValue)){
            $Maxkey=self::getArrayMax($productValue,'price');
            $Minkey=self::getArrayMin($productValue,'price');

            if(isset($productValue[$Maxkey])){
                $value=$productValue[$Maxkey];
                if($value['cost'] > $value['price'])
                    $maxPrice=0;
                else
                    $maxPrice=bcmul($store_brokerage_ratio,bcsub($value['price'],$value['cost']),0);
                unset($value);
            }else $maxPrice=0;

            if(isset($productValue[$Minkey])){
                $value=$productValue[$Minkey];
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