<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:24
 */

namespace app\admin\model\course;

use app\admin\model\system\SystemAdmin;
use traits\ModelTrait;
use basic\ModelBasic;
use think\Db;

/**
 * 知识管理 Model
 * Class Course
 */
class Course extends ModelBasic
{
    use ModelTrait;

    /**
     * 获取配置分类
     * @param array $where
     * @return array
     */
    public static function getAll($where = array()){
        $model = new self;
        if($where['title'] !== '') $model = $model->where('title','LIKE',"%$where[title]%");
        if($where['cid'] !== '')
            $model = $model->where('cid','in',$where['cid']);
        else
            if($where['merchant'])
                $model = $model->where('mer_id','GT',0);
            else
                $model = $model->where('mer_id',0);
        $model = $model->where('status',1)->where('hide',0);
        return self::page($model,function($item){
            if(!$item['mer_id']) $item['admin_name'] = '总后台管理员---》'.SystemAdmin::where('id',$item['admin_id'])->value('real_name');
            else $item['admin_name'] = Merchant::where('id',$item['mer_id'])->value('mer_name').'---》'.MerchantAdmin::where('id',$item['admin_id'])->value('real_name');
            $item['catename'] = Db::name('CourseCategory')->where('id',$item['cid'])->value('title');
        },$where);
    }

    /**
     * 删除图文
     * @param $id
     * @return bool
     */
    public static function del($id){
        return self::edit(['status'=>0],$id,'id');
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
        $count = Db::name('CourseContent')->where('nid',$id)->count();
        $data['nid'] = $id;
        $data['content'] = $content;
        if($count){
            $res = Db::name('CourseContent')->where('nid',$id)->setField('content',$content);
            if($res !== false) $res = true;
        }
        else
            $res = Db::name('CourseContent')->insert($data);
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
            $item['content'] = Db::name('CourseContent')->where('nid',$item['id'])->value('content');
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
    public static function getCourseList($id = '',$field = 'title,author,image_input,synopsis,id'){
        $list = self::where('id','IN',$id)->field($field)->select();
        foreach ($list as $k=>$v){
            $list[$k]['content'] = Db::name('CourseContent')->where('nid',$v['id'])->value('content');
        }
        return $list;
    }
}