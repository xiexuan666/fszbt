<?php
/**
 *
 * User: 招宝通
 */

namespace app\core\behavior;

use app\ebapi\model\store\StoreOrder;
use app\ebapi\model\user\User;
use app\ebapi\model\user\WechatUser;
use app\ebapi\model\user\UserAddress;
use app\admin\model\order\StoreOrder as StoreOrderAdminModel;
use app\core\util\SystemConfigService;
use app\core\logic\Template;

class OrderBehavior
{
    /**
     * 用户确认收货
     * @param $order
     * @param $uid
     */
    public static function storeProductOrderUserTakeDelivery($order, $uid)
    {
        $res1 = StoreOrder::gainUserIntegral($order);
        $res2 = User::backOrderBrokerage($order);
        StoreOrder::orderTakeAfter($order);
        $giveCouponMinPrice = SystemConfigService::get('store_give_con_min_price');
        if($order['total_price'] >= $giveCouponMinPrice) WechatUser::userTakeOrderGiveCoupon($uid);
        if(!($res1 && $res2)) exception('收货失败!');
    }
    /**
     * 订单创建成功后
     * @param $oid
     */
    public static function storeProductOrderCreate($order,$group)
    {
        UserAddress::be(['is_default'=>1,'uid'=>$order['uid']]) || UserAddress::setDefaultAddress($group['addressId'],$order['uid']);
    }

    /**
     * 修改发货状态  为送货
     * @param $data
     *  $data array  送货方式 送货人姓名  送货人电话
     * @param $oid
     * $oid  string store_order表中的id
     */
//    public static function storeProductOrderDeliveryAfter($data,$oid){
//        StoreOrder::orderPostageAfter($data,$oid);
//    }

    /**
     * 修改发货状态  为发货
     * @param $data
     *  $data array  发货方式 送货人姓名  送货人电话
     * @param $oid
     * $oid  string store_order表中的id
     */
//    public static function storeProductOrderDeliveryGoodsAfter($data,$oid){
//        StoreOrder::orderPostageAfter($data,$oid);
//        RoutineTemplate::sendOrderGoods($oid,$data);
//    }

    /**
     * 修改状态 为已收货
     * @param $data
     *  $data array status  状态为  已收货
     * @param $oid
     * $oid  string store_order表中的id
     */
    public static function storeProductOrderTakeDeliveryAfter($order,$oid)
    {
        $res1 = StoreOrder::gainUserIntegral($order);
        $res2 = User::backOrderBrokerage($order);
        StoreOrder::orderTakeAfter($order);
        if(!($res1 && $res2)) exception('收货失败!');
    }

    /**
     * 线下付款
     * @param $id
     * $id 订单id
     */
    public static function storeProductOrderOffline($id){

    }

    /**
     * 修改状态为  已退款
     * @param $data
     *  $data array type 1 直接退款  2 退款后返回原状态  refund_price  退款金额
     * @param $oid
     * $oid  string store_order表中的id
     */
    public static function storeProductOrderRefundYAfter($data,$oid){
        StoreOrderAdminModel::refundTemplate($data,$oid);
    }

    /**
     * 修改状态为  不退款
     * @param $data
     *  $data string  退款原因
     * @param $oid
     * $oid  string store_order表中的id
     */
    public static function storeProductOrderRefundNAfter($data,$oid){

    }


    /**
     * 修改订单状态
     * @param $data
     *  data  total_price 商品总价   pay_price 实际支付
     * @param $oid
     * oid 订单id
     */
    public static function storeProductOrderEditAfter($data,$oid){

    }
    /**
     * 修改送货信息
     * @param $data
     *  $data array  送货人姓名/快递公司   送货人电话/快递单号
     * @param $oid
     * $oid  string store_order表中的id
     */
    public static function storeProductOrderDistributionAfter($data,$oid){

    }

    /**
     * 用户申请退款
     * @param $oid
     * @param $uid
     */
    public static function storeProductOrderApplyRefundAfter($oid, $uid)
    {
        //待完善
        $order = StoreOrder::where('id',$oid)->find();
//        Template::sendAdminNoticeTemplate([
//            'first'=>"亲,您有一个订单申请退款 \n订单号:{$order['order_id']}",
//            'keyword1'=>'申请退款',
//            'keyword2'=>'待处理',
//            'keyword3'=>date('Y/m/d H:i',time()),
//            'remark'=>'请及时处理'
//        ]);
    }


    /**
     * 评价产品
     * @param $replyInfo
     * @param $cartInfo
     */
    public static function storeProductOrderReply($replyInfo, $cartInfo)
    {
        StoreOrder::checkOrderOver($cartInfo['oid']);
    }

    /**
     * 订单全部产品评价完
     * @param $oid
     */
    public static function storeProductOrderOver($oid)
    {

    }

    /**
     * 退积分
     * @param array $order
     *
     */
    public static function storeOrderRegressionIntegralAfter($order)
    {
        return StoreOrder::RegressionIntegral($order);
    }

    /**
     * 退销量
     * @param array $order
     *
     */
    public static function storeOrderRegressionStockAfter($order)
    {
        return StoreOrder::RegressionStock($order);
    }

    /**
     * 退优惠券
     * @param array $order
     *
     */
    public static function storeOrderRegressionCouponAfter($order)
    {
        return StoreOrder::RegressionCoupon($order);
    }

    /*
     * 回退所有
     * @param array $order
     * */
    public  static function storeOrderRegressionAllAfter($order)
    {
        return StoreOrder::RegressionStock($order) && StoreOrder::RegressionIntegral($order) && StoreOrder::RegressionCoupon($order);
    }

    /**
     * 加入购物车成功之后
     * @param array $cartInfo 购物车信息
     * @param array $userInfo 用户信息
     */
    public static function storeProductSetCartAfterAfter($cartInfo, $userInfo)
    {

    }
}