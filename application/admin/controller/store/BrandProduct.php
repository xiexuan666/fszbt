<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:19
 */

namespace app\admin\controller\store;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\UtilService as Util;
use service\PHPTreeService as Phptree;
use service\JsonService as Json;
use service\UploadService as Upload;
use service\JsonService;
use think\Request;
use think\Url;

use traits\CurdControllerTrait;

use app\admin\model\user\User as UserModel;
use app\admin\model\store\BrandCategory as BrandCategoryModel;
use app\admin\model\store\Brand as BrandModel;
use app\admin\model\system\SystemAttachment;

/**
 * 知识管理
 * Class BrandProduct
 * @package app\admin\controller\store
 */
class BrandProduct extends AuthController
{
    use CurdControllerTrait;

    protected $bindModel = BrandModel::class;

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
        $this->assign('cate',BrandCategoryModel::getTierList());
        return $this->fetch();
    }

    /**
     * 异步查找产品
     */
    public function getList(){
        $where=Util::getMore([
            ['page',1],
            ['limit',20],
            ['name',''],
            ['cate_id','']
        ]);
        return JsonService::successlayui(BrandModel::getAll($where));
    }

    /**
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     * 显示创建资源表单页
     */
    public function create()
    {
        $field = [
            Form::select('mer_id','选择商户')->setOptions(function(){
                $list = UserModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['uid'],'label'=>$menu['html'].$menu['nickname']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::select('cate_id','品牌分类')->setOptions(function(){
                $list = BrandCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['cate_name'],'disabled'=>$menu['pid']== 0];
                }
                return $menus;
            })->filterable(1)->multiple(1),

            Form::input('name','品牌名称'),

            Form::frameImageOne('logo','品牌logo，180*180像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'logo')))->icon('image')->width('100%')->height('500px'),
            Form::frameImageOne('recruitment','店招：设计元素只能包含（企业名称+logo），750*150像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'recruitment')))->icon('image')->width('100%')->height('500px'),
            Form::frameImages('poster_image','企业轮播海报，750*375像素仅限5张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'poster_image')))->maxLength(3)->icon('images')->width('100%')->height('500px'),

            Form::frameImageOne('classification','企业经营系列名称、分类及风格，750*200像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'classification')))->icon('image')->width('100%')->height('500px'),
            Form::frameImageOne('about','企业简介、掌舵人介绍，750*500像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'about')))->icon('image')->width('100%')->height('500px'),

            Form::frameImages('slider_image','系列描述、材质、工艺、设计定位，宽750像素仅限9张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')))->maxLength(9)->icon('images')->width('100%')->height('500px'),

            Form::frameImageOne('dot','全国网点分布，750*375像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'dot')))->icon('image')->width('100%')->height('500px'),
            Form::frameImageOne('contact','企业联系信息，750*375像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'contact')))->icon('image')->width('100%')->height('500px'),

            Form::number('browse','浏览量')->min(0)->precision(0)->col(8),
            Form::number('sort','品牌排序')->col(8),
            Form::radio('is_hot','首页推荐',1)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_show','品牌状态',1)->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8),
        ];
        $form = Form::make_post_form('添加品牌',$field,Url::build('save'),2);
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
            ['cate_id',[]],
            ['name',''],
            ['logo',[]],
            ['recruitment',[]],
            ['classification',[]],
            ['about',[]],
            ['dot',[]],
            ['contact',[]],
            ['poster_image',[['sort',0],]],
            ['slider_image',[['sort',0],]],
            ['browse',0],
            ['sort',0],
            ['mer_id',[]],
            ['is_hot',0],
            ['is_show',0],
        ],$request);
        if(count($data['mer_id']) < 1) return Json::fail('请选择商户');
        if(count($data['cate_id']) < 1) return Json::fail('请选择品牌分类');

        $data['mer_id'] = implode(',',$data['mer_id']);
        $data['cate_id'] = implode(',',$data['cate_id']);

        if(count($data['logo'])<1) return Json::fail('请上传品牌logo');
        if(count($data['poster_image'])<1) return Json::fail('请上传品牌海报');
        if(count($data['poster_image'])>5) return Json::fail('品牌海报最多5张图');
        if(count($data['slider_image'])<1) return Json::fail('请上传系列图片');
        if(count($data['slider_image'])>9) return Json::fail('系列图片最多9张图');

        $data['logo'] = $data['logo'][0];
        $data['recruitment'] = $data['recruitment'][0];
        $data['classification'] = $data['classification'][0];
        $data['about'] = $data['about'][0];
        $data['dot'] = $data['dot'][0];
        $data['contact'] = $data['contact'][0];
        $data['poster_image'] = json_encode($data['poster_image']);
        $data['slider_image'] = json_encode($data['slider_image']);
        $data['add_time'] = time();
        BrandModel::set($data);
        return Json::successful('添加新品成功!');
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
        $product = BrandModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $field = [
            Form::select('mer_id','选择商户',explode(',',$product->getData('mer_id')))->setOptions(function(){
                $list = UserModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['uid'],'label'=>$menu['html'].$menu['nickname']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::select('cate_id','品牌分类',explode(',',$product->getData('cate_id')))->setOptions(function(){
                $list = BrandCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['cate_name'],'disabled'=>$menu['pid']== 0];
                }
                return $menus;
            })->filterable(1)->multiple(1),

            Form::input('name','品牌名称',$product->getData('name')),

            Form::frameImageOne('logo','品牌logo，180*180像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'logo')),$product->getData('logo'))->icon('image')->width('100%')->height('500px'),
            Form::frameImageOne('recruitment','店招：设计元素只能包含（企业名称+logo），750*150像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'recruitment')),$product->getData('recruitment'))->icon('image')->width('100%')->height('500px'),
            Form::frameImages('poster_image','企业轮播海报，750*375像素仅限5张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'poster_image')),json_decode($product->getData('poster_image'),1) ? : [])->maxLength(3)->icon('images')->width('100%')->height('500px'),

            Form::frameImageOne('classification','企业经营系列名称、分类及风格，750*200像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'classification')),$product->getData('classification'))->icon('image')->width('100%')->height('500px'),
            Form::frameImageOne('about','企业简介、掌舵人介绍，750*500像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'about')),$product->getData('about'))->icon('image')->width('100%')->height('500px'),

            Form::frameImages('slider_image','系列描述、材质、工艺、设计定位，宽750像素仅限9张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')),json_decode($product->getData('slider_image'),1) ? : [])->maxLength(9)->icon('images')->width('100%')->height('500px'),

            Form::frameImageOne('dot','全国网点分布，750*375像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'dot')),$product->getData('dot'))->icon('image')->width('100%')->height('500px'),
            Form::frameImageOne('contact','企业联系信息，750*375像素仅限1张，支持格式jpg、png',Url::build('admin/widget.images/index',array('fodder'=>'contact')),$product->getData('contact'))->icon('image')->width('100%')->height('500px'),

            Form::number('browse','浏览量',$product->getData('browse'))->min(0)->precision(0)->col(8),
            Form::number('sort','品牌排序',$product->getData('sort'))->col(8),
            Form::radio('is_hot','首页推荐',$product->getData('is_hot'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_show','品牌状态',$product->getData('is_show'))->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8),
        ];
        $form = Form::make_post_form('编辑品牌',$field,Url::build('update',array('id'=>$id)),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 保存更新的资源
     * @param Request $request
     * @param $id
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function update(Request $request, $id)
    {
        $data = Util::postMore([
            ['cate_id',[]],
            ['name',''],
            ['logo',[]],
            ['recruitment',[]],
            ['classification',[]],
            ['about',[]],
            ['dot',[]],
            ['contact',[]],
            ['poster_image',[['sort',0],]],
            ['slider_image',[['sort',0],]],
            ['browse',0],
            ['sort',0],
            ['mer_id',[]],
            ['is_hot',0],
            ['is_show',0],
        ],$request);
        if(count($data['mer_id']) < 1) return Json::fail('请选择商户');
        if(count($data['cate_id']) < 1) return Json::fail('请选择品牌分类');

        $data['mer_id'] = implode(',',$data['mer_id']);
        $data['cate_id'] = implode(',',$data['cate_id']);

        if(count($data['logo'])<1) return Json::fail('请上传品牌logo');
        if(count($data['poster_image'])<1) return Json::fail('请上传品牌海报');
        if(count($data['poster_image'])>5) return Json::fail('品牌海报最多5张图');
        if(count($data['slider_image'])<1) return Json::fail('请上传系列图片');
        if(count($data['slider_image'])>9) return Json::fail('系列图片最多9张图');

        $data['logo'] = $data['logo'][0];
        $data['recruitment'] = $data['recruitment'][0];
        $data['classification'] = $data['classification'][0];
        $data['about'] = $data['about'][0];
        $data['dot'] = $data['dot'][0];
        $data['contact'] = $data['contact'][0];
        $data['poster_image'] = json_encode($data['poster_image']);
        $data['slider_image'] = json_encode($data['slider_image']);
        BrandModel::edit($data,$id);
        return Json::successful('修改成功!');
    }

    /**
     * 显示隐藏
     * @param string $is_show
     * @param string $id
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && JsonService::fail('缺少参数');
        $res=BrandModel::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
        if($res){
            return JsonService::successful($is_show==1 ? '显示成功':'隐藏成功');
        }else{
            return JsonService::fail($is_show==1 ? '显示失败':'隐藏失败');
        }
    }

    /**
     * 首页推荐
     * @param string $is_hot
     * @param string $id
     */
    public function set_hot($is_hot='',$id=''){
        ($is_hot=='' || $id=='') && JsonService::fail('缺少参数');
        $res=BrandModel::where(['id'=>$id])->update(['is_hot'=>(int)$is_hot]);
        if($res){
            return JsonService::successful($is_hot==1 ? '首页推荐成功':'首页推荐取消成功');
        }else{
            return JsonService::fail($is_hot==1 ? '首页推荐失败':'首页推荐取消失败');
        }
    }

    /**
     * @param string $field
     * @param string $id
     * @param string $value
     * 快速编辑
     */
    public function set_product($field='',$id='',$value=''){
        $field=='' || $id=='' || $value=='' && JsonService::fail('缺少参数');
        if(BrandModel::where(['id'=>$id])->update([$field=>$value]))
            return JsonService::successful('保存成功');
        else
            return JsonService::fail('保存失败');
    }

    /**
     * 删除图文
     * @param $id
     */
    public function delete($id)
    {
        $res = BrandModel::del($id);
        if(!$res)
            return Json::fail('删除失败,请稍候再试!');
        else
            return Json::successful('删除成功!');
    }

    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $product = BrandModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>BrandModel::where('id',$id)->value('description'),
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
        $this->assign(BrandModel::getAll($where));
        return $this->fetch();
    }
}