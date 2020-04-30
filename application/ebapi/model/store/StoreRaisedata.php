<?php
/**
 *
 * @author: 招宝通
 */

namespace app\ebapi\model\store;

use app\core\model\routine\RoutineTemplate;//待完善
use app\ebapi\model\user\User;
use basic\ModelBasic;
use traits\ModelTrait;

/**
 * 众筹Model
 * Class StoreRaisedata
 * @package app\ebapi\model\store
 */
class StoreRaisedata extends ModelBasic
{
    use ModelTrait;

    /*
     * 获取众筹完成的用户
     * @param int $uid 用户id
     * @return array
     * */
    public static function getRaisedataOkList($id)
    {
        //$list=self::where(['a.status'=>1,'a.is_refund'=>0])->where('a.cid','eq',$id)->alias('a')->join('__USER__ u','u.uid=a.uid')->column('u.avatar,u.nickname');
        $list=self::where(['status'=>1])->where('cid','eq',$id)->field('id,uid,people,price,total_num,add_time')->select();
        foreach ($list as $item=>$value){
            $user = User::where('uid',$value['uid'])->field('avatar,nickname')->find();
            $list[$item]['add_time'] = date('Y-m-d H:i',$value['add_time']);
            $list[$item]['avatar'] = $user['avatar'];
            $list[$item]['nickname'] = $user['nickname'];
        }
        return $list;
    }
    /*
     * 获取众筹完成的商品总件数
     * */
    public static function getRaisedataOkSumTotalNum($id)
    {

        return self::where('status',1)->where('is_refund',0)->sum('total_num');
    }


    /**
     * 设置结束时间
     * @param $idAll
     * @return $this
     */
    public static function setRaisedataStopTime($idAll){
        $model = new self();
        $model = $model->where('id','IN',$idAll);
        return $model->update(['stop_time'=>time(),'status'=>1]);
    }

    /**
     * 获取正在众筹的数据  团长
     * @param int $cid 产品id
     * @param int $isAll 是否查找所有众筹
     * @return array
     */
    public static function getRaisedataAll($cid,$isAll=false){
        $model = new self();
        $model = $model->alias('p');
        $model = $model->field('p.*,u.nickname,u.avatar');
        $model = $model->where('stop_time','GT',time());
        $model = $model->where('cid',$cid);
        $model = $model->where('k_id',0);
        $model = $model->where('is_refund',0);
        $model = $model->order('add_time desc');
        $model = $model->join('__USER__ u','u.uid = p.uid');
        $list = $model->select();
        $list=count($list) ? $list->toArray() : [];
        if($isAll){
            $pindAll = array();
            foreach ($list as &$v){
                $v['count'] = self::getRaisedataPeople($v['id'],$v['people']);
                $v['h'] = date('H',$v['stop_time']);
                $v['i'] = date('i',$v['stop_time']);
                $v['s'] = date('s',$v['stop_time']);
                $pindAll[] = $v['id'];//开团团长ID
            }
            return [$list,$pindAll];
        }
        return $list;
    }

    /**
     * 获取还差几人
     */
    public static function getRaisedataPeople($kid,$people){
        $model = new self();
        $model = $model->where('k_id',$kid)->where('is_refund',0);
        $count = bcadd($model->count(),1,0);
        return bcsub($people,$count,0);
    }

    /**
     * 判断订单是否在当前的众筹中
     * @param $orderId
     * @param $kid
     * @return bool
     */
    public static function getOrderIdAndRaisedata($orderId,$kid){
        $model = new self();
        $pink = $model->where('k_id',$kid)->whereOr('id',$kid)->column('order_id');
        if(in_array($orderId,$pink))return true;
        else return false;
    }

    /**
     * 判断用户是否在团内
     * @param $id
     * @return int|string
     */
    public static function getIsRaisedataUid($id = 0,$uid = 0){
         $pinkT = self::where('id',$id)->where('uid',$uid)->where('is_refund',0)->count();
         $pink = self::whereOr('k_id',$id)->where('uid',$uid)->where('is_refund',0)->count();
         if($pinkT) return true;
         if($pink) return true;
         else return false;
    }


