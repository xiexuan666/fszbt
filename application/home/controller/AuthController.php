<?php
/**
 *
 * @author: 招宝通
 */

namespace app\home\controller;

use app\home\model\user\User;

use app\ebapi\model\store\StoreOrder;
use app\ebapi\model\store\StoreCouponUser;
use app\ebapi\model\store\StoreProductRelation;
use app\ebapi\model\user\UserNotice;
use app\ebapi\model\user\UserExtract;
use app\ebapi\model\user\UserSubion;

use app\core\util\GroupDataService;
use app\core\util\SystemConfigService;
use app\core\model\user\UserLevel;
use app\core\model\user\UserBill;

use service\UtilService;
use think\Cookie;
use think\Url;

class AuthController extends WapBasic
{
    /**
     * 用户ID
     * @var int
     */
    protected $uid;

    /**
     * 用户信息
     * @var
     */
    protected $userInfo;

    protected function _initialize()
    {
        parent::_initialize();
        try{
            $uid = User::getActiveUid();
        }catch (\Exception $e){
            Cookie::set('is_login',0);
            $url=$this->request->url(true);
            return $this->redirect(Url::build('Login/index',['ref'=>base64_encode(htmlspecialchars($url))]));
        }
        $this->userInfo = User::getUserInfo($uid);

        $this->userInfo['couponCount'] = StoreCouponUser::getUserValidCouponCount($this->userInfo['uid']);
        $this->userInfo['like'] = StoreProductRelation::getUserIdCollect($this->userInfo['uid']);;
        $this->userInfo['orderStatusNum'] = StoreOrder::getOrderStatusNum($this->userInfo['uid']);
        $this->userInfo['orderNum'] = StoreOrder::where('uid',$this->userInfo['uid'])->count();//订单
        $this->userInfo['subionNum'] = UserSubion::where('uid',$this->userInfo['uid'])->count();//订阅
        $this->userInfo['relationNum'] = StoreProductRelation::where('uid',$this->userInfo['uid'])->count();//收藏
        $this->userInfo['notice'] = UserNotice::getNotice($this->userInfo['uid']);
        $this->userInfo['brokerage'] = UserBill::getBrokerage($this->userInfo['uid']);//获取总佣金
        $this->userInfo['recharge'] = UserBill::getRecharge($this->userInfo['uid']);//累计充值

        $this->userInfo['orderStatusSum'] = bcadd(StoreOrder::getOrderStatusSum($this->userInfo['uid']),UserSubion::getOrderStatusSum($this->userInfo['uid']),2);//累计消费

        $this->userInfo['extractTotalPrice'] = UserExtract::userExtractTotalPrice($this->userInfo['uid']);//累计提现
        if($this->userInfo['brokerage'] > $this->userInfo['extractTotalPrice']) {
            $this->userInfo['brokerage']=bcsub($this->userInfo['brokerage'],$this->userInfo['extractTotalPrice'],2);//减去已提现金额
            $extract_price=UserExtract::userExtractTotalPrice($this->userInfo['uid'],0);
            $this->userInfo['brokerage']=$extract_price < $this->userInfo['brokerage'] ? bcsub($this->userInfo['brokerage'],$extract_price,2) : 0;//减去审核中的提现金额
        }else{
            $this->userInfo['brokerage']=0;
        }
        $this->userInfo['extractPrice'] = (float)bcsub($this->userInfo['brokerage'],$this->userInfo['extractTotalPrice'],2) > 0 ? : 0;//可提现
        $this->userInfo['statu'] = (int)SystemConfigService::get('store_brokerage_statu');

        $vipId = UserLevel::getUserLevel($uid);
        $this->userInfo['vip'] = $vipId !==false ? true : false;
        if($this->userInfo['vip']){
            $this->userInfo['vip_id']=$vipId;
            $this->userInfo['vip_icon']=UserLevel::getUserLevelInfo($vipId,'icon');
            $this->userInfo['vip_name']=UserLevel::getUserLevelInfo($vipId,'name');
        }

        if(!$this->userInfo || !isset($this->userInfo['uid'])) return $this->failed('读取用户信息失败!');
        if(!$this->userInfo['status']) return $this->failed('已被禁止登陆!');
        $this->uid = $this->userInfo['uid'];
        //获取当前控制器名称
        $controller = request()->controller();
        $this->assign('controller',$controller);
        //获取当前方法名
        $action = request()->action();
        $this->assign('action',$action);

        //TODO 个人中心菜单
        $routine_my_menus = GroupDataService::getData('routine_my_menus');

        foreach ($routine_my_menus as $item => $value){
            if($value['is_pc'] == 2) {
                unset($routine_my_menus[$item]);
            } else {
                if(in_array($this->userInfo['level'],$value['level'])){
                    $routine_my_menus[$item]['isLevel'] = true;
                } else {
                    $routine_my_menus[$item]['isLevel'] = false;
                }
            }
        }
        $get_key = UtilService::getMore([
            ['get_key',1]
        ],$this->request);
        $this->assign('get_key',$get_key['get_key']);
        $this->assign('my_menus',$routine_my_menus);
        $this->assign('siteData',SystemConfigService::getAll());
        $this->assign('userInfo',$this->userInfo);
    }

}