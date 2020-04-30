<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:24
 */

namespace app\admin\model\article;

use traits\ModelTrait;
use app\admin\model\article\Article as ArticleModel;
use basic\ModelBasic;
use service\UtilService as Util;

/**
 * 知识分类 model
 * Class ArticleCategory
 * @package app\admin\model\article
 */
class ArticleCategory extends ModelBasic
{
    use ModelTrait;

    /**
     * 获取系统分页数据   分类
     * @param array $where
     * @return array
     */
    public static function getList($where = array()){

        $data = ($data = self::systemPage($where,true)->page((int)$where['page'],(int)$where['limit'])->select()) && count($data) ? $data->toArray() :[];
        foreach ($data as &$item){
            if($item['pid']){
                $item['pid_name'] = self::where('id',$item['pid'])->value('title');
            }else{
                $item['pid_name'] = '顶级';
            }
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
        if($where['pid'])  $model = $model->where('pid',$where['pid']);
        else if($where['pid']=='' && $where['title']=='') $model = $model->where('pid',0);
        if($where['status'] != '')  $model = $model->where('status',$where['status']);
        if($where['title'] != '')  $model = $model->where('title','LIKE',"%$where[title]%");
        if($isAjax===true){
            if(isset($where['order']) && $where['order']!=''){
                $model=$model->order(self::setOrder($where['order']));
            }else{
                $model=$model->order('sort desc,id desc');
            }
            return $model;
        }
        return self::page($model,function ($item){
            if($item['pid']){
                $item['pid_name'] = self::where('id',$item['pid'])->value('title');
            }else{
                $item['pid_name'] = '顶级';
            }
        },$where);
    }


    /**
     * 删除分类
     * @param $id
     * @return bool
     */
    public static function delArticleCategory($id)
    {
        if(count(self::getArticle($id,'*'))>0)
            return self::setErrorInfo('请先删除改分类下的文章!');
        return self::where('id',$id)->delete();
    }

    /**
     * 获取分类名称和id     field
     * @param $field
     * @return array
     */
    public  static function getField($field){
        return self::where('is_del','eq',0)->where('status','eq',1)->where('hidden','eq',0)->column($field);
    }

    /**
     * 分级排序列表
     * @param null $model
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getTierList($model = null)
    {
        if($model === null) $model = new self();
        return Util::sortListTier($model->select()->toArray());
    }

    /**
     * 获取分类底下的文章
     * @param $id
     * @param $field
     * @return array
     */
    public static function getArticle($id,$field){
        $res = ArticleModel::where('status',1)->where('hide',0)->column($field,'id');
        $new_res = array();
        foreach ($res as $k=>$v){
            $cid_arr = explode(',',$v['cid']);
            if(in_array($id,$cid_arr)){
                $new_res[$k] = $res[$k];
            }
        }
        return $new_res;
    }
}