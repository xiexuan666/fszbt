<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:19
 */

namespace app\admin\controller\knowledge;

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

use app\admin\model\knowledge\KnowledgeCategory as KnowledgeCategoryModel;
use app\admin\model\knowledge\Knowledge as KnowledgeModel;
use app\admin\model\system\SystemAttachment;

/**
 * 知识管理
 * Class Knowledge
 * @package app\admin\controller\knowledge
 */
class Knowledge extends AuthController
{
    use CurdControllerTrait;

    protected $bindModel = KnowledgeModel::class;

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
        $catlist = KnowledgeCategoryModel::where('is_del',0)->select()->toArray();
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


        $this->assign('cate',KnowledgeCategoryModel::getTierList());
        //$this->assign(KnowledgeModel::getAll($where));
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
        return Json::successlayui(KnowledgeModel::getList($where));
    }

    /**
     * 显示隐藏
     * @param string $is_show
     * @param string $id
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && Json::fail('缺少参数');
        $res = KnowledgeModel::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
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
        $res = KnowledgeModel::where(['id'=>$id])->update(['is_banner'=>(int)$is_banner]);
        if($res){
            return Json::successful($is_banner==1 ? '显示成功':'隐藏成功');
        }else{
            return Json::fail($is_banner==1 ? '显示失败':'隐藏失败');
        }
    }

    /**
     * 设置置顶
     * @param string $is_best
     * @param string $id
     */
    public function set_best($is_best='',$id=''){
        ($is_best=='' || $id=='') && Json::fail('缺少参数');
        $res = KnowledgeModel::where(['id'=>$id])->update(['is_best'=>(int)$is_best]);
        if($res){
            return Json::successful($is_best==1 ? '推荐成功':'取消推荐成功');
        }else{
            return Json::fail($is_best==1 ? '推荐失败':'取消推荐失败');
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
        if(KnowledgeModel::where(['id'=>$id])->update([$field=>$value]))
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
        $res = KnowledgeModel::del($id);
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
            Form::select('cid','文章类目')->setOptions(function(){
                $list = KnowledgeCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::input('title','文章标题')->col(Form::col(24)),
            Form::radio('is_price','此信息是否付费',0)->options([['label'=>'付费','value'=>1],['label'=>'免费','value'=>2]])->col(8),
            Form::number('price','付费金额')->min(0)->col(8),
            Form::frameImages('posters','文章分享宣传海报设计，最多3张（选填）',Url::build('admin/widget.images/index',array('fodder'=>'posters')))->maxLength(3)->icon('images')->width('800px')->height('500px')->spin(0),
            Form::input('directory','文章目录')->placeholder('文章目录(英文“,”隔开多个目录，无需写序列号)')->type('textarea'),
            Form::input('test','免费部分')->placeholder('文章内容宣传免费预览部分')->type('textarea'),
            Form::input('description','文章详情')->placeholder('完整文章内容')->type('textarea'),

            Form::frameImages('slider_image','文章图片格式上传（选填），最多100张',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')))->maxLength(100)->icon('images')->width('800px')->height('500px')->spin(0),

            Form::input('audio_url','音频链接')->col(Form::col(24)),
            Form::input('video_url','视频链接')->col(Form::col(24)),
            Form::frameImageOne('cover','视频干货宣传封面（选填）',Url::build('admin/widget.images/index',array('fodder'=>'cover')))->icon('image')->width('800px')->height('500px'),

            Form::radio('is_show','状态',0)->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8),
            Form::radio('is_banner','置顶',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),Form::radio('is_best','推荐',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8)
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
            ['uid',[]],
            ['cid',[]],
            ['is_price',0],
            ['price',0],
            'title',
            ['posters',[]],
            'directory',
            'test',
            'description',
            ['slider_image',[]],
            'audio_url',
            'video_url',
            ['cover',[]],
            ['is_show',0],
            ['is_banner',0],
            ['is_best',0],
            ['status',1],],$request);

        if(count($data['uid']) < 1) return Json::fail('请选择用户');
        if(count($data['cid']) < 1) return Json::fail('请选择分类');
        $data['uid'] = implode(',',$data['uid']);
        $data['cid'] = implode(',',$data['cid']);

        if(!$data['title']) return Json::fail('请输入标题');

        if(count($data['posters'])) $data['image'] = $data['posters'][0];
        $data['posters'] = json_encode($data['posters']);
        $data['slider_image'] = json_encode($data['slider_image']);
        if(count($data['cover'])) $data['cover'] = $data['cover'][0];
        $data['add_time'] = time();
        $data['admin_id'] = $this->adminId;
        KnowledgeModel::beginTrans();
        $res1 = KnowledgeModel::set($data);
        if($res1)
            $res = true;
        else
            $res =false;
        KnowledgeModel::checkTrans($res);
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
        $product = KnowledgeModel::get($id);
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
            Form::select('cid','文章类目',explode(',',$product->getData('cid')))->setOptions(function(){
                $list = KnowledgeCategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['title']];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::input('title','文章标题',$product->getData('title')),

            Form::radio('is_price','此信息是否付费',$product->getData('is_price'))->options([['label'=>'付费','value'=>1],['label'=>'免费','value'=>2]])->col(8),
            Form::number('price','付费金额',$product->getData('price'))->min(0)->precision(2)->col(8),
            Form::frameImages('posters','文章分享宣传海报设计，最多3张（选填）',Url::build('admin/widget.images/index',array('fodder'=>'posters')),json_decode($product->getData('posters'),1) ? : [])->maxLength(3)->icon('images')->width('800px')->height('500px'),

            Form::input('directory','文章目录',$product->getData('directory'))->placeholder('文章目录(英文“,”隔开多个目录，无需写序列号)')->type('textarea'),
            Form::input('test','免费部分',$product->getData('test'))->placeholder('文章内容宣传免费预览部分')->type('textarea'),
            Form::input('description','文章详情',$product->getData('description'))->placeholder('完整文章内容')->type('textarea'),

            Form::frameImages('slider_image','文章图片格式上传（选填），最多100张',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')),json_decode($product->getData('slider_image'),1) ? : [])->maxLength(100)->icon('images')->width('800px')->height('500px'),

            Form::input('audio_url','音频链接',$product->getData('audio_url')),
            Form::input('video_url','视频链接',$product->getData('video_url')),
            Form::frameImageOne('cover','视频干货宣传封面（选填）',Url::build('admin/widget.images/index',array('fodder'=>'cover')),$product->getData('cover'))->icon('image')->width('800px')->height('500px'),

            Form::radio('is_show','文章状态',$product->getData('is_show'))->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])->col(8),
            Form::radio('is_banner','置顶',$product->getData('is_banner'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_best','推荐',$product->getData('is_best'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8)
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
            ['uid',[]],
            ['cid',[]],
            ['is_price',0],
            ['price',0],
            'title',
            ['posters',[]],
            'directory',
            'test',
            'description',
            ['slider_image',[]],
            'audio_url',
            'video_url',
            ['cover',[]],
            ['is_show',0],
            ['is_banner',0],
            ['is_best',0],
            ['status',1],],$request);

        if(count($data['uid']) < 1) return Json::fail('请选择用户');
        if(count($data['cid']) < 1) return Json::fail('请选择分类');
        $data['uid'] = implode(',',$data['uid']);
        $data['cid'] = implode(',',$data['cid']);

        if(!$data['title']) return Json::fail('请输入标题');

        if(count($data['posters'])) $data['image'] = $data['posters'][0];
        $data['posters'] = json_encode($data['posters']);
        $data['slider_image'] = json_encode($data['slider_image']);
        if(count($data['cover'])) $data['cover'] = $data['cover'][0];
        $data['add_time'] = time();
        $data['admin_id'] = $this->adminId;

        KnowledgeModel::beginTrans();
        $res1 = KnowledgeModel::edit($data,$id);
        if($res1)
            $res = true;
        else
            $res =false;
        KnowledgeModel::checkTrans($res);
        if($res)
            return Json::successful('修改成功!',$id);
        else
            return Json::fail('修改失败，您并没有修改什么!',$id);
    }

    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $product = KnowledgeModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>KnowledgeModel::where('id',$id)->value('description'),
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
        $this->assign(KnowledgeModel::getAll($where));
        return $this->fetch();
    }
}