    /**
     * 判断是否发送模板消息 0 未发送 1已发送
     * @param $uidAll
     * @return int|string
     */
    public static function isTpl($uidAll,$pid){
        if(is_array($uidAll)){
            $countK = self::where('uid','IN',implode(',',$uidAll))->where('is_tpl',0)->where('id',$pid)->count();
            $count = self::where('uid','IN',implode(',',$uidAll))->where('is_tpl',0)->where('k_id',$pid)->count();
        }
        else {
            $countK = self::where('uid',$uidAll)->where('is_tpl',0)->where('id',$pid)->count();
            $count = self::where('uid',$uidAll)->where('is_tpl',0)->where('k_id',$pid)->count();
        }
        return bcadd($countK,$count,0);
    }
    /**
     * 众筹成功提示模板消息
     * @param $uidAll
     * @param $pid
     */
    public static function orderRaisedataAfter($uidAll,$pid){
        $nickname=User::where(['uid'=>self::where(['id'=>$pid])->value('uid')])->value('nickname');
         foreach ($uidAll as $v){
             RoutineTemplate::sendOut('PINK_TRUE',$v,[
                 'keyword1'=>'亲，您的众筹已经完成了',
                 'keyword2'=>$nickname,
                 'keyword3'=>date('Y-m-d H:i:s',time()),
                 'keyword4'=>self::where('id',$pid)->value('price')
             ]);
         }
         self::beginTrans();
         $res1 = self::where('uid','IN',implode(',',$uidAll))->where('id',$pid)->whereOr('k_id',$pid)->update(['is_tpl'=>1]);
         self::checkTrans($res1);
    }

    /**
     * 众筹失败发送的模板消息
     * @param $uid
     * @param $pid
     */
    public static function orderRaisedataAfterNo($uid,$pid,$formId='',$fillTilt='',$isRemove=false){
        $store=self::alias('p')->where('p.id|p.k_id',$pid)->field('c.*')->where('p.uid',$uid)->join('__STORE_COMBINATION__ c','c.id=p.cid')->find();
        $pink=self::where('id|k_id',$pid)->where('uid',$uid)->find();
        if($isRemove){
            RoutineTemplate::sendOut('PINK_REMOVE',$uid,[
                'keyword1'=>$store->title,
                'keyword2'=>$pink->order_id,
                'keyword3'=>$pink->price,
            ],$formId,'/pages/order_details/index?order_id='.$pink->order_id);
        }else{
            RoutineTemplate::sendOut('PINK_Fill',$uid,[
                'keyword1'=>$store->title,
                'keyword2'=>$fillTilt,
                'keyword3'=>$pink->order_id,
                'keyword4'=>date('Y-m-d H:i:s',$pink->add_time),
                'keyword5'=>'申请退款金额：￥'.$pink->price,
            ],$formId,'/pages/order_details/index?order_id='.$pink->order_id);
        }
        self::where('id',$pid)->update(['status'=>3,'stop_time'=>time()]);
        self::where('k_id',$pid)->update(['status'=>3,'stop_time'=>time()]);
    }

