<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:19
 */

namespace app\admin\controller\consultant;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\UtilService as Util;
use service\PHPTreeService as Phptree;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use think\Url;

use traits\CurdControllerTrait;

use app\admin\model\consultant\ConsultantCategory as ConsultantCategoryModel;
use app\admin\model\consultant\Consultant as ConsultantModel;
use app\admin\model\system\SystemAttachment;

/**
 * 知识管理
 * Class Consultant
 * @package app\admin\controller\consultant
 */
class Consultant extends AuthController
{
    use CurdControllerTrait;

    protected $bindModel = ConsultantModel::class;

    /**
     * 显示后台管理员添加的图文
     * @param int $pid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($pid = 0)
    {
        $where = Util::getMore([
            ['title',''],
            ['cid','']
        ],$this->request);
        $pid = $this->request->param('pid');
        $this->assign('where',$where);
        $where['merchant'] = 0;//区分是管理员添加的图文显示  0 还是 商户添加的图文显示  1
        $catlist = ConsultantCategoryModel::where('is_del',0)->select()->toArray();
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


        $this->assign('cate',ConsultantCategoryModel::getTierList());
        $this->assign(ConsultantModel::getAll($where));
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function add()
    {
        $field = [
            Form::select('type_id','知识分类')->setOptions(function(){
                $list = ConsultantCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::input('title','文章标题')->col(Form::col(24)),
            Form::input('author','发布作者')->type('textarea'),
            Form::input('synopsis','知识简介')->type('textarea'),
            Form::frameImageOne('image','文章主图片(305*305px)',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image')->width('100%')->height('500px'),
            Form::frameImages('slider_image','文章轮播图(640*640px)',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')))->maxLength(5)->icon('images')->width('100%')->height('500px')->spin(0),
            Form::number('price','阅读收费')->min(0)->col(8),
            Form::number('sales','销量',0)->min(0)->precision(0)->col(8)->readonly(1),
            Form::number('ficti','虚拟销量')->min(0)->precision(0)->col(8),
            Form::number('sort','排序')->col(8),
            Form::radio('is_show','文章状态',0)->options([['label'=>'上架','value'=>1],['label'=>'下架','value'=>0]])->col(8),
            Form::radio('is_hot','热读文章',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_best','推荐文章',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_new','原创文章',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8)
        ];
        $form = Form::make_post_form('添加文章',$field,Url::build('save'),2);
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
            ['type_id',[]],
            'title',
            'author',
            ['image',[]],
            ['slider_image',[]],
            'synopsis',
            'share_title',
            'share_synopsis',
            ['visit',0],
            ['sort',0],
            'url',

            ['sales',0],
            ['ficti',100],
            ['price',0],
            ['is_show',0],
            ['is_best',0],
            ['is_new',0],

            ['is_banner',0],
            ['is_hot',0],
            ['status',1],],$request);
        $data['cid'] = implode(',',$data['type_id']);

        if(!$data['title']) return Json::fail('请输入文章标题');
        if(count($data['image'])<1) return Json::fail('请上传文章图片');
        if(count($data['slider_image'])<1) return Json::fail('请上传文章轮播图');
        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);
        $data['add_time'] = time();

        $data['admin_id'] = $this->adminId;
        ConsultantModel::beginTrans();
        $res1 = ConsultantModel::set($data);
        if($res1)
            $res = true;
        else
            $res =false;
        ConsultantModel::checkTrans($res);
        if($res)
            return Json::successful('添加图文成功!',$res1->id);
        else
            return Json::successful('添加图文失败!',$res1->id);
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if(!$id) return $this->failed('数据不存在');
        $product = ConsultantModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $field = [
            Form::select('cid','文章分类',explode(',',$product->getData('cid')))->setOptions(function(){
                $list = ConsultantCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::input('title','文章标题',$product->getData('title')),
            Form::input('author','发布作者',$product->getData('author'))->type('textarea'),
            Form::input('synopsis','知识简介',$product->getData('synopsis'))->placeholder('多个用英文状态下的逗号隔开')->type('textarea'),
            Form::frameImageOne('image','文章主图片(305*305px)',Url::build('admin/widget.images/index',array('fodder'=>'image')),$product->getData('image'))->icon('image')->width('100%')->height('500px'),

            Form::frameImages('slider_image','文章轮播图(640*640px)',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')),json_decode($product->getData('slider_image'),1) ? : [])->maxLength(5)->icon('images')->width('100%')->height('500px'),

            Form::number('price','文章售价',$product->getData('price'))->min(0)->precision(2)->col(8),

            Form::number('sales','销量',$product->getData('sales'))->min(0)->precision(0)->col(8)->readonly(1),
            Form::number('ficti','虚拟销量',$product->getData('ficti'))->min(0)->precision(0)->col(8),
            Form::number('sort','排序',$product->getData('sort'))->col(8),
            Form::radio('is_show','文章状态',$product->getData('is_show'))->options([['label'=>'上架','value'=>1],['label'=>'下架','value'=>0]])->col(8),
            Form::radio('is_hot','热读文章',$product->getData('is_hot'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_best','推荐文章',$product->getData('is_best'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_new','原创文章',$product->getData('is_new'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8)
        ];
        $form = Form::make_post_form('编辑文章',$field,Url::build('update',array('id'=>$id)),2);
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
            'title',
            'author',
            ['image',[]],
            ['slider_image',[]],
            'synopsis',
            'share_title',
            'share_synopsis',
            ['visit',0],
            ['sort',0],
            'url',

            ['sales',0],
            ['ficti',100],
            ['price',0],
            ['is_show',0],
            ['is_best',0],
            ['is_new',0],

            ['is_banner',0],
            ['is_hot',0],
            ['status',1],],$request);

        if(count($data['cid']) < 1) return Json::fail('请选择文章分类');
        $data['cid'] = implode(',',$data['cid']);

        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);

        ConsultantModel::beginTrans();
        $res1 = ConsultantModel::edit($data,$id);
        if($res1)
            $res = true;
        else
            $res =false;
        ConsultantModel::checkTrans($res);
        if($res)
            return Json::successful('修改图文成功!',$id);
        else
            return Json::fail('修改图文失败，您并没有修改什么!',$id);
    }

    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $product = ConsultantModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>ConsultantModel::where('id',$id)->value('description'),
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

    /**
     * 删除图文
     * @param $id
     */
    public function delete($id)
    {
        $res = ConsultantModel::del($id);
        if(!$res)
            return Json::fail('删除失败,请稍候再试!');
        else
            return Json::successful('删除成功!');
    }

    public function merchantIndex(){
        $where = Util::getMore([
            ['title','']
        ],$this->request);
        $this->assign('where',$where);
        $where['cid'] = input('cid');
        $where['merchant'] = 1;//区分是管理员添加的图文显示  0 还是 商户添加的图文显示  1
        $this->assign(ConsultantModel::getAll($where));
        return $this->fetch();
    }
}