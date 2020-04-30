<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:19
 */

namespace app\admin\controller\resume;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use service\PHPTreeService as Phptree;

use think\Url;
use think\Request;
use traits\CurdControllerTrait;

use app\admin\model\industry\Industry;
use app\admin\model\position\Position;
use app\admin\model\resume\ResumeEducation;
use app\admin\model\resume\ResumeExpect;
use app\admin\model\resume\ResumeProject;
use app\admin\model\resume\ResumeRelation;
use app\admin\model\resume\ResumeWork;
use app\admin\model\resume\Resume as ResumeModel;
use app\admin\model\system\SystemAttachment;

/**
 * 简历管理
 * Class Resume
 * @package app\admin\controller\resume
 */
class Resume extends AuthController
{
    use CurdControllerTrait;

    protected $bindModel = ResumeModel::class;

    /**
     * 首页列表
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 异步查找列表
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
        return Json::successlayui(ResumeModel::getList($where));
    }

    /**
     * 快速编辑
     * @param string $field
     * @param string $id
     * @param string $value
     */
    public function set_product($field='',$id='',$value=''){
        $field=='' || $id=='' || $value=='' && JsonService::fail('缺少参数');
        if(ResumeModel::where(['id'=>$id])->update([$field=>$value]))
            return Json::successful('保存成功');
        else
            return Json::fail('保存失败');
    }

    /**
     * 设置单个产品上架|下架
     * @param string $is_show
     * @param string $id
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && Json::fail('缺少参数');
        $res = ResumeModel::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
        if($res){
            return Json::successful($is_show==1 ? '上架成功':'下架成功');
        }else{
            return Json::fail($is_show==1 ? '上架失败':'下架失败');
        }
    }

    /**
     * 设置批量产品上架
     */
    public function product_show(){
        $post=Util::postMore([
            ['ids',[]]
        ]);
        if(empty($post['ids'])){
            return Json::fail('请选择需要上架的产品');
        }else{
            $res = ResumeModel::where('id','in',$post['ids'])->update(['is_show'=>1]);
            if($res)
                return Json::successful('上架成功');
            else
                return Json::fail('上架失败');
        }
    }

