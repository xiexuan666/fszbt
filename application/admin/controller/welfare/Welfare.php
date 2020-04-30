<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:24
 */

namespace app\admin\controller\welfare;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use think\Url;
use app\admin\model\welfare\Welfare as WelfareModel;

/**
 * 福利配置管理  控制器
 * Class Welfare
 * @package app\admin\controller\welfare
 */
class Welfare extends AuthController
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
            ['title',''],
            ['page',1],
            ['limit',20]
        ]);
        return Json::successlayui(WelfareModel::getList($where));
    }

    /**
     * 显示隐藏
     * @return json
     */
    public function set_show($status='',$id=''){
        ($status == '' || $id=='') && Json::fail('缺少参数');
        $res = WelfareModel::where(['id'=>$id])->update(['status'=>(int)$status]);
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
        if(WelfareModel::where(['id'=>$id])->update([$field=>$value]))
            return Json::successful('保存成功');
        else
            return Json::fail('保存失败');
    }


    /**
     * 删除福利配置
     * @param $id
     */
    public function delete($id) {
        $res = WelfareModel::delWelfare($id);
        if(!$res)
            return Json::fail(WelfareModel::getErrorInfo('删除失败,请稍候再试!'));
        else
            return Json::successful('删除成功!');
    }

    /**
     * 添加福利配置
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function create() {
        $f = array();
        $f[] = Form::input('title','名称');
        $f[] = Form::input('intr','描述')->type('textarea');
        $f[] = Form::frameImageOne('image','图标',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image');
        $f[] = Form::number('sort','排序',0);
        $f[] = Form::radio('status','状态',1)->options([['value'=>1,'label'=>'显示'],['value'=>0,'label'=>'隐藏']]);
        $form = Form::make_post_form('添加福利配置',$f,Url::build('save'));
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 上传图片
     */
    public function upload(){
        $res = Upload::image('file','welfare');
        $thumbPath = Upload::thumb($res->dir);
        if($res->status == 200)
            return Json::successful('图片上传成功!',['name'=>$res->fileInfo->getSaveName(),'url'=>Upload::pathToUrl($thumbPath)]);
        else
            return Json::fail($res->error);
    }

    /**
     * 保存福利配置
     * @param Request $request
     */
    public function save(Request $request) {
        $data = Util::postMore([
            'title',
            'intr',
            ['image',[]],
            ['sort',0],
            'status',],$request);
        if(!$data['title']) return Json::fail('请输入名称');
        if($data['sort'] < 0) return Json::fail('排序不能是负数');
        if(count($data['image']) > 0) $data['image'] = $data['image'][0];
        $data['add_time'] = time();
        $res = WelfareModel::set($data);
        if(!$res) return Json::fail('文章列表添加失败');
        return Json::successful('添加分类成功!');
    }

    /**
     * 修改福利配置
     * @param $id
     * @return mixed|void
     * @throws \FormBuilder\exception\FormBuilderException
     * @throws \think\exception\DbException
     */
    public function edit($id) {
        if(!$id) return $this->failed('参数错误');
        $welfare = WelfareModel::get($id)->getData();
        if(!$welfare) return Json::fail('数据不存在!');
        $f = array();
        $f[] = Form::input('title','名称',$welfare['title']);
        $f[] = Form::input('intr','描述',$welfare['intr'])->type('textarea');
        $f[] = Form::frameImageOne('image','图标',Url::build('admin/widget.images/index',array('fodder'=>'image')),$welfare['image'])->icon('image');
        $f[] = Form::number('sort','排序',$welfare['sort']);
        $f[] = Form::radio('status','状态',$welfare['status'])->options([['value'=>1,'label'=>'显示'],['value'=>0,'label'=>'隐藏']]);
        $form = Form::make_post_form('编辑福利配置',$f,Url::build('update',array('id'=>$id)));
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 保存修改福利配置
     * @param Request $request
     * @param $id
     * @throws \think\exception\DbException
     */
    public function update(Request $request, $id) {
        $data = Util::postMore([
            'title',
            'intr',
            ['image',[]],
            ['sort',0],
            'status',],$request);
        if(!$data['title']) return Json::fail('请输入名称');
        if(count($data['image']) > 0) $data['image'] = $data['image'][0];
        if($data['sort'] < 0) return Json::fail('排序不能是负数');
        if(!WelfareModel::get($id)) return Json::fail('编辑的记录不存在!');
        WelfareModel::edit($data,$id);
        return Json::successful('修改成功!');
    }


}