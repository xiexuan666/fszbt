<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-09-05
 * Time: 13:29
 */

namespace app\ebapi\model\user;
use app\core\model\system\SystemUserLevel;
use app\core\model\user\UserBill;
use app\core\model\user\UserLevel;
use basic\ModelBasic;
use app\core\util\MiniProgramService;
use app\core\util\SystemConfigService;
use traits\ModelTrait;

class UserReg extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['add_time'];

    protected function setAddTimeAttr()
    {
        return time();
    }

    public static function addRecharge($uid,$price,$vipid,$recharge_type = 'weixin',$paid = 0)
    {
        $order_id = self::getNewOrderId($uid);
        return self::set(compact('order_id','uid','price','vipid','recharge_type','paid'));
    }

    public static function getNewOrderId($uid = 0)
    {
        if(!$uid) return false;
        $count = (int) self::where('add_time',['>=',strtotime(date("Y-m-d"))],['<',strtotime(date("Y-m-d",strtotime('+1 day')))])->count();
        return 'wx1'.date('YmdHis',time()).(10000+$count+$uid);
    }

    public static function jsPay($orderInfo)
    {
        return MiniProgramService::jsPay(WechatUser::uidToOpenid($orderInfo['uid']),$orderInfo['order_id'],$orderInfo['price'],'user_reg','用户注册');
    }

    /**
     * //TODO用户充值成功后
     * @param $orderId
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function regSuccess($orderId)
    {
        $order = self::where('order_id',$orderId)->where('paid',0)->find();
        if(!$order) return false;
        $user = User::getUserInfo($order['uid']);
        self::beginTrans();
        $res1 = self::where('order_id',$order['order_id'])->update(['paid'=>1,'pay_time'=>time()]);
        $res2 = UserBill::income('用户注册',$order['uid'],'now_money','reg',$order['price'],$order['id'],$user['now_money'],'成功注册'.floatval($order['price']).'元');

        //更改用户特权
        $vipinfo = SystemUserLevel::get($order['vipid']);
        $add_valid_time = (int)$vipinfo['valid_date']*86400;
        $uservipinfo = UserLevel::where(['uid'=>$order['uid'],'level_id'=>$order['vipid']])->find();
        //检查是否购买过
        if($uservipinfo){
            $stay=0;
            //剩余时间
            if(time() < $uservipinfo['valid_time']) $stay = $uservipinfo['valid_time']-time();
            //如果购买过当前等级的会员过期了.从当前时间开始计算
            //过期时效: 剩余时间+当前会员等级时间+当前time
            $add_valid_time = $stay+$add_valid_time+time();
            $datas['is_forever']=$vipinfo['is_forever'];
            $datas['valid_time']=$add_valid_time;
            $res3 = UserLevel::where(['uid'=>$order['uid'],'level_id'=>$order['vipid']])->update($datas);
        } else {
            $datas=[
                'is_forever'=>$vipinfo['is_forever'],
                'status'=>1,
                'is_del'=>0,
                'grade'=>$vipinfo['grade'],
                'uid'=>$order['uid'],
                'add_time'=>time(),
                'level_id'=>$order['vipid'],
                'discount'=>$vipinfo['discount'],
            ];
            if($datas['is_forever']){
                $datas['valid_time']=0;
            } else {
                $datas['valid_time']=$add_valid_time;
            }

            $datas['mark']='尊敬的用户'.$user['nickname'].'在'.date('Y-m-d H:i:s',time()).'成为了'.$vipinfo['name'];
            $res3 = UserLevel::set($datas);
        }

        //如果有推荐人，就写入佣金
        //查询推荐人
        $ruser = User::getUserInfo($user['spread_uid']);
        //查询配置推荐人佣金
        $rprice = SystemConfigService::get('store_brokerage_ratio');
        //查询奖励积分比例
        $integral = SystemConfigService::get('set_integral');
        if($ruser){
            $commission = bcdiv(bcmul($order['price'],$rprice),100,2);
            UserBill::income($user['nickname'].'成功注册',$ruser['uid'],'now_money','brokerage',$commission,$order['id'],$ruser['now_money'],'微信支付'.'成功注册'.$order['price'].'元');
        }

        //添加消费者积分
        $sum_integral = bcmul($order['price'],$integral,0);
        User::bcInc($order['uid'],'integral',$sum_integral,'uid');
        UserBill::income('成功注册'.'赠送积分',$order['uid'],'integral','gain',$sum_integral,$order['id'],$user['integral'],'成功注册'.'赠送'.$sum_integral.'积分');


        $res4 = User::edit(['level'=>$vipinfo['grade']],$order['uid'],'uid');
        $res = $res1 && $res2 && $res3 && $res4;
        self::checkTrans($res);
        return $res;
    }
}