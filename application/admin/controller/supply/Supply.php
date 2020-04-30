<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:19
 */

namespace app\admin\controller\supply;

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

use app\admin\model\supply\SupplyCategory as SupplyCategoryModel;
use app\admin\model\supply\Supply as SupplyModel;
use app\admin\model\system\SystemAttachment;

/**
 * 知识管理
 * Class Supply
 * @package app\admin\controller\supply
 */
class Supply extends AuthController
{
    use CurdControllerTrait;

    protected $bindModel = SupplyModel::class;

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
        $catlist = SupplyCategoryModel::where('is_del',0)->select()->toArray();
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

        $this->assign('cate',SupplyCategoryModel::getTierList());
        //$this->assign(SupplyModel::getAll($where));
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
        return Json::successlayui(SupplyModel::getList($where));
    }

    /**
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function add()
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
            Form::select('cid','招商类型')->setOptions(function(){
                $list = SupplyCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::input('title','招商标题'),
            Form::input('author','招商联系人'),
            Form::input('phone','招商热线'),
            Form::input('district','区域'),
            Form::input('province','省份'),
            Form::input('city','城市'),
            Form::frameImageOne('image','封面(305*305px)',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image')->width('800px')->height('500px'),
            Form::frameImages('slider_image','招商海报，最多上传9张',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')))->maxLength(9)->icon('images')->width('800px')->height('500px'),
            Form::radio('is_show','招商状态')->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8),
            Form::radio('is_banner','是否置顶')->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8)
        ];

        $form = Form::make_post_form('添加',$field,Url::build('save'),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * @param Request $request
     */
    public function save(Request $request)
    {
        $data = Util::postMore([
            ['id',0],
            ['uid',[]],
            ['cid',[]],
            'title',
            'author',
            'phone',
            'district',
            'province',
            'city',
            ['image',[]],
            ['slider_image',[]],
            ['is_show',0],
            ['is_banner',0],
            ['status',1],],$request);

        $data['cid'] = implode(',',$data['cid']);
        $data['uid'] = $data['mer_id'] = implode(',',$data['uid']);

        if(count($data['image'])) $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);

        $data['add_time'] = time();
        $data['admin_id'] = $this->adminId;
        SupplyModel::beginTrans();
        $res1 = SupplyModel::set($data);
        if($res1)
            $res = true;
        else
            $res =false;
        SupplyModel::checkTrans($res);
        if($res)
            return Json::successful('添加成功!',$res1->id);
        else
            return Json::successful('添加失败!',$res1->id);
    }

    /**
     * @param $id
     * @return mixed|void
     * @throws \FormBuilder\exception\FormBuilderException
     * @throws \think\exception\DbException
     */
    public function edit($id)
    {
        if(!$id) return $this->failed('数据不存在');
        $product = SupplyModel::get($id);
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
            Form::select('cid','招商类型',explode(',',$product->getData('cid')))->setOptions(function(){
                $list = SupplyCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::input('title','招商标题',$product->getData('title')),
            Form::input('author','招商联系人',$product->getData('author')),
            Form::input('phone','招商热线',$product->getData('phone')),
            Form::input('district','区域',$product->getData('district')),
            Form::input('province','省份',$product->getData('province')),
            Form::input('city','城市',$product->getData('city')),
            Form::frameImageOne('image','封面(305*305px)',Url::build('admin/widget.images/index',array('fodder'=>'image')),$product->getData('image'))->icon('image')->width('800px')->height('500px'),
            Form::frameImages('slider_image','招商海报，最多上传9张',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')),json_decode($product->getData('slider_image'),1) ? : [])->maxLength(9)->icon('images')->width('800px')->height('500px'),
            Form::radio('is_show','招商状态',$product->getData('is_show'))->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8),
            Form::radio('is_banner','是否置顶',$product->getData('is_banner'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8)
        ];
        $form = Form::make_post_form('编辑',$field,Url::build('update',array('id'=>$id)),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id)
    {
        $data = Util::postMore([
            ['uid',[]],
            ['cid',[]],
            'title',
            'author',
            'phone',
            'district',
            'province',
            'city',
            ['image',[]],
            ['slider_image',[]],
            ['is_show',0],
            ['is_banner',0],
            ['status',1],],$request);

        $data['cid'] = implode(',',$data['cid']);
        $data['uid'] = $data['mer_id'] = implode(',',$data['uid']);

        if(count($data['image'])) $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);

        SupplyModel::beginTrans();
        $res1 = SupplyModel::edit($data,$id);
        if($res1)
            $res = true;
        else
            $res =false;
        SupplyModel::checkTrans($res);
        if($res)
            return Json::successful('修改成功!',$id);
        else
            return Json::fail('修改失败，您并没有修改什么!',$id);
    }

    /**
     * 显示隐藏
     * @param string $is_show
     * @param string $id
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && Json::fail('缺少参数');
        $res = SupplyModel::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
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
        $res = SupplyModel::where(['id'=>$id])->update(['is_banner'=>(int)$is_banner]);
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
        if(SupplyModel::where(['id'=>$id])->update([$field=>$value]))
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
        $res = SupplyModel::del($id);
        if(!$res)
            return Json::fail('删除失败,请稍候再试!');
        else
            return Json::successful('删除成功!');
    }

    /**
     * @param $id
     * @return mixed|void
     * @throws \think\exception\DbException
     */
    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $product = SupplyModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>SupplyModel::where('id',$id)->value('description'),
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
        $this->assign(SupplyModel::getAll($where));
        return $this->fetch();
    }
}