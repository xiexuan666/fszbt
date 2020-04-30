<?php
/**
 *
 * @author: 招宝通
 */

namespace app\admin\model\store;

use traits\ModelTrait;
use basic\ModelBasic;
use app\admin\model\store\CompanyClass as CategoryModel;
use app\admin\model\user\User;
use app\admin\model\company\Company;
use app\admin\model\store\Brand;

/**
 * 产品管理 model
 * Class CompanyProduct
 * @package app\admin\model\store
 */
class CompanyProduct extends ModelBasic
{
    use ModelTrait;

    /**删除产品
     * @param $id
     */
    public static function proDelete($id){

    }

    /**
     * 获取产品列表
     * @param $where
     * @return array
     */
    public static function getList($where){
        $model = self::getModelObject($where);
        $model = $model->order('mer_id desc,cate_id desc,sort desc');
        $model=$model->page((int)$where['page'],(int)$where['limit']);
        $data = ($data = $model->select()) && count($data) ? $data->toArray():[];

        foreach ($data as &$item){
            $user = User::where('uid',$item['mer_id'])->find();
            switch ($user['level']){
                //企业商
                case 7:
                    $company = Company::where('uid',$user['uid'])->field('id,title,logo')->find();
                    $item['company_type'] = '企业';
                    $item['company_name'] = $company['title'];
                    break;
                //品牌商
                case 8:
                    $brand = Brand::where('mer_id',$user['uid'])->field('id,name,logo')->find();
                    $item['company_type'] = '品牌';
                    $item['company_name'] = $brand['name'];
                    break;
                default:
                    $item['company_type'] = '个人';
                    $item['company_name'] = $user['nickname'];
            }

            $cateName = CategoryModel::where('id', 'IN', $item['cate_id'])->column('cate_name', 'id');
            $item['cate_name']=is_array($cateName) ? implode(',',$cateName) : '';
            $item['add_time']=date('Y-m-d',$item['add_time']);

            $item['slider_image'] = json_decode($item['slider_image']);
        }

        $count=self::getModelObject($where)->count();
        return compact('count','data');
    }

    /**
     * 获取连表MOdel
     * @param $model
     * @return object
     */
    public static function getModelObject($where=[]){
        $model=new self();
        if(!empty($where)){
            if(isset($where['name']) && $where['name']!=''){
                $model = $model->where('name|cate_id','LIKE',"%$where[name]%");
            }
            if(isset($where['cate_id']) && trim($where['cate_id'])!=''){
                $catid1 = $where['cate_id'].',';//匹配最前面的cateid
                $catid2 = ','.$where['cate_id'].',';//匹配中间的cateid
                $catid3 = ','.$where['cate_id'];//匹配后面的cateid
                $catid4 = $where['cate_id'];//匹配全等的cateid
                $sql = " LIKE '$catid1%' OR `cate_id` LIKE '%$catid2%' OR `cate_id` LIKE '%$catid3' OR `cate_id`=$catid4";
                $model->where(self::getPidSql($where['cate_id']));
            }
        }
        return $model;
    }

    /** 如果有子分类查询子分类获取拼接查询sql
     * @param $cateid
     * @return string
     */
    protected static function getPidSql($cateid){
        $sql = self::getCateSql($cateid);
        $ids = CategoryModel::where('pid', $cateid)->column('id');
        if($ids) foreach ($ids as $v) $sql .= " OR ".self::getcatesql($v);
        return $sql;
    }

    /**根据cateid查询产品 拼sql语句
     * @param $cateid
     * @return string
     */
    protected static function getCateSql($cateid){
        $lcateid = $cateid.',%';//匹配最前面的cateid
        $ccatid = '%,'.$cateid.',%';//匹配中间的cateid
        $ratidid = '%,'.$cateid;//匹配后面的cateid
        return  " `cate_id` LIKE '$lcateid' OR `cate_id` LIKE '$ccatid' OR `cate_id` LIKE '$ratidid' OR `cate_id`=$cateid";
    }


}