    /**
     * 获取当前众筹数据返回订单编号
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public static function getCurrentRaisedata($id,$uid){
        $pink = self::where('id',$id)->where('uid',$uid)->find();
        if(!$pink) $pink = self::where('k_id',$id)->where('uid',$uid)->find();
        return StoreOrder::where('id',$pink['order_id_key'])->value('order_id');
    }

    public static function systemPage($where){
        $model = new self;
        $model = $model->alias('p');
        $model = $model->field('p.*,c.title');
        if($where['data'] !== ''){
            list($startTime,$endTime) = explode(' - ',$where['data']);
            $model = $model->where('p.add_time','>',strtotime($startTime));
            $model = $model->where('p.add_time','<',strtotime($endTime));
        }
        if($where['status']) $model = $model->where('p.status',$where['status']);
        $model = $model->where('p.k_id',0);
        $model = $model->order('p.id desc');
        $model = $model->join('StoreCombination c','c.id=p.cid');
        return self::page($model,function($item)use($where){
            $item['count_people'] = bcadd(self::where('k_id',$item['id'])->count(),1,0);
        },$where);
    }

    public static function isRaisedataBe($data,$id){
        $data['id'] = $id;
        $count = self::where($data)->count();
        if($count) return $count;
        $data['k_id'] = $id;
        $count = self::where($data)->count();
        if($count) return $count;
        else return 0;
    }
    public static function isRaisedataStatus($pinkId){
        if(!$pinkId) return false;
        $stopTime = self::where('id',$pinkId)->value('stop_time');
        if($stopTime < time()) return true; //众筹结束
        else return false;//众筹未结束
    }

    /**
     * 判断众筹结束 后的状态
     * @param $pinkId
     * @return bool
     */
    public static function isSetRaisedataOver($pinkId){
        $people = self::where('id',$pinkId)->value('people');
        $stopTime = self::where('id',$pinkId)->value('stop_time');
        if($stopTime < time()){
            $countNum = self::getRaisedataPeople($pinkId,$people);
            if($countNum) return false;//众筹失败
            else return true;//众筹成功
        }else return true;
    }

    /**
     * 众筹退款
     * @param $id
     * @return bool
     */
    public static function setRefundRaisedata($oid){
        $res = true;
        $order = StoreOrder::where('id',$oid)->find();
        if($order['pink_id']) $id = $order['pink_id'];
        else return $res;
        $count = self::where('id',$id)->where('uid',$order['uid'])->find();//正在众筹 团长
        $countY = self::where('k_id',$id)->where('uid',$order['uid'])->find();//正在众筹 团员
        if(!$count && !$countY) return $res;
        if($count){//团长
            //判断团内是否还有其他人  如果有  团长为第二个进团的人
            $kCount = self::where('k_id',$id)->order('add_time asc')->find();
            if($kCount){
                $res11 = self::where('k_id',$id)->update(['k_id'=>$kCount['id']]);
                $res12 = self::where('id',$kCount['id'])->update(['stop_time'=>$count['add_time']+86400,'k_id'=>0]);
                $res1 = $res11 && $res12;
                $res2 = self::where('id',$id)->update(['stop_time'=>time()-1,'k_id'=>0,'is_refund'=>$kCount['id'],'status'=>3]);
            }else{
                $res1 = true;
                $res2 = self::where('id',$id)->update(['stop_time'=>time()-1,'k_id'=>0,'is_refund'=>$id,'status'=>3]);
            }
            //修改结束时间为前一秒  团长ID为0
            $res = $res1 && $res2;
        }else if($countY){//团员
            $res =  self::where('id',$countY['id'])->update(['stop_time'=>time()-1,'k_id'=>0,'is_refund'=>$id,'status'=>3]);
        }
        return $res;

    }



    /**
     * 众筹人数完成时，判断全部人都是未退款状态
     * @param $pinkIds
     * @return bool
     */
    public static function setRaisedataStatus($pinkIds){
        $orderRaisedata = self::where('id','IN',$pinkIds)->where('is_refund',1)->count();
        if(!$orderRaisedata) return true;
        else return false;
    }


