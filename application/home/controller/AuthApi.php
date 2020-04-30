<?php
/**
 *
 * @author:招宝通
 */

namespace app\home\controller;


use Api\Express;
use app\ebapi\model\supplier\SupplierCategory;
use app\home\model\store\StoreBargain;
use app\home\model\store\StoreBargainUser;
use app\home\model\store\StoreBargainUserHelp;
use app\home\model\store\StoreCouponIssue;
use app\home\model\store\StoreCouponIssueUser;
use app\home\model\store\StoreOrderCartInfo;
use app\home\model\store\StorePink;
use app\home\model\store\StoreProductReply;
use app\home\model\store\StoreService;
use app\home\model\store\StoreServiceLog;
use app\home\model\store\StoreCart;
use app\home\model\store\StoreCategory;
use app\home\model\store\StoreCouponUser;
use app\home\model\store\StoreOrder;
use app\home\model\store\StoreProduct;
use app\home\model\store\StoreProductAttr;
use app\home\model\store\StoreProductRelation;
use app\home\model\user\User;
use app\home\model\user\UserAddress;
use app\home\model\user\UserBill;
use app\home\model\user\UserExtract;
use app\home\model\user\UserRecharge;
use app\home\model\user\UserNotice;
use app\home\model\user\UserSign;
use app\home\model\user\WechatUser;

use app\ebapi\model\store\NewProduct;
use app\ebapi\model\supply\Supply;
use app\ebapi\model\supplier\Supplier;
use app\ebapi\model\job\JobPosition;
use app\ebapi\model\resume\Resume;
use app\ebapi\model\article\Article;
use app\ebapi\model\knowledge\Knowledge;
use app\ebapi\model\dealers\Dealers;
use app\ebapi\model\company\Company;
use app\ebapi\model\position\Position;
use app\ebapi\model\resume\ResumeExpect;
use app\ebapi\model\store\Brand;
use app\ebapi\model\store\StoreBrand;

use app\ebapi\model\store\NewCategory;

use behavior\wap\StoreProductBehavior;
use app\core\util\WechatTemplateService;
use service\CacheService;
use service\HookService;
use service\JsonService;
use service\UploadService as Upload;
use app\core\util\SystemConfigService;
use app\admin\model\system\SystemAttachment;
use service\UtilService;
use think\Cache;
use think\Request;
use think\Url;

class AuthApi extends AuthController
{

    /**
     * @return false|string|void
     * 文件上传
     * ====================================================================
     * ||
     * || 文件上传
     * ||
     * ====================================================================
     */
    public function upload(){
        $siteUrl = 'http://'.$_SERVER['SERVER_NAME'];
        $res = Upload::image('file','editor/'.date('Ymd'));
        $thumbPath = Upload::thumb($res->dir);
        //产品图片上传记录
        $fileInfo = $res->fileInfo->getinfo();
        SystemAttachment::attachmentAdd($res->fileInfo->getSaveName(),$fileInfo['size'],$fileInfo['type'],$res->dir,$thumbPath,1);
        if($res->status == 200){
            $data = array('code'=>0,'msg'=>'上传成功','data'=>array('src'=>$siteUrl.Upload::pathToUrl($thumbPath),'title'=>$res->fileInfo->getSaveName()));
            return json_encode($data,true);
        } else {
            return JsonService::fail($res->error);
        }
    }

    /**
     * 请求新品一级分类
     * ====================================================================
     * ||
     * || 请求新品一级分类
     * ||
     * ====================================================================
     */
    public function get_goods_new_one(){
        $data = NewCategory::pidByCategory(0,'id,cate_name');
        if($data){
            return JsonService::successful($data);
        } else{
            return JsonService::fail('数据请求失败!');
        }
    }

    /**
     * 请求新品二级级分类
     * @param Request $request
     * ====================================================================
     * ||
     * || 请求新品二级级分类
     * ||
     * ====================================================================
     */
    public function get_goods_new_two(Request $request){
        $data = UtilService::getMore([['id',0]],$request);
        $dataCateA = [];
        $dataCateE = NewCategory::pidBySidList($data['id']);
        if($dataCateE) $dataCateE = $dataCateE->toArray();
        $dataCate = array_merge_recursive($dataCateA,$dataCateE);
        return JsonService::successful($dataCate);
    }

    /**
     * 请求职位一级分类
     * ====================================================================
     * ||
     * || 请求职位一级分类
     * ||
     * ====================================================================
     */
    public function get_position_one(){
        $data = Position::pidBy(0,'id,name');
        if($data){
            return JsonService::successful($data);
        } else{
            return JsonService::fail('数据请求失败!');
        }
    }

    /**
     * 请求职位二级分类
     * ====================================================================
     * ||
     * || 请求职位二级分类
     * ||
     * ====================================================================
     */
    public function get_position_two(Request $request){
        $data = UtilService::getMore([['id',0]],$request);
        $dataCateA = [];
        $dataCateE = Position::pidByList($data['id']);
        if($dataCateE) $dataCateE = $dataCateE->toArray();
        $dataCate = array_merge_recursive($dataCateA,$dataCateE);
        return JsonService::successful($dataCate);
    }

    /**
     * 请求企业类别一级分类
     * ====================================================================
     * ||
     * || 请求企业类别一级分类
     * ||
     * ====================================================================
     */
    public function get_supplier_one(){
        $data = SupplierCategory::getCategory(0,'id,cate_name');
        if($data){
            return JsonService::successful($data);
        } else{
            return JsonService::fail('数据请求失败!');
        }
    }

