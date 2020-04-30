<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:19
 */

namespace app\admin\controller\job;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use think\Url;

use traits\CurdControllerTrait;
use app\admin\model\job\JobPosition;
use app\admin\model\welfare\Welfare;
use app\admin\model\job\Job as JobModel;
use app\admin\model\industry\Industry;
use app\admin\model\position\Position;
use app\admin\model\system\SystemAttachment;

/**
 * 职位管理
 * Class Company
 * @package app\admin\controller\company
 */
class Job extends AuthController
{
    use CurdControllerTrait;

    protected $bindModel = JobPosition::class;

    /**
     * 显示后台管理员添加的图文
     * @param int $pid
     * @return mixed
     */
    public function index($pid = 0)
    {
        /*$where = Util::getMore([
            ['title',''],
            ['cid','']
        ],$this->request);


        $pid = $this->request->param('pid');
        $this->assign('where',$where);
        $where['merchant'] = 0;//区分是管理员添加的图文显示  0 还是 商户添加的图文显示  1


        $catlist = JobCategoryModel::select()->toArray();
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


        $this->assign('cate',JobCategoryModel::getTierList());
        $this->assign(JobModel::getAll($where));*/
        return $this->fetch();
    }

    /**
     * 异步查找产品
     *
     * @return json
     */
    public function get_list(){
        $where = Util::getMore([
            ['page',1],
            ['limit',20],
            ['keywords',''],
            ['cate_id',''],
            ['excel',0],
            ['order',''],
            ['type',$this->request->param('type')]
        ]);
        return Json::successlayui(JobPosition::ProductList($where));
    }

    /**
     * 快速编辑
     *
     * @return json
     */
    public function set_product($field='',$id='',$value=''){
        $field=='' || $id=='' || $value=='' && JsonService::fail('缺少参数');
        if(JobPosition::where(['id'=>$id])->update([$field=>$value]))
            return Json::successful('保存成功');
        else
            return Json::fail('保存失败');
    }

