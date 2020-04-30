<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-11
 * Time: 22:13
 */

namespace app\ebapi\controller;

use app\core\model\routine\RoutineCode;
use app\ebapi\model\store\StoreBrand;
use app\ebapi\model\store\StoreCategory;
use app\ebapi\model\store\StoreOrderCartInfo;
use app\ebapi\model\company\Company;
use app\ebapi\model\company\CompanyCategory;
use app\ebapi\model\store\StoreProductAttr;
use app\ebapi\model\store\StoreProductRelation;
use app\ebapi\model\store\StoreProductReply;
use app\ebapi\model\store\CompanyClass;
use app\ebapi\model\store\CompanyProduct;
use app\core\util\GroupDataService;
use app\ebapi\model\user\User;
use service\JsonService;
use app\core\util\SystemConfigService;
use service\UtilService;
use think\Cache;
use think\Request;

class CompanyApi extends AuthController
{
    public static function whiteList()
    {
        return [
            'goods_search',
            'get_routine_hot_search',
            'get_pid_cate',
            'get_product_category',
            'get_company_list',
            'get_company_top',
            'get_company_id',
            'details',
            'get_my_product_list',
            'get_company_class_two',
            'get_company_details',
            'edit_user_company_goods',
            'get_company_product_list',
            'edit_user_company_class',
        ];
    }

    public function get_del_type_company($id = 0){
        $data = CompanyClass::where(array('id'=>$id))->find();
        if($data){
            CompanyClass::where(array('id'=>$data['id']))->delete();
            return JsonService::successful('删除成功！');
        } else {
            return JsonService::fail('删除错成功!');
        }
    }

    public function get_del_company_product($id = 0){
        $data = CompanyProduct::where(array('id'=>$id))->find();
        if($data){
            CompanyProduct::where(array('id'=>$data['id']))->delete();
            return JsonService::successful('删除成功！');
        } else {
            return JsonService::fail('删除错成功!');
        }
    }

    public function get_my_product_list(){
        $data = UtilService::getMore([
            ['uid',0],
            ['keyword',''],
            ['page',0],
            ['limit',0]
        ],$this->request);
        $list = CompanyProduct::where('mer_id',$this->uid)->where('is_show',1)->select();
        foreach ($list as $item=>$value){
            $class = CompanyClass::where('id',$value['cate_id'])->where('is_show',1)->find();
            $list[$item]['cname'] = $class['cate_name'];
        }

        return JsonService::successful($list);
    }

    public function get_my_company_type_list(){

        $data = UtilService::getMore([
            ['keyword',''],
            ['page',0],
            ['limit',0]
        ],$this->request);

        $page = $data['page'];
        $limit = $data['limit'];

        $list = CompanyClass::where('mer_id',$this->uid)->where('is_show',1)->page((int)$page,(int)$limit)->select();
        return JsonService::successful($list);
    }

    public function get_del_company_type($id = 0){
        $data = CompanyClass::where(array('id'=>$id))->find();
        if($data){
            CompanyClass::where(array('id'=>$data['id']))->delete();
            return JsonService::successful('删除成功！');
        } else {
            return JsonService::fail('删除错成功!');
        }
    }

    public function get_company_product_list(){
        $data = UtilService::getMore([
            ['mer_id',0]
        ],$this->request);

        $datas = array();
        $image = array();
        $list = CompanyProduct::where($data)->where('is_show',1)->select();

        $cate_id = [];
        foreach ($list as $item=>$value){
            if($value['cate_id']){
                $cate_id[] = $value['cate_id'];
            }
        }
        $class = CompanyClass::where('id','IN', $cate_id)->where('is_show',1)->select();

        $user = User::where('uid',$data['mer_id'])->field('uid,level')->find();
        if($user['level'] == 8){
            foreach ($class as $item=>$value){
                $goods = CompanyProduct::where('cate_id',$value['id'])->where('is_show',1)->select();
                foreach ($goods as $key=>$val){
                    $goods[$key]['user'] = $user;
                    $image[] = $val['image'];
                }
                $class[$item]['goods'] = $goods;
            }
            $datas['list'] = $class;
        } else {
            foreach ($list as $item=>$value){
                $slider_image = json_decode(json_encode($value['slider_image'],true));
                foreach ($slider_image as $key=>$val){
                    $image[] = $val;
                }
                $cate_name = CompanyClass::where('id',$value['cate_id'])->find();
                $list[$item]['cate_name'] = $cate_name['cate_name'];
                $list[$item]['goods'] = $slider_image;
            }
            $datas['list'] = $list;
        }
        $datas['image'] = $image;
        return JsonService::successful($datas);
    }

