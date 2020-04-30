<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-08-04
 * Time: 09:31
 */

namespace app\ebapi\model\user;

use app\admin\model\notice\Notice;
use app\ebapi\model\course\Course;
use think\Cache;
use basic\ModelBasic;
use traits\ModelTrait;
use service\HookService;
use app\core\util\MiniProgramService;
use app\core\util\SystemConfigService;
use app\core\model\routine\RoutineTemplate;
use app\core\behavior\UserBehavior;

use app\core\model\user\UserLevel;
use app\core\model\system\SystemUserLevel;
use app\core\model\system\SystemUserTask;
use app\core\model\user\UserTaskFinish;

use app\ebapi\model\user\User;
use app\core\model\user\UserBill;
use app\ebapi\model\user\WechatUser;
use app\ebapi\model\article\Article;
use app\ebapi\model\knowledge\Knowledge;
use app\ebapi\model\supply\Supply;
use app\ebapi\model\job\Job;
use app\ebapi\model\job\JobPosition;
use app\ebapi\model\position\Position;
use app\ebapi\model\resume\ResumeExpect;
use app\ebapi\model\resume\Resume;

class UserSubion extends ModelBasic
{
    use ModelTrait;
    protected static $payType = ['weixin'=>'微信支付','yue'=>'余额支付','giving'=>'赠送获取','offline'=>'线下支付'];

    /**
     * 获取某个用户的订阅统计数据
     * @param $uid
     * @return mixed
     * @throws \think\Exception
     */
    public static function getSubionData($uid)
    {
        $data['order_count']=self::where(['is_del'=>0,'paid'=>1,'uid'=>$uid,'refund_status'=>0])->count();
        $data['sum_price']=self::where(['is_del'=>0,'paid'=>1,'uid'=>$uid,'refund_status'=>0])->sum('pay_price');
        $data['unpaid_count']=self::statusByWhere(0,$uid)->where('is_del',0)->where('uid',$uid)->count();
        $data['unshipped_count']=self::statusByWhere(1,$uid)->where('is_del',0)->where('uid',$uid)->count();
        $data['received_count']=self::statusByWhere(2,$uid)->where('is_del',0)->where('uid',$uid)->count();
        $data['evaluated_count']=self::statusByWhere(3,$uid)->where('is_del',0)->where('uid',$uid)->count();
        $data['complete_count']=self::statusByWhere(4,$uid)->where('is_del',0)->where('uid',$uid)->count();
        return $data;
    }

    /**
     * @param $status
     * @param int $uid
     * @param null $model
     * @return UserSubion|null
     */
    public static function statusByWhere($status,$uid=0,$model = null,$catetype=0)
    {
        if($model == null) $model = new self;
        if($catetype) $model->where('type',$catetype);
        if('' === $status)
            return $model;
        else if($status == 0)
            return $model->where('paid',0)->where('status',0)->where('refund_status',0);
        else if($status == 1)//待发货
            return $model->where('paid',1)->where('status',0)->where('refund_status',0);
        else if($status == 2)
            return $model->where('paid',1)->where('status',1)->where('refund_status',0);
        else if($status == 3)
            return $model->where('paid',1)->where('status',2)->where('refund_status',0);
        else if($status == 4)
            return $model->where('paid',1)->where('status',3)->where('refund_status',0);
        else if($status == -1)
            return $model->where('paid',1)->where('refund_status',1);
        else if($status == -2)
            return $model->where('paid',1)->where('refund_status',2);
        else if($status == -3)
            return $model->where('paid',1)->where('refund_status','IN','1,2');
        else
            return $model;
    }

