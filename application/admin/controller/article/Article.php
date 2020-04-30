<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:19
 */

namespace app\admin\controller\article;

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

use app\admin\model\article\ArticleCategory as ArticleCategoryModel;
use app\admin\model\article\Article as ArticleModel;
use app\admin\model\system\SystemAttachment;

/**
 * 知识管理
 * Class Article
 * @package app\admin\controller\article
 */
class Article extends AuthController
{
    use CurdControllerTrait;

    protected $bindModel = ArticleModel::class;

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
        $catlist = ArticleCategoryModel::where('is_del',0)->select()->toArray();
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


        $this->assign('cate',ArticleCategoryModel::getTierList());
        //$this->assign(ArticleModel::getAll($where));
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
        return Json::successlayui(ArticleModel::getList($where));
    }

    /**
     * 显示隐藏
     * @param string $is_show
     * @param string $id
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && Json::fail('缺少参数');
        $res = ArticleModel::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
        if($res){
            return Json::successful($is_show==1 ? '显示成功':'隐藏成功');
        }else{
            return Json::fail($is_show==1 ? '显示失败':'隐藏失败');
        }
    }

    /**
     * 设置置顶
     * @param string $is_banner
     * @param string $id
     */
    public function set_banner($is_banner='',$id=''){
        ($is_banner=='' || $id=='') && Json::fail('缺少参数');
        $res = ArticleModel::where(['id'=>$id])->update(['is_banner'=>(int)$is_banner]);
        if($res){
            return Json::successful($is_banner==1 ? '显示成功':'隐藏成功');
        }else{
            return Json::fail($is_banner==1 ? '显示失败':'隐藏失败');
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
        if(ArticleModel::where(['id'=>$id])->update([$field=>$value]))
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
        $res = ArticleModel::del($id);
        if(!$res)
            return Json::fail('删除失败,请稍候再试!');
        else
            return Json::successful('删除成功!');
    }


    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $field = [
            Form::select('uid','选择用户')->setOptions(function(){
                $list = UserModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['uid'],'label'=>$menu['html'].$menu['nickname']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::select('cid','信息类目')->setOptions(function(){
                $list = ArticleCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::radio('tag','信息标签')->options([['label'=>'个人','value'=>'个人'],['label'=>'企业','value'=>'企业'],['label'=>'经销商','value'=>'经销商'],['label'=>'物业','value'=>'物业']])->col(24),
            Form::input('title','信息标题')->col(Form::col(24)),
            Form::radio('is_price','此信息是否付费',0)->options([['label'=>'付费','value'=>1],['label'=>'免费','value'=>2]])->col(8),
            Form::number('price','付费金额')->min(0)->col(8),
            Form::input('author','信息联系人')->col(Form::col(24)),
            Form::input('phone','联系人电话')->col(Form::col(24)),
            Form::input('description','信息详情')->placeholder('信息详情')->type('textarea'),
            Form::frameImageOne('image','封面(305*305px)',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image')->width('800px')->height('500px'),
            Form::frameImages('slider_image','信息图片，最多可上传8张，单张上传（选填）',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')))->maxLength(5)->icon('images')->width('800px')->height('500px')->spin(0),
            Form::number('sort','信息排序')->col(8),
            Form::radio('is_show','状态',0)->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8),
            Form::radio('is_banner','置顶',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8)
        ];
        $form = Form::make_post_form('添加',$field,Url::build('save'),2);
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
            'tag',
            'title',
            'author',
            'phone',
            ['image',[]],
            ['slider_image',[]],
            ['is_price',1],
            'description',
            ['sort',0],
            ['price',0],
            ['is_show',0],
            ['is_banner',0],
            ['status',1],],$request);

        if(count($data['uid']) < 1) return Json::fail('请选择用户');
        if(count($data['cid']) < 1) return Json::fail('请选择分类');
        $data['uid'] = implode(',',$data['uid']);
        $data['cid'] = implode(',',$data['cid']);

        if(!$data['title']) return Json::fail('请输入标题');
        if(count($data['image'])<1) return Json::fail('请上传图片');
        if(count($data['slider_image'])<1) return Json::fail('请上传轮播图');
        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);
        $data['add_time'] = time();

        $data['admin_id'] = $this->adminId;
        ArticleModel::beginTrans();
        $res1 = ArticleModel::set($data);
        if($res1)
            $res = true;
        else
            $res =false;
        ArticleModel::checkTrans($res);
        if($res)
            return Json::successful('添加成功!',$res1->id);
        else
            return Json::successful('添加失败!',$res1->id);
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
        $product = ArticleModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $field = [
            Form::select('uid','选择用户',explode(',',$product->getData('uid')))->setOptions(function(){
                $list = UserModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['uid'],'label'=>$menu['html'].$menu['nickname']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::select('cid','信息类目',explode(',',$product->getData('cid')))->setOptions(function(){
                $list = ArticleCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::radio('tag','信息标签',$product->getData('tag'))->options([['label'=>'个人','value'=>'个人'],['label'=>'企业','value'=>'企业'],['label'=>'经销商','value'=>'经销商'],['label'=>'物业','value'=>'物业']])->col(24),
            Form::input('title','信息标题',$product->getData('title')),
            Form::radio('is_price','此信息是否付费',$product->getData('is_price'))->options([['label'=>'付费','value'=>1],['label'=>'免费','value'=>2]])->col(8),
            Form::number('price','付费金额',$product->getData('price'))->min(0)->precision(2)->col(8),
            Form::input('author','信息联系人',$product->getData('author')),
            Form::input('phone','联系人电话',$product->getData('phone')),
            Form::input('description','信息详情',$product->getData('description'))->placeholder('信息详情')->type('textarea'),
            Form::frameImageOne('image','封面(305*305px)',Url::build('admin/widget.images/index',array('fodder'=>'image')),$product->getData('image'))->icon('image')->width('800px')->height('500px'),
            Form::frameImages('slider_image','信息图片，最多可上传8张，单张上传（选填）',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')),json_decode($product->getData('slider_image'),1) ? : [])->maxLength(5)->icon('images')->width('800px')->height('500px'),

            Form::number('sort','排序',$product->getData('sort'))->col(8),
            Form::radio('is_show','状态',$product->getData('is_show'))->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8),
            Form::radio('is_banner','置顶',$product->getData('is_banner'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8)
        ];
        $form = Form::make_post_form('编辑',$field,Url::build('update',array('id'=>$id)),2);
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
            'tag',
            'title',
            'author',
            ['image',[]],
            ['slider_image',[]],
            'phone',
            'description',
            ['sort',0],
            ['price',0],
            ['is_show',0],
            ['is_price',1],
            ['is_banner',0],
            ['status',1],],$request);

        if(count($data['uid']) < 1) return Json::fail('请选择用户');
        if(count($data['cid']) < 1) return Json::fail('请选择分类');
        $data['uid'] = implode(',',$data['uid']);
        $data['cid'] = implode(',',$data['cid']);

        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);

        ArticleModel::beginTrans();
        $res1 = ArticleModel::edit($data,$id);
        if($res1)
            $res = true;
        else
            $res =false;
        ArticleModel::checkTrans($res);
        if($res)
            return Json::successful('修改成功!',$id);
        else
            return Json::fail('修改失败，您并没有修改什么!',$id);
    }

    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $product = ArticleModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>ArticleModel::where('id',$id)->value('description'),
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
        $this->assign(ArticleModel::getAll($where));
        return $this->fetch();
    }
}