    /**
     * 设置单个产品上架|下架
     *
     * @return json
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && Json::fail('缺少参数');
        $res=JobPosition::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
        if($res){
            return Json::successful($is_show==1 ? '上架成功':'下架成功');
        }else{
            return Json::fail($is_show==1 ? '上架失败':'下架失败');
        }
    }

    /**
     * 设置批量产品上架
     *
     * @return json
     */
    public function product_show(){
        $post=Util::postMore([
            ['ids',[]]
        ]);
        if(empty($post['ids'])){
            return Json::fail('请选择需要上架的产品');
        }else{
            $res=JobPosition::where('id','in',$post['ids'])->update(['is_show'=>1]);
            if($res)
                return Json::successful('上架成功');
            else
                return Json::fail('上架失败');
        }
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function add()
    {
        $field = [
            Form::select('cid','职位分类')->setOptions(function(){
                $list = JobCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::input('title','职位标题')->col(Form::col(32)),
            Form::input('author','联系姓名')->col(Form::col(32)),
            Form::input('phone','联系手机')->col(Form::col(32)),
            Form::input('address','联系地址')->col(Form::col(32)),
            Form::input('region','招聘地区')->col(Form::col(32)),
            Form::input('name','公司名称')->col(Form::col(32)),
            Form::input('age_for','工作经验')->col(Form::col(32)),
            Form::input('education','学历要求')->col(Form::col(32)),
            Form::input('salary','薪资待遇')->col(Form::col(32)),
            Form::input('welfare','其他福利')->col(Form::col(32))->placeholder('多个用英文状态下的逗号隔开'),
            Form::input('tag','招聘标签')->col(Form::col(32))->placeholder('多个用英文状态下的逗号隔开'),
            Form::input('scale','公司规模')->col(Form::col(32)),
            Form::input('synopsis','公司简介')->type('textarea'),
            Form::frameImageOne('image','招聘封面(305*305px)',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image')->width('100%')->height('500px'),
            Form::frameImages('slider_image','更多图片(640*640px)',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')))->maxLength(5)->icon('images')->width('100%')->height('500px')->spin(0),
            Form::number('visit','浏览数量',0)->min(0)->precision(0)->col(8)->readonly(1),
            Form::number('ficti','职位评分')->min(0)->precision(0)->col(8),
            Form::number('sort','排序')->col(8),
            Form::radio('is_show','职位状态',0)->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8),
            Form::radio('is_hot','热门职位',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_top','置顶职位',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_new','最新职位',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8)
        ];
        $form = Form::make_post_form('添加职位',$field,Url::build('save'),2);
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
            'title',
            'author',
            'phone',
            'address',
            'region',
            'name',
            'age_for',
            'education',
            'salary',
            'welfare',
            'tag',
            'scale',
            ['image',[]],
            ['slider_image',[]],
            'synopsis',
            ['visit',100],
            ['ficti',100],
            ['sort',0],
            ['ficti',0],
            ['is_show',0],
            ['is_top',0],
            ['is_new',0],
            ['is_hot',0],],$request);
        $data['cid'] = implode(',',$data['cid']);

        if(!$data['title']) return Json::fail('请输入职位标题');
        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);
        $data['add_time'] = time();

        $data['admin_id'] = $this->adminId;
        JobModel::beginTrans();
        $res1 = JobModel::set($data);
        if($res1)
            $res = true;
        else
            $res =false;
        JobModel::checkTrans($res);
        if($res)
            return Json::successful('添加职位成功!',$res1->id);
        else
            return Json::successful('添加职位失败!',$res1->id);
    }

    /**
     * 编辑
     * @param $id
     * @return mixed|void
     * @throws \FormBuilder\exception\FormBuilderException
     * @throws \think\exception\DbException
     */
    public function edit($id)
    {
        if(!$id) return $this->failed('数据不存在');
        $product = JobPosition::get($id);
        $wel = Welfare::where('status',1)->select();
        $welAll = [];
        foreach ($wel as $item=>$value){
            $welAll[$item] = array('value'=>$value['id'],'label'=>$value['title']);
        }
        if(count($product['welfare'])){
            $welfare = json_decode($product['welfare'],true);
        } else {
            $welfare = [1];
        }

        if(!$product) return Json::fail('数据不存在!');
        $field = [
            Form::select('industry','招聘部门',explode(',',$product->getData('industry')))->setOptions(function(){
                $list = Industry::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['name']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::select('position','招聘职位',explode(',',$product->getData('position')))->setOptions(function(){
                $list = Position::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['name']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::checkbox('checkbox','福利待遇',$welfare)->options($welAll),
            Form::input('age_for','年龄要求',$product->getData('age_for')),
            Form::input('education','学历要求',$product->getData('education')),
            Form::input('skills','工作经验',$product->getData('skills')),
            Form::input('salary','薪资范围',$product->getData('salary')),
            Form::input('address','工作城市',$product->getData('address')),
            Form::input('duty','岗位职责',$product->getData('duty'))->type('textarea'),
            Form::input('description','任职要求',$product->getData('description'))->type('textarea'),
            Form::radio('is_show','职位状态',$product->getData('is_show'))->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8),
            Form::radio('is_top','置顶职位',$product->getData('is_top'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
        ];
        $form = Form::make_post_form('编辑职位',$field,Url::build('update',array('id'=>$id)),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 更新编辑
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id)
    {
        $data = Util::postMore([
            ['position',[]],
            ['industry',[]],
            ['checkbox',[]],
            'skills',
            'duty',
            'description',
            'age_for',
            'education',
            'salary',
            'address',
            ['views',0],
            ['sort',0],
            ['is_show',0],
            ['is_top',0],],$request);

        if(count($data['industry']) < 1) return Json::fail('请选择招聘部门');
        $data['industry'] = implode(',',$data['industry']);
        if(count($data['position']) < 1) return Json::fail('请选择招聘职位');
        $data['position'] = implode(',',$data['position']);
        if(count($data['checkbox']) > 0) $data['welfare'] = json_encode($data['checkbox']);//implode(',',$data['welfare']);
        $data['add_time'] = time();
        JobPosition::beginTrans();
        $res1 = JobPosition::edit($data,$id);
        if($res1)
            $res = true;
        else
            $res =false;
        JobPosition::checkTrans($res);
        if($res)
            return Json::successful('修改成功!',$id);
        else
            return Json::fail('修改失败，您并没有修改什么!',$id);
    }

    public function edit_duty($id){
        if(!$id) return $this->failed('数据不存在');
        $product = JobPosition::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>JobPosition::where('id',$id)->value('duty'),
            'field'=>'duty',
            'action'=>Url::build('change_field',['id'=>$id,'field'=>'duty'])
        ]);
        return $this->fetch('public/edit_content');
    }

    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $product = JobPosition::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>JobPosition::where('id',$id)->value('description'),
            'field'=>'description',
            'action'=>Url::build('change_field',['id'=>$id,'field'=>'description'])
        ]);
        return $this->fetch('public/edit_content');
    }

    /**
     * 展示页面   添加和删除
     * @return mixed
     */
    public function create(){
        $id = input('id');
        $cid = input('cid');
        $news = array();
        $news['id'] = '';
        $news['image_input'] = '';
        $news['title'] = '';
        $news['author'] = '';

        $news['phone'] = '';
        $news['address'] = '';
        $news['experience'] = '';
        $news['education'] = '';
        $news['salary'] = '';
        $news['welfare'] = '';

        $news['is_banner'] = '';
        $news['is_hot'] = '';
        $news['content'] = '';
        $news['synopsis'] = '';
        $news['url'] = '';
        $news['cid'] = array();
        if($id){
            $news = JobModel::where('n.id',$id)->alias('n')->field('n.*,c.content')->join('JobContent c','c.nid=n.id')->find();
            if(!$news) return $this->failedNotice('数据不存在!');
            $news['cid'] = explode(',',$news['cid']);
        }
        $all = array();
        $select =  0;
        if(!$cid)
            $cid = '';
        else {
            if($id){
                $all = JobCategoryModel::where('id',$cid)->where('hidden','neq',0)->column('id,title');
                $select = 1;
            }else{
                $all = JobCategoryModel::where('id',$cid)->column('id,title');
                $select = 1;
            }

        }
        if(empty($all)){
            $select =  0;
            $list = JobCategoryModel::getTierList();
            $all = [];
            foreach ($list as $menu){
                $all[$menu['id']] = $menu['html'].$menu['title'];
            }
        }
        $this->assign('all',$all);
        $this->assign('news',$news);
        $this->assign('cid',$cid);
        $this->assign('select',$select);
        return $this->fetch();
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
     * 添加和修改图文
     * @param Request $request
     */
    public function add_new(Request $request){
        $post  = $request->post();
        $data = Util::postMore([
            ['id',0],
            ['cid',[]],
            'title',
            'author',

            'phone',
            'address',
            'experience',
            'education',
            'salary',
            'welfare',

            'image_input',
            'content',
            'synopsis',
            'share_title',
            'share_synopsis',
            ['visit',0],
            ['sort',0],
            'url',
            ['is_banner',0],
            ['is_hot',0],
            ['status',1],],$request);
        $data['cid'] = implode(',',$data['cid']);

        $content = $data['content'];
        unset($data['content']);
        if($data['id']){
            $id = $data['id'];
            unset($data['id']);
            JobModel::beginTrans();
            $res1 = JobModel::edit($data,$id,'id');
            $res2 = JobModel::setContent($id,$content);
            if($res1 && $res2)
                $res = true;
            else
                $res =false;
            JobModel::checkTrans($res);
            if($res)
                return Json::successful('修改图文成功!',$id);
            else
                return Json::fail('修改图文失败，您并没有修改什么!',$id);
        }else{
            $data['add_time'] = time();
            $data['admin_id'] = $this->adminId;
            JobModel::beginTrans();
            $res1 = JobModel::set($data);
            $res2 = false;
            if($res1)
                $res2 = JobModel::setContent($res1->id,$content);
            if($res1 && $res2)
                $res = true;
            else
                $res =false;
            JobModel::checkTrans($res);
            if($res)
                return Json::successful('添加图文成功!',$res1->id);
            else
                return Json::successful('添加图文失败!',$res1->id);
        }
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
        $res = JobPosition::where('id',$id)->delete();
        if($res)
            return Json::successful('删除成功!');
        else
            return Json::fail(JobPosition::getErrorInfo('删除失败,请稍候再试!'));
    }


    public function merchantIndex(){
        $where = Util::getMore([
            ['title','']
        ],$this->request);
        $this->assign('where',$where);
        $where['cid'] = input('cid');
        $where['merchant'] = 1;//区分是管理员添加的图文显示  0 还是 商户添加的图文显示  1
        $this->assign(JobModel::getAll($where));
        return $this->fetch();
    }
}