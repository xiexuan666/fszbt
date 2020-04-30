<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-15
 * Time: 18:11
 */

namespace app\ebapi\controller;

use app\ebapi\model\consultant\Consultant AS ConsultantModel;
use app\ebapi\model\consultant\ConsultantCategory;
use service\JsonService;
use service\UtilService;
use think\Request;

class ConsultantApi extends AuthController
{

    public static function whiteList()
    {
        return [
            'get_consultant_cate',
            'get_cid_consultant',
            'get_consultant_hot',
            'get_consultant_banner',
            'visit',
            'get_class',
            'get_user_consultant',
            'edit_user_consultant'
        ];
    }

    /**
     * TODO 获取文章分类
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_consultant_cate(){
        $cateInfo = ConsultantCategory::getKnowledgeCategory();
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
    public function get_cid_consultant($cid = 0,$first = 0,$limit = 8){
        $list = ConsultantModel::cidByKnowledgeList($cid,$first,$limit,"id,title,image,slider_image,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url")?:[];
        foreach ($list as $item=>$value){
            $list[$item]['slider_image'] = json_decode($value['slider_image']);
        }
        return $this->successful($list);
    }

    /**
     * TODO 获取热门文章
     * @return json
     */
    public function get_consultant_hot()
    {
        $list = ConsultantModel::getKnowledgeListHot("id,title,image,slider_image,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url")?:[];
        foreach ($list as $item=>$value){
            $list[$item]['slider_image'] = json_decode($value['slider_image']);
        }

        return $this->successful($list);
    }

    /**
     * TODO 获取热门banner文章
     * @return json
     */
    public function get_consultant_banner()
    {
        return $this->successful(ConsultantModel::getKnowledgeListBanner("id,title,image,slider_image,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url"));
    }

    /**
     * TODO 获取文章详情
     * @param int $id
     * @return json
     */
    public function visit($id = 0)
    {
        $content = ConsultantModel::getKnowledgeOne($id);
        if(!$content || !$content["status"]) return $this->fail('此文章已经不存在!');
        $content["visit"] = $content["visit"] + 1;
        $content["cart_name"] = ConsultantCategory::getKnowledgeCategoryField($content['cid']);
        $content['add_time'] = date('Y-m-d H:i:s',$content['add_time']);
        ConsultantModel::edit(['visit'=>$content["visit"]],$id);//增加浏览次数
        return $this->successful($content);
    }

    public function get_class(){
        $data = ConsultantCategory::getTierList();

        $array = [];
        foreach ($data as $key=>$val){
            $array[] = $val['title'];
        }

        $data_arr['array'] = $array;
        $data_arr['list'] = $data;
        return JsonService::successful($data_arr);
    }

    /**
     * 获取一条用户简历
     * @param string $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_user_consultant($id = ''){
        $data = [];
        if($id && is_numeric($id) && ConsultantModel::be(['is_show'=>1,'id'=>$id,'uid'=>$this->userInfo['uid']])){
            $data = ConsultantModel::find($id);
        }
        return JsonService::successful($data);
    }

    public function edit_user_consultant()
    {

        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $addressInfo = UtilService::postMore([
            ['cid',''],
            ['title',''],
            ['phone',''],
            ['author',''],
            ['description',''],
            ['status',1],
            ['is_show',1],
            ['add_time',time()],
            ['id',0]
        ],$request);
        
        $addressInfo['uid'] = $this->userInfo['uid'];
        $addressInfo['image'] = $this->userInfo['avatar'];
        $addressInfo['slider_image'] = json_encode(array($this->userInfo['avatar']),true);

        if($addressInfo['id'] && ConsultantModel::be(['id'=>$addressInfo['id'],'uid'=>$this->userInfo['uid'],'is_show'=>1])){
            $id = $addressInfo['id'];
            unset($addressInfo['id']);
            if(ConsultantModel::edit($addressInfo,$id,'id')){
                return JsonService::successful();
            }else
                return JsonService::fail('编辑招聘失败!');
        }else{
            if($address = ConsultantModel::set($addressInfo)){
                return JsonService::successful(['id'=>$address->id]);
            }else
                return JsonService::fail('添加招聘失败!');
        }
    }
}