    /**
     * 创建众筹
     * @param $order
     * @return mixed
     */
    public static function createRaisedata($order){
        $order = StoreOrder::tidyOrder($order,true)->toArray();
        if($order['raise_id']){//众筹存在

            $res = false;
            $pink['uid'] = $order['uid'];//用户id
            if(self::isRaisedataBe($pink,$order['raise_id'])) return false;
            $pink['order_id'] = $order['order_id'];//订单id  生成
            $pink['order_id_key'] = $order['id'];//订单id  数据库id
            $pink['total_num'] = $order['total_num'];//购买个数
            $pink['total_price'] = $order['pay_price'];//总金额
            $pink['k_id'] = $order['raise_id'];//众筹id

            foreach ($order['cartInfo'] as $v){
                $pink['cid'] = $v['raise_id'];//众筹产品id
                $pink['pid'] = $v['product_id'];//产品id
                $pink['people'] = StoreRaise::where('id',$v['raise_id'])->value('people');//几人众筹
                $pink['price'] = $v['productInfo']['price'];//单价
                $stopTime = StoreRaise::where('id',$v['raise_id'])->value('stop_time');//获取众筹产品结束的时间
                if($stopTime < time()+86400)  $pink['stop_time'] = $stopTime;//结束时间
                $pink['add_time'] = time();//开团时间
                $res = self::set($pink)->toArray();
                $pink['id'] = $res['id'];
            }

            RoutineTemplate::sendOut('PINK_TRUE',$order['uid'],[
                'keyword1'=>StoreRaise::where('id',$pink['cid'])->value('title'),
                'keyword2'=>User::where('uid',self::where('id',$pink['k_id'])->value('uid'))->value('nickname'),
                'keyword3'=>date('Y-m-d H:i:s',$pink['add_time']),
                'keyword3'=>$pink['total_price'],
            ],'','/pages/order_details/index?order_id='.$pink['order_id']);

            //处理众筹完成
            list($pinkAll,$pinkT,$count,$idAll,$uidAll)=self::getRaisedataMemberAndRaisedataK($pink);

            if($pinkT['status']==1){
                if(!$count)//组团完成
                    self::RaisedataComplete($uidAll,$idAll,$pink['uid'],$pinkT);
                else
                    self::RaisedataFail($pinkAll,$pinkT,0);
            }
            if($res) return true;
            else return false;
        }else{
            $res = false;
            $pink['uid'] = $order['uid'];//用户id
            $pink['order_id'] = $order['order_id'];//订单id  生成
            $pink['order_id_key'] = $order['id'];//订单id  数据库id
            $pink['total_num'] = $order['total_num'];//购买个数
            $pink['total_price'] = $order['pay_price'];//总金额
            $pink['k_id'] = 0;//众筹id
            foreach ($order['cartInfo'] as $v){
                $pink['cid'] = $v['raise_id'];//众筹产品id
                $pink['pid'] = $v['product_id'];//产品id
                $pink['people'] = StoreRaise::where('id',$v['raise_id'])->value('people');//几人众筹
                $pink['price'] = $v['productInfo']['price'];//单价
                $stopTime = StoreRaise::where('id',$v['raise_id'])->value('stop_time');//获取众筹产品结束的时间
                if($stopTime < time()+86400)  $pink['stop_time'] = $stopTime;//结束时间
                $pink['add_time'] = time();//开团时间
                $res1 = self::set($pink)->toArray();
                $res2 = StoreOrder::where('id',$order['id'])->update(['raise_id'=>$res1['id']]);
                $res = $res1 && $res2;
            }
            /*RoutineTemplate::sendOut('OPEN_PINK_SUCCESS',$order['uid'],[
                'keyword1'=>date('Y-m-d H:i:s',$pink['add_time']),
                'keyword2'=>date('Y-m-d H:i:s',$pink['stop_time']),
                'keyword3'=>StoreRaise::where('id',$pink['cid'])->value('title'),
                'keyword4'=>$pink['order_id'],
                'keyword4'=>$pink['total_price'],
            ],'','/pages/order_details/index?order_id='.$pink['order_id']);*/
            if($res) return true;
            else return false;
        }
    }

    /*
     * 获取参团人和团长和众筹总人数
     * @param array $pink
     * @return array
     * */
    public static function getRaisedataMemberAndRaisedataK($pink){

        //查找众筹团员和团长
        if($pink['k_id']){
            $pinkAll = self::getRaisedataMember($pink['k_id']);
            $pinkT = self::getRaisedataUserOne($pink['id']);
        }else{
            $pinkAll = self::getRaisedataMember($pink['k_id']);
            $pinkT = $pink;
        }
        $count = count($pinkAll)+1;
        $count=(int)$pinkT['people']-$count;
        $idAll = [];
        $uidAll =[];
        //收集众筹用户id和众筹id
        foreach ($pinkAll as $k=>$v){
            $idAll[$k] = $v['id'];
            $uidAll[$k] = $v['uid'];
        }
        $idAll[] = $pinkT['id'];
        $uidAll[] = $pinkT['uid'];
        return [$pinkAll,$pinkT,$count,$idAll,$uidAll];
    }

