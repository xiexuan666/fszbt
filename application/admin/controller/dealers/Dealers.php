<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:19
 */

namespace app\admin\controller\dealers;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\UtilService as Util;
use service\PHPTreeService as Phptree;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use think\Url;

use traits\CurdControllerTrait;

use app\admin\model\user\User as UserModel;
use app\admin\model\dealers\DealersCategory as DealersCategoryModel;
use app\admin\model\dealers\Dealers as DealersModel;
use app\admin\model\system\SystemAttachment;

/**
 * 知识管理
 * Class Dealers
 * @package app\admin\controller\dealers
 */
class Dealers extends AuthController
{
    use CurdControllerTrait;

    protected $bindModel = DealersModel::class;

    /**
     * 显示后台管理员添加的图文
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $where = Util::getMore([
            ['title',''],
            ['cid','']
        ],$this->request);
        $pid = $this->request->param('pid');
        $this->assign('where',$where);
        $catlist = DealersCategoryModel::where('is_del',0)->select()->toArray();
        //获取分类列表
        if($catlist){
            $tree = Phptree::makeTreeForHtml($catlist);
            $this->assign(compact('tree'));
            if($pid){
                $pids = Util::getChildrenPid($tree,$pid);
                $where['cid'] = ltrim($pid.$pids);
            }
        }else{
            $tree = [];
            $this->assign(compact('tree'));
        }

        $this->assign('cate',DealersCategoryModel::getTierList());
        return $this->fetch();
    }

    /**
     * 异步查找产品
     */
    public function getList(){
        $where=Util::getMore([
            ['page',1],
            ['limit',20],
            ['title',''],
            ['cid','']
        ]);
        return Json::successlayui(DealersModel::getList($where));
    }

    /**
     * 显示隐藏
     * @param string $is_show
     * @param string $id
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && Json::fail('缺少参数');
        $res = DealersModel::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
        if($res){
            return Json::successful($is_show==1 ? '显示成功':'隐藏成功');
        }else{
            return Json::fail($is_show==1 ? '显示失败':'隐藏失败');
        }
    }

    /**
     * 设置置顶
     * @param string $is_top
     * @param string $id
     */
    public function set_top($is_top='',$id=''){
        ($is_top=='' || $id=='') && Json::fail('缺少参数');
        $res = DealersModel::where(['id'=>$id])->update(['is_top'=>(int)$is_top]);
        if($res){
            return Json::successful($is_top==1 ? '置顶成功':'取消置顶成功');
        }else{
            return Json::fail($is_top==1 ? '置顶失败':'取消置顶失败');
        }
    }

    /**
     * 快速编辑
     * @param string $field
     * @param string $id
     * @param string $value
     * @return mixed
     */
    public function set_editor($field='',$id='',$value=''){
        $field=='' || $id=='' || $value=='' && Json::fail('缺少参数');
        if(DealersModel::where(['id'=>$id])->update([$field=>$value]))
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
        $res = DealersModel::del($id);
        if(!$res)
            return Json::fail('删除失败,请稍候再试!');
        else
            return Json::successful('删除成功!');
    }

