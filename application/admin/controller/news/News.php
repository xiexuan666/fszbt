<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:19
 */

namespace app\admin\controller\news;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\UtilService as Util;
use service\PHPTreeService as Phptree;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use think\Url;

use traits\CurdControllerTrait;

use app\admin\model\news\NewsCategory as NewsCategoryModel;
use app\admin\model\news\News as NewsModel;
use app\admin\model\system\SystemAttachment;

/**
 * 知识管理
 * Class News
 * @package app\admin\controller\news
 */
class News extends AuthController
{
    use CurdControllerTrait;

    protected $bindModel = NewsModel::class;

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
        $this->assign('cate',NewsCategoryModel::getTierList());
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
        return Json::successlayui(NewsModel::getAll($where));
    }

    /**
     * 显示隐藏
     * @param string $is_show
     * @param string $id
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && Json::fail('缺少参数');
        $res=NewsModel::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
        if($res){
            return Json::successful($is_show==1 ? '显示成功':'隐藏成功');
        }else{
            return Json::fail($is_show==1 ? '显示失败':'隐藏失败');
        }
    }

    /**
     * 首页推荐
     * @param string $is_banner
     * @param string $id
     */
    public function set_hot($is_banner='',$id=''){
        ($is_banner=='' || $id=='') && Json::fail('缺少参数');
        $res=NewsModel::where(['id'=>$id])->update(['is_banner'=>(int)$is_banner]);
        if($res){
            return Json::successful($is_banner==1 ? '首页推荐成功':'首页推荐取消成功');
        }else{
            return Json::fail($is_banner==1 ? '首页推荐失败':'首页推荐取消失败');
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
        if(NewsModel::where(['id'=>$id])->update([$field=>$value]))
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
        $res = NewsModel::del($id);
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
            Form::select('type_id','分类')->setOptions(function(){
                $list = NewsCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::input('title','标题')->col(Form::col(24)),
            Form::input('author','作者')->col(Form::col(24)),
            Form::frameImageOne('image','置顶海报',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image')->width('800px')->height('500px'),
            Form::frameImages('slider_image','广告图片',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')))->maxLength(3)->icon('images')->width('800px')->height('500px')->spin(0),
            Form::radio('is_new','是否转载',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::input('synopsis','转载说明')->type('textarea'),
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

        if(!$data['title']) return Json::fail('请输入标题');
        if(count($data['image'])<1) return Json::fail('请上传图片');
        if(count($data['slider_image'])<1) return Json::fail('请上传轮播图');
        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);
        $data['add_time'] = time();

        $data['admin_id'] = $this->adminId;
        NewsModel::beginTrans();
        $res1 = NewsModel::set($data);
        if($res1)
            $res = true;
        else
            $res =false;
        NewsModel::checkTrans($res);
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
        $product = NewsModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $field = [
            Form::select('cid','分类',explode(',',$product->getData('cid')))->setOptions(function(){
                $list = NewsCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::input('title','标题',$product->getData('title')),
            Form::input('author','作者',$product->getData('author')),
            Form::frameImageOne('image','置顶海报',Url::build('admin/widget.images/index',array('fodder'=>'image')),$product->getData('image'))->icon('image')->width('800px')->height('500px'),

            Form::frameImages('slider_image','广告图片',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')),json_decode($product->getData('slider_image'),1) ? : [])->maxLength(3)->icon('images')->width('800px')->height('500px'),
            Form::radio('is_new','是否转载',$product->getData('is_new'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::input('synopsis','转载说明',$product->getData('synopsis'))->type('textarea'),

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

        if(count($data['cid']) < 1) return Json::fail('请选择分类');
        $data['cid'] = implode(',',$data['cid']);

        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);

        NewsModel::beginTrans();
        $res1 = NewsModel::edit($data,$id);
        if($res1)
            $res = true;
        else
            $res =false;
        NewsModel::checkTrans($res);
        if($res)
            return Json::successful('修改成功!',$id);
        else
            return Json::fail('修改失败，您并没有修改什么!',$id);
    }

    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $product = NewsModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>NewsModel::where('id',$id)->value('description'),
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
        $this->assign(NewsModel::getAll($where));
        return $this->fetch();
    }
}