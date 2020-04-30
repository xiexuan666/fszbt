<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:24
 */

namespace app\admin\model\supplier;

use app\admin\model\system\SystemAdmin;
use traits\ModelTrait;
use basic\ModelBasic;
use think\Db;

use app\admin\model\supplier\SupplierCategory as CategoryModel;

/**
 * 知识管理 Model
 * Class Supplier
 */
class Supplier extends ModelBasic
{
    use ModelTrait;

    /**
     * 获取产品列表
     * @param $where
     * @return array
     * @throws \think\Exception
     */
    public static function getList($where){
        $model = self::getModelObject($where);
        $model = $model->order('is_top desc,sort desc');
        $model = $model->page((int)$where['page'],(int)$where['limit']);
        $data = ($data = $model->select()) && count($data) ? $data->toArray():[];
        foreach ($data as &$item){
            $cateName = CategoryModel::where('id', 'IN', $item['cate_id'])->column('cate_name', 'id');
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
                $model = $model->where('name|id','LIKE',"%$where[title]%");
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
        return  " `cate_id` LIKE '$lcateid' OR `cate_id` LIKE '$ccatid' OR `cate_id` LIKE '$ratidid' OR `cate_id`=$cateid";
    }


    /**
     * 获取配置分类
     * @param array $where
     * @return array
     */
    public static function getAll($where = array()){

        $model = new self;
        if($where['cate_name'] !== '') $model = $model->where('cate_name','LIKE',"%$where[cate_name]%");
        if($where['cate_id'] !== '')
            $model = $model->where('cate_id','in',$where['cate_id']);
        else
            if($where['merchant'])
                $model = $model->where('mer_id','GT',0);
            else
                $model = $model->where('mer_id',0);

            return self::page($model,function($item){

            $item['catename'] = Db::name('SupplierCategory')->where('id',$item['cate_id'])->value('cate_name');
        },$where);
    }

    /**
     * 删除图文
     * @param $id
     * @return bool
     */
    public static function del($id){
        return self::where('id',$id)->delete();
        //return self::edit(['status'=>0],$id,'id');
    }

    /**
     * 获取指定字段的值
     * @return array
     */
    public static function getNews()
    {
        return self::where('status',1)->where('hide',0)->order('id desc')->column('id,title');
    }

    /**
     * 给表中的字符串类型追加值
     * 删除所有有当前分类的id之后重新添加
     * @param $cid
     * @param $id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function saveBatchCid($cid,$id){
        $res_all = self::where('cid','LIKE',"%$cid%")->select();//获取所有有当前分类的图文
        foreach ($res_all as $k=>$v){
            $cid_arr = explode(',',$v['cid']);
            if(in_array($cid,$cid_arr)){
                $key = array_search($cid, $cid_arr);
                array_splice($cid_arr, $key, 1);
            }
            if(empty($cid_arr)) {
                $data['cid'] = 0;
                self::edit($data,$v['id']);
            }else{
                $data['cid'] = implode(',',$cid_arr);
                self::edit($data,$v['id']);
            }
        }
        $res = self::where('id','IN',$id)->select();
        foreach ($res as $k=>$v){
            if(!in_array($cid,explode(',',$v['cid']))){
                if(!$v['cid']){
                    $data['cid'] = $cid;
                }else{
                    $data['cid'] = $v['cid'].','.$cid;
                }
                self::edit($data,$v['id']);
            }
        }
        return true;
    }

    public static function setContent($id,$content){
        $count = Db::name('SupplierContent')->where('nid',$id)->count();
        $data['nid'] = $id;
        $data['content'] = $content;
        if($count){
            $res = Db::name('SupplierContent')->where('nid',$id)->setField('content',$content);
            if($res !== false) $res = true;
        }
        else
            $res = Db::name('SupplierContent')->insert($data);
        return $res;
    }

    public static function merchantPage($where = array()){
        $model = new self;
        if($where['title'] !== '') $model = $model->where('title','LIKE',"%$where[title]%");
        if($where['cid'] !== '') $model = $model->where('cid','LIKE',"%$where[cid]%");
        $model = $model
            ->where('status',1)
            ->where('hide',0)
            ->where('admin_id',$where['admin_id'])
            ->where('mer_id',$where['mer_id']);
        return self::page($model,function($item){
            $item['content'] = Db::name('SupplierContent')->where('nid',$item['id'])->value('content');
        },$where);
    }

    /**
     * 获取指定文章列表  图文管理使用
     * @param string $id
     * @param string $field
     * @return false|PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getArticleList($id = '',$field = 'title,author,image_input,synopsis,id'){
        $list = self::where('id','IN',$id)->field($field)->select();
        foreach ($list as $k=>$v){
            $list[$k]['content'] = Db::name('SupplierContent')->where('nid',$v['id'])->value('content');
        }
        return $list;
    }
}