    public function create()
    {
        $field = [
            Form::select('uid','选择经销商用户')->setOptions(function(){
                $list = UserModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['uid'],'label'=>$menu['html'].$menu['nickname']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::select('cid','经营区域')->setOptions(function(){
                $list = DealersCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
                }
                return $menus;
            })->filterable(1)->multiple(1),

            Form::frameImageOne('logo','经销商封面名片，570*285像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'logo')))->icon('image')->width('100%')->height('500px'),

            Form::input('title','经销商名称'),

            Form::frameImageOne('about','经销商简介、掌舵人介绍，750*500像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'about')))->icon('image')->width('100%')->height('500px'),

            Form::frameImages('slider_image','经营位置信息，卖场位置，店面形象，团队，宽750像素仅限9张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')))->maxLength(9)->icon('images')->width('100%')->height('500px'),

            Form::frameImageOne('contact','经销商联系信息，750*375像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'contact')))->icon('image')->width('100%')->height('500px'),

            Form::number('sort','经销商排序')->col(8),
            Form::radio('is_show','经销商状态')->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8),
            Form::radio('is_top','经销商置顶')->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
        ];
        $form = Form::make_post_form('添加经销商',$field,Url::build('save'),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data = Util::postMore([
            ['id',0],
            ['cid',[]],
            ['uid',[]],
            'title',
            ['logo',[]],
            ['slider_image',[]],
            ['about',[]],
            ['contact',[]],
            ['sort',0],
            ['is_show',1],
            ['is_hot',0],
            ['is_top',0],
            ['status',1],],$request);

        if(count($data['cid']) < 1) return Json::fail('请选择经销商分类');
        if(count($data['uid']) < 1) return Json::fail('请选择经销商用户');
        $data['cid'] = implode(',',$data['cid']);
        $data['uid'] = implode(',',$data['uid']);

        $data['logo'] = $data['logo'][0];
        $data['slider_image'] = json_encode($data['slider_image']);
        $data['about'] = $data['about'][0];
        $data['contact'] = $data['contact'][0];
        $data['admin_id'] = $this->adminId;

        DealersModel::beginTrans();
        $res1 = DealersModel::set($data);
        if($res1)
            $res = true;
        else
            $res =false;
        DealersModel::checkTrans($res);
        if($res)
            return Json::successful('添加成功!',$res1->id);
        else
            return Json::successful('添加失败!',$res1->id);
    }

    public function edit($id)
    {
        if(!$id) return $this->failed('数据不存在');
        $product = DealersModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $field = [
            Form::select('uid','选择经销商用户',explode(',',$product->getData('uid')))->setOptions(function(){
                $list = UserModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['uid'],'label'=>$menu['html'].$menu['nickname']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::select('cid','经营区域',explode(',',$product->getData('cid')))->setOptions(function(){
                $list = DealersCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
                }
                return $menus;
            })->filterable(1)->multiple(1),

            Form::frameImageOne('logo','经销商封面名片，570*285像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'logo')),$product->getData('logo'))->icon('image')->width('100%')->height('500px'),

            Form::input('title','经销商名称',$product->getData('title')),

            Form::frameImageOne('about','经销商简介、掌舵人介绍，750*500像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'about')),$product->getData('about'))->icon('image')->width('100%')->height('500px'),

            Form::frameImages('slider_image','经营位置信息，卖场位置，店面形象，团队，宽750像素仅限9张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')),json_decode($product->getData('slider_image'),1) ? : [])->maxLength(9)->icon('images')->width('100%')->height('500px'),

            Form::frameImageOne('contact','经销商联系信息，750*375像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'contact')),$product->getData('contact'))->icon('image')->width('100%')->height('500px'),

            Form::number('sort','经销商排序',$product->getData('sort'))->col(8),
            Form::radio('is_show','经销商状态',$product->getData('is_show'))->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8),
            Form::radio('is_top','经销商置顶',$product->getData('is_top'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8)
        ];
        $form = Form::make_post_form('编辑经销商',$field,Url::build('update',array('id'=>$id)),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }


    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $data = Util::postMore([
            ['cid',[]],
            ['uid',[]],
            'title',
            ['logo',[]],
            ['slider_image',[]],
            ['about',[]],
            ['contact',[]],
            ['sort',0],
            ['is_show',1],
            ['is_hot',0],
            ['is_top',0],
            ['status',1],],$request);

        if(count($data['cid']) < 1) return Json::fail('请选择经销商分类');
        if(count($data['uid']) < 1) return Json::fail('请选择经销商用户');
        $data['cid'] = implode(',',$data['cid']);
        $data['uid'] = implode(',',$data['uid']);

        $data['logo'] = $data['logo'][0];
        $data['slider_image'] = json_encode($data['slider_image']);
        $data['about'] = $data['about'][0];
        $data['contact'] = $data['contact'][0];

        DealersModel::beginTrans();
        $res1 = DealersModel::edit($data,$id);
        if($res1)
            $res = true;
        else
            $res =false;
        DealersModel::checkTrans($res);
        if($res)
            return Json::successful('修改成功!',$id);
        else
            return Json::fail('修改失败，您并没有修改什么!',$id);
    }

    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $product = DealersModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>DealersModel::where('id',$id)->value('description'),
            'field'=>'description',
            'action'=>Url::build('change_field',['id'=>$id,'field'=>'description'])
        ]);
        return $this->fetch('public/edit_content');
    }


    /**
     * 上传图文图片
     */
    public function upload_image(){
        $res = Upload::Image($_POST['file'],'wechat/image/'.date('Ymd'));
        //产品图片上传记录
        $fileInfo = $res->fileInfo->getinfo();
        SystemAttachment::attachmentAdd($res->fileInfo->getSaveName(),$fileInfo['size'],$fileInfo['type'],$res->dir,'',5);
        if(!$res->status) return Json::fail($res->error);
        return Json::successful('上传成功!',['url'=>$res->filePath]);
    }


    public function merchantIndex(){
        $where = Util::getMore([
            ['title','']
        ],$this->request);
        $this->assign('where',$where);
        $where['cid'] = input('cid');
        $where['merchant'] = 1;//区分是管理员添加的图文显示  0 还是 商户添加的图文显示  1
        $this->assign(DealersModel::getAll($where));
        return $this->fetch();
    }
}