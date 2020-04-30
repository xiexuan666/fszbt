<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-11
 * Time: 22:13
 */

namespace app\ebapi\controller;

use app\core\model\routine\RoutineCode;
use app\ebapi\model\dealers\Dealers;
use app\ebapi\model\dealers\DealersCategory;
use app\core\util\GroupDataService;
use service\JsonService;
use app\core\util\SystemConfigService;
use service\UtilService;
use think\Request;

class DealersApi extends AuthController
{
    public static function whiteList()
    {
        return [
            'goods_search',
            'get_routine_hot_search',
            'get_pid_cate',
            'get_product_category',
            'get_dealers_list',
            'get_dealers_top',
            'get_dealers_id',
            'details',
            'get_my_product_list',
            'get_dealers_class_two',
            'get_dealers_details',
            'edit_user_dealers_goods',
            'get_dealers_product_list',
        ];
    }

    public function get_dealers_details($id=0){
        if(!$id || !($data = DealersProduct::where('id',$id)->find())) return JsonService::fail('商品不存在或已下架');
        $data["browse"] = $data["browse"] + 1;
        DealersProduct::edit(['browse'=>$data["browse"]],$id);
        $cate_nam = DealersClass::where(array('id'=>$data['cate_id']))->find();
        $data['cate_name'] = $cate_nam['cate_name'];
        return JsonService::successful($data);
    }

    /**
     * 获取公司信息
     * 2019-07-27
     */
    public function get_user_dealers(){
        $data = Dealers::where(array('uid'=>$this->userInfo['uid']))->find();
        if($data){
            $cate = DealersCategory::where('id',$data['cid'])->find();
            $data['cname'] = $cate['title'];
            $data['slider_image'] = json_decode($data['slider_image'],1);
            return JsonService::successful($data);
        } else {
            return JsonService::fail('没有数据!');
        }
    }

    /**
     * 编辑企业信息
     * 2019-07-27
     */
    public function edit_user_dealers(){
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['comment',''],
            ['logo',[]],
            ['series',[]],
            ['about',[]],
            ['contact',[]],
            ['title',''],
            ['phone',''],
            ['synopsis',''],
            ['author',''],
            ['welfare',''],
            ['description',''],
            ['is_default',0],
            ['cid',0],
            ['is_show',1],
            ['status',1],
            ['is_consent',0],
            ['id',0]
        ],$request);

        $data['logo'] = $data['logo'][0];
        $data['image'] = $data['series'][0];
        $data['slider_image'] = json_encode($data['series']);
        $data['about'] = $data['about'][0];
        $data['contact'] = $data['contact'][0];

        $data['uid'] = $this->userInfo['uid'];
        unset($data['series']);
        $data['add_time'] = time();
        if($data['id'] && Dealers::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid']])){
            $id = $data['id'];
            unset($data['id']);
            if(Dealers::edit($data,$id,'id')){
                return JsonService::successful('编辑成功!');
            } else {
                return JsonService::fail('编辑失败!');
            }
        } else {
            if($address = Dealers::set($data)){
                return JsonService::successful('添加成功!');
            } else {
                return JsonService::fail('添加失败!');
            }
        }
    }

    public function get_pid_cate(){
        $data = DealersCategory::getCategory();
        return JsonService::successful($data);
    }

    public function get_product_category()
    {
        $list = DealersCategory::getCategory();
        foreach ($list as $item=>$value){
            $list[$item]['child'] = Dealers::where('cid',$value['id'])->where('is_show',1)->order('sort desc')->select();
        }
        return JsonService::successful($list);
    }

    public function details($id=0){
        if(!$id || !($data = Dealers::getValidProduct($id))) return JsonService::fail('不存在或已下架');
        $data['poster_image'] = json_decode($data['poster_image']);
        $data['slider_image'] = json_decode($data['slider_image']);
        return JsonService::successful($data);
    }

    public function get_dealers_top()
    {
        $list = Dealers::where('is_top',1)->where('status',1)->select();
        return JsonService::successful($list);
    }

    /**
     * 产品海报二维码
     * @param int $id
     */
    public function product_promotion_code($id = 0){
        if(!$id) return JsonService::fail('参数错误ID不存在');
        $count = Dealers::validWhere()->count();
        if(!$count) return JsonService::fail('参数错误');
        $path = makePathToUrl('routine/product/',4);
        if($path == '') return JsonService::fail('生成上传目录失败,请检查权限!');
        $codePath = $path.$id.'_'.$this->userInfo['uid'].'_product.jpg';
        $domain = SystemConfigService::get('site_url').'/';
        if(!file_exists($codePath)){
            if(!is_dir($path)) mkdir($path,0777,true);
            $data='id='.$id;
            if($this->userInfo['is_promoter'] || SystemConfigService::get('store_brokerage_statu')==2) $data.='&pid='.$this->uid;
            $res = RoutineCode::getPageCode('pages/dealers/details/index',$data,280);
            if($res) file_put_contents($codePath,$res);
            else return JsonService::fail('二维码生成失败');
        }
        return JsonService::successful($domain.$codePath);
    }



}