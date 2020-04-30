<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-15
 * Time: 18:11
 */

namespace app\ebapi\controller;

use app\ebapi\model\supply\Supply AS SupplyModel;
use app\ebapi\model\supply\SupplyCategory;

use app\core\model\routine\RoutineCode;
use app\core\util\SystemConfigService;

use app\core\model\user\UserLevel;
use app\core\model\system\SystemUserTask;
use app\core\model\user\UserTaskFinish;

use app\ebapi\model\user\User;
use app\ebapi\model\user\UserSubion;
use service\JsonService;
use service\UtilService;
use service\CacheService;
use think\Request;

class SupplyApi extends AuthController
{

    public static function whiteList()
    {
        return [
            'get_supply_cate',
            'get_cid_supply',
            'get_supply_hot',
            'get_supply_banner',
            'visit',
            'get_class',
            'get_user_supply',
            'edit_user_supply',
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
        $data = SupplyCategory::getCategory();
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
        $dataCateE = SupplyCategory::pidBySidList($data['id']);
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
            ['keyword',''],
            ['page',0],
            ['limit',0]
        ],$this->request);

        $keyword = $data['keyword'];
        $page = $data['page'];
        $limit = $data['limit'];
        unset($data['keyword']);
        unset($data['page']);
        unset($data['limit']);
        $list = SupplyModel::where($data)->where('keyword|title','LIKE',htmlspecialchars("%$keyword%"))->page((int)$page,(int)$limit)->select();
        return JsonService::successful($list);
    }


    /**
     * TODO 获取文章分类
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_supply_cate(){
        $cateInfo = SupplyCategory::getCategory();
        if($cateInfo) $cateInfo = $cateInfo->toArray();
        else $cateInfo = [];
        array_unshift($cateInfo,['id'=>0,'title'=>'最新']);
        return $this->successful($cateInfo);
    }

    /**
     * TODO 文章列表
     * @param int $cid
     * @param int $first
     * @param int $limit
     */
    public function get_cid_supply($cid = 0,$first = 0,$limit = 8,$mer_id = 0){

        $list = SupplyModel::cidByList($cid,$first,$limit,$mer_id,"id,uid,cid,type,title,image,slider_image,visit,from_unixtime(add_time,'%Y年%m月%d日 %H:%i') as add_time,synopsis,url")?:[];
        foreach ($list as $item=>$value){
            $list[$item]['slider_image'] = json_decode($value['slider_image']);
            $title = SupplyCategory::where('id',$value['cid'])->field('title')->find();
            $list[$item]['cname'] = $title['title'];
        }
        return $this->successful($list);
    }

    /**
     * TODO 获取热门文章
     * @return json
     */
    public function get_supply_hot($mer_id = 0)
    {
        $list = SupplyModel::getListHot($mer_id,"id,uid,cid,type,title,image,slider_image,visit,from_unixtime(add_time,'%Y年%m月%d日 %H:%i') as add_time,synopsis,url")?:[];
        foreach ($list as $item=>$value){
            $list[$item]['slider_image'] = json_decode($value['slider_image']);
            $title = SupplyCategory::where('id',$value['cid'])->field('title')->find();
            $list[$item]['cname'] = $title['title'];
        }

        return $this->successful($list);
    }


    /**
     * 获取文章详情
     * @param int $id
     */
    public function visit($id = 0)
    {
        $data = SupplyModel::getOne($id);
        if(!$data || !$data["status"]) return $this->fail('此文章已经不存在!');
        $data["visit"] = $data["visit"] + 1;
        $data["cname"] = SupplyCategory::getCategoryField($data['cid']);
        $data['add_time'] = date('Y-m-d H:i:s',$data['add_time']);
        $data['slider_image'] = json_decode($data['slider_image'],1);
        $data['merchants_pumping'] = SystemConfigService::get('merchants_pumping');
        SupplyModel::edit(['visit'=>$data["visit"]],$id);//增加浏览次数

        $list = SupplyModel::where('is_best',1)->select();
        foreach ($list as $item=>$value){
            $list[$item]['slider_image'] = json_decode($value['slider_image']);
            $list[$item]['add_time'] = date('Y年m月d日 H:i:s',$value['add_time']);
        }
        $data['directoryArr'] = explode(',',$data['directory']);
        $data['list'] = $list;

        $count = UserSubion::where('uid',$this->userInfo['uid'])->where('subion_id',$data['id'])->where('paid',1)->where('type','supply')->count();
        $data['paycount'] = $count?$count:0;

        $data['userinfo'] = User::getUserInfo($data['uid']);

        return $this->successful($data);
    }

    /**
     * 发布内容
     */
    public function edit_user_supply()
    {
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['cid',0],
            ['title',''],
            ['author',''],
            ['phone',''],
            ['description',''],
            ['district',''],
            ['province',''],
            ['city',''],
            ['pics',[]],
            ['status',1],
            ['is_show',1],
            ['is_consent',0],
            ['num',0],
            ['add_time',time()],
            ['id',0]
        ],$request);

        $data['uid'] = $this->userInfo['uid'];
        $data['mer_id'] = $this->userInfo['uid'];
        $data['type'] = $this->userInfo['level'];
        if(count($data['pics']) > 0) $data['image'] = $data['pics'][0];
        $data['slider_image'] = json_encode($data['pics']);

        unset($data['pics']);

        if($data['id'] && SupplyModel::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid'],'is_show'=>1])){
            $id = $data['id'];
            unset($data['id']);
            if(SupplyModel::edit($data,$id,'id')){
                return JsonService::successful(array('id'=>$id,'msg'=>'编辑成功！'));
            }else
                return JsonService::fail('编辑失败!');
        }else{
            $cacheName = $this->userInfo['uid'];
            $result = CacheService::get($cacheName,null);

            if(bcadd($result['add_time'],10,0) > time()){
                return JsonService::fail('不能重复提交!');
            } else {
                if($res = SupplyModel::set($data)){
                    if($data['num'] > 0){
                        $this->NumberAddSupply();
                    }
                    $cacheTime = 1800;
                    CacheService::set($cacheName,$res,$cacheTime);
                    return JsonService::successful(array('id'=>$res->id,'msg'=>'发布成功！'));
                } else {
                    return JsonService::fail('添加失败!');
                }
            }
        }
    }

    /**
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 检查-招商-赠送剩余额度
     */
    public function NumberAddSupply(){
        $level_id = UserLevel::getUserLevel($this->userInfo['uid']);
        $level = UserLevel::getUserLevelInfo($level_id);
        $data = SystemUserTask::where('level_id',$level['level_id'])->where('task_type','NumberAddSupply')->find();
        $count = UserTaskFinish::where('task_id',$data['id'])->where('uid',$this->userInfo['uid'])->where('status',1)->count();
        if($data['number'] >= $count){
            $set['uid'] = $this->userInfo['uid'];
            $set['task_id'] = $data['id'];
            $set['status'] = 1;
            $set['add_time'] = time();
            UserTaskFinish::set($set);
        }
    }

    /**
     * 删除内容
     */
    public function get_del($id = 0){
        $data = SupplyModel::where(array('id'=>$id))->find();
        if($data){
            SupplyModel::where(array('id'=>$data['id']))->delete();
            return JsonService::successful('删除成功！');
        } else {
            return JsonService::fail('删除错成功!');
        }
    }

    /**
     * TODO 获取热门banner文章
     * @return json
     */
    public function get_supply_banner($mer_id = 0)
    {
        return $this->successful(SupplyModel::getListBanner($mer_id,"id,title,type,image,slider_image,visit,from_unixtime(add_time,'%Y年%m月%d日 %H:%i') as add_time,synopsis,url"));
    }

    /**
     * @param string $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_user_supply($id = ''){
        $data = [];
        if($id && is_numeric($id) && SupplyModel::be(['is_show'=>1,'id'=>$id,'uid'=>$this->userInfo['uid']])){
            $data = SupplyModel::find($id);
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
        $count = SupplyModel::count();
        if(!$count) return JsonService::fail('参数错误');
        $path = makePathToUrl('routine/supply/',4);
        if($path == '') return JsonService::fail('生成上传目录失败,请检查权限!');
        $codePath = $path.$id.'_'.$this->userInfo['uid'].'_supply.jpg';
        $domain = SystemConfigService::get('site_url').'/';

        if(!file_exists($codePath)){
            if(!is_dir($path)) mkdir($path,0777,true);
            $data='?id='.$id;
            if($this->userInfo['is_promoter'] || SystemConfigService::get('store_brokerage_statu')==2) $data.='&pid='.$this->uid;
            $res = RoutineCode::getPageCode('pages/supply/details/index',$data,280);
            if($res) file_put_contents($codePath,$res);
            else return JsonService::fail('二维码生成失败');
        }
        return JsonService::successful($domain.$codePath);
    }
}