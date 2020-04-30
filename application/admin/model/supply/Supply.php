<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:24
 */

namespace app\admin\model\supply;

use traits\ModelTrait;
use basic\ModelBasic;
use think\Db;

use app\admin\model\user\User;
use app\admin\model\company\Company;
use app\admin\model\store\Brand;
use app\admin\model\supply\SupplyCategory as CategoryModel;

/**
 * 招商 Model
 * Class Supply
 */
class Supply extends ModelBasic
{
    use ModelTrait;

    /**
     * 获取分类
     * @param array $where
     * @return array
     */
    public static function getAll($where = array()){
        $model = new self;
        if($where['title'] !== '') $model = $model->where('title','LIKE',"%$where[title]%");
        if($where['cid'] !== ''){
            $model = $model->where('cid','in',$where['cid']);
        }

        return self::page($model,function($item){
            $item['catename'] = Db::name('SupplyCategory')->where('id',$item['cid'])->value('title');
        },$where);
    }

    /**
     * 获取产品列表
     * @param $where
     * @return array
     * @throws \think\Exception
     */
    public static function getList($where){
        $model = self::getModelObject($where);
        $model = $model->order('is_pay desc,is_banner desc,sort desc');
        $model = $model->page((int)$where['page'],(int)$where['limit']);
        $data = ($data = $model->select()) && count($data) ? $data->toArray():[];
        foreach ($data as &$item){
            $user = User::where('uid',$item['uid'])->find();
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

            $cateName = CategoryModel::where('id', 'IN', $item['cid'])->column('title', 'id');
            $item['ctitle']=is_array($cateName) ? implode(',',$cateName) : '';
            $item['add_time']=date('Y-m-d',$item['add_time']);
        }
        $count = self::getModelObject($where)->count();
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
            if(isset($where['title']) && $where['title']!=''){
                $model = $model->where('title|id','LIKE',"%$where[title]%");
            }
            if(isset($where['cid']) && trim($where['cid'])!=''){
                $catid1 = $where['cid'].',';//匹配最前面的cateid
                $catid2 = ','.$where['cid'].',';//匹配中间的cateid
                $catid3 = ','.$where['cid'];//匹配后面的cateid
                $catid4 = $where['cid'];//匹配全等的cateid
                $sql = " LIKE '$catid1%' OR `cid` LIKE '%$catid2%' OR `cid` LIKE '%$catid3' OR `cid`=$catid4";
                $model->where(self::getPidSql($where['cid']));
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
        //查询如果有子分类获取子分类查询sql语句
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
        return  " `cid` LIKE '$lcateid' OR `cid` LIKE '$ccatid' OR `cid` LIKE '$ratidid' OR `cid`=$cateid";
    }

    /**
     * 删除图文
     * @param $id
     * @return bool
     */
    public static function del($id){
        return self::where('id',$id)->delete();
    }
}