<?php

namespace app\admin\controller\store;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use app\admin\model\store\CompanyProductRelation;
use service\JsonService;
use think\Db;
use traits\CurdControllerTrait;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use app\admin\model\user\User as UserModel;
use app\admin\model\store\CompanyClass as CategoryModel;
use app\admin\model\store\CompanyProduct as ProductModel;
use think\Url;

use app\admin\model\system\SystemAttachment;


/**
 * 产品管理
 * Class CompanyProduct
 * @package app\admin\controller\store
 */
class CompanyProduct extends AuthController
{

    use CurdControllerTrait;

    protected $bindModel = ProductModel::class;

    /**
     * @return mixed
     * @throws \think\Exception
     * 显示资源列表
     */
    public function index()
    {
        //获取分类
        $this->assign('cate',CategoryModel::getTierList());
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
        return JsonService::successlayui(ProductModel::getList($where));
    }

    /**
     * @param string $is_show
     * @param string $id
     * 设置单个产品上架|下架
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && JsonService::fail('缺少参数');
        $res=ProductModel::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
        if($res){
            return JsonService::successful($is_show==1 ? '上架成功':'下架成功');
        }else{
            return JsonService::fail($is_show==1 ? '上架失败':'下架失败');
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
        if(ProductModel::where(['id'=>$id])->update([$field=>$value]))
            return JsonService::successful('保存成功');
        else
            return JsonService::fail('保存失败');
    }

    /**
     * 设置批量产品上架
     */
    public function product_show(){
        $post=Util::postMore([
            ['ids',[]]
        ]);
        if(empty($post['ids'])){
            return JsonService::fail('请选择需要上架的产品');
        }else{
            $res=ProductModel::where('id','in',$post['ids'])->update(['is_show'=>1]);
            if($res)
                return JsonService::successful('上架成功');
            else
                return JsonService::fail('上架失败');
        }
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
            Form::select('cate_id','新品分类')->setOptions(function(){
                $list = CategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['cate_name'],'disabled'=>$menu['pid']== 0];
                }
                return $menus;
            })->filterable(1)->multiple(1),

            Form::input('name','商品名称')->col(Form::col(24)),
            Form::frameImageOne('image','商品封面(280*280px)',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image')->width('100%')->height('500px'),
            Form::frameImages('slider_image','商品详情(宽750px)',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')))->maxLength(9)->icon('images')->width('100%')->height('500px')->spin(0),
            Form::number('sort','商品排序')->col(8),
            Form::radio('is_show','商品状态',0)->options([['label'=>'上架','value'=>1],['label'=>'下架','value'=>0]])->col(8),
        ];
        $form = Form::make_post_form('添加商品',$field,Url::build('save'),2);
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
            ['mer_id',[]],
            ['cate_id',[]],
            'name',
            ['image',[]],
            ['slider_image',[]],
            ['sort',0],
            ['is_show',0],
        ],$request);
        if(count($data['mer_id']) < 1) return Json::fail('请选择商户');
        if(count($data['cate_id']) < 1) return Json::fail('请选择商品分类');

        $data['mer_id'] = implode(',',$data['mer_id']);
        $data['cate_id'] = implode(',',$data['cate_id']);
        if(!$data['name']) return Json::fail('请输入商品名称');
        if(count($data['image'])<1) return Json::fail('请上传商品封面');
        if(count($data['slider_image'])<1) return Json::fail('请上传商品详情');
        if(count($data['slider_image'])>9) return Json::fail('商品详情最多9张图');
        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);
        $data['add_time'] = time();
        ProductModel::set($data);
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
        $product = ProductModel::get($id);
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
            Form::select('cate_id','商品系列',explode(',',$product->getData('cate_id')))->setOptions(function(){
                $list = CategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['cate_name'],'disabled'=>$menu['pid']== 0];
                }
                return $menus;
            })->filterable(1)->multiple(1),

            Form::input('name','商品名称',$product->getData('name'))->col(Form::col(24)),
            Form::frameImageOne('image','商品封面(280*280px)',Url::build('admin/widget.images/index',array('fodder'=>'image')),$product->getData('image'))->icon('image')->width('100%')->height('500px'),
            Form::frameImages('slider_image','商品详情(宽750px)',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')),json_decode($product->getData('slider_image'),1) ? : [])->maxLength(9)->icon('images')->width('100%')->height('500px'),
            Form::number('sort','新品排序',$product->getData('sort'))->col(8),
            Form::radio('is_show','新品状态',$product->getData('is_show'))->options([['label'=>'上架','value'=>1],['label'=>'下架','value'=>0]])->col(8),
        ];
        $form = Form::make_post_form('编辑商品',$field,Url::build('update',array('id'=>$id)),2);
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
            ['mer_id',[]],
            'name',
            ['image',[]],
            ['slider_image',[['sort',0],]],
            ['sort',0],
            ['is_show',0],
        ],$request);
        if(count($data['mer_id']) < 1) return Json::fail('请选择商户');
        if(count($data['cate_id']) < 1) return Json::fail('请选择商品分类');
        $data['mer_id'] = implode(',',$data['mer_id']);
        $data['cate_id'] = implode(',',$data['cate_id']);

        if(count($data['image'])<1) return Json::fail('请上传商品封面');
        if(count($data['slider_image'])<1) return Json::fail('请上传商品详情');
        if(count($data['slider_image'])>9) return Json::fail('商品详情最多9张图');

        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);
        ProductModel::edit($data,$id);
        return Json::successful('修改成功!');
    }

    /**
     * 上传图片
     */
    public function upload()
    {
        $res = Upload::image('file','store/product/'.date('Ymd'));
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
     * 编辑内容
     * @param $id
     * @return mixed|void
     * @throws \think\exception\DbException
     */
    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $product = ProductModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>ProductModel::where('id',$id)->value('description'),
            'field'=>'description',
            'action'=>Url::build('change_field',['id'=>$id,'field'=>'description'])
        ]);
        return $this->fetch('public/edit_content');
    }

    /**
     * @param $id
     * 删除指定资源
     */
    public function delete($id)
    {

        if(!$id) return $this->failed('数据不存在');
        if(!ProductModel::be(['id'=>$id])) return $this->failed('产品数据不存在');
        if(ProductModel::be(['id'=>$id,'is_del'=>1])){
            $data['is_del'] = 0;
            if(!ProductModel::where('id',$id)->delete())
                return Json::fail(ProductModel::getErrorInfo('恢复失败,请稍候再试!'));
            else
                return Json::successful('成功恢复产品!');
        }else{
            $data['is_del'] = 1;
            if(!ProductModel::where('id',$id)->delete())
                return Json::fail(ProductModel::getErrorInfo('删除失败,请稍候再试!'));
            else
                return Json::successful('成功移到回收站!');
        }

    }

    /**
     * @param $id
     * @return mixed|void
     * @throws \think\exception\DbException
     * 点赞
     */
    public function collect($id){
        if(!$id) return $this->failed('数据不存在');
        $product = ProductModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign(CompanyProductRelation::getCollect($id));
        return $this->fetch();
    }

    /**
     * @param $id
     * @return mixed|void
     * @throws \think\exception\DbException
     * 收藏
     */
    public function like($id){
        if(!$id) return $this->failed('数据不存在');
        $product = ProductModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign(CompanyProductRelation::getLike($id));
        return $this->fetch();
    }

    /**
     * @param Request $request
     * 修改产品价格
     */
    public function edit_product_price(Request $request){
        $data = Util::postMore([
            ['id',0],
            ['price',0],
        ],$request);
        if(!$data['id']) return Json::fail('参数错误');
        $res = ProductModel::edit(['price'=>$data['price']],$data['id']);
        if($res) return Json::successful('修改成功');
        else return Json::fail('修改失败');
    }

    /**
     * @param Request $request
     * 修改产品库存
     */
    public function edit_product_stock(Request $request){
        $data = Util::postMore([
            ['id',0],
            ['stock',0],
        ],$request);
        if(!$data['id']) return Json::fail('参数错误');
        $res = ProductModel::edit(['stock'=>$data['stock']],$data['id']);
        if($res) return Json::successful('修改成功');
        else return Json::fail('修改失败');
    }

}
