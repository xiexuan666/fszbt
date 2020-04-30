<?php
/**
 *
 * @author: 招宝通
 */

namespace app\admin\model\store;

use traits\ModelTrait;
use basic\ModelBasic;
use service\UtilService;

use app\admin\model\user\User;
use app\admin\model\company\Company;
use app\admin\model\store\Brand;

/**
 * Class CompanyClass
 * @package app\admin\model\store
 */
class CompanyClass extends ModelBasic
{
    use ModelTrait;

    /*
     * 异步获取分类列表
     * @param $where
     * @return array
     */
    public static function getList($where){
        $data = ($data = self::systemPage($where,true)->page((int)$where['page'],(int)$where['limit'])->select()) && count($data) ? $data->toArray() :[];

        foreach ($data as &$item){
            $user = User::where('uid',$item['mer_id'])->find();
            switch ($user['level']){
                //企业商
                case 7:
                    $company = Company::where('uid',$user['uid'])->field('id,title,logo')->find();
                    $item['company_name'] = '企业 & '.$company['title'];
                    break;
                //品牌商
                case 8:
                    $brand = Brand::where('mer_id',$user['uid'])->field('id,name,logo')->find();
                    $item['company_name'] = '品牌 & '.$brand['name'];
                    break;
                default:
                    $item['company_name'] = '个人 & '.$user['nickname'];
            }
            $item['add_time'] = date('Y-m-d',$item['add_time']);
        }
        $count=self::systemPage($where,true)->count();
        return compact('count','data');
    }
    /**
     * @param $where
     * @return array
     */
    public static function systemPage($where,$isAjax=false){
        $model = new self;
        if($where['is_show'] != '')  $model = $model->where('is_show',$where['is_show']);
        if($where['cate_name'] != '')  $model = $model->where('cate_name','LIKE',"%$where[cate_name]%");
        if($isAjax===true){
            if(isset($where['order']) && $where['order']!=''){
                $model=$model->order(self::setOrder($where['order']));
            }else{
                $model=$model->order('sort desc,id desc');
            }
            return $model;
        }
        return self::page($model,function ($item){
            if($item['mer_id']){
                $user = User::where('uid',$item['mer_id'])->find();
                switch ($user['level']){
                    //企业商
                    case 7:
                        $company = Company::where('uid',$user['uid'])->field('id,title,logo')->find();
                        $item['company_name'] = '企业 & '.$company['title'];
                        break;
                    //品牌商
                    case 8:
                        $brand = Brand::where('mer_id',$user['uid'])->field('id,name,logo')->find();
                        $item['company_name'] = '品牌 & '.$brand['name'];
                        break;
                    default:
                        $item['company_name'] = '个人 & '.$user['nickname'];

                }
            }
        },$where);
    }

    /**
     * 获取顶级分类
     * @return array
     */
    public static function getCategory($field = 'id,cate_name')
    {
        return self::where('is_show',1)->column($field);
    }

    /**
     * 分级排序列表
     * @param null $model
     * @return array
     */
    public static function getTierList($model = null)
    {
        if($model === null) $model = new self();
        return UtilService::sortListTier($model->order('sort desc,id desc')->select()->toArray());
    }

    public static function delCategory($id){
        $count = self::where('pid',$id)->count();
        if($count)
            return false;
        else{
            return self::del($id);
        }
    }
}