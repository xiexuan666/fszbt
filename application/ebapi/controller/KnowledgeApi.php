<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-15
 * Time: 18:11
 */

namespace app\ebapi\controller;
use app\ebapi\model\user\User;
use app\ebapi\model\user\UserSubion;
use app\ebapi\model\knowledge\Knowledge AS KnowledgeModel;
use app\ebapi\model\knowledge\Knowledge;
use app\ebapi\model\knowledge\KnowledgeCategory;
use app\ebapi\model\knowledge\KnowledgeRelation;

use app\core\model\routine\RoutineCode;
use app\core\util\SystemConfigService;

use service\JsonService;
use service\UtilService;
use service\CacheService;
use think\Request;

class KnowledgeApi extends AuthController
{

    public static function whiteList()
    {
        return [
            'get_knowledge_cate',
            'get_cid_knowledge',
            'get_knowledge_hot',
            'get_knowledge_banner',
            'visit',
            'get_class',
            'get_user_knowledge',
            'edit_user_knowledge'
        ];
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 一级分类
     */
    public function get_one(){
        $data = KnowledgeCategory::getKnowledgeCategory();
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
        $dataCateE = KnowledgeCategory::pidBySidList($data['id']);
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
        $list = Knowledge::where($data)->where('status',1)->where('keyword|title','LIKE',htmlspecialchars("%$keyword%"))->select();
        foreach ($list as $item=>$value){
            $cate_nam = KnowledgeCategory::where(array('id'=>$value['cid']))->find();
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
    public function get_knowledge_cate(){
        $cateInfo = KnowledgeCategory::getKnowledgeCategory();
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
    public function get_cid_knowledge($cid = 0,$first = 0,$limit = 8){
        $list = KnowledgeModel::cidByKnowledgeList($cid,$first,$limit,"id,uid,tag,title,image,slider_image,posters,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url")?:[];
        foreach ($list as $item=>$value){
            $list[$item]['slider_image'] = json_decode($value['slider_image']);
            $list[$item]['posters'] = json_decode($value['posters']);
            $user = User::where('uid',$value['uid'])->field('nickname')->find();
            $list[$item]['nickname'] = $user['nickname'];
        }
        return $this->successful($list);
    }

    /**
     * TODO 获取热门文章
     * @return json
     */
    public function get_knowledge_hot()
    {
        $list =KnowledgeModel::getKnowledgeListHot("id,uid,tag,title,image,slider_image,posters,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url")?:[];
        foreach ($list as $item=>$value){
            $list[$item]['slider_image'] = json_decode($value['slider_image']);
            $list[$item]['posters'] = json_decode($value['posters']);
        }

        return $this->successful($list);
    }


    /**
     * 获取文章详情
     * @param int $id
     */
    public function visit($id = 0)
    {
        $data = KnowledgeModel::getKnowledgeOne($id);
        if(!$data || !$data["is_show"]) return $this->fail('此文章已经不存在!');
        $data['userCollect'] = KnowledgeRelation::isProductRelation($id,$this->userInfo['uid'],'collect');

        $data["visit"] = $data["visit"] + 1;
        $data["cname"] = KnowledgeCategory::getKnowledgeCategoryField($data['cid']);
        $data['add_time'] = date('Y-m-d H:i:s',$data['add_time']);
        $data['cover'] = json_decode($data['cover'],1);
        $data['posters'] = json_decode($data['posters'],1);
        $data['slider_image'] = json_decode($data['slider_image'],1);
        KnowledgeModel::edit(['visit'=>$data["visit"]],$id);//增加浏览次数

        $list = KnowledgeModel::where('is_best',1)->select();
        foreach ($list as $item=>$value){
            $list[$item]['cover'] = json_decode($value['cover']);
            $list[$item]['posters'] = json_decode($value['posters']);
            $list[$item]['slider_image'] = json_decode($value['slider_image']);
            $list[$item]['add_time'] = date('Y-m-d H:i:s',$value['add_time']);

            $user = User::where('uid',$value['uid'])->field('nickname')->find();
            $list[$item]['nickname'] = $user['nickname'];
        }

        $data['directoryArr'] = explode(',',$data['directory']);
        $data['list'] = $list;

        $count = UserSubion::where('uid',$this->userInfo['uid'])->where('subion_id',$data['id'])->where('paid',1)->where('type','knowledge')->count();
        $data['paycount'] = $count?$count:0;

        $data['user'] = User::where('uid',$data['uid'])->find();

        return $this->successful($data);
    }

    /**
     * 发布内容
     */
    public function edit_user_knowledge()
    {

        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['cid',''],
            ['title',''],
            ['price',0],
            ['directory',''],
            ['test',''],
            ['description',''],
            ['audio_url',''],
            ['video_url',''],
            ['cover',[]],
            ['posters',[]],
            ['pics',[]],
            ['status',1],
            ['is_show',1],
            ['is_price',1],
            ['is_consent',0],
            ['add_time',time()],
            ['id',0]
        ],$request);

        $data['uid'] = $this->userInfo['uid'];

        $data['image'] = count($data['posters'])?$data['posters'][0]:'';
        $data['posters'] = json_encode($data['posters']);
        $data['cover'] = json_encode($data['cover']);
        $data['slider_image'] = json_encode($data['pics']);

        if($data['id'] && KnowledgeModel::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid'],'is_show'=>1])){
            $id = $data['id'];
            unset($data['id']);
            if(KnowledgeModel::edit($data,$id,'id')){
                return JsonService::successful('编辑成功!');
            }else
                return JsonService::fail('编辑失败!');
        }else{

            $cacheName = $this->userInfo['uid'];
            $result = CacheService::get($cacheName,null);

            if(bcadd($result['add_time'],10,0) > time()){
                return JsonService::fail('不能重复提交!');
            } else {
                if($res = KnowledgeModel::set($data)){
                    $cacheTime = 1800;
                    CacheService::set($cacheName,$res,$cacheTime);
                    return JsonService::successful(['id' => $res->id]);
                } else {
                    return JsonService::fail('添加失败!');
                }
            }

        }
    }

    /**
     * 删除内容
     */
    public function get_del($id = 0){
        $data = KnowledgeModel::where(array('id'=>$id))->find();
        if($data){
            KnowledgeModel::where(array('id'=>$data['id']))->delete();
            return JsonService::successful('删除成功！');
        } else {
            return JsonService::fail('删除错成功!');
        }
    }

    /**
     * TODO 获取热门banner文章
     * @return json
     */
    public function get_knowledge_banner()
    {
        return $this->successful(KnowledgeModel::getKnowledgeListBanner("id,tag,title,image,slider_image,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url"));
    }

    /**
     * @param string $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_user_knowledge($id = ''){
        $data = [];
        if($id && is_numeric($id) && KnowledgeModel::be(['is_show'=>1,'id'=>$id,'uid'=>$this->userInfo['uid']])){
            $data = KnowledgeModel::find($id);
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
        $count = KnowledgeModel::count();
        if(!$count) return JsonService::fail('参数错误');
        $path = makePathToUrl('routine/knowledge/',4);
        if($path == '') return JsonService::fail('生成上传目录失败,请检查权限!');
        $codePath = $path.$id.'_'.$this->userInfo['uid'].'_knowledge.jpg';
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

    /**
     * 取消收藏
     * @param $productId
     * @param string $category
     * @return \think\response\Json
     */
    public function uncollect_product($productId,$category = 'product'){
        if(!$productId || !is_numeric($productId)) return JsonService::fail('参数错误');
        $res = KnowledgeRelation::unProductRelation($productId,$this->userInfo['uid'],'collect',$category);
        if(!$res) return JsonService::fail(KnowledgeRelation::getErrorInfo());
        else return JsonService::successful();
    }

    /**
     * 添加收藏
     * @param $productId
     * @param string $category
     * @return \think\response\Json
     */
    public function collect_product($productId,$category = 'product'){
        if(!$productId || !is_numeric($productId)) return JsonService::fail('参数错误');
        $res = KnowledgeRelation::productRelation($productId,$this->userInfo['uid'],'collect',$category);
        if(!$res) return JsonService::fail(KnowledgeRelation::getErrorInfo());
        else return JsonService::successful();
    }

    /**
     * 获取收藏产品
     * @param int $first
     * @param int $limit
     * @return \think\response\Json
     */
    public function get_user_collect_product($page = 0,$limit = 8)
    {
        return JsonService::successful(KnowledgeRelation::getUserCollectProduct($this->uid,$page,$limit));
    }

    /**
     * 获取收藏产品删除
     * @param int $first
     * @param int $limit
     * @return \think\response\Json
     */
    public function get_user_collect_product_del($pid=0)
    {
        if($pid){
            $list = KnowledgeRelation::where('uid',$this->userInfo['uid'])->where('product_id',$pid)->delete();
            return JsonService::successful($list);
        }else
            return JsonService::fail('缺少参数');
    }
}