<?php

namespace app\admin\controller\ump;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use traits\CurdControllerTrait;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use app\admin\model\store\StoreProduct as ProductModel;
use app\admin\model\ump\StoreRaiseAttr;
use app\admin\model\ump\StoreRaiseAttrResult;
use app\admin\model\ump\StoreRaise as StoreRaiseModel;
use think\Url;
use app\admin\model\system\SystemAttachment;
use app\admin\model\ump\StoreRaisedata;

/**
 * 众筹管理
 * Class StoreRaise
 * @package app\admin\controller\store
 */
class StoreRaise extends AuthController
{

    use CurdControllerTrait;

    protected $bindModel = StoreRaiseModel::class;

    /**
     * @return mixed
     */
    public function index()
    {
        $this->assign('countRaise',StoreRaiseModel::getRaiseCount());
        $this->assign(StoreRaiseModel::getStatistics());
        $this->assign('raiseId',StoreRaiseModel::getRaiseIdAll());
        return $this->fetch();
    }
    public function save_excel(){
        $where = Util::getMore([
            ['is_show',''],
            ['store_name',''],
        ]);
        StoreRaiseModel::SaveExcel($where);
    }
    /**
     * 异步获取众筹数据
     */
    public function get_raise_list(Request $request){
        $where=Util::getMore([
            ['page',1],
            ['limit',20],
            ['export',0],
            ['is_show',''],
            ['is_host',''],
            ['store_name','']
        ],$request);
        $raiseList = StoreRaiseModel::systemPage($where);
        if(is_object($raiseList['list'])) $raiseList['list'] = $raiseList['list']->toArray();
        $data = $raiseList['list']['data'];
        foreach ($data as $k=>$v){
            $data[$k]['_stop_time'] = date('Y/m/d H:i:s',$v['stop_time']);
        }
        return Json::successlayui(['count'=>$raiseList['list']['total'],'data'=>$data]);
    }

