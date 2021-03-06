<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:24
 */

namespace app\admin\controller\answer;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use think\Url;
use app\admin\model\answer\AnswerCategory as AnswerCategoryModel;
use app\admin\model\answer\Answer as AnswerModel;

/**
 * 知识分类管理  控制器
 * Class AnswerCategory
 * @package app\admin\controller\answer
 */
class AnswerCategory extends AuthController
{
    /**
     * 分类管理
     * @return mixed
     */
    public function index(){
        $where = Util::getMore([
            ['status',''],
            ['title',''],
        ],$this->request);
        $this->assign('where',$where);
        $this->assign(AnswerCategoryModel::systemPage($where));
        return $this->fetch();
    }

    /**
     * 添加分类管理
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function create(){
        $f = array();
        $f[] = Form::select('pid','父级id')->setOptions(function(){
            $list = AnswerCategoryModel::getTierList();
            $menus[] = ['value'=>0,'label'=>'顶级分类'];
            foreach ($list as $menu){
                $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
            }
            return $menus;
        })->filterable(1);
        $f[] = Form::input('title','分类名称');
        $f[] = Form::input('intr','分类简介')->type('textarea');
        $f[] = Form::frameImageOne('image','分类图片',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image');
        $f[] = Form::number('sort','排序',0);
        $f[] = Form::radio('status','状态',1)->options([['value'=>1,'label'=>'显示'],['value'=>0,'label'=>'隐藏']]);
        $form = Form::make_post_form('添加分类',$f,Url::build('save'));
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');

    }

    /**
     * 上传图片
     */
    public function upload(){
        $res = Upload::image('file','article');
        $thumbPath = Upload::thumb($res->dir);
        if($res->status == 200)
            return Json::successful('图片上传成功!',['name'=>$res->fileInfo->getSaveName(),'url'=>Upload::pathToUrl($thumbPath)]);
        else
            return Json::fail($res->error);
    }

    /**
     * 保存分类管理
     * @param Request $request
     */
    public function save(Request $request){
        $data = Util::postMore([
            'title',
            'pid',
            'intr',
            ['new_id',[]],
            ['image',[]],
            ['sort',0],
            'status',],$request);
        if(!$data['title']) return Json::fail('请输入分类名称');
        if(count($data['image']) != 1) return Json::fail('请选择分类图片，并且只能上传一张');
        if($data['sort'] < 0) return Json::fail('排序不能是负数');
        $data['add_time'] = time();
        $data['image'] = $data['image'][0];
        $new_id = $data['new_id'];
        unset($data['new_id']);
        $res = AnswerCategoryModel::set($data);
        if(!AnswerModel::saveBatchCid($res['id'],implode(',',$new_id))) return Json::fail('文章列表添加失败');
        return Json::successful('添加分类成功!');
    }

    /**
     * 修改分类
     * @param $id
     * @return mixed|void
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function edit($id){
        if(!$id) return $this->failed('参数错误');
        $article = AnswerCategoryModel::get($id)->getData();
        if(!$article) return Json::fail('数据不存在!');
        $f = array();
        $f[] = Form::select('pid','父级id',(string)$article['pid'])->setOptions(function(){
            $list = AnswerCategoryModel::getTierList();
            $menus[] = ['value'=>0,'label'=>'顶级分类'];
            foreach ($list as $menu){
                $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
            }
            return $menus;
        })->filterable(1);
        $f[] = Form::input('title','分类名称',$article['title']);
        $f[] = Form::input('intr','分类简介',$article['intr'])->type('textarea');
        $f[] = Form::frameImageOne('image','分类图片',Url::build('admin/widget.images/index',array('fodder'=>'image')),$article['image'])->icon('image');
        $f[] = Form::number('sort','排序',0);
        $f[] = Form::radio('status','状态',$article['status'])->options([['value'=>1,'label'=>'显示'],['value'=>0,'label'=>'隐藏']]);
        $form = Form::make_post_form('编辑分类',$f,Url::build('update',array('id'=>$id)));
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');

    }



    public function update(Request $request, $id)
    {
        $data = Util::postMore([
            'pid',
            'title',
            'intr',
            ['image',[]],
            ['sort',0],
            'status',],$request);
        if(!$data['title']) return Json::fail('请输入分类名称');
        if(count($data['image']) != 1) return Json::fail('请选择分类图片，并且只能上传一张');
        if($data['sort'] < 0) return Json::fail('排序不能是负数');
        $data['image'] = $data['image'][0];
        if(!AnswerCategoryModel::get($id)) return Json::fail('编辑的记录不存在!');
        AnswerCategoryModel::edit($data,$id);
        return Json::successful('修改成功!');
    }

    /**
     * 删除分类
     * @param $id
     */
    public function delete($id)
    {
        $res = AnswerCategoryModel::delAnswerCategory($id);
        if(!$res)
            return Json::fail(AnswerCategoryModel::getErrorInfo('删除失败,请稍候再试!'));
        else
            return Json::successful('删除成功!');
    }
}