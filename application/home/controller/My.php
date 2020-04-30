<?php
/**
 *
 * @author: 招宝通
 */

namespace app\home\controller;


use Api\Express;
use app\admin\model\system\SystemConfig;
use app\home\model\store\StoreBargainUser;
use app\home\model\store\StoreBargainUserHelp;
use app\home\model\store\StoreCombination;
use app\home\model\store\StoreOrderCartInfo;
use app\home\model\store\StorePink;
use app\home\model\store\StoreProduct;
use app\home\model\store\StoreProductRelation;
use app\home\model\store\StoreProductReply;
use app\home\model\store\StoreCouponUser;
use app\home\model\store\StoreOrder;
use app\home\model\user\User;
use app\home\model\user\UserBill;
use app\home\model\user\UserExtract;
use app\home\model\user\UserNotice;

use app\ebapi\model\article\Article AS ArticleModel;
use app\ebapi\model\article\ArticleCategory;

use app\ebapi\model\knowledge\Knowledge;
use app\ebapi\model\knowledge\KnowledgeCategory;

use app\ebapi\model\user\UserSubion;

use app\core\util\GroupDataService;
use app\home\model\user\UserAddress;
use app\home\model\user\UserSign;
use service\CacheService;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use app\admin\model\system\SystemAttachment;
use app\core\util\SystemConfigService;

use think\Request;
use think\Url;

class My extends AuthController
{

    public function user_cut(){
        $list = StoreBargainUser::getBargainUserAll($this->userInfo['uid']);
        if($list){
            foreach ($list as $k=>$v){
                $list[$k]['con_price'] = bcsub($v['bargain_price'],$v['price'],2);
                $list[$k]['helpCount'] = StoreBargainUserHelp::getBargainUserHelpPeopleCount($v['bargain_id'],$this->userInfo['uid']);
            }
            $this->assign('list',$list);
        }else return $this->failed('暂无参与砍价',Url::build('My/index'));
        return $this->fetch();
    }

    public function index(){
        $this->assign([
            'menus'=>GroupDataService::getData('my_index_menu')?:[],
            'orderStatusNum'=>StoreOrder::getOrderStatusNum($this->userInfo['uid']),
            'notice'=>UserNotice::getNotice($this->userInfo['uid']),
            'statu' =>(int)SystemConfig::getValue('store_brokerage_statu'),
        ]);
        return $this->fetch();
    }

    /**
     * @return mixed
     * 我的资讯列表
     * ====================================================================
     * ||
     * ||
     * || 我的资讯管理 B
     * ||
     * ||
     * ====================================================================
     */
    public function article() {
        return $this->fetch();
    }