    public function get_company_class_one(){
        $list = CompanyClass::where('mer_id',$this->uid)->select();
        return JsonService::successful($list);
    }

    public function get_company_class_two(Request $request){
        $data = UtilService::getMore([['id',0]],$request);
        $dataCateA = [];
        $dataCateA[0]['id'] = $data['id'];
        $dataCateA[0]['cate_name'] = '全部系列';
        $dataCateA[0]['pid'] = 0;
        $dataCateE = CompanyClass::pidBySidList($data['id']);
        if($dataCateE) $dataCateE = $dataCateE->toArray();
        $dataCate = [];
        $dataCate = array_merge_recursive($dataCateA,$dataCateE);
        return JsonService::successful($dataCate);
    }

    public function get_company_details($id=0){
        if(!$id || !($data = CompanyProduct::where('id',$id)->find())) return JsonService::fail('商品不存在或已下架');
        $data["browse"] = $data["browse"] + 1;
        CompanyProduct::edit(['browse'=>$data["browse"]],$id);//增加浏览次数
        $cate_nam = CompanyClass::where(array('id'=>$data['cate_id']))->find();
        $data['cate_name'] = $cate_nam['cate_name'];
        return JsonService::successful($data);
    }

    public function get_company_type_details($id=0){
        if(!$id || !($data = CompanyClass::where('id',$id)->find())) return JsonService::fail('系列不存在或已下架');
        return JsonService::successful($data);
    }

    public function edit_user_company_goods(){
        $request = Request::instance();
        if (!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['cate_id', 0],
            ['images',[]],
            ['pics', []],
            ['is_show', 1],
            ['add_time', time()],
            ['is_consent',0],
            ['id', 0]
        ], $request);

        $data['mer_id'] = $this->userInfo['uid'];
        $data['image'] = $data['images'][0];
        unset($data['images']);
        $data['slider_image'] = json_encode($data['pics']);
        unset($data['pics']);