    /**
     * 请求企业类别二级级分类
     * @param Request $request
     * ====================================================================
     * ||
     * || 请求企业类别二级级分类
     * ||
     * ====================================================================
     */
    public function get_supplier_two(Request $request){
        $data = UtilService::getMore([['id',0]],$request);
        $dataCateA = [];
        $dataCateE = SupplierCategory::pidBySidList($data['id']);
        if($dataCateE) $dataCateE = $dataCateE->toArray();
        $dataCate = array_merge_recursive($dataCateA,$dataCateE);
        return JsonService::successful($dataCate);
    }

    public function user_sign()
    {
        $signed = UserSign::checkUserSigned($this->userInfo['uid']);
        if($signed) return JsonService::fail('已签到');
        if(false !== $integral = UserSign::sign($this->userInfo))
            return JsonService::successful('签到获得'.floatval($integral).'积分');
        else
            return JsonService::fail('签到失败!');
    }

    public function set_cart($productId = '',$cartNum = 1,$uniqueId = '')
    {
        if(!$productId || !is_numeric($productId)) return $this->failed('参数错误!');
        $res = StoreCart::setCart($this->userInfo['uid'],$productId,$cartNum,$uniqueId,'product');
        if(!$res)
            return $this->failed(StoreCart::getErrorInfo('加入购物车失败!'));
        else{
            HookService::afterListen('store_product_set_cart_after',$res,$this->userInfo,false,StoreProductBehavior::class);
            return $this->successful('ok',['cartId'=>$res->id]);
        }
    }

    public function now_buy($productId = '',$cartNum = 1,$uniqueId = '',$combinationId = 0,$secKillId=0,$bargainId = 0)
    {
        if($productId == '') return $this->failed('参数错误!');
        if($bargainId && StoreBargainUserHelp::getSurplusPrice($bargainId,$this->userInfo['uid'])) return JsonService::fail('请先砍价');
        $res = StoreCart::setCart($this->userInfo['uid'],$productId,$cartNum,$uniqueId,'product',1,$combinationId,$secKillId,$bargainId);
        if(!$res)
            return $this->failed(StoreCart::getErrorInfo('订单生成失败!'));
        else {
            return $this->successful('ok', ['cartId' => $res->id]);
        }
    }

    public function like_product($productId = '',$category = 'product')
    {
        if(!$productId || !is_numeric($productId)) return $this->failed('参数错误!');
        $res = StoreProductRelation::productRelation($productId,$this->userInfo['uid'],'like',$category);
        if(!$res)
            return $this->failed(StoreProductRelation::getErrorInfo('点赞失败!'));
        else
            return $this->successful();
    }

    public function unlike_product($productId = '',$category = 'product')
    {

        if(!$productId || !is_numeric($productId)) return $this->failed('参数错误!');
        $res = StoreProductRelation::unProductRelation($productId,$this->userInfo['uid'],'like',$category);
        if(!$res)
            return $this->failed(StoreProductRelation::getErrorInfo('取消点赞失败!'));
        else
            return $this->successful();
    }

    public function collect_product($productId,$category = 'product')
    {
        if(!$productId || !is_numeric($productId)) return $this->failed('参数错误!');
        $res = StoreProductRelation::productRelation($productId,$this->userInfo['uid'],'collect',$category);
        if(!$res)
            return $this->failed(StoreProductRelation::getErrorInfo('收藏失败!'));
        else
            return $this->successful();
    }

