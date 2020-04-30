<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:24
 */

namespace app\admin\controller\supply;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use think\Url;
use app\admin\model\supply\SupplyCategory as SupplyCategoryModel;

/**
 * 知识分类管理  控制器
 * Class SupplyCategory
 * @package app\admin\controller\supply
 */
class SupplyCategory extends AuthController
{
    /**
     * @return mixed
     */
    public function index(){
        $this->assign('pid',$this->request->get('pid',0));
        $this->assign('cate',SupplyCategoryModel::getTierList());
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
        return Json::successlayui(SupplyCategoryModel::getList($where));
    }

    /**
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function create(){
        $f = array();
        $f[] = Form::select('pid','父级id')->setOptions(function(){
            $list = SupplyCategoryModel::getTierList();
            $menus[] = ['value'=>0,'label'=>'顶级类型'];
            foreach ($list as $menu){
                $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
            }
            return $menus;
        })->filterable(1);
        $f[] = Form::input('title','类型名称');
        $f[] = Form::frameImageOne('image','类型图标',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image');
        $f[] = Form::number('sort','类型排序',0);
        $f[] = Form::radio('status','类型状态',1)->options([['value'=>1,'label'=>'显示'],['value'=>0,'label'=>'隐藏']]);
        $form = Form::make_post_form('添加类型',$f,Url::build('save'));
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');

    }



    /**
     * @param Request $request
     */
    public function save(Request $request){
        $data = Util::postMore([
            'title',
            'pid',
            ['image',[]],
            ['sort',0],
            'status',],$request);
        if(!$data['title']) return Json::fail('请输入类型名称');
        if($data['sort'] < 0) return Json::fail('排序不能是负数');
        if(count($data['image']) == 1) $data['image'] = $data['image'][0];
        $data['add_time'] = time();
        $res = SupplyCategoryModel::set($data);
        if(!$res) return Json::fail('添加失败');
        return Json::successful('添加成功!');
    }

    /**
     * @param $id
     * @return mixed|void
     * @throws \FormBuilder\exception\FormBuilderException
     * @throws \think\exception\DbException
     */
    public function edit($id){
        if(!$id) return $this->failed('参数错误');
        $article = SupplyCategoryModel::get($id)->getData();
        if(!$article) return Json::fail('数据不存在!');
        $f = array();
        $f[] = Form::select('pid','父级id',(string)$article['pid'])->setOptions(function(){
            $list = SupplyCategoryModel::getTierList();
            $menus[] = ['value'=>0,'label'=>'顶级类型'];
            foreach ($list as $menu){
                $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
            }
            return $menus;
        })->filterable(1);
        $f[] = Form::input('title','类型名称',$article['title']);
        $f[] = Form::frameImageOne('image','类型图标',Url::build('admin/widget.images/index',array('fodder'=>'image')),$article['image'])->icon('image');
        $f[] = Form::number('sort','类型排序',$article['sort']);
        $f[] = Form::radio('status','类型状态',$article['status'])->options([['value'=>1,'label'=>'显示'],['value'=>0,'label'=>'隐藏']]);
        $form = Form::make_post_form('编辑类型',$f,Url::build('update',array('id'=>$id)));
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');

    }

    /**
     * @param Request $request
     * @param $id
     * @throws \think\exception\DbException
     */
    public function update(Request $request, $id)
    {
        $data = Util::postMore([
            'pid',
            ['image',[]],
            'title',
            ['sort',0],
            'status',],$request);
        if(!$data['title']) return Json::fail('请输入类型名称');
        if($data['sort'] < 0) return Json::fail('排序不能是负数');
        if(count($data['image']) == 1) $data['image'] = $data['image'][0];

        if(!SupplyCategoryModel::get($id)) return Json::fail('编辑的记录不存在!');
        SupplyCategoryModel::edit($data,$id);
        return Json::successful('修改成功!');
    }

    /**
     * 显示隐藏
     * @return json
     */
    public function set_show($status='',$id=''){
        ($status == '' || $id=='') && Json::fail('缺少参数');
        $res = SupplyCategoryModel::where(['id'=>$id])->update(['status'=>(int)$status]);
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
        if(SupplyCategoryModel::where(['id'=>$id])->update([$field=>$value]))
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
        $res = SupplyCategoryModel::del($id);
        if(!$res)
            return Json::fail(SupplyCategoryModel::getErrorInfo('删除失败,请稍候再试!'));
        else
            return Json::successful('删除成功!');
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