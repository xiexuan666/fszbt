<?php
namespace app\admin\controller\store;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\JsonService;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use app\admin\model\store\CompanyClass as CategoryModel;
use think\Url;
use app\admin\model\system\SystemAttachment;

/**
 * 产品分类控制器
 * Class CompanyClass
 * @package app\admin\controller\system
 */
class CompanyClass extends AuthController
{
    /**
     * 显示资源列表
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 异步获取分类列表
     */
    public function getList(){
        $where = Util::getMore([
            ['is_show',''],
            ['cate_name',''],
            ['page',1],
            ['limit',20]
        ]);
        return JsonService::successlayui(CategoryModel::getList($where));
    }

    /**
     * 设置显示隐藏
     * @param string $is_show
     * @param string $id
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && JsonService::fail('缺少参数');
        $res=CategoryModel::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
        if($res){
            return JsonService::successful($is_show==1 ? '显示成功':'隐藏成功');
        }else{
            return JsonService::fail($is_show==1 ? '显示失败':'隐藏失败');
        }
    }

    /**
     * 快速编辑
     * @param string $field
     * @param string $id
     * @param string $value
     */
    public function set_category($field='',$id='',$value=''){
        $field=='' || $id=='' || $value=='' && JsonService::fail('缺少参数');
        if(CategoryModel::where(['id'=>$id])->update([$field=>$value]))
            return JsonService::successful('保存成功');
        else
            return JsonService::fail('保存失败');
    }

    /**
     * 显示编辑资源表单页
     * @param $id
     * @return mixed|void
     * @throws \FormBuilder\exception\FormBuilderException
     * @throws \think\exception\DbException
     */
    public function edit($id)
    {
        $c = CategoryModel::get($id);
        if(!$c) return Json::fail('数据不存在!');
        $field = [
            Form::input('cate_name','分类名称',$c->getData('cate_name')),
            Form::frameImageOne('pic','分类图标',Url::build('admin/widget.images/index',array('fodder'=>'pic')),$c->getData('pic'))->icon('image'),
            Form::number('sort','排序',$c->getData('sort')),
            Form::radio('is_show','状态',$c->getData('is_show'))->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])
        ];
        $form = Form::make_post_form('编辑分类',$field,Url::build('update',array('id'=>$id)),2);

        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 保存更新的资源
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id)
    {
        $data = Util::postMore([
            'cate_name',
            ['pic',[]],
            'sort',
            ['is_show',0]
        ],$request);
        if(!$data['cate_name']) return Json::fail('请输入分类名称');
        if(count($data['pic'])<1) return Json::fail('请上传分类图标');
        if($data['sort'] <0 ) $data['sort'] = 0;
        $data['pic'] = $data['pic'][0];
        CategoryModel::edit($data,$id);
        return Json::successful('修改成功!');
    }

    /**
     * 上传图片
     */
    public function upload()
    {
        $res = Upload::image('file','store/category'.date('Ymd'));
        $thumbPath = Upload::thumb($res->dir);
        //产品图片上传记录
        $fileInfo = $res->fileInfo->getinfo();
        SystemAttachment::attachmentAdd($res->fileInfo->getSaveName(),$fileInfo['size'],$fileInfo['type'],$res->dir,$thumbPath,1);

        if($res->status == 200)
            return Json::successful('图片上传成功!',['name'=>$res->fileInfo->getSaveName(),'url'=>Upload::pathToUrl($thumbPath)]);
        else
            return Json::fail($res->error);
    }

    /**
     * 删除指定资源
     * @param $id
     */
    public function delete($id)
    {
        if(!CategoryModel::delCategory($id))
            return Json::fail(CategoryModel::getErrorInfo('删除失败,请稍候再试!'));
        else
            return Json::successful('删除成功!');
    }
}
