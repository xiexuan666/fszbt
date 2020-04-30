<?php

namespace app\admin\controller\store;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use app\admin\model\store\StoreCourseAttr;
use app\admin\model\store\StoreCourseAttrResult;
use app\admin\model\store\StoreCourseRelation;
use app\admin\model\system\SystemConfig;
use service\JsonService;
use think\Db;
use traits\CurdControllerTrait;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use app\admin\model\store\StoreCategory as CategoryModel;
use app\admin\model\store\StoreCourse as CourseModel;
use think\Url;

use app\admin\model\system\SystemAttachment;


/**
 * 产品管理
 * Class StoreCourse
 * @package app\admin\controller\store
 */
class StoreCourse extends AuthController
{

    use CurdControllerTrait;

    protected $bindModel = CourseModel::class;

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {

        $type=$this->request->param('type');
        //获取分类
        $this->assign('cate',CategoryModel::getTierList());
        //出售中产品
        $onsale =  CourseModel::where(['is_show'=>1,'is_del'=>0])->count();
        //待上架产品
        $forsale =  CourseModel::where(['is_show'=>0,'is_del'=>0])->count();
        //仓库中产品
        $warehouse =  CourseModel::where(['is_del'=>0])->count();
        //已经售馨产品
        $outofstock = CourseModel::getModelObject()->where(CourseModel::setData(4))->count();
        //警戒库存
        $policeforce =CourseModel::getModelObject()->where(CourseModel::setData(5))->count();
        //回收站
        $recycle =  CourseModel::where(['is_del'=>1])->count();

        $this->assign(compact('type','onsale','forsale','warehouse','outofstock','policeforce','recycle'));
        return $this->fetch();
    }
    /**
     * 异步查找产品
     *
     * @return json
     */
    public function course_ist(){
        $where=Util::getMore([
            ['page',1],
            ['limit',20],
            ['store_name',''],
            ['cate_id',''],
            ['excel',0],
            ['order',''],
            ['type',$this->request->param('type')]
        ]);
        return JsonService::successlayui(CourseModel::CourseList($where));
    }
    /**
     * 设置单个产品上架|下架
     *
     * @return json
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && JsonService::fail('缺少参数');
        $res=CourseModel::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
        if($res){
            return JsonService::successful($is_show==1 ? '上架成功':'下架成功');
        }else{
            return JsonService::fail($is_show==1 ? '上架失败':'下架失败');
        }
    }
    /**
     * 设置单个置顶|取消置顶
     * @return json
     */
    public function set_hot($is_hot='',$id=''){
        ($is_hot=='' || $id=='') && JsonService::fail('缺少参数');
        $res=CourseModel::where(['id'=>$id])->update(['is_hot'=>(int)$is_hot]);
        if($res){
            return JsonService::successful($is_hot==1 ? '置顶成功':'取消置顶成功');
        }else{
            return JsonService::fail($is_hot==1 ? '置顶失败':'取消置顶失败');
        }
    }
    /**
     * 快速编辑
     *
     * @return json
     */
    public function set_course($field='',$id='',$value=''){
        $field=='' || $id=='' || $value=='' && JsonService::fail('缺少参数');
        if(CourseModel::where(['id'=>$id])->update([$field=>$value]))
            return JsonService::successful('保存成功');
        else
            return JsonService::fail('保存失败');
    }
    /**
     * 设置批量产品上架
     *
     * @return json
     */
    public function course_show(){
        $post=Util::postMore([
            ['ids',[]]
        ]);
        if(empty($post['ids'])){
            return JsonService::fail('请选择需要上架的产品');
        }else{
            $res=CourseModel::where('id','in',$post['ids'])->update(['is_show'=>1]);
            if($res)
                return JsonService::successful('上架成功');
            else
                return JsonService::fail('上架失败');
        }
    }
    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $field = [
            Form::input('store_name','课程名称')->col(Form::col(24)),
            Form::input('store_info','课时|培训机构')->type('textarea'),
            Form::dateTimeRange('section_time','报名截止时间'),
            Form::frameImageOne('pic','置顶广告图',Url::build('admin/widget.images/index',array('fodder'=>'pic')))->icon('image')->width('100%')->height('500px'),
            Form::frameImageOne('image','课程主图',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image')->width('100%')->height('500px'),
            Form::frameImages('slider_image','课程海报',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')))->maxLength(5)->icon('images')->width('100%')->height('500px')->spin(0),
            Form::number('price','课程价格')->min(0)->col(8),
            Form::number('vip_price','报名价格')->min(0)->col(8),
            Form::number('sales','真实销量',0)->min(0)->precision(0)->col(8)->readonly(1),
            Form::number('ficti','虚拟销量')->min(0)->precision(0)->col(8),
            Form::number('stock','课程人数')->min(0)->precision(0)->col(8),
            Form::number('sort','课程排序')->col(8),
            Form::radio('is_hot','是否置顶',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_show','课程状态',0)->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8)
        ];
        $form = Form::make_post_form('添加课程',$field,Url::build('save'),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
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
        $course = CourseModel::get($id);
        if(!$course) return Json::fail('数据不存在!');
        $field = [
            Form::input('store_name','课程名称',$course->getData('store_name')),
            Form::input('store_info','课时|培训机构',$course->getData('store_info'))->type('textarea'),
            Form::dateTimeRange('section_time','报名截止时间',$course->getData('start_time'),$course->getData('stop_time')),
            Form::frameImageOne('pic','置顶广告图',Url::build('admin/widget.images/index',array('fodder'=>'pic')),$course->getData('pic'))->icon('image')->width('100%')->height('500px'),
            Form::frameImageOne('image','课程主图',Url::build('admin/widget.images/index',array('fodder'=>'image')),$course->getData('image'))->icon('image')->width('100%')->height('500px'),
            Form::frameImages('slider_image','课程海报',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')),json_decode($course->getData('slider_image'),1) ? : [])->maxLength(5)->icon('images')->width('100%')->height('500px'),
            Form::number('price','课程价格',$course->getData('price'))->min(0)->precision(2)->col(8),
            Form::number('vip_price','报名价格',$course->getData('vip_price'))->min(0)->col(8),
            Form::number('sales','真实销量',$course->getData('sales'))->min(0)->precision(0)->col(8)->readonly(1),
            Form::number('ficti','虚拟销量',$course->getData('ficti'))->min(0)->precision(0)->col(8),
            Form::number('stock','课程人数',$course->getData('stock'))->min(0)->precision(0)->col(8),
            Form::number('sort','课程排序',$course->getData('sort'))->col(8),
            Form::radio('is_hot','是否置顶',$course->getData('is_hot'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_show','课程状态',$course->getData('is_show'))->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8)
        ];
        $form = Form::make_post_form('编辑课程',$field,Url::build('update',array('id'=>$id)),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 上传图片
     * @return \think\response\Json
     */
    public function upload()
    {
        $res = Upload::image('file','store/course/'.date('Ymd'));
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
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data = Util::postMore([
            'store_name',
            'store_info',
            ['section_time',[]],
            ['pic',[]],
            ['image',[]],
            ['slider_image',[]],
            ['vip_price',0],
            ['price',0],
            ['sort',0],
            ['stock',100],
            'sales',
            ['ficti',100],
            ['is_hot',0],
            ['is_show',0],
            ['mer_use',0],
        ],$request);

        if(!$data['store_name']) return Json::fail('请输入课程名称');
        if(count($data['image'])<1) return Json::fail('请上传课程图片');
        if(count($data['slider_image'])<1) return Json::fail('请上传课程轮播图');
        if($data['price'] == '' || $data['price'] < 0) return Json::fail('请输入课程售价');
        if($data['vip_price'] == '' || $data['vip_price'] < 0) return Json::fail('请输入最低价');
        if($data['stock'] == '' || $data['stock'] < 0) return Json::fail('请输入人数');
        if(count($data['pic'])) $data['pic'] = $data['pic'][0];
        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);
        $data['start_time'] = strtotime($data['section_time'][0]);
        $data['stop_time'] = strtotime($data['section_time'][1]);
        $data['add_time'] = time();
        $data['description'] = '';
        $res=CourseModel::set($data);
        if($res){
            return Json::successful('添加成功!');
        } else {
            return Json::fail('网络忙，稍后再试');
        }
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
            'store_name',
            'store_info',
            ['section_time',[]],
            ['pic',[]],
            ['image',[]],
            ['slider_image',[]],
            ['vip_price',0],
            ['price',0],
            ['sort',0],
            ['stock',0],
            ['ficti',100],
            ['is_hot',0],
            ['is_show',0],
            ['mer_use',0],
        ],$request);
        if(!$data['store_name']) return Json::fail('请输入课程名称');
        if(count($data['image'])<1) return Json::fail('请上传课程图片');
        if(count($data['slider_image'])<1) return Json::fail('请上传课程轮播图');
        if(count($data['slider_image'])>5) return Json::fail('轮播图最多5张图');
        if($data['price'] == '' || $data['price'] < 0) return Json::fail('请输入课程售价');
        if($data['vip_price'] == '' || $data['vip_price'] < 0) return Json::fail('请输入最低价');
        if($data['stock'] == '' || $data['stock'] < 0) return Json::fail('请输入人数');
        if(count($data['pic'])) $data['pic'] = $data['pic'][0];
        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);
        $data['start_time'] = strtotime($data['section_time'][0]);
        $data['stop_time'] = strtotime($data['section_time'][1]);
        $res = CourseModel::edit($data,$id);
        if($res){
            return Json::successful('添加成功!');
        } else {
            return Json::fail('网络忙，稍后再试');
        }
    }

    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $course = CourseModel::get($id);
        if(!$course) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>CourseModel::where('id',$id)->value('description'),
            'field'=>'description',
            'action'=>Url::build('change_field',['id'=>$id,'field'=>'description'])
        ]);
        return $this->fetch('public/edit_content');
    }

    public function attr($id)
    {
        if(!$id) return $this->failed('数据不存在!');
        $result = StoreCourseAttrResult::getResult($id);
        $image = CourseModel::where('id',$id)->value('image');
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
        $course = CourseModel::get($id);
        if(!$course) return Json::fail('产品不存在');
        $attrFormat = attrFormat($attr)[1];
        if(count($detail)){
            foreach ($attrFormat as $k=>$v){
                foreach ($detail as $kk=>$vv){
                    if($v['detail'] == $vv['detail']){
                        $attrFormat[$k]['price'] = $vv['price'];
                        $attrFormat[$k]['cost'] = isset($vv['cost']) ? $vv['cost'] : $course['cost'];
                        $attrFormat[$k]['sales'] = $vv['sales'];
                        $attrFormat[$k]['pic'] = $vv['pic'];
                        $attrFormat[$k]['check'] = false;
                        break;
                    }else{
                        $attrFormat[$k]['cost'] = $course['cost'];
                        $attrFormat[$k]['price'] = '';
                        $attrFormat[$k]['sales'] = '';
                        $attrFormat[$k]['pic'] = $course['image'];
                        $attrFormat[$k]['check'] = true;
                    }
                }
            }
        }else{
            foreach ($attrFormat as $k=>$v){
                $attrFormat[$k]['cost'] = $course['cost'];
                $attrFormat[$k]['price'] = $course['price'];
                $attrFormat[$k]['sales'] = $course['stock'];
                $attrFormat[$k]['pic'] = $course['image'];
                $attrFormat[$k]['check'] = false;
            }
        }
        return Json::successful($attrFormat);
    }

    public function set_attr($id)
    {
        if(!$id) return $this->failed('课程不存在!');
        list($attr,$detail) = Util::postMore([
            ['items',[]],
            ['attrs',[]]
        ],$this->request,true);
        $res = StoreCourseAttr::createCourseAttr($attr,$detail,$id);
        if($res)
            return $this->successful('编辑属性成功!');
        else
            return $this->failed(StoreCourseAttr::getErrorInfo());
    }

    public function clear_attr($id)
    {
        if(!$id) return $this->failed('产品不存在!');
        if(false !== StoreCourseAttr::clearCourseAttr($id) && false !== StoreCourseAttrResult::clearResult($id))
            return $this->successful('清空产品属性成功!');
        else
            return $this->failed(StoreCourseAttr::getErrorInfo('清空产品属性失败!'));
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
        if(!CourseModel::be(['id'=>$id])) return $this->failed('产品数据不存在');
        if(CourseModel::be(['id'=>$id,'is_del'=>1])){
            $data['is_del'] = 0;
            if(!CourseModel::edit($data,$id))
                return Json::fail(CourseModel::getErrorInfo('恢复失败,请稍候再试!'));
            else
                return Json::successful('成功恢复产品!');
        }else{
            $data['is_del'] = 1;
            if(!CourseModel::edit($data,$id))
                return Json::fail(CourseModel::getErrorInfo('删除失败,请稍候再试!'));
            else
                return Json::successful('成功移到回收站!');
        }

    }




    /**
     * 点赞
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function collect($id){
        if(!$id) return $this->failed('数据不存在');
        $course = CourseModel::get($id);
        if(!$course) return Json::fail('数据不存在!');
        $this->assign(StoreCourseRelation::getCollect($id));
        return $this->fetch();
    }

    /**
     * 收藏
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function like($id){
        if(!$id) return $this->failed('数据不存在');
        $course = CourseModel::get($id);
        if(!$course) return Json::fail('数据不存在!');
        $this->assign(StoreCourseRelation::getLike($id));
        return $this->fetch();
    }
    /**
     * 修改产品价格
     * @param Request $request
     */
    public function edit_course_price(Request $request){
        $data = Util::postMore([
            ['id',0],
            ['price',0],
        ],$request);
        if(!$data['id']) return Json::fail('参数错误');
        $res = CourseModel::edit(['price'=>$data['price']],$data['id']);
        if($res) return Json::successful('修改成功');
        else return Json::fail('修改失败');
    }

    /**
     * 修改产品库存
     * @param Request $request
     */
    public function edit_course_stock(Request $request){
        $data = Util::postMore([
            ['id',0],
            ['stock',0],
        ],$request);
        if(!$data['id']) return Json::fail('参数错误');
        $res = CourseModel::edit(['stock'=>$data['stock']],$data['id']);
        if($res) return Json::successful('修改成功');
        else return Json::fail('修改失败');
    }



}
