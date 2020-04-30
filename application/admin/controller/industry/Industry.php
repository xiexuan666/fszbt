<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:24
 */

namespace app\admin\controller\industry;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use think\Url;
use app\admin\model\industry\Industry as IndustryModel;

/**
 * 人才分类管理  控制器
 * Class Industry
 * @package app\admin\controller\industry
 */
class Industry extends AuthController
{
    /**
     * 分类管理
     * @return mixed
     */
    public function index(){
        return $this->fetch();
    }

    public function getList(){
        $where = Util::getMore([
            ['status',''],
            ['name',''],
            ['page',1],
            ['limit',20]
        ]);
        return Json::successlayui(IndustryModel::getList($where));
    }

    /**
     * 显示隐藏
     * @return json
     */
    public function set_show($status='',$id=''){
        ($status == '' || $id=='') && Json::fail('缺少参数');
        $res = IndustryModel::where(['id'=>$id])->update(['status'=>(int)$status]);
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
        if(IndustryModel::where(['id'=>$id])->update([$field=>$value]))
            return Json::successful('保存成功');
        else
            return Json::fail('保存失败');
    }


    /**
     * 删除分类
     * @param $id
     */
    public function delete($id)
    {
        $res = IndustryModel::delIndustry($id);

        if(!$res)
            return Json::fail(IndustryModel::getErrorInfo('删除失败,请稍候再试!'));
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
        $f[] = Form::input('name','名称');
        $f[] = Form::frameImageOne('image','图片',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image');
        $f[] = Form::number('sort','排序',0);
        $f[] = Form::radio('status','状态',1)->options([['value'=>1,'label'=>'显示'],['value'=>0,'label'=>'隐藏']]);
        $form = Form::make_post_form('添加',$f,Url::build('save'));
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');

    }
    
    /**
     * 保存分类管理
     * @param Request $request
     */
    public function save(Request $request){
        $data = Util::postMore([
            'name',
            ['image',[]],
            ['sort',0],
            'status',],$request);
        if(!$data['name']) return Json::fail('请输入名称');
        if(count($data['image']) != 1) return Json::fail('请选择图片，并且只能上传一张');
        if($data['sort'] < 0) return Json::fail('排序不能是负数');
        $data['add_time'] = time();
        $data['pic'] = $data['image'][0];
        IndustryModel::set($data);
        return Json::successful('添加成功!');
    }

    /**
     * 修改分类
     * @param $id
     * @return mixed|void
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function edit($id){
        if(!$id) return $this->failed('参数错误');
        $article = IndustryModel::get($id)->getData();
        if(!$article) return Json::fail('数据不存在!');
        $f = array();
        $f[] = Form::input('name','名称',$article['name']);
        $f[] = Form::frameImageOne('image','图片',Url::build('admin/widget.images/index',array('fodder'=>'image')),$article['pic'])->icon('image');
        $f[] = Form::number('sort','排序',0);
        $f[] = Form::radio('status','状态',$article['status'])->options([['value'=>1,'label'=>'显示'],['value'=>0,'label'=>'隐藏']]);
        $form = Form::make_post_form('编辑',$f,Url::build('update',array('id'=>$id)));
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');

    }



    public function update(Request $request, $id)
    {
        $data = Util::postMore([
            'name',
            ['image',[]],
            ['sort',0],
            'status',],$request);
        if(!$data['name']) return Json::fail('请输入名称');
        if(count($data['image']) != 1) return Json::fail('请选择图片，并且只能上传一张');
        if($data['sort'] < 0) return Json::fail('排序不能是负数');
        $data['pic'] = $data['image'][0];
        if(!IndustryModel::get($id)) return Json::fail('编辑的记录不存在!');
        IndustryModel::edit($data,$id);
        return Json::successful('修改成功!');
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

}