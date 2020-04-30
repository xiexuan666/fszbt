<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-14
 * Time: 11:56
 */

namespace app\ebapi\controller;

use app\core\model\routine\RoutineCode;
use app\admin\model\talent\TalentCategory;
use app\ebapi\model\store\StoreOrderCartInfo;
use app\ebapi\model\talent\Talent;
use app\ebapi\model\store\StoreProductAttr;
use app\ebapi\model\store\StoreProductRelation;
use app\ebapi\model\store\StoreProductReply;
use app\core\util\GroupDataService;
use service\JsonService;
use app\core\util\SystemConfigService;
use service\UtilService;
use think\Request;
use think\Cache;


/**
 * 人才简历接口 控制器
 * Class TalentApi
 * @package app\ebapi\controller
 */
class TalentApi extends AuthController
{
    public static function whiteList()
    {
        return [
            'get_talent_list',
            'details',
            'talent_promotion_code',
            'get_user_talent',
            'edit_user_talent',
            'get_class'
        ];
    }

    /**
     * 获取简历列表
     */
    public function get_talent_list()
    {
        $data = UtilService::getMore([
            ['sid',0],
            ['cid',0],
            ['keyword',''],
            ['fictiOrder',''],
            ['visitOrder',''],
            ['news',0],
            ['page',0],
            ['limit',0]
        ],$this->request);
        return JsonService::successful(Talent::getList($data,$this->uid));
    }

    /**
     * 简历详情页
     * @param int $id
     */
    public function details($id=0){
        if(!$id || !($storeInfo = Talent::getDetails($id))) return JsonService::fail('商品不存在或已下架');
        $storeInfo['tag'] = explode(',',$storeInfo['tag']);
        $storeInfo['welfare'] = explode(',',$storeInfo['welfare']);
        return JsonService::successful($storeInfo);
    }

    /**
     * 简历海报二维码
     * @param int $id
     * @throws \think\Exception
     */
    public function talent_promotion_code($id = 0){
        if(!$id) return JsonService::fail('参数错误ID不存在');
        $count = Talent::validWhere()->count();
        if(!$count) return JsonService::fail('参数错误');
        $path = makePathToUrl('routine/talent/',4);
        if($path == '') return JsonService::fail('生成上传目录失败,请检查权限!');
        $codePath = $path.$id.'_'.$this->userInfo['uid'].'_talent.jpg';
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

    public function get_class(){
        $data = TalentCategory::getTierList();

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
    public function get_user_talent($id = ''){
        $data = [];
        if($id && is_numeric($id) && Talent::be(['is_show'=>1,'id'=>$id,'uid'=>$this->userInfo['uid']])){
            $data = Talent::find($id);
        }
        return JsonService::successful($data);
    }

    /**
     * 修改收货地址
     */
    public function edit_user_talent()
    {
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $addressInfo = UtilService::postMore([
            ['cid',0],
            ['title',''],
            ['author',''],
            ['phone',''],
            ['address',''],
            ['region_arr',[]],
            ['is_default',false],
            ['experience',''],
            ['education',''],
            ['description',''],
            ['salary',''],
            ['welfare',''],
            ['tag',''],
            ['status',1],
            ['is_show',1],
            ['add_time',time()],
            ['id',0]
        ],$request);

        $addressInfo['province'] = $addressInfo['region_arr']['province'];
        $addressInfo['city'] = $addressInfo['region_arr']['city'];
        $addressInfo['district'] = $addressInfo['region_arr']['district'];
        $addressInfo['region'] = $addressInfo['region_arr']['province'].','.$addressInfo['region_arr']['city'].','.$addressInfo['region_arr']['district'];

        $addressInfo['is_default'] = $addressInfo['is_default'] == true ? 1 : 0;
        $addressInfo['uid'] = $this->userInfo['uid'];
        $addressInfo['image'] = $this->userInfo['avatar'];
        unset($addressInfo['region_arr']);

        if($addressInfo['id'] && Talent::be(['id'=>$addressInfo['id'],'uid'=>$this->userInfo['uid'],'is_show'=>1])){
            $id = $addressInfo['id'];
            unset($addressInfo['id']);
            if(Talent::edit($addressInfo,$id,'id')){
                if($addressInfo['is_default'])
                    Talent::setDefaultTalten($id,$this->userInfo['uid']);
                return JsonService::successful();
            }else
                return JsonService::fail('编辑简历失败!');
        }else{
            if($address = Talent::set($addressInfo)){
                if($addressInfo['is_default'])
                    Talent::setDefaultTalten($address->id,$this->userInfo['uid']);
                return JsonService::successful(['id'=>$address->id]);
            }else
                return JsonService::fail('添加简历失败!');
        }
    }
}