    public function raise($id = 0){
        if(!$id) return $this->failed('数据不存在');
        $product = ProductModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $f = array();
        $f[] = Form::hidden('product_id',$id);
        $f[] = Form::input('title','众筹标题',$product->getData('store_name'));
        $f[] = Form::input('unit_name','产品单位',$product->getData('unit_name'))->placeholder('个、位、份、件');
        $f[] = Form::dateTimeRange('section_time','活动时间');
        $f[] = Form::frameImageOne('image','众筹主图',Url::build('admin/widget.images/index',array('fodder'=>'image')),$product->getData('image'))->icon('image');
        $f[] = Form::frameImages('images','众筹海报',Url::build('admin/widget.images/index',array('fodder'=>'images')),json_decode($product->getData('slider_image')))->maxLength(5)->icon('images');
        $f[] = Form::number('price','众筹定金')->min(0)->col(12);
        $f[] = Form::number('original_price','众筹原价')->min(3)->col(12);
        $f[] = Form::number('di_price','众筹最低价')->min(3)->col(12);
        $f[] = Form::number('stock','众筹库存',$product->getData('stock'))->min(0)->precision(0)->col(12);
        $f[] = Form::number('sort','众筹排序')->col(12);
        $f[] = Form::radio('is_show','活动状态',1)->options([['label'=>'开启','value'=>1],['label'=>'关闭','value'=>0]])->col(12);
        $form = Form::make_post_form('开启众筹',$f,Url::build('save'));
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }
    
    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $f = array();
        $f[] = Form::select('product_id','产品名称')->setOptions(function(){
            $list = ProductModel::getTierList();
            foreach ($list as $menu){
                $menus[] = ['value'=>$menu['id'],'label'=>$menu['store_name'].'/'.$menu['id']];
            }
            return $menus;
        })->filterable(1);
        $f[] = Form::input('title','众筹标题');
        $f[] = Form::input('unit_name','产品单位')->placeholder('个、位、份、件');
        $f[] = Form::dateTimeRange('section_time','活动时间');
        $f[] = Form::frameImageOne('image','众筹主图',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image');
        $f[] = Form::frameImages('images','众筹海报',Url::build('admin/widget.images/index',array('fodder'=>'images')))->maxLength(5)->icon('images');
        $f[] = Form::number('price','众筹定金')->min(0)->col(12);
        $f[] = Form::number('original_price','众筹原价')->min(3)->col(12);
        $f[] = Form::number('di_price','众筹最低价')->min(3)->col(12);
        $f[] = Form::number('stock','众筹库存')->min(0)->precision(0)->col(12);
        $f[] = Form::number('sort','众筹排序')->col(12);
        $f[] = Form::radio('is_show','活动状态',1)->options([['label'=>'开启','value'=>1],['label'=>'关闭','value'=>0]])->col(12);
        $form = Form::make_post_form('添加众筹',$f,Url::build('save'));
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request,$id=0)
    {
        $data = Util::postMore([
            'product_id',
            'title',
            ['image',''],
            ['images',[]],
            ['section_time',[]],
            'price',
            'original_price',
            'di_price',
            'unit_name',
            'sort',
            'stock',
            ['is_show',0],
        ],$request);
        if(!$data['title']) return Json::fail('请输入众筹名称');
        if(!$data['image']) return Json::fail('请上传众筹主图');
        if(count($data['images'])<1) return Json::fail('请上传内容图片');
        if($data['price'] == '' || $data['price'] < 0) return Json::fail('请输入众筹定金');
        if($data['original_price'] == '' || $data['original_price'] < 1) return Json::fail('请输入众筹原价');
        if(count($data['section_time'])<1) return Json::fail('请选择活动时间');
        if($data['stock'] == '' || $data['stock'] < 0) return Json::fail('请输入众筹库存');
        $data['images'] = json_encode($data['images']);
        $data['add_time'] = time();
        $data['start_time'] = strtotime($data['section_time'][0]);
        $data['stop_time'] = strtotime($data['section_time'][1]);
        unset($data['section_time']);
        if($id){
            $product = StoreRaiseModel::get($id);
            if(!$product) return Json::fail('数据不存在!');
            $data['product_id']=$product['product_id'];
            StoreRaiseModel::edit($data,$id);
            return Json::successful('编辑成功!');
        }else{
            $data['description'] = '';
            StoreRaiseModel::set($data);
            return Json::successful('添加成功!');
        }

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
        $product = StoreRaiseModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $f = array();
        $f[] = Form::hidden('product_id',$product->getData('product_id'));
        $f[] = Form::input('title','众筹标题',$product->getData('title'));
        $f[] = Form::input('unit_name','产品单位',$product->getData('unit_name'))->placeholder('个、位、份、件');
        $f[] = Form::dateTimeRange('section_time','活动时间',$product->getData('start_time'),$product->getData('stop_time'));
        $f[] = Form::frameImageOne('image','众筹主图',Url::build('admin/widget.images/index',array('fodder'=>'image')),$product->getData('image'))->icon('image');
        $f[] = Form::frameImages('images','众筹海报',Url::build('admin/widget.images/index',array('fodder'=>'images')),json_decode($product->getData('images')))->maxLength(5)->icon('images');
        $f[] = Form::number('price','众筹定金',$product->getData('price'))->min(0)->col(12);
        $f[] = Form::number('original_price','众筹原价',$product->getData('original_price'))->min(2)->col(12);
        $f[] = Form::number('di_price','众筹最低价',$product->getData('di_price'))->min(3)->col(12);
        $f[] = Form::number('stock','众筹库存',$product->getData('stock'))->min(0)->precision(0)->col(12);
        $f[] = Form::number('sort','众筹排序',$product->getData('sort'))->col(12);
        $f[] = Form::radio('is_show','活动状态',$product->getData('is_show'))->options([['label'=>'开启','value'=>1],['label'=>'关闭','value'=>0]])->col(12);
        $form = Form::make_post_form('编辑众筹',$f,Url::build('save',compact('id')));
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if(!$id) return $this->failed('数据不存在');
        $data['is_del'] = 1;
        if(!StoreRaiseModel::edit($data,$id))
            return Json::fail(StoreRaiseModel::getErrorInfo('删除失败,请稍候再试!'));
        else
            return Json::successful('删除成功!');
    }

    /**
     * 属性页面
     * @param $id
     * @return mixed|void
     */
    public function attr($id)
    {
        if(!$id) return $this->failed('数据不存在!');
        $result = StoreRaiseAttrResult::getResult($id);
        $image = StoreRaiseModel::where('id',$id)->value('image');
        $empty = array(
            'attr'=>array(
                0=>array(
                    'value'=>'套餐',
                    'detailValue'=>'',
                    'attrHidden'=>true,
                    'detail'=>array()
                )
            ),
        );
        $result = $result?$result:$empty;
        $this->assign(compact('id','result','product','image'));
        return $this->fetch();
    }

