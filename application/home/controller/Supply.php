<?php
/**
 *
 * @author: 招宝通
 */

namespace app\home\controller;

use app\ebapi\model\supply\Supply AS SupplyModel;
use app\ebapi\model\supply\SupplyCategory;

use app\admin\model\system\SystemConfig;
use app\home\model\store\StoreBargain;
use app\home\model\store\StoreBargainUser;
use app\home\model\store\StoreBargainUserHelp;
use app\home\model\store\StoreCategory;
use app\home\model\store\StoreCoupon;
use app\home\model\store\StoreSeckill;
use app\home\model\store\StoreCouponIssue;
use app\home\model\store\StoreCouponIssueUser;
use app\home\model\store\StoreCouponUser;
use app\home\model\store\StorePink;
use app\home\model\store\StoreProductReply;
use app\home\model\store\StoreCart;
use app\home\model\store\StoreOrder;
use app\home\model\store\StoreProduct;
use app\home\model\store\StoreProductAttr;
use app\home\model\store\StoreProductRelation;
use app\home\model\user\User;
use app\home\model\user\WechatUser;
use app\home\model\store\StoreCombination;
use app\core\util\GroupDataService;
use app\core\util\SystemConfigService;
use service\UtilService;
use think\Cache;
use think\Request;
use think\Url;
use service\JsonService;
use service\CacheService;

/**
 * Class Supply
 * @package app\home\controller
 * 招商控制器
 */
class Supply extends AuthController
{

    /**
     * @return mixed
     * 招商管理
     */
    public function admin(){
        return $this->fetch();
    }

    /**
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 请求数据
     */
    public function get_list(){
        $where = UtilService::getMore([
            ['uid',$this->userInfo['uid']],
            ['keyword',''],
            ['page',0],
            ['limit',0]
        ],$this->request);

        $keyword = $where['keyword'];
        $page = $where['page'];
        $limit = $where['limit'];
        unset($where['keyword']);
        unset($where['page']);
        unset($where['limit']);

        $data = SupplyModel::where($where)->where('title','LIKE',htmlspecialchars("%$keyword%"))->order('id desc')->page((int)$page,(int)$limit)->select();

        foreach ($data as $item=>$value){
            $info = SupplyCategory::where(array('id'=>$value['cid']))->field('id,title')->find()->toArray();
            $data[$item]['ctitle'] = $info['title'];
            $data[$item]['add_time'] = date('Y-m-d H:i',$value['add_time']);
        }

        $code = 0;
        $msg = '请求成功';
        $count = 100;
        echo json_encode(compact('code','msg','count','data'));
    }

    /**
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 添加 编辑
     */
    public function create(){
        $list = SupplyCategory::getCategory()->toArray();
        $where = UtilService::getMore([
            ['uid',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        if($where['id']){
            $data = SupplyModel::where($where)->find()->toArray();
            if($data) {
                $data['slider_image_data'] = implode(",",json_decode($data['slider_image'],true));
                $data['slider_image'] = json_decode($data['slider_image'],true);
            }
        } else {
            $data = array(
                'cid'=>0,
                'title'=>'',
                'author'=>'',
                'phone'=>'',
                'district'=>'',
                'province'=>'',
                'city'=>'',
                'description'=>'',
                'slider_image'=>[],
                'slider_image_data'=>'',
                'is_consent'=>0,
                'id'=>0
            );
        }

        $this->assign(compact('data','list'));
        return $this->fetch();
    }

    /**
     * 添加 编辑 提交保存
     */
    public function save()
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
            ['slider_image',''],
            ['is_show',1],
            ['is_consent',0],
            ['add_time',time()],
            ['id',0]
        ],$request);

        $data['uid'] = $this->userInfo['uid'];
        $data['type'] = $this->userInfo['level'];
        if($data['slider_image']) $slider_image = array_filter(explode(',',$data['slider_image']));

        if(count($slider_image)) {
            if($data['id']){
                $data['image'] = $slider_image[0];
            } else {
                $data['image'] = $slider_image[1];
            }
            $data['slider_image'] = json_encode($slider_image);
        } else {
            return JsonService::fail('请上传招商海报!');
        }

        if($data['is_consent']) {
            $data['is_consent'] = 1;
        } else {
            return JsonService::fail('请阅读并同意发布信息协议!');
        }

        if($data['id'] && SupplyModel::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid']])){
            $id = $data['id'];
            unset($data['id']);
            if(SupplyModel::edit($data,$id,'id')){
                return JsonService::successful('编辑成功！');
            }else
                return JsonService::fail('编辑失败!');
        }else{
            $cacheName = $this->userInfo['uid'];
            $result = CacheService::get($cacheName,null);

            if(bcadd($result['add_time'],10,0) > time()){
                return JsonService::fail('不能重复提交!');
            } else {
                if($res = SupplyModel::set($data)){
                    $cacheTime = 1800;
                    CacheService::set($cacheName,$res,$cacheTime);
                    return JsonService::successful('发布成功！');
                } else {
                    return JsonService::fail('添加失败!');
                }
            }
        }
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 删除
     */
    public function del(){
        $where = UtilService::getMore([
            ['uid',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        $data = SupplyModel::where($where)->find();
        if(!$data) return JsonService::fail('删除的数据丢失了!');
        $delData = SupplyModel::where($where)->delete();
        if(!$delData) return JsonService::fail('网络忙，稍后再试!');
        return JsonService::successful('数据删除成功!');
    }

}