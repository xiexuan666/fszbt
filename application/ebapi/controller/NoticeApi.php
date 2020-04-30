<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-15
 * Time: 18:11
 */

namespace app\ebapi\controller;
use app\ebapi\model\user\UserSubion;
use app\ebapi\model\notice\Notice AS NoticeModel;
use app\ebapi\model\notice\NoticeCategory;

use app\core\model\routine\RoutineCode;
use app\core\util\SystemConfigService;

use service\JsonService;
use service\UtilService;
use think\Request;

class NoticeApi extends AuthController
{

    public static function whiteList()
    {
        return [
            'get_notice_cate',
            'get_cid_notice',
            'get_notice_hot',
            'get_notice_banner',
            'visit',
            'get_class',
            'get_user_notice',
            'edit_user_notice',
            'get_promotion_code'
        ];
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 一级分类
     */
    public function get_one(){
        $data = NoticeCategory::getCategory();
        if($data){
            return JsonService::successful($data);
        } else {
            return JsonService::fail('暂无数据!');
        }
    }

    /**
     * @param Request $request
     * 二级分类
     */
    public function get_two(Request $request){
        $data = UtilService::getMore([['id',0]],$request);
        $dataCateA = [];
        $dataCateA[0]['id'] = $data['id'];
        $dataCateA[0]['title'] = '全部';
        $dataCateA[0]['pid'] = 0;
        $dataCateE = NoticeCategory::pidBySidList($data['id']);
        if($dataCateE) $dataCateE = $dataCateE->toArray();
        $dataCate = array_merge_recursive($dataCateA,$dataCateE);
        if($dataCate){
            return JsonService::successful($dataCate);
        } else {
            return JsonService::fail('暂无数据!');
        }
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取我的文章列表
     */
    public function get_my_list(){
        $data = UtilService::getMore([
            ['uid',$this->userInfo['uid']],
            ['keyword','']
        ],$this->request);
        $keyword = $data['keyword'];
        unset($data['keyword']);
        $list = NoticeModel::where($data)->where('status',1)->where('keyword|title','LIKE',htmlspecialchars("%$keyword%"))->select();
        foreach ($list as $item=>$value){
            $cate_nam = NoticeCategory::where(array('id'=>$value['cid']))->find();
            $list[$item]['cname'] = $cate_nam['title'];
        }
        return JsonService::successful($list);
    }


    /**
     * TODO 获取文章分类
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_notice_cate(){
        $cateInfo = NoticeCategory::getCategory();
        if($cateInfo) $cateInfo = $cateInfo->toArray();
        else $cateInfo = [];
        array_unshift($cateInfo,['id'=>0,'title'=>'热门']);
        return $this->successful($cateInfo);
    }

    /**
     * TODO 文章列表
     * @param int $cid
     * @param int $first
     * @param int $limit
     */
    public function get_cid_notice($cid = 0,$first = 0,$limit = 8,$mer_id = 0){
        $list = NoticeModel::cidByList($cid,$first,$limit,$mer_id,"id,uid,tag,title,image,slider_image,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url")?:[];
        foreach ($list as $item=>$value){
            $list[$item]['slider_image'] = json_decode($value['slider_image']);
        }
        return $this->successful($list);
    }

    /**
     * TODO 获取热门文章
     * @return json
     */
    public function get_notice_hot($mer_id = 0)
    {
        $list = NoticeModel::getListHot($mer_id,"id,uid,tag,title,image,slider_image,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url")?:[];
        foreach ($list as $item=>$value){
            $list[$item]['slider_image'] = json_decode($value['slider_image']);
        }

        return $this->successful($list);
    }


    /**
     * 获取文章详情
     * @param int $id
     */
    public function visit($id = 0)
    {
        $data = NoticeModel::getOne($id);
        if(!$data || !$data["status"]) return $this->fail('此文章已经不存在!');
        $data["visit"] = $data["visit"] + 1;
        $data["cname"] = NoticeCategory::getCategoryField($data['cid']);
        $data['add_time'] = date('Y-m-d H:i:s',$data['add_time']);
        $data['slider_image'] = json_decode($data['slider_image'],1);
        NoticeModel::edit(['visit'=>$data["visit"]],$id);//增加浏览次数

        $list = NoticeModel::where('is_best',1)->where('status',1)->select();
        foreach ($list as $item=>$value){
            $list[$item]['slider_image'] = json_decode($value['slider_image']);
            $list[$item]['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
        }
        $data['directoryArr'] = explode(',',$data['directory']);
        $data['list'] = $list;

        $count = UserSubion::where('uid',$this->userInfo['uid'])->where('subion_id',$data['id'])->where('paid',1)->where('type','notice')->count();
        $data['paycount'] = $count?$count:0;

        return $this->successful($data);
    }

    /**
     * 发布内容
     */
    public function edit_user_notice()
    {

        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['cid',''],
            ['title',''],
            ['keyword',''],
            ['synopsis',''],
            ['directory',''],
            ['description',''],
            ['pics',[]],
            ['status',1],
            ['is_show',1],
            ['is_price',2],
            ['add_time',time()],
            ['id',0]
        ],$request);

        $data['uid'] = $this->userInfo['uid'];
        $data['image'] = $data['pics'][0];
        $data['slider_image'] = json_encode($data['pics']);

        $data['share_title'] = $data['title'];
        $data['share_synopsis'] = $data['synopsis'];

        if($data['id'] && NoticeModel::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid'],'is_show'=>1])){
            $id = $data['id'];
            unset($data['id']);
            if(NoticeModel::edit($data,$id,'id')){
                return JsonService::successful('编辑成功!');
            }else
                return JsonService::fail('编辑失败!');
        }else{
            if($address = NoticeModel::set($data)){
                return JsonService::successful('添加成功!');
            }else
                return JsonService::fail('添加失败!');
        }
    }

    /**
     * 删除内容
     */
    public function get_del($id = 0){
        $data = NoticeModel::where(array('id'=>$id))->find();
        if($data){
            NoticeModel::where(array('id'=>$data['id']))->delete();
            return JsonService::successful('删除成功！');
        } else {
            return JsonService::fail('删除错成功!');
        }
    }

    /**
     * TODO 获取热门banner文章
     * @return json
     */
    public function get_notice_banner($mer_id = 0)
    {
        return $this->successful(NoticeModel::getListBanner($mer_id,"id,uid,tag,title,image,slider_image,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url"));
    }

    /**
     * @param string $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_user_notice($id = ''){
        $data = [];
        if($id && is_numeric($id) && NoticeModel::be(['is_show'=>1,'id'=>$id,'uid'=>$this->userInfo['uid']])){
            $data = NoticeModel::find($id);
        }
        return JsonService::successful($data);
    }

    /**
     * 简历海报二维码
     * @param int $id
     * @throws \think\Exception
     */
    public function get_promotion_code($id = 0){
        if(!$id) return JsonService::fail('参数错误ID不存在');
        $count = NoticeModel::count();
        if(!$count) return JsonService::fail('参数错误');
        $path = makePathToUrl('routine/notice/',4);
        if($path == '') return JsonService::fail('生成上传目录失败,请检查权限!');
        $codePath = $path.$id.'_'.$this->userInfo['uid'].'_notice.jpg';
        $domain = SystemConfigService::get('site_url').'/';

        if(!file_exists($codePath)){
            if(!is_dir($path)) mkdir($path,0777,true);
            $data='?id='.$id;
            if($this->userInfo['is_promoter'] || SystemConfigService::get('store_brokerage_statu')==2) $data.='&pid='.$this->uid;
            $res = RoutineCode::getPageCode('',$data,280);
            if($res) file_put_contents($codePath,$res);
            else return JsonService::fail('二维码生成失败');
        }
        return JsonService::successful($domain.$codePath);
    }
}