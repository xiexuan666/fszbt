<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:19
 */

namespace app\admin\controller\agreement;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\UtilService as Util;
use service\JsonService as Json;
use think\Request;
use think\Url;

use traits\CurdControllerTrait;

use app\admin\model\agreement\Agreement as DataModel;

/**
 * 协议管理
 * Class Agreement
 * @package app\admin\controller\agreement
 */
class Agreement extends AuthController
{
    use CurdControllerTrait;

    protected $bindModel = DataModel::class;

    /**
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 异步查找产品
     */
    public function getList(){
        $where=Util::getMore([
            ['page',1],
            ['limit',20],
            ['title','']
        ]);
        return Json::successlayui(DataModel::getList($where));
    }

    /**
     * 显示隐藏
     * @param string $is_show
     * @param string $id
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && Json::fail('缺少参数');
        $res=DataModel::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
        if($res){
            return Json::successful($is_show==1 ? '显示成功':'隐藏成功');
        }else{
            return Json::fail($is_show==1 ? '显示失败':'隐藏失败');
        }
    }

    /**
     * @param string $field
     * @param string $id
     * @param string $value
     * 快速编辑
     */
    public function set_product($field='',$id='',$value=''){
        $field=='' || $id=='' || $value=='' && Json::fail('缺少参数');
        if(DataModel::where(['id'=>$id])->update([$field=>$value]))
            return Json::successful('保存成功');
        else
            return Json::fail('保存失败');
    }

    /**
     * 删除图文
     * @param $id
     */
    public function delete($id)
    {
        $res = DataModel::del($id);
        if(!$res)
            return Json::fail('删除失败,请稍候再试!');
        else
            return Json::successful('删除成功!');
    }

    /**
     * 显示创建资源表单页
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function create()
    {
        $field = [
            Form::input('title','标题')->col(Form::col(24)),
            Form::frameImageOne('image','图片',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image')->width('800px')->height('500px'),
            Form::radio('is_show','状态',0)->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8)
        ];
        $form = Form::make_post_form('添加',$field,Url::build('save'),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 保存新建的资源
     * @param Request $request
     */
    public function save(Request $request)
    {
        $data = Util::postMore([
            ['id',0],
            'title',
            ['image',[]],
            ['sort',0],
            ['is_show',0],],$request);
        if(!$data['title']) return Json::fail('请输入标题');
        if(count($data['image']) == 1) $data['image'] = $data['image'][0];
        $data['add_time'] = time();
        DataModel::beginTrans();
        $res1 = DataModel::set($data);
        if($res1)
            $res = true;
        else
            $res =false;
        DataModel::checkTrans($res);
        if($res)
            return Json::successful('添加成功!',$res1->id);
        else
            return Json::successful('添加失败!',$res1->id);
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
        if(!$id) return $this->failed('数据不存在');
        $data = DataModel::get($id);
        if(!$data) return Json::fail('数据不存在!');
        $field = [
            Form::input('title','标题',$data->getData('title')),
            Form::frameImageOne('image','图片',Url::build('admin/widget.images/index',array('fodder'=>'image')),$data->getData('image'))->icon('image')->width('800px')->height('500px'),
            Form::radio('is_show','状态',$data->getData('is_show'))->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8)
        ];
        $form = Form::make_post_form('编辑',$field,Url::build('update',array('id'=>$id)),2);
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
            'title',
            ['image',[]],
            ['visit',0],
            ['sort',0],
            ['is_show',0],],$request);

        if(count($data['image']) == 1) $data['image'] = $data['image'][0];

        DataModel::beginTrans();
        $res1 = DataModel::edit($data,$id);
        if($res1)
            $res = true;
        else
            $res =false;
        DataModel::checkTrans($res);
        if($res)
            return Json::successful('修改成功!',$id);
        else
            return Json::fail('修改失败，您并没有修改什么!',$id);
    }

    /**
     * 编辑内容
     * @param $id
     * @return mixed|void
     * @throws \think\exception\DbException
     */
    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $data = DataModel::get($id);
        if(!$data) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>DataModel::where('id',$id)->value('description'),
            'field'=>'description',
            'action'=>Url::build('change_field',['id'=>$id,'field'=>'description'])
        ]);
        return $this->fetch('public/edit_content');
    }
}