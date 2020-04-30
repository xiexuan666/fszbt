<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-09-05
 * Time: 21:00
 */

namespace app\ebapi\model\user;
use app\core\model\user\UserBill;
use app\ebapi\model\store\NewProduct as ModProduct;
use app\ebapi\model\supply\Supply;
use basic\ModelBasic;
use app\core\util\MiniProgramService;
use traits\ModelTrait;

class UserCharge extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['add_time'];

    protected function setAddTimeAttr()
    {
        return time();
    }

    public static function addRecharge($uid,$price,$addid,$type = 'goods',$recharge_type = 'weixin',$paid = 0)
    {
        $order_id = self::getNewOrderId($uid);
        return self::set(compact('order_id','uid','price','addid','type','recharge_type','paid'));
    }

    public static function getNewOrderId($uid = 0)
    {
        if(!$uid) return false;
        $count = (int) self::where('add_time',['>=',strtotime(date("Y-m-d"))],['<',strtotime(date("Y-m-d",strtotime('+1 day')))])->count();
        return 'wx1'.date('YmdHis',time()).(10000+$count+$uid);
    }

    public static function jsPay($orderInfo)
    {
        return MiniProgramService::jsPay(WechatUser::uidToOpenid($orderInfo['uid']),$orderInfo['order_id'],$orderInfo['price'],'user_charge','收费发布信息');
    }

    /**
     * //TODO付费发布成功后
     * @param $orderId
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function paySuccess($orderId)
    {
        $order = self::where('order_id',$orderId)->where('paid',0)->find();
        if(!$order) return false;
        $user = User::getUserInfo($order['uid']);
        self::beginTrans();
        $res1 = self::where('order_id',$order['order_id'])->update(['paid'=>1,'pay_time'=>time()]);
        $datas['is_pay'] = 1;
        $datas['pay_time'] = time();
        $datas['pay_price'] = $order['price'];
        if($order['type'] == 'goods'){
            $type = '新品';
            $info = ModProduct::where('id',$order['addid'])->find();
            if($info){
                $res2 = ModProduct::where('mer_id',$order['uid'])->where('id',$order['addid'])->update($datas);
            }
        } elseif($order['type'] == 'supply') {
            $type = '招商';
            $info = Supply::where('id',$order['addid'])->find();
            if($info){
                $res2 = Supply::where('mer_id',$order['uid'])->where('id',$order['addid'])->update($datas);
            }
        }
        $res3 = UserBill::income('付费发布'.$type.'信息',$order['uid'],'now_money','pay_product',$order['price'],$order['id'],$user['now_money'],'成功发布信息'.floatval($order['price']).'元');



        $res = $res1 && $res2 && $res3;
        self::checkTrans($res);
        return $res;
    }
}