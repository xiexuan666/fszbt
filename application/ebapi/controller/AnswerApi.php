<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-15
 * Time: 18:11
 */

namespace app\ebapi\controller;

use app\ebapi\model\answer\Answer AS AnswerModel;
use app\ebapi\model\answer\AnswerCategory;

use app\admin\model\system\SystemGroup as GroupModel;
use app\admin\model\system\SystemGroupData as GroupDataModel;

use app\core\util\GroupDataService;
use service\JsonService;
use service\UtilService;
use think\Request;

class AnswerApi extends AuthController
{

    public static function whiteList()
    {
        return [
            'get_answer_cate',
            'get_cid_answer',
            'get_answer_hot',
            'get_answer_banner',
            'visit',
            'get_class',
            'get_user_answer',
            'edit_user_answer',
            'get_my_list'
        ];
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 一级分类
     */
    public function get_one(){
        $data = AnswerCategory::getCategory();
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
        $dataCateE = AnswerCategory::pidBySidList($data['id']);
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
        $list = AnswerModel::where('uid',$data['uid'])->where('keyword|title','LIKE',htmlspecialchars("%$keyword%"))->select();
        foreach ($list as $item=>$value){
            $cate_nam = AnswerCategory::where(array('id'=>$value['cid']))->find();
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
    public function get_answer_cate(){
        $cateInfo = AnswerCategory::getCategory();
        if($cateInfo) $cateInfo = $cateInfo->toArray();
        else $cateInfo = [];
        array_unshift($cateInfo,['id'=>0,'title'=>'热门问答']);
        return $this->successful($cateInfo);
    }

    /**
     * TODO 文章列表
     * @param int $cid
     * @param int $first
     * @param int $limit
     */
    public function get_cid_answer($cid = 0,$first = 0,$limit = 8){
        $fields = json_decode(GroupModel::where('config_name','celebrity')->value("fields"),true);
        $info = [];
        foreach ($fields as $key => $value) {
            if(isset($value["param"])){
                $value["param"] = str_replace("\r\n","\n",$value["param"]);//防止不兼容
                $params = explode("\n",$value["param"]);
                if(is_array($params) && !empty($params)){
                    foreach ($params as $index => $v) {
                        $vl = explode('=>',$v);
                        if(isset($vl[0]) && isset($vl[1])){
                            $info[$index]["value"] = $vl[0];
                            $info[$index]["label"] = $vl[1];
                        }
                    }
                }
            }
        }

        $list = GroupDataService::getData('celebrity');
        foreach ($list as $item=>$value){
            $found_key = array_search($value['type'], array_column($info, 'value'));
            $type = $info[$found_key];
            $list[$item]['type'] = $type['label'];
        }

        shuffle($list);
  
        return $this->successful($list?:[]);
    }

    /**
     * TODO 获取热门文章
     * @return json
     */
    public function get_answer_hot()
    {
        $list = AnswerModel::getListHot("id,title,image,slider_image,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url")?:[];
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
        $data = AnswerModel::getOne($id);
        if(!$data || !$data["status"]) return $this->fail('此文章已经不存在!');
        $data["visit"] = $data["visit"] + 1;
        $data["cname"] = AnswerCategory::getCategoryField($data['cid']);
        $data['add_time'] = date('Y-m-d H:i:s',$data['add_time']);
        $data['slider_image'] = json_decode($data['slider_image'],1);
        AnswerModel::edit(['visit'=>$data["visit"]],$id);//增加浏览次数
        return $this->successful($data);
    }

    /**
     * 发布内容
     */
    public function edit_user_answer()
    {

        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['cid',''],
            ['title',''],
            ['author',''],
            ['turnover',''],
            ['number',''],
            ['series',''],
            ['series_name',''],
            ['core_product',''],
            ['planning_briefly',''],
            ['detailed',''],
            ['consultant',''],
            ['description',''],
            ['pics',[]],
            ['status',1],
            ['is_show',1],
            ['is_yes',1],
            ['add_time',time()],
            ['id',0]
        ],$request);

        $data['uid'] = $this->userInfo['uid'];
        $data['image'] = $data['pics'][0];
        $data['slider_image'] = json_encode($data['pics']);

        $data['share_title'] = $data['title'];
        $data['share_synopsis'] = $data['description'];

        if($data['id'] && AnswerModel::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid'],'is_show'=>1])){
            $id = $data['id'];
            unset($data['id']);
            if(AnswerModel::edit($data,$id,'id')){
                return JsonService::successful('编辑成功!');
            }else
                return JsonService::fail('编辑失败!');
        }else{
            if($address = AnswerModel::set($data)){
                return JsonService::successful('添加成功!');
            }else
                return JsonService::fail('添加失败!');
        }
    }

    /**
     * 删除内容
     */
    public function get_del($id = 0){
        $data = AnswerModel::where(array('id'=>$id))->find();
        if($data){
            AnswerModel::where(array('id'=>$data['id']))->delete();
            return JsonService::successful('删除成功！');
        } else {
            return JsonService::fail('删除错成功!');
        }
    }

    /**
     * TODO 获取热门banner文章
     * @return json
     */
    public function get_answer_banner()
    {
        return $this->successful(AnswerModel::getListBanner("id,title,image,slider_image,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url"));
    }

    /**
     * @param string $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_user_answer($id = ''){
        $data = [];
        if($id && is_numeric($id) && AnswerModel::be(['is_show'=>1,'id'=>$id,'uid'=>$this->userInfo['uid']])){
            $data = AnswerModel::find($id);
        }
        return JsonService::successful($data);
    }
}