    public function uncollect_product()
    {
        $where = UtilService::getMore([
            ['uid',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);

        if(!$where['id'] || !is_numeric($where['id'])) return $this->failed('参数错误!');
        $res = StoreProductRelation::where($where)->delete();
        if(!$res)
            return $this->failed(StoreProductRelation::getErrorInfo('取消收藏失败!'));
        else
            return $this->successful('取消收藏成功!');
    }

    public function get_cart_num()
    {
        return JsonService::successful('ok',StoreCart::getUserCartNum($this->userInfo['uid'],'product'));
    }

    public function get_cart_list()
    {
        return JsonService::successful('ok',StoreCart::getUserProductCartList($this->userInfo['uid']));
    }

    public function change_cart_num($cartId = '',$cartNum = '')
    {
        if(!$cartId || !$cartNum || !is_numeric($cartId) || !is_numeric($cartNum)) return JsonService::fail('参数错误!');
        StoreCart::changeUserCartNum($cartId,$cartNum,$this->userInfo['uid']);
        return JsonService::successful();
    }

    public function remove_cart($ids='')
    {
        if(!$ids) return JsonService::fail('参数错误!');
        StoreCart::removeUserCart($this->userInfo['uid'],$ids);
        return JsonService::successful();
    }


    public function get_use_coupon()
    {
        return JsonService::successful('',StoreCouponUser::getUserValidCoupon($this->userInfo['uid']));
    }

    /**
     * @param int $first
     * @param int $limit
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 请求收藏数据
     */
    public function get_user_collect_product($first = 0,$limit = 8)
    {
        $data = StoreProductRelation::where('uid',$this->userInfo['uid'])
            ->where('type','collect')
            ->order('add_time DESC')
            ->limit($first,$limit)->select()->toArray();

        foreach ($data as $key=>$val){
            $data[$key]['linkData'] = self::get_user_collect_product_value($val['category'],$val['product_id']);
        }

        $code = 0;
        $msg = '请求成功';
        $count = 100;

        echo json_encode(compact('code','msg','count','data'));
    }

    /**
     * @param $data
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 处理收藏事件
     */
    public function get_user_collect_product_value($data,$id){
        switch ($data){
            case 'new_goods':
                $info = NewProduct::where('id',$id)->field('id,name,image')->find();
                $linkData['image'] = $info['image'];
                $linkData['store_name'] = $info['name'];
                $linkData['type'] = '新品';
                $linkData['price'] = 0;
                $linkData['url'] = 'goods/detail';
                $linkData['color'] = '#ffcc00';
                return $linkData;
                break;
            case 'supply':
                $info = Supply::where('id',$id)->field('id,title,image')->find();
                $linkData['image'] = $info['image'];
                $linkData['store_name'] = $info['title'];
                $linkData['type'] = '招商';
                $linkData['price'] = 0;
                $linkData['url'] = 'supply/detail';
                $linkData['color'] = '#ff0000';
                return $linkData;
                break;
            case 'article':
                $info = Article::where('id',$id)->field('id,title,image,price')->find();
                $linkData['image'] = $info['image'];
                $linkData['store_name'] = $info['title'];
                $linkData['type'] = '资讯';
                $linkData['price'] = $info['price'];
                $linkData['url'] = 'article/detail';
                $linkData['color'] = '#ffccff';
                return $linkData;
                break;
            case 'knowledge':
                $info = Knowledge::where('id',$id)->field('id,title,image,price')->find();
                $linkData['image'] = $info['image'];
                $linkData['store_name'] = $info['title'];
                $linkData['type'] = '干货';
                $linkData['price'] = $info['price'];
                $linkData['url'] = 'knowledge/detail';
                $linkData['color'] = '#ff00ff';
                return $linkData;
                break;
            case 'company':
                $info = Company::where('id',$id)->field('id,title,logo')->find();
                $linkData['image'] = $info['logo'];
                $linkData['store_name'] = $info['title'];
                $linkData['type'] = '企业';
                $linkData['price'] = 0;
                $linkData['url'] = 'company/detail';
                $linkData['color'] = '#66ff00';
                return $linkData;
                break;
            case 'brand':
                $info = Brand::where('id',$id)->field('id,name,logo')->find();
                $linkData['image'] = $info['logo'];
                $linkData['store_name'] = $info['name'];
                $linkData['type'] = '品牌';
                $linkData['price'] = 0;
                $linkData['url'] = 'brand/detail';
                $linkData['color'] = '#66ffff';
                return $linkData;
                break;
            case 'dealers':
                $info = Dealers::where('id',$id)->field('id,title,logo')->find();
                $linkData['image'] = $info['logo'];
                $linkData['store_name'] = $info['title'];
                $linkData['type'] = '经销商';
                $linkData['price'] = 0;
                $linkData['url'] = 'dealers/detail';
                $linkData['color'] = '#6633ff';
                return $linkData;
                break;
            case 'supplier':
                $info = Supplier::where('id',$id)->field('id,name,logo')->find();
                $linkData['image'] = $info['logo'];
                $linkData['store_name'] = $info['name'];
                $linkData['type'] = '供应商';
                $linkData['price'] = 0;
                $linkData['url'] = 'supplier/detail';
                $linkData['color'] = '#660000';
                return $linkData;
                break;
            case 'job':
                $info = JobPosition::where('id',$id)->field('id,uid,address,position,salary')->find();
                $company = Company::where('uid',$info['uid'])->field('id,title,logo')->find();

                $position_info = Position::where(array('id'=>$info['position']))->find();
                if($position_info['pid']) $positionOne = Position::where(array('id'=>$position_info['pid']))->find();

                $linkData['image'] = $company['logo'];
                $linkData['store_name'] = $positionOne ? $positionOne['name'].' · '.$position_info['name']:$position_info['name'];
                $linkData['type'] = '招聘';
                $linkData['price'] = $info['salary'];
                $linkData['url'] = 'job/detail';
                $linkData['color'] = '#0066ff';
                return $linkData;
                break;
            case 'resume':
                $info = Resume::where('id',$id)->field('id,uid,status')->find();
                $expect = ResumeExpect::where(array('uid'=>$info['uid']))->order('id asc')->find();
                $user = User::where('uid',$info['uid'])->field('uid,realname,avatar')->find();

                $position = Position::where(array('id'=>$expect['position']))->find();
                if($position['pid']) {
                    $positionOne = Position::where(array('id'=>$position['pid']))->find();
                    if($positionOne){
                        $info['position'] = $positionOne['name'].' · '.$position['name'];
                    }
                } else {
                    $info['position'] = $position['name'];
                }

                $linkData['image'] = $user['avatar'];
                $linkData['store_name'] = $info['position'];
                $linkData['type'] = '求职';
                $linkData['price'] = $expect['salary'];
                $linkData['url'] = 'resume/detail';
                $linkData['color'] = '#00cc33';
                return $linkData;
                break;
            default:
                $info = StoreProduct::where('id',$id)->field('id,store_name,image,price')->find();
                $linkData['image'] = $info['image'];
                $linkData['store_name'] = $info['store_name'];
                $linkData['type'] = '积分商品';
                $linkData['price'] = $info['price'];
                $linkData['url'] = 'store/detail';
                $linkData['color'] = '#8DB6CD';
                return $linkData;
        }
    }

    public function remove_user_collect_product($productId = '')
    {
        if(!$productId || !is_numeric($productId)) return JsonService::fail('参数错误!');
        StoreProductRelation::unProductRelation($productId,$this->userInfo['uid'],'collect','product');
        return JsonService::successful();
    }

    public function set_user_default_address($addressId = '')
    {
        if(!$addressId || !is_numeric($addressId)) return JsonService::fail('参数错误!');
        if(!UserAddress::be(['is_del'=>0,'id'=>$addressId,'uid'=>$this->userInfo['uid']]))
            return JsonService::fail('地址不存在!');
        $res = UserAddress::setDefaultAddress($addressId,$this->userInfo['uid']);
        if(!$res)
            return JsonService::fail('地址不存在!');
        else
            return JsonService::successful();
    }

    public function edit_user_address()
    {
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $addressInfo = UtilService::postMore([
            ['address',[]],
            ['is_default',false],
            ['real_name',''],
            ['post_code',''],
            ['phone',''],
            ['detail',''],
            ['id',0]
        ],$request);
        $addressInfo['province'] = $addressInfo['address']['province'];
        $addressInfo['city'] = $addressInfo['address']['city'];
        $addressInfo['district'] = $addressInfo['address']['district'];
        $addressInfo['is_default'] = $addressInfo['is_default'] == true ? 1 : 0;
        $addressInfo['uid'] = $this->userInfo['uid'];
        unset($addressInfo['address']);

        if($addressInfo['id'] && UserAddress::be(['id'=>$addressInfo['id'],'uid'=>$this->userInfo['uid'],'is_del'=>0])){
            $id = $addressInfo['id'];
            unset($addressInfo['id']);
            if(UserAddress::edit($addressInfo,$id,'id')){
                if($addressInfo['is_default'])
                    UserAddress::setDefaultAddress($id,$this->userInfo['uid']);
                return JsonService::successful();
            }else
                return JsonService::fail('编辑收货地址失败!');
        }else{
            if($address = UserAddress::set($addressInfo)){
                if($addressInfo['is_default'])
                    UserAddress::setDefaultAddress($address->id,$this->userInfo['uid']);
                return JsonService::successful();
            }else
                return JsonService::fail('添加收货地址失败!');
        }


    }

    public function user_default_address()
    {
        $defaultAddress = UserAddress::getUserDefaultAddress($this->userInfo['uid'],'id,real_name,phone,province,city,district,detail,is_default');
        if($defaultAddress)
            return JsonService::successful('ok',$defaultAddress);
        else
            return JsonService::successful('empty',[]);
    }

    public function remove_user_address($addressId = '')
    {
        if(!$addressId || !is_numeric($addressId)) return JsonService::fail('参数错误!');
        if(!UserAddress::be(['is_del'=>0,'id'=>$addressId,'uid'=>$this->userInfo['uid']]))
            return JsonService::fail('地址不存在!');
        if(UserAddress::edit(['is_del'=>'1'],$addressId,'id'))
            return JsonService::successful();
        else
            return JsonService::fail('删除地址失败!');
    }

    /**
     * 创建订单
     * @param string $key
     * @return \think\response\Json
     */
    public function create_order($key = '')
    {
        if(!$key) return JsonService::fail('参数错误!');
        if(StoreOrder::be(['order_id|unique'=>$key,'uid'=>$this->userInfo['uid'],'is_del'=>0]))
            return JsonService::status('extend_order','订单已生成',['orderId'=>$key,'key'=>$key]);
        list($addressId,$couponId,$payType,$useIntegral,$mark,$combinationId,$pinkId,$seckill_id,$bargainId) = UtilService::postMore([
            'addressId','couponId','payType','useIntegral','mark',['combinationId',0],['pinkId',0],['seckill_id',0],['bargainId',0]
        ],Request::instance(),true);
        $payType = strtolower($payType);
        if($bargainId) StoreBargainUser::setBargainUserStatus($bargainId,$this->userInfo['uid']);//修改砍价状态
        if($pinkId) if(StorePink::getIsPinkUid($pinkId)) return JsonService::status('ORDER_EXIST','订单生成失败，你已经在该团内不能再参加了',['orderId'=>StoreOrder::getStoreIdPink($pinkId)]);
        if($pinkId) if(StoreOrder::getIsOrderPink($pinkId)) return JsonService::status('ORDER_EXIST','订单生成失败，你已经参加该团了，请先支付订单',['orderId'=>StoreOrder::getStoreIdPink($pinkId)]);
        $order = StoreOrder::cacheKeyCreateOrder($this->userInfo['uid'],$key,$addressId,$payType,$useIntegral,$couponId,$mark,$combinationId,$pinkId,$seckill_id,$bargainId);
        $orderId = $order['order_id'];
        $info = compact('orderId','key');
        if($orderId){
            if($payType == 'weixin'){
                $orderInfo = StoreOrder::where('order_id',$orderId)->find();
                if(!$orderInfo || !isset($orderInfo['paid'])) exception('支付订单不存在!');
                if($orderInfo['paid']) exception('支付已支付!');
                if(bcsub((float)$orderInfo['pay_price'],0,2) <= 0){
                    if(StoreOrder::jsPayPrice($orderId,$this->userInfo['uid']))
                        return JsonService::status('success','微信支付成功',$info);
                    else
                        return JsonService::status('pay_error',StoreOrder::getErrorInfo());
                }else{
                    try{
                        $jsConfig = StoreOrder::jsPay($orderId);
                    }catch (\Exception $e){
                        return JsonService::status('pay_error',$e->getMessage(),$info);
                    }
                    $info['jsConfig'] = $jsConfig;
                    return JsonService::status('wechat_pay','订单创建成功',$info);
                }
            }else if($payType == 'yue'){
                if(StoreOrder::yuePay($orderId,$this->userInfo['uid']))
                    return JsonService::status('success','余额支付成功',$info);
                else
                    return JsonService::status('pay_error',StoreOrder::getErrorInfo());
            }else if($payType == 'offline'){
                StoreOrder::createOrderTemplate($order);
                return JsonService::status('success','订单创建成功',$info);
            }
        }else{
            return JsonService::fail(StoreOrder::getErrorInfo('订单生成失败!'));
        }
    }

    public function get_user_order_list($type = '',$first = 0, $limit = 8,$search = '')
    {
//        StoreOrder::delCombination();//删除拼团未支付订单
        $type=='null' && $type='';
        if($search){
            $order = StoreOrder::searchUserOrder($this->userInfo['uid'],$search)?:[];
            $list = $order == false ? [] : [$order];
        }else{
            if(!is_numeric($type)) $type = '';
            $list = StoreOrder::getUserOrderList($this->userInfo['uid'],$type,$first,$limit);
        }
        foreach ($list as $k=>$order){
            $list[$k] = StoreOrder::tidyOrder($order,true);
            if($list[$k]['_status']['_type'] == 3){
                foreach ($order['cartInfo']?:[] as $key=>$product){
                    $list[$k]['cartInfo'][$key]['is_reply'] = StoreProductReply::isReply($product['unique'],'product');
                }
            }
        }
        return JsonService::successful($list);
    }

    public function user_remove_order($uni = '')
    {
        if(!$uni) return JsonService::fail('参数错误!');
        $res = StoreOrder::removeOrder($uni,$this->userInfo['uid']);
        if($res)
            return JsonService::successful();
        else
            return JsonService::fail(StoreOrder::getErrorInfo());
    }

    /**
     * 支付订单
     * @param string $uni
     * @return \think\response\Json
     */
    public function pay_order($uni = '')
    {
        if(!$uni) return JsonService::fail('参数错误!');
        $order= StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni);
        if(!$order) return JsonService::fail('订单不存在!');
        if($order['paid']) return JsonService::fail('该订单已支付!');
        if($order['pink_id']) if(StorePink::isPinkStatus($order['pink_id'])) return JsonService::fail('该订单已失效!');
        if($order['pay_type'] == 'weixin'){
            try{
                $jsConfig = StoreOrder::jsPay($order);
            }catch (\Exception $e){
                return JsonService::fail($e->getMessage());
            }
            return JsonService::status('wechat_pay',['jsConfig'=>$jsConfig,'order_id'=>$order['order_id']]);
        }else if($order['pay_type'] == 'yue'){
            if($res = StoreOrder::yuePay($order['order_id'],$this->userInfo['uid']))
                return JsonService::successful('余额支付成功');
            else
                return JsonService::fail(StoreOrder::getErrorInfo());
        }else if($order['pay_type'] == 'offline'){
            StoreOrder::createOrderTemplate($order);
            return JsonService::successful('订单创建成功');
        }
    }

    public function apply_order_refund($uni = '',$text = '')
    {
        if(!$uni || $text == '') return JsonService::fail('参数错误!');
        $res = StoreOrder::orderApplyRefund($uni,$this->userInfo['uid'],$text);
        if($res)
            return JsonService::successful();
        else
            return JsonService::fail(StoreOrder::getErrorInfo());
    }

    public function user_take_order($uni = '')
    {
        if(!$uni) return JsonService::fail('参数错误!');

        $res = StoreOrder::takeOrder($uni,$this->userInfo['uid']);
        if($res)
            return JsonService::successful();
        else
            return JsonService::fail(StoreOrder::getErrorInfo());
    }

    public function user_wechat_recharge($price = 0)
    {
        if(!$price || $price <=0) return JsonService::fail('参数错误');
        $storeMinRecharge = SystemConfigService::get('store_user_min_recharge');
        if($price < $storeMinRecharge) return JsonService::fail('充值金额不能低于'.$storeMinRecharge);
        $rechargeOrder = UserRecharge::addRecharge($this->userInfo['uid'],$price);
        if(!$rechargeOrder) return JsonService::fail('充值订单生成失败!');
        try{
            return JsonService::successful(UserRecharge::jsPay($rechargeOrder));
        }catch (\Exception $e){
            return JsonService::fail($e->getMessage());
        }
    }

    public function user_balance_list($first = 0,$limit = 8)
    {
        $list = UserBill::where('uid',$this->userInfo['uid'])->where('category','now_money')
            ->field('mark,pm,number,add_time')
            ->where('status',1)->order('add_time DESC')->limit($first,$limit)->select()->toArray();
        foreach ($list as &$v){
            $v['add_time'] = date('Y/m/d H:i',$v['add_time']);
        }
        return JsonService::successful($list);
    }

    public function user_integral_list($first = 0,$limit = 8)
    {
        $list = UserBill::where('uid',$this->userInfo['uid'])->where('category','integral')
        ->field('mark,pm,number,add_time')
        ->where('status',1)->order('add_time DESC')->limit($first,$limit)->select()->toArray();
        foreach ($list as &$v){
            $v['add_time'] = date('Y/m/d H:i',$v['add_time']);
            $v['number'] = floatval($v['number']);
        }
        return JsonService::successful($list);

    }

    public function user_comment_product($unique = '')
    {
        if(!$unique) return JsonService::fail('参数错误!');
        $cartInfo = StoreOrderCartInfo::where('unique',$unique)->find();
        $uid = $this->userInfo['uid'];
        if(!$cartInfo || $uid != $cartInfo['cart_info']['uid']) return JsonService::fail('评价产品不存在!');
        if(StoreProductReply::be(['oid'=>$cartInfo['oid'],'unique'=>$unique]))
            return JsonService::fail('该产品已评价!');
        $group = UtilService::postMore([
            ['comment',''],['pics',[]],['product_score',5],['service_score',5]
        ],Request::instance());
        $group['comment'] = htmlspecialchars(trim($group['comment']));
        if(sensitive_words_filter($group['comment'])) return JsonService::fail('请注意您的用词，谢谢！！');
        if($group['product_score'] < 1) return JsonService::fail('请为产品评分');
        else if($group['service_score'] < 1) return JsonService::fail('请为商家服务评分');
        $group = array_merge($group,[
            'uid'=>$uid,
            'oid'=>$cartInfo['oid'],
            'unique'=>$unique,
            'product_id'=>$cartInfo['product_id'],
            'reply_type'=>'product'
        ]);
        StoreProductReply::beginTrans();
        $res = StoreProductReply::reply($group,'product');
        if(!$res) {
            StoreProductReply::rollbackTrans();
            return JsonService::fail('评价失败!');
        }
        try{
            HookService::listen('store_product_order_reply',$group,$cartInfo,false,StoreProductBehavior::class);
        }catch (\Exception $e){
            StoreProductReply::rollbackTrans();
            return JsonService::fail($e->getMessage());
        }
        StoreProductReply::commitTrans();
        return JsonService::successful();
    }

    public function get_product_category()
    {
        $parentCategory = StoreCategory::pidByCategory(0,'id,cate_name')->toArray();
        foreach ($parentCategory as $k=>$category){
            $category['child'] = StoreCategory::pidByCategory($category['id'],'id,cate_name')->toArray();
            $parentCategory[$k] = $category;
        }
        return JsonService::successful($parentCategory);
    }

    public function get_spread_list($first = 0,$limit = 20)
    {
        $list = User::where('spread_uid',$this->userInfo['uid'])->field('uid,nickname,avatar,add_time')->limit($first,$limit)->order('add_time DESC')->select()->toArray();
        foreach ($list as $k=>$user){
            $list[$k]['add_time'] = date('Y/m/d',$user['add_time']);
        }
        return JsonService::successful($list);
    }

    public function get_product_list($keyword = '', $cId = 0,$sId = 0,$priceOrder = '', $salesOrder = '', $news = 0, $first = 0, $limit = 8)
    {
        if(!empty($keyword)){
            $encodedData = str_replace(' ','+',$keyword);
            $keyword = base64_decode(htmlspecialchars($encodedData));
        }
        $model = StoreProduct::validWhere();
        if($cId && $sId){
            $product_ids=\think\Db::name('store_product_cate')->where('cate_id',$sId)->column('product_id');
            if(count($product_ids))
                $model=$model->where('id',"in",$product_ids);
            else
                $model=$model->where('cate_id',-1);
        }elseif($cId){
            $sids = StoreCategory::pidBySidList($cId)?:[];
            $sids[] = $cId;
            $model->where('cate_id','IN',$sids);
        }
        if(!empty($keyword)) $model->where('keyword|store_name','LIKE',"%$keyword%");
        if($news) $model->where('is_new',1);
        $baseOrder = '';
        if($priceOrder) $baseOrder = $priceOrder == 'desc' ? 'price DESC' : 'price ASC';
//        if($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC';
        if($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'ficti DESC' : 'ficti ASC';
        if($baseOrder) $baseOrder .= ', ';
        $model->order($baseOrder.'sort DESC, add_time DESC');
        $list = $model->limit($first,$limit)->field('id,store_name,image,sales,ficti,price,stock')->select()->toArray();
        if($list) setView($this->uid,0,$sId,'search','product',$keyword);
        return JsonService::successful($list);
    }

    public function user_get_coupon($couponId = '')
    {
        if(!$couponId || !is_numeric($couponId)) return JsonService::fail('参数错误!');
        if(StoreCouponIssue::issueUserCoupon($couponId,$this->userInfo['uid'])){
            return JsonService::successful('领取成功');
        }else{
            return JsonService::fail(StoreCouponIssue::getErrorInfo('领取失败!'));
        }
    }

    public function product_reply_list($productId = '',$first = 0,$limit = 8, $filter = 'all')
    {
        if(!$productId || !is_numeric($productId)) return JsonService::fail('参数错误!');
        $list = StoreProductReply::getProductReplyList($productId,$filter,$first,$limit);
        return JsonService::successful($list);
    }

    public function product_attr_detail($productId = '')
    {
        if(!$productId || !is_numeric($productId)) return JsonService::fail('参数错误!');
        list($productAttr,$productValue) = StoreProductAttr::getProductAttrDetail($productId);
        return JsonService::successful(compact('productAttr','productValue'));

    }

    public function user_address_list()
    {
        $list = UserAddress::getUserValidAddressList($this->userInfo['uid'],'id,real_name,phone,province,city,district,detail,is_default');
        return JsonService::successful($list);
    }

    public function get_notice_list($page = 0, $limit = 8)
    {
        $list = UserNotice::getNoticeList($this->userInfo['uid'],$page,$limit);
        return JsonService::successful($list);
    }
    public function see_notice($nid){
        UserNotice::seeNotice($this->userInfo['uid'],$nid);
        return JsonService::successful();
    }

    public function refresh_msn(Request $request)
    {
        $params = $request->post();
        $remind_where = "mer_id = ".$params["mer_id"]." AND uid = ".$params["uid"]." AND to_uid = ".$params["to_uid"]." AND type = 0 AND remind = 0";
        $remind_list = StoreServiceLog::where($remind_where)->order("add_time asc")->select();
        foreach ($remind_list as $key => $value) {
            if(time() - $value["add_time"] > 3){
                StoreServiceLog::edit(array("remind"=>1),$value["id"]);
                $now_user = StoreService::field("uid,nickname")->where(array("uid"=>$params["uid"]))->find();
                if(!$now_user)$now_user = User::field("uid,nickname")->where(array("uid"=>$params["uid"]))->find();
                if($params["to_uid"]) {
                    $head = '您有新的消息，请注意查收！';
                    WechatTemplateService::sendTemplate(WechatUser::uidToOpenid($params["to_uid"]),WechatTemplateService::SERVICE_NOTICE,[
                        'first'=>$head,
                        'keyword1'=>$now_user["nickname"],
                        'keyword2'=>"客服提醒",
                        'keyword3'=> preg_replace('/<img.*? \/>/','[图片]',$value["msn"]),
                        'keyword4'=>date('Y-m-d H:i:s',time()),
                        'remark'=>'点击立即查看消息'
                    ],Url::build('service/service_ing',['to_uid'=>$now_user["uid"],'mer_id'=>$params["mer_id"]],true,true));
                }
            }
        }
        $where = "mer_id = ".$params["mer_id"]." AND uid = ".$params["to_uid"]." AND to_uid = ".$params["uid"]." AND type = 0";
        $list = StoreServiceLog::where($where)->order("add_time asc")->select()->toArray();
        $ids = [];
        foreach ($list as $key => $value) {
            //设置发送人与接收人区别
            if($value["uid"] == $params["uid"])
                $list[$key]['my'] = "my";
            else
                $list[$key]['my'] = "to";

            array_push($ids,$value["id"]);
        }

        //设置这些消息为已读
        StoreServiceLog::where(array("id"=>array("in",$ids)))->update(array("type"=>1,"remind"=>1));
        return JsonService::successful($list);
    }

    public function add_msn(Request $request){
        $params = $request->post();
        if($params["type"] == "html")
            $data["msn"] = htmlspecialchars_decode($params["msn"]);
        else
            $data["msn"] = $params["msn"];
        $data["uid"] = $params["uid"];
        $data["to_uid"] = $params["to_uid"];
        $data["mer_id"] = $params["mer_id"] > 0 ? $params["mer_id"] : 0;
        $data["add_time"] = time();
        StoreServiceLog::set($data);
        return JsonService::successful();
    }

    public function get_msn(Request $request){
        $params = $request->post();
        $size = 10;
        $page = $params["page"]>=0 ? $params["page"] : 1;
        $where = "(mer_id = ".$params["mer_id"]." AND uid = ".$params["uid"]." AND to_uid = ".$params["to_uid"].") OR (mer_id = ".$params["mer_id"]." AND uid = ".$params["to_uid"]." AND to_uid = ".$params["uid"].")";
        $list = StoreServiceLog::where($where)->limit(($page-1)*$size,$size)->order("add_time desc")->select()->toArray();
        foreach ($list as $key => $value) {
            //设置发送人与接收人区别
            if($value["uid"] == $params["uid"])
                $list[$key]['my'] = "my";
            else
                $list[$key]['my'] = "to";

            //设置这些消息为已读
            if($value["uid"] == $params["to_uid"] && $value["to_uid"] == $params["uid"])StoreServiceLog::edit(array("type"=>1,"remind"=>1),$value["id"]);
        }
        $list=array_reverse($list);
        return JsonService::successful($list);
    }

    public function refresh_msn_new(Request $request){
        $params = $request->post();
        $now_user = User::getUserInfo($this->userInfo['uid']);
        if($params["last_time"] > 0)
            $where = "(uid = ".$now_user["uid"]." OR to_uid = ".$now_user["uid"].") AND add_time>".$params["last_time"];
        else
            $where = "uid = ".$now_user["uid"]." OR to_uid = ".$now_user["uid"];


        $msn_list = StoreServiceLog::where($where)->order("add_time desc")->select()->toArray();
        $info_array = $list = [];
        foreach ($msn_list as $key => $value){
            $to_uid = $value["uid"] == $now_user["uid"] ? $value["to_uid"] : $value["uid"];
            if(!in_array(["to_uid"=>$to_uid,"mer_id"=>$value["mer_id"]],$info_array)){
                $info_array[count($info_array)] = ["to_uid"=>$to_uid,"mer_id"=>$value["mer_id"]];

                $to_user = StoreService::field("uid,nickname,avatar")->where(array("uid"=>$to_uid))->find();
                if(!$to_user)$to_user = User::field("uid,nickname,avatar")->where(array("uid"=>$to_uid))->find();
                $to_user["mer_id"] = $value["mer_id"];
                $to_user["mer_name"] = '';
                $value["to_info"] = $to_user;
                $value["count"] = StoreServiceLog::where(array("mer_id"=>$value["mer_id"],"uid"=>$to_uid,"to_uid"=>$now_user["uid"],"type"=>0))->count();
                $list[count($list)] = $value;
            }
        }
        return JsonService::successful($list);
    }

    public function get_user_brokerage_list($uid, $first = 0,$limit = 8)
    {
        if(!$uid)
            return $this->failed('用户不存在');
        $list = UserBill::field('A.mark,A.add_time,A.number,A.pm')->alias('A')->limit($first,$limit)
            ->where('A.category','now_money')->where('A.type','brokerage')
            ->where('A.uid',$this->userInfo['uid'])
            ->join('__STORE_ORDER__ B','A.link_id = B.id AND B.uid = '.$uid)->select()->toArray();
        return JsonService::successful($list);
    }

    public function user_extract()
    {
        if(UserExtract::userExtract($this->userInfo,UtilService::postMore([
            ['type','','','extract_type'],'real_name','alipay_code','bank_code','bank_address',['price','','','extract_price']
        ])))
            return JsonService::successful('申请提现成功!');
        else
            return JsonService::fail(Extract::getErrorInfo());
    }

    public function get_issue_coupon_list($limit = 2)
    {
        $list = StoreCouponIssue::validWhere('A')->join('__STORE_COUPON__ B','A.cid = B.id')
            ->field('A.*,B.coupon_price,B.use_min_price')->order('B.sort DESC,A.id DESC')->limit($limit)->select()->toArray()?:[];
        $list_coupon=[];
        foreach ($list as $k=>&$v){
            if(!($v['is_use']=StoreCouponIssueUser::be(['uid'=>$this->userInfo['uid'],'issue_coupon_id'=>$v['id']])) && $v['total_count'] > 0 && $v['remain_count'] >0){
                array_push($list_coupon,$v);
            }
        }
        return JsonService::successful($list_coupon);
    }

    public function clear_cache($uni = '')
    {
        if($uni)CacheService::clear();
    }

    /**
     * 获取今天正在拼团的人的头像和名称
     * @return \think\response\Json
     */
    public function get_pink_second_one(){
        $addTime =  mt_rand(time()-30000,time());
        $storePink = StorePink::where('p.add_time','GT',$addTime)->alias('p')->where('p.status',1)->join('User u','u.uid=p.uid')->field('u.nickname,u.avatar as src')->find();
        return JsonService::successful($storePink);
    }

    public function order_details($uni = ''){

        if(!$uni) return JsonService::fail('参数错误!');
        $order = StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni);
        if(!$order) return JsonService::fail('订单不存在!');
        $order = StoreOrder::tidyOrder($order,true);
        $res = array();
        foreach ($order['cartInfo'] as $v) {
            if($v['combination_id']) return JsonService::fail('拼团产品不能再来一单，请在拼团产品内自行下单!');
            else  $res[] = StoreCart::setCart($this->userInfo['uid'], $v['product_id'], $v['cart_num'], isset($v['productInfo']['attrInfo']['unique']) ? $v['productInfo']['attrInfo']['unique'] : '', 'product', 0, 0);
        }
        $cateId = [];
        foreach ($res as $v){
            if(!$v) return JsonService::fail('再来一单失败，请重新下单!');
            $cateId[] = $v['id'];
        }
        return JsonService::successful('ok',implode(',',$cateId));

    }


    /**
     * 帮好友砍价
     * @param int $bargainId
     * @param int $bargainUserId
     * @return \think\response\Json
     */
    public function set_bargain_help(){
        list($bargainId,$bargainUserId) = UtilService::postMore([
            'bargainId','bargainUserId'],Request::instance(),true);
        if(!$bargainId || !$bargainUserId) return JsonService::fail('参数错误');
        $res = StoreBargainUserHelp::setBargainUserHelp($bargainId,$bargainUserId,$this->userInfo['uid']);
        if($res) {
            if(!StoreBargainUserHelp::getSurplusPrice($bargainId,$bargainUserId)){//砍价成功，发模板消息
                $bargainUserTableId = StoreBargainUser::getBargainUserTableId($bargainId,$bargainUserId);
                $bargain = StoreBargain::where('id',$bargainId)->find()->toArray();
                $bargainUser = StoreBargainUser::where('id',$bargainUserTableId)->find()->toArray();
            }
            return JsonService::status('SUCCESS','砍价成功');
        }
        else return JsonService::status('ERROR','砍价失败，请稍后再帮助朋友砍价');
    }

    /**
     * 砍价分享添加次数
     * @param int $bargainId
     */
    public function add_bargain_share($bargainId = 0){
        if(!$bargainId) return JsonService::successful();
        StoreBargain::addBargainShare($bargainId);
        return JsonService::successful();
    }

}