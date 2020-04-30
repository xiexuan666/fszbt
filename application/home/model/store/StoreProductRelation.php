<?php
/**
 *
 * @author: 招宝通
 */

namespace app\home\model\store;


use basic\ModelBasic;
use behavior\home\StoreProductBehavior;
use service\HookService;
use traits\ModelTrait;

class StoreProductRelation extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['add_time'];

    protected function setAddTimeAttr($value)
    {
        return time();
    }

    public static function productRelation($productId,$uid,$relationType,$category = 'product')
    {
        if(!$productId) return self::setErrorInfo('产品不存在!');
        $relationType = strtolower($relationType);
        $category = strtolower($category);
        $data = ['uid'=>$uid,'product_id'=>$productId,'type'=>$relationType,'category'=>$category];
        if(self::be($data)) return true;
        self::set($data);
        HookService::afterListen('store_'.$category.'_'.$relationType,$productId,$uid,false,StoreProductBehavior::class);
        return true;
    }

    public static function unProductRelation($productId,$uid,$relationType,$category = 'product')
    {
        if(!$productId) return self::setErrorInfo('产品不存在!');
        $relationType = strtolower($relationType);
        $category = strtolower($category);
        self::where(['uid'=>$uid,'product_id'=>$productId,'type'=>$relationType,'category'=>$category])->delete();
        HookService::afterListen('store_'.$category.'_un_'.$relationType,$productId,$uid,false,StoreProductBehavior::class);
        return true;
    }

    public static function productRelationNum($productId,$relationType,$category = 'product')
    {
        $relationType = strtolower($relationType);
        $category = strtolower($category);
        return self::where('type',$relationType)->where('product_id',$productId)->where('category',$category)->count();
    }

    public static function isProductRelation($product_id,$uid,$relationType,$category = 'product')
    {
        $type = strtolower($relationType);
        $category = strtolower($category);
        return self::be(compact('product_id','uid','type','category'));
    }

}