    /**
     * 个人中心获取个人订阅列表和订单搜索
     * @param $uid 用户uid
     * @param $type string 查找订单类型
     * @param $page 分页
     * @param $limit 每页显示多少条
     * @param $search 订阅号
     * @return array|mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getUserSubionSearchList($uid,$type,$page,$limit,$search,$catetype)
    {
        if($search){
            $order = self::searchUserSubion($uid,$search)?:[];
            $list = $order == false ? [] : [$order];
        }else{
            $list = self::getUserSubionList($uid,$type,$page,$limit,$catetype);
        }
        foreach ($list as $k=>$order){
            $list[$k] = self::tidySubion($order,true);
            if($list[$k]['_status']['_type'] == 3){
                foreach ($order['cartInfo']?:[] as $key=>$product){
                    $list[$k]['cartInfo'][$key]['add_time'] = date('Y-m-d H:i',$product['add_time']);
                }
            }
            if($order['type'] == 'article'){
                $Info = Article::where('id',$order['subion_id'])->field('id,title,image')->find();
                $list[$k]['info'] = $Info;
                $list[$k]['url'] = 'supply/detail';
                $list[$k]['_type'] = '资讯';
            } else if ($order['type'] == 'knowledge'){
                $Info = Knowledge::where('id',$order['subion_id'])->field('id,title,image')->find();
                $list[$k]['info'] = $Info;
                $list[$k]['url'] = 'knowledge/detail';
                $list[$k]['_type'] = '干货';
            } else if ($order['type'] == 'supply'){
                $Info = Supply::where('id',$order['subion_id'])->field('id,title,image')->find();
                $list[$k]['info'] = $Info;
                $list[$k]['url'] = 'supply/detail';
                $list[$k]['_type'] = '招商';
            } else if ($order['type'] == 'job'){
                $Info = JobPosition::where('id',$order['subion_id'])->find();
                $Job = Job::where('uid',$Info['uid'])->find();
                $User = User::where('uid',$Info['uid'])->find();
                $Position = Position::where('id',$Info['position'])->find();
                $Info['title'] = $Job['name'].' ['.$Position['name'].'] · '.$Info['address'];
                if($Job){
                    $Info['phone'] = $Job['phone'];
                } else {
                    $Info['phone'] = $User['phone'];
                }

                $Info['image'] = $User['avatar'];
                $Info['price'] = SystemConfigService::get('recruitment_price');
                $list[$k]['info'] = $Info;
                $list[$k]['url'] = 'job/detail';
                $list[$k]['_type'] = '招聘';
            } else if ($order['type'] == 'resume'){
                $Info = Resume::where('id',$order['subion_id'])->find();
                $expect = ResumeExpect::where(array('uid'=>$Info['uid']))->order('id asc')->find();
                $position = Position::where(array('id'=>$expect['position']))->find();
                if($position['pid']) {
                    $positionOne = Position::where(array('id'=>$position['pid']))->find();
                    if($positionOne){
                        $Info['position'] = $positionOne['name'].' · '.$position['name'];
                    }
                } else {
                    $Info['position'] = $position['name'];
                }

                $Info['title'] = $Info['name'].' ['.$Info['position'].']';
                $Info['price'] = SystemConfigService::get('resume_price');
                $list[$k]['info'] = $Info;
                $list[$k]['url'] = 'resume/detail';
                $list[$k]['_type'] = '求职';
            } else if ($order['type'] == 'course'){
                $Info = Course::where('id',$order['subion_id'])->find();
                $list[$k]['info'] = $Info;
                $list[$k]['url'] = 'course/detail';
                $list[$k]['_type'] = '课程';
            }
            $avatar = User::where('uid',$order['mer_id'])->field('avatar')->find();
            $list[$k]['avatar'] = $avatar['avatar'];

        }
        return $list;
    }

    /**
     * @param $uid
     * @param $order_id
     * @return bool|mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function searchUserSubion($uid,$order_id)
    {
        $order = self::where('uid',$uid)->where('order_id',$order_id)->where('is_del',0)->field('id,order_id,pay_price,total_num,total_price,paid,status,refund_status,pay_type,subion_id,deduction_price,type,mer_id')
            ->order('add_time DESC')->find();
        if(!$order)
            return false;
        else
            return self::tidySubion($order->toArray(),true);
    }

    /**
     * @param $uid
     * @param string $status
     * @param int $page
     * @param int $limit
     * @return mixed
     * @throws \think\Exception
     */
    public static function getUserSubionList($uid,$status = '',$page = 0,$limit = 8,$catetype)
    {
        $list = self::statusByWhere($status,$uid,'',$catetype)->where('is_del',0)->where('uid',$uid)
            ->field('add_time,id,order_id,pay_price,total_num,total_price,paid,status,refund_status,pay_type,subion_id,deduction_price,type,mer_id')
            ->order('add_time DESC')->page((int)$page,(int)$limit)->select()->toArray();
        foreach ($list as $k=>$order){
            $list[$k] = self::tidySubion($order,true);
        }
        return $list;
    }