    /**
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function add()
    {
        $field = [
            Form::select('cid','求职分类')->setOptions(function(){
                $list = ResumeCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::input('title','求职标题')->col(Form::col(32)),
            Form::input('author','联系姓名')->col(Form::col(32)),
            Form::input('phone','联系手机')->col(Form::col(32)),
            Form::input('address','联系地址')->col(Form::col(32)),
            Form::input('region','招聘地区')->col(Form::col(32)),
            Form::input('experience','工作经验')->col(Form::col(32)),
            Form::input('education','最高学历')->col(Form::col(32)),
            Form::input('salary','薪资要求')->col(Form::col(32)),
            Form::input('welfare','其他福利')->col(Form::col(32))->placeholder('多个用英文状态下的逗号隔开'),
            Form::input('tag','技能标签')->col(Form::col(32))->placeholder('多个用英文状态下的逗号隔开'),
            Form::frameImageOne('image','作品封面(305*305px)',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image')->width('100%')->height('500px'),
            Form::frameImages('slider_image','更多作品(640*640px)',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')))->maxLength(5)->icon('images')->width('100%')->height('500px')->spin(0),
            Form::number('visit','浏览数量',0)->min(0)->precision(0)->col(8)->readonly(1),
            Form::number('ficti','求职评分')->min(0)->precision(0)->col(8),
            Form::number('sort','排序')->col(8),
            Form::radio('is_show','求职状态',0)->options([['label'=>'上架','value'=>1],['label'=>'下架','value'=>0]])->col(8),
            Form::radio('is_top','置顶求职',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_new','最新求职',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8)
        ];
        $form = Form::make_post_form('添加求职',$field,Url::build('save'),2);
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
            ['cid',[]],
            'title',
            'author',
            'phone',
            'address',
            'region',
            'experience',
            'education',
            'salary',
            'welfare',
            'tag',
            ['image',[]],
            ['slider_image',[]],
            ['visit',100],
            ['ficti',100],
            ['sort',0],
            ['ficti',0],
            ['is_show',0],
            ['is_top',0],
            ['is_new',0],],$request);
        $data['cid'] = implode(',',$data['cid']);

        if(!$data['title']) return Json::fail('请输入职位标题');
        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);
        $data['add_time'] = time();

        $data['admin_id'] = $this->adminId;
        ResumeModel::beginTrans();
        $res1 = ResumeModel::set($data);
        if($res1)
            $res = true;
        else
            $res =false;
        ResumeModel::checkTrans($res);
        if($res)
            return Json::successful('添加职位成功!',$res1->id);
        else
            return Json::successful('添加职位失败!',$res1->id);
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
        $product = ResumeModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $field = [
            Form::input('name','求职姓名',$product->getData('name')),
            Form::input('phone','联系电话',$product->getData('phone')),
            Form::radio('sex','选择性别',$product->getData('sex'))->options([['label'=>'先生','value'=>1],['label'=>'女士','value'=>2]])->col(8),
            Form::radio('status','求职状态',$product->getData('status'))->options([['label'=>'离职-随时到岗','value'=>'离职-随时到岗'],['label'=>'离职-1周内到岗','value'=>'离职-1周内到岗'],['label'=>'在职-考虑机会','value'=>'在职-考虑机会'],['label'=>'在职-1月内到岗','value'=>'在职-1月内到岗']])->col(24),
            Form::input('work_time','参加工作时间',$product->getData('work_time')),
            Form::input('description','求职优势',$product->getData('description')),

            Form::frameImages('slider_image','求职作品',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')),json_decode($product->getData('slider_image'),1) ? : [])->maxLength(5)->icon('images')->width('100%')->height('500px'),

            Form::radio('is_show','是否显示',$product->getData('is_show'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_top','是否置顶',$product->getData('is_top'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8)
        ];
        $form = Form::make_post_form('编辑求职',$field,Url::build('update',array('id'=>$id)),2);
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
            'name',
            'phone',
            'status',
            'work_time',
            ['slider_image',[]],
            ['description',''],
            ['is_show',0],
            ['is_top',0],
            ['sex',0],],$request);

        if(count($data['slider_image']) > 0) $data['image'] = $data['slider_image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);

        ResumeModel::beginTrans();
        $res1 = ResumeModel::edit($data,$id);
        if($res1)
            $res = true;
        else
            $res =false;
        ResumeModel::checkTrans($res);
        if($res)
            return Json::successful('修改成功!',$id);
        else
            return Json::fail('修改失败，您并没有修改什么!',$id);
    }

    /**
     * @param $id
     * @return mixed|void
     * @throws \think\exception\DbException
     */
    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $product = ResumeModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>ResumeModel::where('id',$id)->value('description'),
            'field'=>'description',
            'action'=>Url::build('change_field',['id'=>$id,'field'=>'description'])
        ]);
        return $this->fetch('public/edit_content');
    }

    /**
     * @param $id
     * @return mixed|void
     * @throws \think\exception\DbException
     */
    public function expectList($id){
        if(!$id) return $this->failed('数据不存在');
        $data = ResumeModel::get($id);
        if(!$data) return Json::fail('数据不存在!');
        $list = ResumeExpect::where('uid',$data['uid'])->select();
        foreach ($list as $item=>$value){
            $position = Position::where(array('id'=>$value['position']))->find();
            if($position['pid']) {
                $positionOne = Position::where(array('id'=>$position['pid']))->find();
                if($positionOne){
                    $list[$item]['position'] = $positionOne['name'].' · '.$position['name'];
                }
            } else {
                $list[$item]['position'] = $position['name'];
            }
            $industry = Industry::where(array('id'=>$value['industry']))->find();
            $list[$item]['industry'] = $industry['name'];
        }
        $this->assign([
            'data'=>$data,
            'list'=>$list
        ]);
        return $this->fetch();
    }

    /**
     * @param $id
     * @return mixed|void
     * @throws \think\exception\DbException
     */
    public function workList($id){
        if(!$id) return $this->failed('数据不存在');
        $data = ResumeModel::get($id);
        if(!$data) return Json::fail('数据不存在!');
        $list = ResumeWork::where('uid',$data['uid'])->select();
        foreach ($list as $item=>$value){
            $position = Position::where(array('id'=>$value['position']))->find();
            if($position['pid']) {
                $positionOne = Position::where(array('id'=>$position['pid']))->find();
                if($positionOne){
                    $list[$item]['position'] = $positionOne['name'].' · '.$position['name'];
                }
            } else {
                $list[$item]['position'] = $position['name'];
            }
            $industry = Industry::where(array('id'=>$value['industry']))->find();

            $list[$item]['start_time'] = date('Y.m',strtotime($value['start_time']));
            $list[$item]['stop_time'] = date('Y.m',strtotime($value['stop_time']));
            $list[$item]['industry'] = $industry['name'];
        }
        $this->assign([
            'data'=>$data,
            'list'=>$list
        ]);
        return $this->fetch();
    }

    /**
     * @param $id
     * @return mixed|void
     * @throws \think\exception\DbException
     */
    public function educationList($id){
        if(!$id) return $this->failed('数据不存在');
        $data = ResumeModel::get($id);
        if(!$data) return Json::fail('数据不存在!');
        $list = ResumeEducation::where('uid',$data['uid'])->select();
        foreach ($list as $item=>$value){
            $education[$item]['start_time'] = date('Y',strtotime($value['start_time']));
            $education[$item]['stop_time'] = date('Y',strtotime($value['stop_time']));
        }
        $this->assign([
            'data'=>$data,
            'list'=>$list
        ]);
        return $this->fetch();
    }

    /**
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
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
            $news = ResumeModel::where('n.id',$id)->alias('n')->field('n.*,c.content')->join('ResumeContent c','c.nid=n.id')->find();
            if(!$news) return $this->failedNotice('数据不存在!');
            $news['cid'] = explode(',',$news['cid']);
        }
        $all = array();
        $select =  0;
        if(!$cid)
            $cid = '';
        else {
            if($id){
                $all = ResumeCategoryModel::where('id',$cid)->where('hidden','neq',0)->column('id,title');
                $select = 1;
            }else{
                $all = ResumeCategoryModel::where('id',$cid)->column('id,title');
                $select = 1;
            }

        }
        if(empty($all)){
            $select =  0;
            $list = ResumeCategoryModel::getTierList();
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
            ResumeModel::beginTrans();
            $res1 = ResumeModel::edit($data,$id,'id');
            $res2 = ResumeModel::setContent($id,$content);
            if($res1 && $res2)
                $res = true;
            else
                $res =false;
            ResumeModel::checkTrans($res);
            if($res)
                return Json::successful('修改图文成功!',$id);
            else
                return Json::fail('修改图文失败，您并没有修改什么!',$id);
        }else{
            $data['add_time'] = time();
            $data['admin_id'] = $this->adminId;
            ResumeModel::beginTrans();
            $res1 = ResumeModel::set($data);
            $res2 = false;
            if($res1)
                $res2 = ResumeModel::setContent($res1->id,$content);
            if($res1 && $res2)
                $res = true;
            else
                $res =false;
            ResumeModel::checkTrans($res);
            if($res)
                return Json::successful('添加图文成功!',$res1->id);
            else
                return Json::successful('添加图文失败!',$res1->id);
        }
    }

    /**
     * 删除指定资源
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if(!$id) return $this->failed('数据不存在');
        if(!ResumeModel::be(['id'=>$id])) return $this->failed('数据不存在');
        if(ResumeModel::be(['id'=>$id,'is_del'=>1])){
            $data['is_del'] = 0;
            if(!ResumeModel::edit($data,$id))
                return Json::fail(ResumeModel::getErrorInfo('恢复失败,请稍候再试!'));
            else
                return Json::successful('成功恢复!');
        }else{
            $data['is_del'] = 1;
            if(!ResumeModel::edit($data,$id))
                return Json::fail(ResumeModel::getErrorInfo('删除失败,请稍候再试!'));
            else
                return Json::successful('成功移到回收站!');
        }

    }

    /**
     * @return mixed
     */
    public function merchantIndex(){
        $where = Util::getMore([
            ['title','']
        ],$this->request);
        $this->assign('where',$where);
        $where['cid'] = input('cid');
        $where['merchant'] = 1;//区分是管理员添加的图文显示  0 还是 商户添加的图文显示  1
        $this->assign(ResumeModel::getAll($where));
        return $this->fetch();
    }
}