    /**
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 请求我的资讯列表
     */
    public function article_list(){
        $where = Util::getMore([
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

        $data = ArticleModel::where($where)->where('status',1)->where('keyword|title','LIKE',htmlspecialchars("%$keyword%"))->field('id,uid,cid,title,image,phone,author,price,status,is_show,is_price,is_consent,add_time')->order('id desc')->page((int)$page,(int)$limit)->select();
        foreach ($data as $item=>$value){
            $cate_nam = ArticleCategory::where(array('id'=>$value['cid']))->find();
            $data[$item]['cname'] = $cate_nam['title'];
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
     * 添加我的资讯
     */
    public function article_create() {
        $cat_list = ArticleCategory::getCategory()->toArray();
        $where = Util::getMore([
            ['uid',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        if($where['id']){
            $data = ArticleModel::where($where)->find()->toArray();
            if($data) {
                $data['arr_image'] = json_decode($data['slider_image'],true);
                $data['slider_image'] = implode(",",json_decode($data['slider_image'],true));
            }
        } else {
            $data = array('cid'=>0,
                'tag'=>'个人',
                'title'=>'',
                'phone'=>'',
                'author'=>'',
                'price'=>'',
                'description'=>'',
                'image'=>'',
                'slider_image'=>'',
                'arr_image'=>[],
                'is_show'=>1,
                'is_price'=>2,
                'is_consent'=>0,
                'id'=>0
            );
        }

        $this->assign(compact('cat_list','data'));
        return $this->fetch();
    }

    /**
     * 提交保存我的资讯
     */
    public function article_save(){
        $request = Request::instance();
        if(!$request->isPost()) return Json::fail('参数错误!');
        $data = Util::postMore([
            ['cid',''],
            ['tag',''],
            ['title',''],
            ['price',0],
            ['phone',''],
            ['author',''],
            ['price',''],
            ['description',''],
            ['image',''],
            ['slider_image',''],
            ['status',1],
            ['is_show',1],
            ['is_price',2],
            ['is_consent',0],
            ['add_time',time()],
            ['id',0]
        ],$request);

        $data['slider_image'] = json_encode(array_filter(explode(',',$data['slider_image'])));
        if($data['is_consent']) {
            $data['is_consent'] = 1;
        } else {
            return Json::fail('请阅读并同意发布信息协议!');
        }
        $data['uid'] = $this->userInfo['uid'];

        if($data['id'] && ArticleModel::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid'],'is_show'=>1])){
            $id = $data['id'];
            unset($data['id']);
            if(ArticleModel::edit($data,$id,'id')){
                return Json::successful('编辑成功!');
            } else {
                return Json::fail('编辑失败!');
            }
        } else {
            if($address = ArticleModel::set($data)){
                return Json::successful('添加成功!');
            } else {
                return Json::fail('添加失败!');
            }
        }

    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 删除 我的资讯
     */
    public function article_del(){
        $where = Util::getMore([
            ['uid',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        $data = ArticleModel::where($where)->find();
        if(!$data) return Json::fail('删除的数据丢失了!');
        $delData = ArticleModel::where($where)->delete();
        if(!$delData) return Json::fail('网络忙，稍后再试!');
        return Json::successful('数据删除成功!');
    }

    /**
     * @return mixed
     * 我的干货列表
     * ====================================================================
     * ||
     * ||
     * || 我的干货管理 B
     * ||
     * ||
     * ====================================================================
     */
    public function knowledge() {
        return $this->fetch();
    }

    /**
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 请求我的干货列表
     */
    public function knowledge_list(){
        $where = Util::getMore([
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

        $data = Knowledge::where($where)->where('status',1)->where('keyword|title','LIKE',htmlspecialchars("%$keyword%"))->field('id,uid,cid,title,image,author,price,is_show,is_price,is_consent,add_time')->order('id desc')->page((int)$page,(int)$limit)->select();
        foreach ($data as $item=>$value){
            $cate_nam = KnowledgeCategory::where(array('id'=>$value['cid']))->find();
            $data[$item]['cname'] = $cate_nam['title'];
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
     * 添加 编辑 我的干货
     */
    public function knowledge_create() {
        $cat_list = KnowledgeCategory::getKnowledgeCategory()->toArray();
        $where = Util::getMore([
            ['uid',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        if($where['id']){
            $data = Knowledge::where($where)->find()->toArray();
            if($data) {
                $data['arr_posters'] = json_decode($data['posters'],true);
                $data['posters'] = implode(",",json_decode($data['posters'],true));
                $data['arr_image'] = json_decode($data['slider_image'],true);
                $data['slider_image'] = implode(",",json_decode($data['slider_image'],true));
            }
        } else {
            $data = array(
                'cid'=>0,
                'title'=>'',
                'audio_url'=>'',
                'video_url'=>'',
                'directory'=>'',
                'price'=>'',
                'test'=>'',
                'description'=>'',
                'image'=>'',
                'posters'=>'',
                'arr_posters'=>[],
                'slider_image'=>'',
                'arr_image'=>[],
                'is_show'=>1,
                'is_price'=>2,
                'is_consent'=>0,
                'id'=>0
            );
        }

        $this->assign(compact('cat_list','data'));
        return $this->fetch();
    }

    /**
     * 提交保存我的干货
     */
    public function knowledge_save(){
        $request = Request::instance();
        if(!$request->isPost()) return Json::fail('参数错误!');
        $data = Util::postMore([
            ['cid',''],
            ['title',''],
            ['price',0],
            ['audio_url',''],
            ['video_url',''],
            ['directory',''],
            ['price',''],
            ['test',''],
            ['description',''],
            ['image',''],
            ['posters',''],
            ['slider_image',''],
            ['status',1],
            ['is_show',1],
            ['is_price',2],
            ['is_consent',0],
            ['add_time',time()],
            ['id',0]
        ],$request);
        $data['posters'] = json_encode(array_filter(explode(',',$data['posters'])));
        $data['slider_image'] = json_encode(array_filter(explode(',',$data['slider_image'])));
        if($data['is_consent']) {
            $data['is_consent'] = 1;
        } else {
            return Json::fail('请阅读并同意发布信息协议!');
        }
        $data['uid'] = $this->userInfo['uid'];

        if($data['id'] && Knowledge::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid'],'is_show'=>1])){
            $id = $data['id'];
            unset($data['id']);
            if(Knowledge::edit($data,$id,'id')){
                return Json::successful('编辑成功!');
            } else {
                return Json::fail('编辑失败!');
            }
        } else {
            if($address = Knowledge::set($data)){
                return Json::successful('添加成功!');
            } else {
                return Json::fail('添加失败!');
            }
        }

    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 删除 我的干货
     */
    public function knowledge_del(){
        $where = Util::getMore([
            ['uid',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        $data = Knowledge::where($where)->find();
        if(!$data) return Json::fail('删除的数据丢失了!');
        $delData = Knowledge::where($where)->delete();
        if(!$delData) return Json::fail('网络忙，稍后再试!');
        return Json::successful('数据删除成功!');
    }

    /**
     * @return false|string|void
     * 文件上传
     * ====================================================================
     * ||
     * ||
     * || 文件上传 B
     * ||
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
            return Json::fail($res->error);
        }
    }



    public function about()
    {
        return $this->fetch();
    }

    public function sign_in()
    {
        $signed = UserSign::checkUserSigned($this->userInfo['uid']);
        $signCount = UserSign::userSignedCount($this->userInfo['uid']);
        $signList = UserSign::userSignBillWhere($this->userInfo['uid'])
            ->field('number,add_time')->order('id DESC')
            ->limit(30)->select()->toArray();
        $goodsList = StoreProduct::getNewProduct('image,price,sales,store_name,id','0,20')->toArray();
        $this->assign(compact('signed','signCount','signList','goodsList'));
        return $this->fetch();
    }

    public function coupon()
    {
        $uid = $this->userInfo['uid'];
        $couponList = StoreCouponUser::all(function($query) use($uid){
            $query->where('status','0')->where('uid',$uid)->order('is_fail ASC,status ASC,add_time DESC')->whereOr(function($query) use($uid){
                $query->where('uid',$uid)->where('status','<>',0)->where('end_time','>',time()-(7*86400));
            });
        })->toArray();
        $couponList = StoreCouponUser::tidyCouponList($couponList);
        $this->assign([
            'couponList'=>$couponList
        ]);
        return $this->fetch();
    }

    public function collect()
    {
        return $this->fetch();
    }



    public function recharge()
    {
        return $this->fetch();
    }



    /**
     * @return mixed|void
     * 我的地址
     * ====================================================================
     * ||
     * ||
     * || 我的地址 B
     * ||
     * ||
     * ====================================================================
     */
    public function address()
    {
        return $this->fetch();
    }

    public function iframe(){
        return $this->fetch();
    }

    /**
     * 请求地址列表
     */
    public function get_address_list(){
        $data = UserAddress::getUserValidAddressList($this->userInfo['uid'],'id,real_name,phone,province,city,district,detail,is_default');
        $code = 0;
        $msg = '请求成功';
        $count = 100;

        echo json_encode(compact('code','msg','count','data'));
    }

    public function edit_address($addressId = '')
    {
        if($addressId && is_numeric($addressId) && UserAddress::be(['is_del'=>0,'id'=>$addressId,'uid'=>$this->userInfo['uid']])){
            $addressInfo = UserAddress::find($addressId)->toArray();
        }else{
            $addressInfo = [];
        }
        $this->assign(compact('addressInfo'));
        return $this->fetch();
    }

    /**
     * @param string $uni
     * @return mixed|void
     * 我的订单
     * ====================================================================
     * ||
     * ||
     * || 我的订单 B
     * ||
     * ||
     * ====================================================================
     */
    public function order($uni = '')
    {
        if(!$uni || !$order = StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni)) return $this->redirect(Url::build('order_list'));
        $this->assign([
            'order'=>StoreOrder::tidyOrder($order,true)
        ]);
        return $this->fetch();
    }

    /**
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取订单列表
     */
    public function get_order_list(){
        $where = Util::getMore([
            ['uid',$this->userInfo['uid']],
            ['keyword',''],
            ['is_del',0],
            ['page',0],
            ['limit',0]
        ],$this->request);

        $keyword = $where['keyword'];
        $page = $where['page'];
        $limit = $where['limit'];
        unset($where['keyword']);
        unset($where['page']);
        unset($where['limit']);

        $data = StoreOrder::where($where)->where('order_id|user_phone','LIKE',htmlspecialchars("%$keyword%"))->page((int)$page,(int)$limit)->select()->toArray();

        foreach ($data as $item=>$value){
            $data[$item]['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
            $cartinfo = StoreOrderCartInfo::where('oid',$value['id'])->find()->toArray();
            $data[$item]['cart_info'] = $cartinfo['cart_info'];
        }

        $code = 0;
        $msg = '请求成功';
        $count = 100;

        echo json_encode(compact('code','msg','count','data'));
    }

    /**
     * @return mixed
     * 删除订单
     */
    public function user_remove_order(){
        $where = Util::getMore([
            ['order_id',0]
        ],$this->request);

        if(!$where['order_id']) return Json::fail('参数错误!');
        $res = StoreOrder::removeOrder($where['order_id'],$this->userInfo['uid']);
        if($res)
            return Json::successful('删除成功！');
        else
            return Json::fail(StoreOrder::getErrorInfo());
    }

    /**
     * @return mixed
     * 确认收货
     */
    public function user_take_order(){
        $where = Util::getMore([
            ['order_id',0]
        ],$this->request);

        if(!$where['order_id']) return Json::fail('参数错误!');

        $res = StoreOrder::takeOrder($where['order_id'],$this->userInfo['uid']);
        if($res)
            return Json::successful('确认收货成功！');
        else
            return Json::fail(StoreOrder::getErrorInfo());
    }


    public function orderPinkOld($uni = '')
    {
        if(!$uni || !$order = StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni)) return $this->redirect(Url::build('order_list'));
        $this->assign([
            'order'=>StoreOrder::tidyOrder($order,true)
        ]);
        return $this->fetch('order');
    }


        public function order_list()
    {
        return $this->fetch();
    }

    public function order_reply($unique = '')
    {
        if(!$unique || !StoreOrderCartInfo::be(['unique'=>$unique]) || !($cartInfo = StoreOrderCartInfo::where('unique',$unique)->find())) return $this->failed('评价产品不存在!');
        $this->assign(['cartInfo'=>$cartInfo]);
        return $this->fetch();
    }

    /**
     * @return mixed|void
     * 我的订阅
     * ====================================================================
     * ||
     * ||
     * || 我的订阅 B
     * ||
     * ||
     * ====================================================================
     */
    public function subion(){
        return $this->fetch();
    }

    public function get_user_subion_list()
    {
        list($type,$page,$limit,$search,$catetype)=Util::getMore([
            ['type',''],
            ['page',''],
            ['limit',''],
            ['search',''],
            ['catetype',''],
        ],$this->request,true);

        $code = 0;
        $msg = '请求成功';
        $count = 100;

        $data = UserSubion::getUserSubionSearchList($this->uid,$type,$page,$limit,$search,$catetype);
        echo json_encode(compact('code','msg','count','data'));
    }

    public function balance()
    {
        $this->assign([
            'userMinRecharge'=>SystemConfigService::get('store_user_min_recharge')
        ]);
        return $this->fetch();
    }

    public function integral()
    {
        return $this->fetch();
    }

    public function spread_list()
    {
        $statu = (int)SystemConfig::getValue('store_brokerage_statu');
        if($statu == 1){
            if(!User::be(['uid'=>$this->userInfo['uid'],'is_promoter'=>1]))
                return $this->failed('没有权限访问!');
        }
        $this->assign([
            'total'=>User::where('spread_uid',$this->userInfo['uid'])->count()
        ]);
        return $this->fetch();
    }

    public function notice()
    {

        return $this->fetch();
    }

    public function express($uni = '')
    {
        if(!$uni || !($order = StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni))) return $this->failed('查询订单不存在!');
        if($order['delivery_type'] != 'express' || !$order['delivery_id']) return $this->failed('该订单不存在快递单号!');
        $cacheName = $uni.$order['delivery_id'];
        $result = CacheService::get($cacheName,null);
        if($result === null){
            $result = Express::query($order['delivery_id']);
            if(is_array($result) &&
                isset($result['result']) &&
                isset($result['result']['deliverystatus']) &&
                $result['result']['deliverystatus'] >= 3)
                $cacheTime = 0;
            else
                $cacheTime = 1800;
            CacheService::set($cacheName,$result,$cacheTime);
        }
        $this->assign([
            'order'=>$order,
            'express'=>$result
        ]);
        return $this->fetch();
    }


    public function user_pro()
    {
        $statu = (int)SystemConfig::getValue('store_brokerage_statu');
        if($statu == 1){
            if(!User::be(['uid'=>$this->userInfo['uid'],'is_promoter'=>1]))
                return $this->failed('没有权限访问!');
        }
        $userBill = new UserBill();
        $number = $userBill->where('uid',$this->userInfo['uid'])
            ->where('add_time','BETWEEN',[strtotime('today -1 day'),strtotime('today')])
            ->where('category','now_money')
            ->where('type','brokerage')
            ->value('SUM(number)')?:0;
        $allNumber = $userBill
            ->where('uid',$this->userInfo['uid'])
            ->where('category','now_money')
            ->where('type','brokerage')
            ->value('SUM(number)')?:0;
        $extractNumber = UserExtract::userExtractTotalPrice($this->userInfo['uid']);
        $this->assign([
            'number'=>$number,
            'allnumber'=>$allNumber,
            'extractNumber'=>$extractNumber
        ]);
        return $this->fetch();
    }


    public function commission()
    {
        $uid = (int)Request::instance()->get('uid',0);
        if(!$uid) return $this->failed('用户不存在!');
        $this->assign(['uid'=>$uid]);
        return $this->fetch();
    }

    public function extract()
    {
        $minExtractPrice = floatval(SystemConfigService::get('user_extract_min_price'))?:0;
        $extractInfo = UserExtract::userLastInfo($this->userInfo['uid'])?:[
            'extract_type'=>'bank',
            'real_name'=>'',
            'bank_code'=>'',
            'bank_address'=>'',
            'alipay_code'=>''
        ];
        $this->assign(compact('minExtractPrice','extractInfo'));
        return $this->fetch();
    }


    /**
     * 创建拼团
     * @param string $uni
     */
//    public function createPink($uni = ''){
//        if(!$uni || !$order = StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni)) return $this->redirect(Url::build('order_list'));
//        $order = StoreOrder::tidyOrder($order,true)->toArray();
//        if($order['pink_id']){//拼团存在
//            $res = false;
//            $pink['uid'] = $order['uid'];//用户id
//            if(StorePink::isPinkBe($pink,$order['pink_id'])) return $this->redirect('order_pink',['id'=>$order['pink_id']]);
//            $pink['order_id'] = $order['order_id'];//订单id  生成
//            $pink['order_id_key'] = $order['id'];//订单id  数据库id
//            $pink['total_num'] = $order['total_num'];//购买个数
//            $pink['total_price'] = $order['pay_price'];//总金额
//            $pink['k_id'] = $order['pink_id'];//拼团id
//            foreach ($order['cartInfo'] as $v){
//                $pink['cid'] = $v['combination_id'];//拼团产品id
//                $pink['pid'] = $v['product_id'];//产品id
//                $pink['people'] = StoreCombination::where('id',$v['combination_id'])->value('people');//几人拼团
//                $pink['price'] = $v['productInfo']['price'];//单价
//                $pink['stop_time'] = 0;//结束时间
//                $pink['add_time'] = time();//开团时间
//                $res = StorePink::set($pink)->toArray();
//            }
//            if($res) $this->redirect('order_pink',['id'=>$res['id']]);
//            else $this->failed('创建拼团失败,请退款后再次拼团',Url::build('my/index'));
//            $this->redirect('order_pink',['id'=>$order['pink_id']]);
//        }else{
//            $res = false;
//            $pink['uid'] = $order['uid'];//用户id
//            $pink['order_id'] = $order['order_id'];//订单id  生成
//            $pink['order_id_key'] = $order['id'];//订单id  数据库id
//            $pink['total_num'] = $order['total_num'];//购买个数
//            $pink['total_price'] = $order['pay_price'];//总金额
//            $pink['k_id'] = 0;//拼团id
//            foreach ($order['cartInfo'] as $v){
//                $pink['cid'] = $v['combination_id'];//拼团产品id
//                $pink['pid'] = $v['product_id'];//产品id
//                $pink['people'] = StoreCombination::where('id',$v['combination_id'])->value('people');//几人拼团
//                $pink['price'] = $v['productInfo']['price'];//单价
//                $pink['stop_time'] = time()+86400;//结束时间
//                $pink['add_time'] = time();//开团时间
//                $res1 = StorePink::set($pink)->toArray();
//                $res2 = StoreOrder::where('id',$order['id'])->update(['pink_id'=>$res1['id']]);
//                $res = $res1 && $res2;
//            }
//            if($res) $this->redirect('order_pink',['id'=>$res1['id']]);
//            else $this->failed('创建拼团失败,请退款后再次拼团',Url::build('my/index'));
//        }
//    }

     /**
     * 参团详情页
     */
    public function order_pink($id = 0){
        if(!$id) return $this->failed('参数错误',Url::build('my/index'));
        $pink = StorePink::getPinkUserOne($id);
        if(isset($pink['is_refund']) && $pink['is_refund']) {
            if($pink['is_refund'] != $pink['id']){
                $id = $pink['is_refund'];
                return $this->order_pink($id);
            }else{
                return $this->failed('订单已退款',Url::build('store/combination_detail',['id'=>$pink['cid']]));
            }
        }
        if(!$pink) return $this->failed('参数错误',Url::build('my/index'));
        $pinkAll = array();//参团人  不包括团长
        $pinkT = array();//团长
        if($pink['k_id']){
            $pinkAll = StorePink::getPinkMember($pink['k_id']);
            $pinkT = StorePink::getPinkUserOne($pink['k_id']);
        }else{
            $pinkAll = StorePink::getPinkMember($pink['id']);
            $pinkT = $pink;
        }
        $store_combination = StoreCombination::getCombinationOne($pink['cid']);//拼团产品
        $count = count($pinkAll)+1;
        $count = (int)$pinkT['people']-$count;//剩余多少人
        $is_ok = 0;//判断拼团是否完成
        $idAll =  array();
        $uidAll =  array();
        if(!empty($pinkAll)){
            foreach ($pinkAll as $k=>$v){
                $idAll[$k] = $v['id'];
                $uidAll[$k] = $v['uid'];
            }
        }

        $userBool = 0;//判断当前用户是否在团内  0未在 1在
        $pinkBool = 0;//判断当前用户是否在团内  0未在 1在
        $idAll[] = $pinkT['id'];
        $uidAll[] = $pinkT['uid'];
        if($pinkT['status'] == 2){
            $pinkBool = 1;
        }else{
            if(!$count){//组团完成
                $idAll = implode(',',$idAll);
                $orderPinkStatus = StorePink::setPinkStatus($idAll);
                if($orderPinkStatus){
                    if(in_array($this->uid,$uidAll)){
                        StorePink::setPinkStopTime($idAll);
                        if(StorePink::isTpl($uidAll,$pinkT['id'])) StorePink::orderPinkAfter($uidAll,$pinkT['id']);
                        $pinkBool = 1;
                    }else  $pinkBool = 3;
                }else $pinkBool = 6;
            }
            else{
                if($pinkT['stop_time'] < time()){//拼团时间超时  退款
                    if($pinkAll){
                        foreach ($pinkAll as $v){
                            if($v['uid'] == $this->uid){
                                $res = StoreOrder::orderApplyRefund(StoreOrder::where('id',$v['order_id_key'])->value('order_id'),$this->uid,'拼团时间超时');
                                if($res){
                                    if(StorePink::isTpl($v['uid'],$pinkT['id'])) StorePink::orderPinkAfterNo($v['uid'],$v['k_id']);
                                    $pinkBool = 2;
                                }else return $this->failed(StoreOrder::getErrorInfo(),Url::build('index'));
                            }
                        }
                    }
                    if($pinkT['uid'] == $this->uid){
                        $res = StoreOrder::orderApplyRefund(StoreOrder::where('id',$pinkT['order_id_key'])->value('order_id'),$this->uid,'拼团时间超时');
                        if($res){
                            if(StorePink::isTpl($pinkT['uid'],$pinkT['id']))  StorePink::orderPinkAfterNo($pinkT['uid'],$pinkT['id']);
                            $pinkBool = 2;
                        }else return $this->failed(StoreOrder::getErrorInfo(),Url::build('index'));
                    }
                    if(!$pinkBool) $pinkBool = 3;
                }
            }
        }
        $store_combination_host =  StoreCombination::getCombinationHost();//获取推荐的拼团产品
        if(!empty($pinkAll)){
            foreach ($pinkAll as $v){
                if($v['uid'] == $this->uid) $userBool = 1;
            }
        }
        if($pinkT['uid'] == $this->uid) $userBool = 1;
        $combinationOne = StoreCombination::getCombinationOne($pink['cid']);
        if(!$combinationOne) return $this->failed('拼团不存在或已下架');
        $combinationOne['images'] = json_decode($combinationOne['images'],true);
        $combinationOne['userLike'] = StoreProductRelation::isProductRelation($combinationOne['product_id'],$this->userInfo['uid'],'like');
        $combinationOne['like_num'] = StoreProductRelation::productRelationNum($combinationOne['product_id'],'like');
        $combinationOne['userCollect'] = StoreProductRelation::isProductRelation($combinationOne['product_id'],$this->userInfo['uid'],'collect');
        $this->assign('storeInfo',$combinationOne);
        $this->assign('current_pink_order',StorePink::getCurrentPink($id));
        $this->assign(compact('pinkBool','is_ok','userBool','store_combination','pinkT','pinkAll','count','store_combination_host'));
        return $this->fetch();
    }

    /**
     * 参团详情页  失败或者成功展示页
     */
    public function order_pink_after($id = 0){
        if(!$id) return $this->failed('参数错误',Url::build('my/index'));
        $pink = StorePink::getPinkUserOne($id);
        if(!$pink) return $this->failed('参数错误',Url::build('my/index'));
        $pinkAll = array();//参团人  不包括团长
        $pinkT = array();//团长
        if($pink['k_id']){
            $pinkAll = StorePink::getPinkMember($pink['k_id']);
            $pinkT = StorePink::getPinkUserOne($pink['k_id']);
        }else{
            $pinkAll = StorePink::getPinkMember($pink['id']);
            $pinkT = $pink;
        }
        $store_combination = StoreCombination::getCombinationOne($pink['cid']);//拼团产品
        $count = count($pinkAll)+1;
        $count = (int)$pinkT['people']-$count;//剩余多少人
        $idAll =  array();
        $uidAll =  array();
        if(!empty($pinkAll)){
            foreach ($pinkAll as $k=>$v){
                $idAll[$k] = $v['id'];
                $uidAll[$k] = $v['uid'];
            }
        }
        $idAll[] = $pinkT['id'];
        $uidAll[] = $pinkT['uid'];
        $userBool = 0;//判断当前用户是否在团内是否完成拼团
        if(!$count) $userBool = 1;//组团完成
        $store_combination_host =  StoreCombination::getCombinationHost();//获取推荐的拼团产品
        $combinationOne = StoreCombination::getCombinationOne($pink['cid']);
        if(!$combinationOne) return $this->failed('拼团不存在或已下架');
        $combinationOne['images'] = json_decode($combinationOne['images'],true);
        $combinationOne['userLike'] = StoreProductRelation::isProductRelation($combinationOne['product_id'],$this->userInfo['uid'],'like');
        $combinationOne['like_num'] = StoreProductRelation::productRelationNum($combinationOne['product_id'],'like');
        $combinationOne['userCollect'] = StoreProductRelation::isProductRelation($combinationOne['product_id'],$this->userInfo['uid'],'collect');
        $this->assign('storeInfo',$combinationOne);
        $this->assign(compact('userBool','store_combination','pinkT','pinkAll','count','store_combination_host'));
        return $this->fetch();
    }

    /**
     * 售后服务  退款订单
     * @return mixed
     */
    public function order_customer(){
        return $this->fetch();
    }

}