    /**
     * 订阅详情
     * @param $uid
     * @param $key
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getUserSubionDetail($uid,$key)
    {
        $data = self::where('order_id|unique',$key)->where('uid',$uid)->where('is_del',0)->find();
        if($data['type'] == 'article'){
            $Info = Article::where('id',$data['subion_id'])->find();
        } else if($data['type'] == 'knowledge'){
            $Info = Knowledge::where('id',$data['subion_id'])->find();
        } else if($data['type'] == 'supply'){
            $Info = Supply::where('id',$data['subion_id'])->find();
        } else if($data['type'] == 'job'){
            $Info = JobPosition::where('id',$data['subion_id'])->find();

            $Job = Job::where('uid',$Info['uid'])->find();
            $User = User::where('uid',$Info['uid'])->find();
            $Position = Position::where('id',$Info['position'])->find();
            $Info['title'] = $Position['name'].' · '.$Info['address'];
            if($Job){
                $Info['phone'] = $Job['phone'];
            } else {
                $Info['phone'] = $User['phone'];
            }

            $Info['image'] = $User['avatar'];
            $Info['price'] = SystemConfigService::get('recruitment_price');

        } else if($data['type'] == 'resume'){
            $Info = Resume::where('id',$data['subion_id'])->find();
            $Info['title'] = $Info['name'];
            $Info['price'] = SystemConfigService::get('resume_price');
        } else if($data['type'] == 'course'){
            $Info = Course::where('id',$data['subion_id'])->find();
        }

        $Info['add_time'] = isset($Info['add_time']) ? (strstr($Info['add_time'],'-')===false ? date('Y-m-d H:i:s',$Info['add_time']) : $Info['add_time'] ): '';
        $data['info'] = $Info;
        return $data;
    }

    /**
     * 取消未支付订阅
     * @param $order_id
     * @return bool|false|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function cancelSubion($order_id)
    {
        $order = self::where('order_id',$order_id)->find();
        if(!$order) return self::setErrorInfo('没有查到此订阅');
        self::beginTrans();
        try{
            $order->is_del=1;
            self::commitTrans();
            return $order->save();
        }catch (\Exception $e){
            self::rollbackTrans();
            return self::setErrorInfo(['line'=>$e->getLine(),'message'=>$e->getMessage()]);
        }
    }

    /**
     * 删除订阅
     * @param $uni
     * @param $uid
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function removeSubion($uni, $uid)
    {
        $order = self::getUserSubionDetail($uid,$uni);
        if(!$order) return self::setErrorInfo('订阅不存在!');
        $order = self::tidySubion($order);
        if($order['_status']['_type'] != 0 && $order['_status']['_type']!= -2 && $order['_status']['_type'] != 4)
            return self::setErrorInfo('该订阅无法删除!');
        if(false !== self::edit(['is_del'=>1],$order['id'],'id') && false !== UserSubionStatus::status($order['id'],'remove_subion','删除订阅')) {
            //未支付和已退款的状态下才可以退积分退库存退优惠券
            if($order['_status']['_type']== 0 || $order['_status']['_type']== -2) {
                HookService::afterListen('store_subion_regression_all',$order,null,false,UserBehavior::class);
            }
            return true;
        }else
            return self::setErrorInfo('订阅删除失败!');
    }

    /**
     * TODO JS支付
     * @param $orderId
     * @param string $field
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function jsPay($orderId,$field = 'order_id')
    {
        if(is_string($orderId))
            $orderInfo = self::where($field,$orderId)->find();
        else
            $orderInfo = $orderId;

        if(!$orderInfo || !isset($orderInfo['paid'])) exception('订阅单不存在!');
        if($orderInfo['paid']) exception('订阅已支付!');
        if($orderInfo['pay_price'] <= 0) exception('该订阅无需支付!');

        $openid = WechatUser::getOpenId($orderInfo['uid']);
        return MiniProgramService::jsPay($openid,$orderInfo['order_id'],$orderInfo['pay_price'],'subion',SystemConfigService::get('site_name'));
    }

    /**
     * 微信支付 为 0元时
     * @param $order_id
     * @param $uid
     * @param string $formId
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function jsPayPrice($order_id,$uid,$formId = ''){
        $orderInfo = self::where('uid',$uid)->where('order_id',$order_id)->find();
        if(!$orderInfo) return self::setErrorInfo('订阅单不存在!');
        if($orderInfo['paid']) return self::setErrorInfo('该订阅单已支付!');
        self::beginTrans();
        $res = self::paySuccess($order_id,'weixin',$formId);//微信支付为0时
        self::checkTrans($res);
        return $res;
    }

    //TODO 余额支付
    /**
     * 余额支付
     * @param $order_id
     * @param $uid
     * @param string $formId
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function yuePay($order_id,$uid,$formId = '')
    {
        $orderInfo = self::where('uid',$uid)->where('order_id',$order_id)->where('is_del',0)->find();
        if(!$orderInfo) return self::setErrorInfo('订单不存在!');
        if($orderInfo['paid']) return self::setErrorInfo('该订单已支付!');
        $userInfo = User::getUserInfo($uid);
        if($userInfo['now_money'] < $orderInfo['pay_price'])
            return self::setErrorInfo(['status'=>'pay_deficiency','msg'=>'余额不足'.floatval($orderInfo['pay_price'])]);
        self::beginTrans();

        $res1 = false !== User::bcDec($uid,'now_money',$orderInfo['pay_price'],'uid');
        $res2 = self::paySuccess($order_id,'yue',$formId);//余额支付成功
        try{
            HookService::listen('yue_pay_product',$userInfo,$orderInfo,false,PaymentBehavior::class);
        }catch (\Exception $e){
            self::rollbackTrans();
            return self::setErrorInfo($e->getMessage());
        }
        $res = $res1 && $res2;
        self::checkTrans($res);
        return $res;
    }

    //TODO 赠送获取
    /**
     * 赠送获取
     * @param $order_id
     * @param $uid
     * @param string $formId
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function givingPay($order_id,$uid,$formId = '')
    {
        $orderInfo = self::where('uid',$uid)->where('order_id',$order_id)->where('is_del',0)->find();
        if(!$orderInfo) return self::setErrorInfo('订单不存在!');
        if($orderInfo['paid']) return self::setErrorInfo('该订单已支付!');
        $userInfo = User::getUserInfo($uid);
        self::beginTrans();

        $res = self::paySuccess($order_id,'giving',$formId);//赠送获取成功
        try{
            HookService::listen('giving_pay_product',$userInfo,$orderInfo,false,PaymentBehavior::class);
        }catch (\Exception $e){
            self::rollbackTrans();
            return self::setErrorInfo($e->getMessage());
        }
        
        self::checkTrans($res);
        return $res;
    }

    /**
     * 线下支付消息通知
     * 待完善
     *
     * */
    public static function createTemplate($order)
    {
        //$goodsName = StoreOrderCartInfo::getProductNameList($order['id']);
        //        RoutineTemplateService::sendTemplate(WechatUser::getOpenId($order['uid']),RoutineTemplateService::ORDER_CREATE, [
        //            'first'=>'亲，您购买的商品已支付成功',
        //            'keyword1'=>date('Y/m/d H:i',$order['add_time']),
        //            'keyword2'=>implode(',',$goodsName),
        //            'keyword3'=>$order['order_id'],
        //            'remark'=>'点击查看订单详情'
        //        ],Url::build('/wap/My/order',['uni'=>$order['order_id']],true,true));
        //        RoutineTemplateService::sendAdminNoticeTemplate([
        //            'first'=>"亲,您有一个新订单 \n订单号:{$order['order_id']}",
        //            'keyword1'=>'新订单',
        //            'keyword2'=>'线下支付',
        //            'keyword3'=>date('Y/m/d H:i',time()),
        //            'remark'=>'请及时处理'
        //        ]);
    }