    /**
     * 获取一条众筹数据
     * @param $id
     * @return mixed
     */
    public static function getRaisedataUserOne($id){
        $model = new self();
        $model = $model->alias('p');
        $model = $model->field('p.*,u.nickname,u.avatar');
        $model = $model->where('id',$id);
        $model = $model->join('__USER__ u','u.uid = p.uid');
        $list = $model->find();
        if($list) return $list->toArray();
        else return [];
    }

    /**
     * 获取众筹的团员
     * @param $id
     * @return mixed
     */
    public static function getRaisedataMember($id){
        $model = new self();
        $model = $model->alias('p');
        $model = $model->field('p.*,u.nickname,u.avatar');
        $model = $model->where('k_id',$id);
        $model = $model->where('is_refund',0);
        $model = $model->join('__USER__ u','u.uid = p.uid');
        $model = $model->order('id asc');
        $list = $model->select();
        if($list) return $list->toArray();
        else return [];
    }

    /*
     * 获取一条今天正在众筹的人的头像和名称
     * */
    public static function getRaisedataSecondOne()
    {
        $addTime =  mt_rand(time()-30000,time());
         return self::where('p.add_time','GT',$addTime)->alias('p')->where('p.status',1)->join('User u','u.uid=p.uid')->field('u.nickname,u.avatar as src')->find();
    }
    /**
     * 众筹成功后给团长返佣金
     * @param int $id
     * @return bool
     */
//    public static function setRakeBackColonel($id = 0){
//        if(!$id) return false;
//        $pinkRakeBack = self::where('id',$id)->field('people,price,uid,id')->find()->toArray();
//        $countPrice = bcmul($pinkRakeBack['people'],$pinkRakeBack['price'],2);
//        if(bcsub((float)$countPrice,0,2) <= 0) return true;
//        $rakeBack = (SystemConfigService::get('rake_back_colonel') ?: 0)/100;
//        if($rakeBack <= 0) return true;
//        $rakeBackPrice = bcmul($countPrice,$rakeBack,2);
//        if($rakeBackPrice <= 0) return true;
//        $mark = '众筹成功,奖励佣金'.floatval($rakeBackPrice);
//        self::beginTrans();
//        $res1 = UserBill::income('获得众筹佣金',$pinkRakeBack['uid'],'now_money','colonel',$rakeBackPrice,$id,0,$mark);
//        $res2 = User::bcInc($pinkRakeBack['uid'],'now_money',$rakeBackPrice,'uid');
//        $res = $res1 && $res2;
//        self::checkTrans($res);
//        return $res;
//    }

    /*
    *  众筹完成更改数据写入内容
    * @param array $uidAll 当前众筹uid
    * @param array $idAll 当前众筹pink_id
    * @param array $pinkT 团长信息
    * @return int
    * */
    public static function RaisedataComplete($uidAll,$idAll,$uid,$pinkT)
    {
        $pinkBool=6;
        try{
            if(self::setRaisedataStatus($idAll)){
                self::setRaisedataStopTime($idAll);
                if(in_array($uid,$uidAll)){
                    if(self::isTpl($uidAll,$pinkT['id'])) self::orderRaisedataAfter($uidAll,$pinkT['id']);
                    $pinkBool = 1;
                }else  $pinkBool = 3;
            }
            return $pinkBool;
        }catch (\Exception $e){
            self::setErrorInfo($e->getMessage());
            return $pinkBool;
        }
    }