    /**
     * 生成属性
     * @param int $id
     */
    public function is_format_attr($id = 0){
        if(!$id) return Json::fail('产品不存在');
        list($attr,$detail) = Util::postMore([
            ['items',[]],
            ['attrs',[]]
        ],$this->request,true);
        $product = StoreRaiseModel::get($id);
        if(!$product) return Json::fail('产品不存在');
        $attrFormat = attrFormat($attr)[1];
        if(count($detail)){
            foreach ($attrFormat as $k=>$v){
                foreach ($detail as $kk=>$vv){
                    if($v['detail'] == $vv['detail']){
                        $attrFormat[$k]['price'] = $vv['price'];
                        $attrFormat[$k]['sales'] = $vv['sales'];
                        $attrFormat[$k]['pic'] = $vv['pic'];
                        $attrFormat[$k]['check'] = false;
                        break;
                    }else{
                        $attrFormat[$k]['price'] = '';
                        $attrFormat[$k]['sales'] = '';
                        $attrFormat[$k]['pic'] = $product['image'];
                        $attrFormat[$k]['check'] = true;
                    }
                }
            }
        }else{
            foreach ($attrFormat as $k=>$v){
                $attrFormat[$k]['price'] = $product['price'];
                $attrFormat[$k]['sales'] = $product['stock'];
                $attrFormat[$k]['pic'] = $product['image'];
                $attrFormat[$k]['check'] = false;
            }
        }
        return Json::successful($attrFormat);
    }

    /**
     * 添加 修改属性
     * @param $id
     */
    public function set_attr($id)
    {
        if(!$id) return $this->failed('产品不存在!');
        list($attr,$detail) = Util::postMore([
            ['items',[]],
            ['attrs',[]]
        ],$this->request,true);
        $res = StoreRaiseAttr::createProductAttr($attr,$detail,$id);
        if($res)
            return $this->successful('编辑成功!');
        else
            return $this->failed(StoreRaiseAttr::getErrorInfo());
    }

    /**
     * 清除属性
     * @param $id
     */
    public function clear_attr($id)
    {
        if(!$id) return $this->failed('产品不存在!');
        if(false !== StoreRaiseAttr::clearProductAttr($id) && false !== StoreRaiseAttrResult::clearResult($id))
            return $this->successful('清空成功!');
        else
            return $this->failed(StoreRaiseAttr::getErrorInfo('清空失败!'));
    }

    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $product = StoreRaiseModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>StoreRaiseModel::where('id',$id)->value('description'),
            'field'=>'description',
            'action'=>Url::build('change_field',['id'=>$id,'field'=>'description'])
        ]);
        return $this->fetch('public/edit_content');
    }

    /**
     * 上传图片
     * @return \think\response\Json
     */
    public function upload()
    {
        $res = Upload::image('file','store/product/'.date('Ymd'));
        $thumbPath = Upload::thumb($res->dir);
        //产品图片上传记录
        $fileInfo = $res->fileInfo->getinfo();
        SystemAttachment::attachmentAdd($res->fileInfo->getSaveName(),$fileInfo['size'],$fileInfo['type'],$res->dir,$thumbPath,2);
        if($res->status == 200)
            return Json::successful('图片上传成功!',['name'=>$res->fileInfo->getSaveName(),'url'=>Upload::pathToUrl($thumbPath)]);
        else
            return Json::fail($res->error);
    }

    /**众筹列表
     * @return mixed
     */
    public function raise_list()
    {
        $where = Util::getMore([
            ['status',''],
            ['data',''],
        ],$this->request);
        $this->assign('where',$where);
        $this->assign(StoreRaisedata::systemPage($where));

        return $this->fetch();
    }

    /**众筹人列表
     * @return mixed
     */
    public function order_pink($id){
        if(!$id) return $this->failed('数据不存在');
        $StoreRaisedata = StoreRaisedata::getRaisedataUserOne($id);
        if(!$StoreRaisedata) return $this->failed('数据不存在!');
        $list = StoreRaisedata::getRaisedataMember($id);
        $list[] = $StoreRaisedata;
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 修改众筹状态
     * @param $status
     * @param int $idd
     */
    public function set_raise_status($status,$id = 0){
        if(!$id) return Json::fail('参数错误');
        $res = StoreRaiseModel::edit(['is_show'=>$status],$id);
        if($res) return Json::successful('修改成功');
        else return Json::fail('修改失败');
    }


}