    /**
     * 生成订阅
     * @param $uid
     * @param $key
     * @param $payType
     * @param $subionid
     * @param $type
     * @return bool|object
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function cacheKeyCreateSubion($uid,$key,$payType,$subionid,$mer_id,$type)
    {

        if(!array_key_exists($payType,self::$payType)) return self::setErrorInfo('选择支付方式有误!');
        if(self::be(['unique'=>$key,'uid'=>$uid])) return self::setErrorInfo('请勿重复提交订阅');

        $userInfo = User::getUserInfo($uid);
        if(!$userInfo) return  self::setErrorInfo('用户不存在!');

        $cartGroup = self::getCacheSubionInfo($uid,$key);
        if(!$cartGroup) return self::setErrorInfo('订阅已过期,请刷新当前页面!');
        $priceGroup = $cartGroup['priceGroup'];
        $payPrice = (float)$priceGroup['totalPrice'];

        $orderInfo = [
            'uid'=>$uid,
            'order_id'=>self::getNewOrderId(),
            'real_name'=>$userInfo['nickname'],
            'user_phone'=>$userInfo['phone'],
            'subion_id'=>$subionid,
            'total_num'=>1,
            'total_price'=>$priceGroup['totalPrice'],
            'pay_price'=>$payPrice,
            'deduction_price'=>0,
            'paid'=>0,
            'pay_type'=>$payType,
            'type'=>$type,
            'use_integral'=>0,
            'gain_integral'=>0,
            'is_channel'=>1,
            'unique'=>$key,
            'mer_id'=>$mer_id,
            'add_time'=>time()
        ];

        $order = self::set($orderInfo);
        if(!$order)return self::setErrorInfo('订阅生成失败!');
        self::clearCacheSubionInfo($uid,$key);
        self::commitTrans();
        UserSubionStatus::status($order['id'],'cache_key_create_subion','订阅生成');
        return $order;
    }

    /**
     * @param $order
     * @param bool $detail
     * @param bool $isPic
     * @return mixed
     * @throws \think\Exception
     */
    public static function tidySubion($order,$detail = false,$isPic=false)
    {
        $status = [];
        if(!$order['paid'] && $order['pay_type'] == 'offline' && !$order['status'] >= 2){
            $status['_type'] = 9;
            $status['_title'] = '线下付款';
            $status['_msg'] = '等待商家处理,请耐心等待';
            $status['_class'] = 'nobuy';
        }else if(!$order['paid']){
            $status['_type'] = 0;
            $status['_title'] = '未支付';
            $status['_msg'] = '立即支付订阅吧';
            $status['_class'] = 'nobuy';
        }else if($order['refund_status'] == 1){
            $status['_type'] = -1;
            $status['_title'] = '申请退款中';
            $status['_msg'] = '商家审核中,请耐心等待';
            $status['_class'] = 'state-sqtk';
        }else if($order['refund_status'] == 2){
            $status['_type'] = -2;
            $status['_title'] = '已退款';
            $status['_msg'] = '已为您退款,感谢您的支持';
            $status['_class'] = 'state-sqtk';
        }else if(!$order['status']){
            $status['_type'] = 1;
            $status['_title'] = '未完成';
            $status['_msg'] = '商家未完成,请耐心等待';
            $status['_class'] = 'state-nfh';
        }else if($order['status'] == 1){
            $status['_type'] = 2;
            $status['_title'] = '成功订阅';
            $status['_msg'] = date('m月d日H时i分',UserSubionStatus::getTime($order['id'],'delivery_goods')).'成功订阅';
            $status['_class'] = 'state-ysh';
        }else if($order['status'] == 2){
            $status['_type'] = 3;
            $status['_title'] = '待评价';
            $status['_msg'] = '已订阅,快去评价一下吧';
            $status['_class'] = 'state-ypj';
        }else if($order['status'] == 3){
            $status['_type'] = 4;
            $status['_title'] = '订阅成功';
            $status['_msg'] = '订阅成功,感谢您的支持';
            $status['_class'] = 'state-ytk';
        }
        if(isset($order['pay_type']))
            $status['_payType'] = isset(self::$payType[$order['pay_type']]) ? self::$payType[$order['pay_type']] : '其他方式';

        $order['_status'] = $status;
        $order['_pay_time']=isset($order['pay_time']) && $order['pay_time'] != null ? date('Y-m-d H:i:s',$order['pay_time']) : date('Y-m-d H:i:s',$order['add_time']);
        $order['_add_time']=isset($order['add_time']) ? (strstr($order['add_time'],'-')===false ? date('Y-m-d H:i:s',$order['add_time']) : $order['add_time'] ): '';
        $order['status_pic']='';

        ///=============
        if($order['type'] == 'job'){
            $Info = JobPosition::where('id',$order['subion_id'])->find();

            $Job = Job::where('uid',$Info['uid'])->find();
            $User = User::where('uid',$Info['uid'])->find();
            $Position = Position::where('id',$Info['position'])->find();
            $order['title'] = $Position['name'].' · '.$Info['address'];
            if($Job){
                $order['phone'] = $Job['phone'];
            } else {
                $order['phone'] = $User['phone'];
            }

            $order['image'] = $User['avatar'];
            $order['price'] = SystemConfigService::get('recruitment_price');

        } else if($order['type'] == 'resume'){
            $Info = Resume::where('id',$order['subion_id'])->find();

            $order['title'] = $Info['name'];
            $order['price'] = SystemConfigService::get('resume_price');
        }
        ///=============
        //获取状态图片
        if($isPic){
            $order_details_images=\app\core\util\GroupDataService::getData('order_details_images') ? : [];
            foreach ($order_details_images as $image){
                if(isset($image['order_status']) && $image['order_status']==$order['_status']['_type']){
                    $order['status_pic']=$image['pic'];
                    break;
                }
            }
        }
        return $order;
    }