        if ($data['id'] && CompanyProduct::be(['id' => $data['id'], 'mer_id' => $this->userInfo['uid'], 'is_show' => 1])) {
            $id = $data['id'];
            unset($data['id']);
            if (CompanyProduct::edit($data, $id, 'id')) {
                return JsonService::successful();
            } else
                return JsonService::fail('编辑失败!');
        } else {
            if ($res = CompanyProduct::set($data)) {
                return JsonService::successful(['id' => $res->id]);
            } else
                return JsonService::fail('添加失败!');
        }
    }

    public function edit_user_company_class(){
        $request = Request::instance();
        if (!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['id', 0],
            ['cate_name',''],
            ['is_show', 1],
            ['add_time', time()],
        ], $request);

        $data['mer_id'] = $this->userInfo['uid'];

        if ($data['id'] && CompanyClass::be(['id' => $data['id'], 'mer_id' => $this->userInfo['uid'], 'is_show' => 1])) {
            $id = $data['id'];
            if (CompanyClass::edit($data, $id, 'id')) {
                return JsonService::successful();
            } else
                return JsonService::fail('编辑失败!');
        } else {
            if ($res = CompanyClass::set($data)) {
                return JsonService::successful(['id' => $res->id]);
            } else
                return JsonService::fail('添加失败!');
        }
    }

    /**
     * 分类搜索页面
     * @param Request $request
     * @return \think\response\Json
     */
    public function goods_search()
    {
        list($keyword) = UtilService::getMore([['keyword',0]],null,true);
        return JsonService::successful(StoreProduct::getSearchStorePage($keyword,$this->uid));
    }
    /**
     * 分类页面
     * @param Request $request
     * @return \think\response\Json
     */
    public function store1(Request $request)
    {
        $data = UtilService::postMore([['keyword',''],['cid',''],['sid','']],$request);
        $keyword = addslashes($data['keyword']);
        $cid = intval($data['cid']);
        $sid = intval($data['sid']);
        $category = null;
        if($sid) $category = StoreCategory::get($sid);
        if($cid && !$category) $category = StoreCategory::get($cid);
        $data['keyword'] = $keyword;
        $data['cid'] = $cid;
        $data['sid'] = $sid;
        return JsonService::successful($data);
    }

    /**
     * 一级分类
     * @return \think\response\Json
     */
    public function get_pid_cate(){
        $data = CompanyCategory::getCategory();
        return JsonService::successful($data);
    }

    /**
     * 获取一级和二级分类
     * @return \think\response\Json
     */
    public function get_product_category()
    {
        $list = CompanyCategory::getCategory();
        foreach ($list as $item=>$value){
            $list[$item]['child'] = Company::where('cid',$value['id'])->where('is_show',1)->order('sort desc')->select();
        }
        return JsonService::successful($list);
    }

    /**
     * 二级分类
     * @param Request $request
     * @return \think\response\Json
     */
    public function get_id_cate(Request $request){
        $data = UtilService::postMore([['id',0]],$request);
        $dataCateA = [];
        $dataCateA[0]['id'] = $data['id'];
        $dataCateA[0]['cate_name'] = '全部商品';
        $dataCateA[0]['pid'] = 0;
        $dataCateE = StoreCategory::pidBySidList($data['id']);//根据一级分类获取二级分类
        if($dataCateE) $dataCateE = $dataCateE->toArray();
        $dataCate = [];
        $dataCate = array_merge_recursive($dataCateA,$dataCateE);
        return JsonService::successful($dataCate);
    }

    /**
     * 分类页面产品
     * @param string $keyword
     * @param int $cId
     * @param int $sId
     * @param string $priceOrder
     * @param string $salesOrder
     * @param int $news
     * @param int $first
     * @param int $limit
     * @return \think\response\Json
     */
    public function get_company_list()
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
        return JsonService::successful(Company::getCompanyList($data,$this->uid));
    }

    public function get_company_top()
    {
        $list = Company::where('is_top',1)->where('status',1)->select();
        return JsonService::successful($list);
    }

    /**
     * 详情页
     * 2019-09-07
     * @param int $mer_id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_company_id($mer_id=0){
        if(!$mer_id || !($storeInfo = Company::where('uid',$mer_id)->find())) return JsonService::successful(array('yes'=>1,'msg'=>'不存在或未完善企业'));
        $storeInfo['yes'] = 0;
        return JsonService::successful($storeInfo);
    }

    /**
     * @param int $id
     * 详情页
     * 2019-07-27
     */
    public function details($id=0){
        if(!$id || !($data = Company::getValidProduct($id))) return JsonService::fail('不存在或已下架');
        $data['poster_image'] = json_decode($data['poster_image']);
        $data['slider_image'] = json_decode($data['slider_image']);
        return JsonService::successful($data);
    }

    /**
     * 获取公司信息
     * 2019-07-27
     */
    public function get_user_company(){
        $data = Company::where(array('uid'=>$this->userInfo['uid']))->find();
        if($data){
            $cate = CompanyCategory::where('id',$data['cid'])->find();
            $data['cname'] = $cate['title'];
            $data['poster_image'] = json_decode($data['poster_image'],1);
            $data['slider_image'] = json_decode($data['slider_image'],1);
            return JsonService::successful($data);
        } else {
            return JsonService::fail('没有数据!');
        }
    }

    /**
     * 编辑企业信息
     * 2019-07-27
     */
    public function edit_user_company(){
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['comment',''],
            ['logo',[]],
            ['series',[]],
            ['poster',[]],
            ['styleer',[]],
            ['classification',[]],
            ['about',[]],
            ['contact',[]],
            ['title',''],
            ['phone',''],
            ['synopsis',''],
            ['author',''],
            ['welfare',''],
            ['description',''],
            ['is_default',0],
            ['cid',0],
            ['status',1],
            ['is_show',1],
            ['is_consent',0],
            ['id',0]
        ],$request);

        $data['logo'] = $data['logo'][0];
        $data['image'] = $data['series'][0];
        $data['slider_image'] = json_encode($data['series']);
        $data['poster_image'] = json_encode($data['poster']);
        $data['classification'] = $data['classification'][0];
        $data['about'] = $data['about'][0];
        $data['contact'] = $data['contact'][0];
        $data['share_title'] = $data['title'];
        $data['share_synopsis'] = $data['synopsis'];

        $data['uid'] = $this->userInfo['uid'];
        unset($data['series']);
        unset($data['poster']);
        $data['add_time'] = time();
        if($data['id'] && Company::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid']])){
            $id = $data['id'];
            unset($data['id']);
            if(Company::edit($data,$id,'id')){
                return JsonService::successful('编辑成功!');
            } else {
                return JsonService::fail('编辑失败!');
            }
        } else {
            if($address = Company::set($data)){
                return JsonService::successful('添加成功!');
            } else {
                return JsonService::fail('添加失败!');
            }
        }
    }

    /*
     * 获取产品是否收藏
     *
     * */
    public function get_product_collect($product_id=0)
    {
        return JsonService::successful(['userCollect'=>StoreProductRelation::isProductRelation($product_id,$this->userInfo['uid'],'collect')]);
    }
    /**
     * 获取产品评论
     * @param int $productId
     * @return \think\response\Json
     */
    public function get_product_reply($productId = 0){
        if(!$productId) return JsonService::fail('参数错误');
        $replyCount = StoreProductReply::productValidWhere()->where('product_id',$productId)->count();
        $reply = StoreProductReply::getRecProductReply($productId);
        return JsonService::successful(['replyCount'=>$replyCount,'reply'=>$reply]);
    }

    /**
     * 添加点赞
     * @param string $productId
     * @param string $category
     * @return \think\response\Json
     */
    public function like_product($productId = '',$category = 'product'){
        if(!$productId || !is_numeric($productId))  return JsonService::fail('参数错误');
        $res = StoreProductRelation::productRelation($productId,$this->userInfo['uid'],'like',$category);
        if(!$res) return  JsonService::fail(StoreProductRelation::getErrorInfo());
        else return JsonService::successful();
    }

    /**
     * 取消点赞
     * @param string $productId
     * @param string $category
     * @return \think\response\Json
     */
    public function unlike_product($productId = '',$category = 'product'){
        if(!$productId || !is_numeric($productId)) return JsonService::fail('参数错误');
        $res = StoreProductRelation::unProductRelation($productId,$this->userInfo['uid'],'like',$category);
        if(!$res) return JsonService::fail(StoreProductRelation::getErrorInfo());
        else return JsonService::successful();
    }

    /**
     * 添加收藏
     * @param $productId
     * @param string $category
     * @return \think\response\Json
     */
    public function collect_product($productId,$category = 'product'){
        if(!$productId || !is_numeric($productId)) return JsonService::fail('参数错误');
        $res = StoreProductRelation::productRelation($productId,$this->userInfo['uid'],'collect',$category);
        if(!$res) return JsonService::fail(StoreProductRelation::getErrorInfo());
        else return JsonService::successful();
    }

    /**
     * 批量收藏
     * @param string $productId
     * @param string $category
     * @return \think\response\Json
     */
    public function collect_product_all($productId = '',$category = 'product'){
        if($productId == '') return JsonService::fail('参数错误');
        $productIdS = explode(',',$productId);
        $res = StoreProductRelation::productRelationAll($productIdS,$this->userInfo['uid'],'collect',$category);
        if(!$res) return JsonService::fail(StoreProductRelation::getErrorInfo());
        else return JsonService::successful('收藏成功');
    }

    /**
     * 取消收藏
     * @param $productId
     * @param string $category
     * @return \think\response\Json
     */
    public function uncollect_product($productId,$category = 'product'){
        if(!$productId || !is_numeric($productId)) return JsonService::fail('参数错误');
        $res = StoreProductRelation::unProductRelation($productId,$this->userInfo['uid'],'collect',$category);
        if(!$res) return JsonService::fail(StoreProductRelation::getErrorInfo());
        else return JsonService::successful();
    }

    /**
     * 获取收藏产品
     * @param int $first
     * @param int $limit
     * @return \think\response\Json
     */
    public function get_user_collect_product($page = 0,$limit = 8)
    {
        return JsonService::successful(StoreProductRelation::getUserCollectProduct($this->uid,$page,$limit));
    }
    /**
     * 获取收藏产品删除
     * @param int $first
     * @param int $limit
     * @return \think\response\Json
     */
    public function get_user_collect_product_del($pid=0)
    {
        if($pid){
            $list = StoreProductRelation::where('uid',$this->userInfo['uid'])->where('product_id',$pid)->delete();
            return JsonService::successful($list);
        }else
            return JsonService::fail('缺少参数');
    }

    /**
     * 获取订单内的某个产品信息
     * @param string $uni
     * @param string $productId
     * @return \think\response\Json
     */
    public function get_order_product($unique = ''){
        if(!$unique || !StoreOrderCartInfo::be(['unique'=>$unique]) || !($cartInfo = StoreOrderCartInfo::where('unique',$unique)->find())) return JsonService::fail('评价产品不存在!');
        return JsonService::successful($cartInfo);
    }



    /**
     * 获取产品评论
     * @param string $productId
     * @param int $first
     * @param int $limit
     * @param int $type
     * @return \think\response\Json
     */
    public function product_reply_list($productId = '',$page = 0,$limit = 8, $type = 0)
    {
        if(!$productId || !is_numeric($productId)) return JsonService::fail('参数错误!');
        $list = StoreProductReply::getProductReplyList($productId,(int)$type,$page,$limit);
        return JsonService::successful($list);
    }

    /*
     * 获取评论数量和评论好评度
     * @param int $productId
     * @return \think\response\Json
     * */
    public function product_reply_count($productId = '')
    {
        if(!$productId) return JsonService::fail('缺少参数');
        return JsonService::successful(StoreProductReply::productReplyCount($productId));
    }

    /**
     * 获取商品属性数据
     * @param string $productId
     * @return \think\response\Json
     */
    public function product_attr_detail($productId = '')
    {
        if(!$productId || !is_numeric($productId)) return JsonService::fail('参数错误!');
        list($productAttr,$productValue) = StoreProductAttr::getProductAttrDetail($productId);
        return JsonService::successful(compact('productAttr','productValue'));

    }

    /**
     * 产品海报二维码
     * @param int $id
     */
    public function product_promotion_code($id = 0){
        if(!$id) return JsonService::fail('参数错误ID不存在');
        $count = Company::validWhere()->count();
        if(!$count) return JsonService::fail('参数错误');
        $path = makePathToUrl('routine/product/',4);
        if($path == '') return JsonService::fail('生成上传目录失败,请检查权限!');
        $codePath = $path.$id.'_'.$this->userInfo['uid'].'_product.jpg';
        $domain = SystemConfigService::get('site_url').'/';
        if(!file_exists($codePath)){
            if(!is_dir($path)) mkdir($path,0777,true);
            $data='id='.$id;
            if($this->userInfo['is_promoter'] || SystemConfigService::get('store_brokerage_statu')==2) $data.='&pid='.$this->uid;
            $res = RoutineCode::getPageCode('pages/company/details/index',$data,280);
            if($res) file_put_contents($codePath,$res);
            else return JsonService::fail('二维码生成失败');
        }
        return JsonService::successful($domain.$codePath);
    }

    /**
     * 热门搜索
     */
    public function get_routine_hot_search(){
        $routineHotSearch = GroupDataService::getData('routine_hot_search') ? :[];
        return JsonService::successful($routineHotSearch);
    }
}