    /*
     * 众筹失败 退款
     * @param array $pinkAll 众筹数据,不包括团长
     * @param array $pinkT 团长数据
     * @param int $pinkBool
     * @param boolen $isRunErr 是否返回错误信息
     * @param boolen $isIds 是否返回记录所有众筹id
     * @return int| boolen
     * */
    public static function RaisedataFail($pinkAll,$pinkT,$pinkBool,$isRunErr=true,$isIds=false){
        self::startTrans();
        $pinkIds=[];
        try{
            if($pinkT['stop_time'] < time()){//众筹时间超时  退款
                //团员退款
                foreach ($pinkAll as $v){
                    if(StoreOrder::orderApplyRefund(StoreOrder::getRaisedataOrderId($v['order_id_key']),$v['uid'],'众筹时间超时') && self::isTpl($v['uid'],$pinkT['id'])){
                        self::orderRaisedataAfterNo($v['uid'],$v['k_id']);
                        if($isIds) array_push($pinkIds,$v['id']);
                        $pinkBool = 2;
                    }else{
                        if($isRunErr) return self::setErrorInfo(StoreOrder::getErrorInfo(),true);
                    }
                }
                //团长退款
                if(StoreOrder::orderApplyRefund(StoreOrder::getRaisedataOrderId($pinkT['order_id_key']),$pinkT['uid'],'众筹时间超时') && self::isTpl($pinkT['uid'],$pinkT['id'])){
                    self::orderRaisedataAfterNo($pinkT['uid'],$pinkT['id']);
                    if($isIds) array_push($pinkIds,$pinkT['id']);
                    $pinkBool = 2;
                }else{
                    if($isRunErr) return self::setErrorInfo(StoreOrder::getErrorInfo(),true);
                }
                if(!$pinkBool) $pinkBool = 3;
            }
            self::commit();
            if($isIds) return $pinkIds;
            return $pinkBool;
        }catch (\Exception $e){
            self::rollback();
            return self::setErrorInfo($e->getMessage());
        }
    }


    /*
     * 取消开团
     * @param int $uid 用户id
     * @param int $pink_id 团长id
     * @return boolean
     * */
    public static function removeRaisedata($uid,$cid,$pink_id,$formId,$nextRaisedataT=null)
    {
        $pinkT=self::where(['uid'=>$uid,'id'=>$pink_id,'cid'=>$cid,'k_id'=>0,'is_refund'=>0,'status'=>1])->where('stop_time','GT',time())->find();
        if(!$pinkT) return self::setErrorInfo('未查到众筹信息，无法取消');
        self::startTrans();
        try{
            list($pinkAll,$pinkT,$count,$idAll,$uidAll)=self::getRaisedataMemberAndRaisedataK($pinkT);
            if(count($pinkAll)){
                if(self::getRaisedataPeople($pink_id,$pinkT->people)){
                    //众筹未完成，众筹有成员取消开团取 紧跟团长后众筹的人
                    if(isset($pinkAll[0])) $nextRaisedataT=$pinkAll[0];
                }else{
                    //众筹完成
                    self::RaisedataComplete($uidAll,$idAll,$uid,$pinkT);
                    return self::setErrorInfo(['status'=>200,'msg'=>'众筹已完成，无法取消']);
                }
            }
            //取消开团
            if(StoreOrder::orderApplyRefund(StoreOrder::getRaisedataOrderId($pinkT['order_id_key']),$pinkT['uid'],'众筹取消开团') && self::isTpl($pinkT['uid'],$pinkT['id']))
                self::orderRaisedataAfterNo($pinkT['uid'],$pinkT['id'],$formId,'众筹取消开团',true);
            else
                return self::setErrorInfo(['status'=>200,'msg'=>StoreOrder::getErrorInfo()],true);
            //当前团有人的时候
            if(is_array($nextRaisedataT)){
                self::where('id',$nextRaisedataT['id'])->update(['k_id'=>0,'status'=>1,'stop_time'=>$pinkT['stop_time']]);
                self::where('k_id',$pinkT['id'])->update(['k_id'=>$nextRaisedataT['id']]);
                StoreOrder::where('order_id',$nextRaisedataT['order_id'])->update(['pink_id'=>$nextRaisedataT['id']]);
            }
            self::commitTrans();
            return true;
        }catch (\Exception $e){
            return self::setErrorInfo($e->getLine().':'.$e->getMessage(),true);
        }
    }
}