    /**
     * //TODO 支付成功后
     * @param $orderId
     * @param string $paytype
     * @param string $formId
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function paySuccess($orderId,$paytype='weixin',$formId = '')
    {
        $order = self::where('order_id',$orderId)->find();
        $res = self::where('order_id',$orderId)->update(['paid'=>1,'status'=>3,'pay_type'=>$paytype,'pay_time'=>time()]);//订单改为支付

        //添加消费记录
        $userInfo = User::getUserInfo($order['uid']);

        if($paytype == 'weixin') {
            $paytypeData = '微信';
        } elseif ($paytype == 'giving'){
            $paytypeData = '赠送';
        } else {
            $paytypeData = '余额';
        }

        //不等于赠送的时候
        if($paytype != 'giving'){
            //添加佣金记录
            if($order['type'] == 'article'){
                $pumpingData = '资讯订阅';
                $pumping = SystemConfigService::get('activity_pumping');

                $number = bcdiv(bcmul($order['pay_price'],$pumping),100,2);
                UserBill::income($pumpingData,$order['mer_id'],'now_money','brokerage',$number,$order['id'],$userInfo['now_money'],$paytypeData.'支付'.$pumpingData.$order['pay_price'].'元');
            } else if($order['type'] == 'course') {
                $pumpingData = '课程订阅';
                $pumping = SystemConfigService::get('classes_price');

                $number = bcdiv(bcmul($order['pay_price'],$pumping),100,2);
                UserBill::income($pumpingData,$order['mer_id'],'now_money','brokerage',$number,$order['id'],$userInfo['now_money'],$paytypeData.'支付'.$pumpingData.$order['pay_price'].'元');
            } else if($order['type'] == 'knowledge') {
                $pumpingData = '干货订阅';
                $pumping = SystemConfigService::get('goods_pumping');

                $number = bcdiv(bcmul($order['pay_price'],$pumping),100,2);
                UserBill::income($pumpingData,$order['mer_id'],'now_money','brokerage',$number,$order['id'],$userInfo['now_money'],$paytypeData.'支付'.$pumpingData.$order['pay_price'].'元');
            } else if($order['type'] == 'supply') {
                $pumpingData = '招商订阅';
            } else if($order['type'] == 'job') {
                $pumpingData = '求职';
            } else if($order['type'] == 'resume') {
                $pumpingData = '招聘';
            } else {
                $pumpingData = '其他';
            }

            //如果有推荐人，就写入佣金
            //查询推荐人
            $ruser = User::getUserInfo($userInfo['spread_uid']);
            //查询配置推荐人佣金
            $rprice = SystemConfigService::get('store_brokerage_ratio');
            //查询奖励积分比例
            $integral = SystemConfigService::get('set_integral');
            if($ruser){
                $commission = bcdiv(bcmul($order['pay_price'],$rprice),100,2);
                UserBill::income($userInfo['nickname'].$pumpingData,$ruser['uid'],'now_money','brokerage',$commission,$order['id'],$ruser['now_money'],$paytypeData.'支付'.$pumpingData.$order['pay_price'].'元');
            }

            //添加消费者积分
            $sum_integral = bcmul($order['pay_price'],$integral,0);
            User::bcInc($order['uid'],'integral',$sum_integral,'uid');
            UserBill::income('购买'.$pumpingData.'赠送积分',$order['uid'],'integral','gain',$sum_integral,$order['id'],$userInfo['integral'],'购买'.$pumpingData.'赠送'.$sum_integral.'积分');

            UserBill::expend($pumpingData,$order['uid'],'now_money','pay_product',$order['pay_price'],$order['id'],$userInfo['now_money'],$paytypeData.'支付'.$pumpingData.$order['pay_price'].'元');
        }

        //添加购买次数
        User::bcInc($order['uid'],'pay_count',1,'uid');
        $oid = self::where('order_id',$orderId)->value('id');
        UserSubionStatus::status($oid,'pay_success','用户付款成功');
        RoutineTemplate::sendOrderSuccess($formId,$orderId);
        HookService::afterListen('user_level',User::where('uid',$order['uid'])->find(),false,UserBehavior::class);

        return false !== $res;
    }

    /**
     * @param $uid
     * @param $cartInfo
     * @param $priceGroup
     * @param array $other
     * @param int $cacheTime
     * @return string
     */
    public static function cacheSubionInfo($uid,$cartInfo,$priceGroup,$other = [],$cacheTime = 600)
    {
        $key = md5(time());
        Cache::set('user_order_'.$uid.$key,compact('cartInfo','priceGroup','other'),$cacheTime);
        return $key;
    }

    /**
     * @param $uid
     * @param $key
     * @return mixed|null
     */
    public static function getCacheSubionInfo($uid,$key)
    {
        $cacheName = 'user_order_'.$uid.$key;
        if(!Cache::has($cacheName)) return null;
        return Cache::get($cacheName);
    }

    /**
     * @param $uid
     * @param $key
     */
    public static function clearCacheSubionInfo($uid,$key)
    {
        Cache::clear('user_order_'.$uid.$key);
    }

    /**
     * 生成订阅号
     * @return string
     * @throws \think\Exception
     */
    public static function getNewOrderId()
    {
        $count = (int) self::where('add_time',['>=',strtotime(date("Y-m-d"))],['<',strtotime(date("Y-m-d",strtotime('+1 day')))])->count();
        return 'wx'.date('YmdHis',time()).(10000+$count+1);
    }

    /**
     * 累计消费
     * @param $uid
     * @return float|int
     */
    public static function getOrderStatusSum($uid)
    {
        return self::where(['uid'=>$uid,'is_del'=>0,'paid'=>1])->sum('pay_price');
    }

}