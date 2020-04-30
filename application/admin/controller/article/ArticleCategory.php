<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:24
 */

namespace app\admin\controller\article;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use think\Url;
use app\admin\model\article\ArticleCategory as CategoryModel;
use app\admin\model\article\Article as ArticleModel;

/**
 * 知识分类管理  控制器
 * Class ArticleCategory
 * @package app\admin\controller\article
 */
class ArticleCategory extends AuthController
{
    /**
     * 分类管理
     * @return mixed
     */
    public function index(){
        $this->assign('pid',$this->request->get('pid',0));
        $this->assign('cate',CategoryModel::getTierList());
        return $this->fetch();
    }

    public function getList(){
        $where = Util::getMore([
            ['status',''],
            ['pid',$this->request->param('pid','')],
            ['title',''],
            ['page',1],
            ['limit',20],
            ['order','']
        ]);
        return Json::successlayui(CategoryModel::getList($where));
    }

    /**
     * 显示隐藏
     * @return json
     */
    public function set_show($status='',$id=''){
        ($status == '' || $id=='') && Json::fail('缺少参数');
        $res = CategoryModel::where(['id'=>$id])->update(['status'=>(int)$status]);
        if($res){
            return Json::successful($status==1 ? '显示成功':'隐藏成功');
        }else{
            return Json::fail($status==1 ? '显示失败':'隐藏失败');
        }
    }

    /**
     * 快速编辑
     * @return json
     */
    public function set_category($field='',$id='',$value=''){
        $field=='' || $id=='' || $value=='' && Json::fail('缺少参数');
        if(CategoryModel::where(['id'=>$id])->update([$field=>$value]))
            return Json::successful('保存成功');
        else
            return Json::fail('保存失败');
    }

    /**
     * 删除
     * @param $id
     */
    public function delete($id)
    {
        $res = CategoryModel::del($id);
        if(!$res)
            return Json::fail(CategoryModel::getErrorInfo('删除失败,请稍候再试!'));
        else
            return Json::successful('删除成功!');
    }

    /**
     * 添加分类管理
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function create(){
        $f = array();
        $f[] = Form::select('pid','父级id')->setOptions(function(){
            $list = CategoryModel::getTierList();
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
        $res = CategoryModel::set($data);
        if(!ArticleModel::saveBatchCid($res['id'],implode(',',$new_id))) return Json::fail('文章列表添加失败');
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
        $article = CategoryModel::get($id)->getData();
        if(!$article) return Json::fail('数据不存在!');
        $f = array();
        $f[] = Form::select('pid','父级id',(string)$article['pid'])->setOptions(function(){
            $list = CategoryModel::getTierList();
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
        if(!CategoryModel::get($id)) return Json::fail('编辑的记录不存在!');
        CategoryModel::edit($data,$id);
        return Json::successful('修改成功!');
    }


}