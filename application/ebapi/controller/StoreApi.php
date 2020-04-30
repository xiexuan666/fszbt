<?php
namespace app\ebapi\controller;


use app\core\model\routine\RoutineCode;//待完善
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
use app\ebapi\model\store\StoreCategory;
use app\ebapi\model\store\StoreBrand;
use app\ebapi\model\store\StoreOrderCartInfo;
use app\ebapi\model\store\StoreProduct;
use app\ebapi\model\store\StoreProductAttr;
use app\ebapi\model\store\StoreProductRelation;
use app\ebapi\model\store\StoreProductReply;
use app\core\util\GroupDataService;
use app\ebapi\model\user\User;
use service\JsonService;
use app\core\util\SystemConfigService;
use service\UtilService;
use app\core\util\MiniProgramService;
use think\Request;
use think\Cache;

/**
 * 小程序产品和产品分类api接口
 * Class StoreApi
 * @package app\ebapi\controller
 *
 */
class StoreApi extends AuthController
{

    public static function whiteList()
    {
        return [
            'goods_search',
            'get_routine_hot_search',
            'get_pid_cate',
            'get_product_category',

            'get_pid_brand',
            'get_brand',
            'get_cate_brand',
            'get_two_cate_brand',
            'edit_user_brand',
            'get_brand_details',

            'get_product_list',
            'get_product_news_list',
            'details',
            'get_two',
            'get_id_cate',
            'get_my_product_list',
            'edit_user_goods',
            'get_user_collect_all',
            'collect_product'
        ];
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
        $data = StoreCategory::pidByCategory(0,'id,cate_name');//一级分类
        if(Cache::has('one_pid_cate_list'))
            return JsonService::successful(Cache::get('one_pid_cate_list'));
        else{
            Cache::set('one_pid_cate_list',$data);
            return JsonService::successful($data);
        }
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
     * 获取一级和二级分类
     * @return \think\response\Json
     */
    public function get_product_category()
    {
        $data = StoreCategory::getProductCategory();
        return JsonService::successful($data);
    }


    /**
     * 一级分类
     * @return \think\response\Json
     */
    public function get_one(){
        $data = StoreCategory::pidByCategory(0,'id,cate_name');//一级分类
        if(Cache::has('one_pid_cate_list'))
            return JsonService::successful(Cache::get('one_pid_cate_list'));
        else{
            Cache::set('one_pid_cate_list',$data);
            return JsonService::successful($data);
        }
    }

    /**
     * 二级分类
     * @param Request $request
     * @return \think\response\Json
     */
    public function get_two(Request $request){
        $data = UtilService::getMore([['id',0]],$request);
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
    public function get_product_list()
    {
        $data = UtilService::getMore([
            ['sid',0],
            ['cid',0],
            ['mer_id',0],
            ['keyword',''],
            ['priceOrder',''],
            ['salesOrder',''],
            ['news',0],
            ['type',0],
            ['page',0],
            ['limit',0]
        ],$this->request);
        return JsonService::successful(StoreProduct::getProductList($data,$this->uid));
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
    public function get_product_news_list()
    {
        $where = UtilService::getMore([
            ['sid',0],
            ['cid',0],
            ['mer_id',0],
            ['keyword',''],
            ['priceOrder',''],
            ['salesOrder',''],
            ['news',0],
            ['type',0],
            ['page',0],
            ['limit',0]
        ],$this->request);
        $data['category']   = StoreCategory::where('pid','<>',0)->select();
        $data['list']       = StoreProduct::getProductList($where,$this->uid);
        return JsonService::successful($data);
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
     * $data['mer_id'] = $this->userInfo['uid'];
     */
    public function get_my_product_list()
    {
        $data = UtilService::getMore([
            ['mer_id',$this->userInfo['uid']],
            ['keyword','']
        ],$this->request);
        $keyword = $data['keyword'];
        unset($data['keyword']);
        $list = StoreProduct::where($data)->where('keyword|store_name','LIKE',htmlspecialchars("%$keyword%"))->select();
        foreach ($list as $item=>$value){
            $cate_nam = StoreCategory::where(array('id'=>$value['cate_id']))->find();
            if($value['type'] == 1){
                $type = '采购';
            } else {
                $type = '新品';
            }
            $list[$item]['type'] = $type;
            $list[$item]['cate_name'] = $cate_nam['cate_name'];
        }
        return JsonService::successful($list);
    }

    /**
     * 商品详情页
     * @param Request $request
     */
    public function details($id=0){
        if(!$id || !($storeInfo = StoreProduct::getValidProduct($id))) return JsonService::fail('商品不存在或已下架');
        $storeInfo['userCollect'] = StoreProductRelation::isProductRelation($id,$this->userInfo['uid'],'collect');
        list($productAttr,$productValue) = StoreProductAttr::getProductAttrDetail($id);
        setView($this->userInfo['uid'],$id,$storeInfo['cate_id'],'viwe');

        $cate_nam = StoreCategory::where(array('id'=>$storeInfo['cate_id']))->find();
        $storeInfo['cate_name'] = $cate_nam['cate_name'];

        $data['storeInfo'] = StoreProduct::setLevelPrice($storeInfo,$this->uid,true);
        $data['similarity'] = StoreProduct::cateIdBySimilarityProduct($storeInfo['cate_id'],'id,store_name,image,price,sales,ficti',4);
        $data['productAttr'] = $productAttr;
        $data['productValue'] = $productValue;
        $data['priceName']=StoreProduct::getPacketPrice($storeInfo,$productValue);
        $data['reply'] = StoreProductReply::getRecProductReply($storeInfo['id']);
        $data['replyCount'] = StoreProductReply::productValidWhere()->where('product_id',$storeInfo['id'])->count();
        if($data['replyCount']){
            $goodReply=StoreProductReply::productValidWhere()->where('product_id',$storeInfo['id'])->where('product_score',5)->count();
            $data['replyChance']=bcdiv($goodReply,$data['replyCount'],2);
            $data['replyChance']=bcmul($data['replyChance'],100,3);
        }else $data['replyChance']=0;
        $data['mer_id'] = StoreProduct::where('id',$storeInfo['id'])->value('mer_id');

        return JsonService::successful($data);
    }

    /*
     * 获取产品是否收藏
     *
     * */
    public function get_product_collect($product_id=0,$category = 'product')
    {
        return JsonService::successful(['userCollect'=>StoreProductRelation::isProductRelation($product_id,$this->userInfo['uid'],'collect',$category)]);
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
     * 获取收藏产品
     * @param int $first
     * @param int $limit
     * @return \think\response\Json
     */
    public function get_user_collect_all($page = 0,$limit = 8,$catetype = 0)
    {
        if($catetype){
            $catetype_arr = $catetype;
        } else {
            $catetype_arr = array('new_goods','supply','article','knowledge','company','brand','dealers','supplier','job','resume','product');
        }
        $list = StoreProductRelation::where('type','collect')->where('uid',$this->uid)->where('category','IN',$catetype_arr)->order('category DESC,add_time DESC')->page((int)$page,(int)$limit)->select();
        foreach ($list as $key=>$val){
            switch ($val['category']) {
                case 'new_goods':
                    $info = NewProduct::where('id',$val['product_id'])->field('id,name,image')->find();
                    $list[$key]['store_name'] = $info['name'];
                    $list[$key]['type'] = '新品';
                    $list[$key]['price'] = 0;
                    $list[$key]['url'] = 'goods/details';
                    $list[$key]['color'] = '#ffcc00';
                    break;
                case 'supply':
                    $info = Supply::where('id',$val['product_id'])->field('id,title,image')->find();
                    $list[$key]['store_name'] = $info['title'];
                    $list[$key]['type'] = '招商';
                    $list[$key]['price'] = 0;
                    $list[$key]['url'] = 'supply/details';
                    $list[$key]['color'] = '#ff0000';
                    break;
                case 'article':
                    $info = Article::where('id',$val['product_id'])->field('id,title,image,price')->find();
                    $list[$key]['store_name'] = $info['title'];
                    $list[$key]['type'] = '资讯';
                    $list[$key]['price'] = $info['price'];
                    $list[$key]['url'] = 'article/details';
                    $list[$key]['color'] = '#ffccff';
                    break;
                case 'knowledge':
                    $info = Knowledge::where('id',$val['product_id'])->field('id,title,image,price')->find();
                    $list[$key]['store_name'] = $info['title'];
                    $list[$key]['type'] = '干货';
                    $list[$key]['price'] = $info['price'];
                    $list[$key]['url'] = 'knowledge/details';
                    $list[$key]['color'] = '#ff00ff';
                    break;
                case 'company':
                    $info = Company::where('id',$val['product_id'])->field('id,title,logo')->find();
                    $info['image'] = $info['logo'];
                    $list[$key]['store_name'] = $info['title'];
                    $list[$key]['type'] = '企业';
                    $list[$key]['price'] = 0;
                    $list[$key]['url'] = 'company/details';
                    $list[$key]['color'] = '#66ff00';
                    break;
                case 'brand':
                    $info = Brand::where('id',$val['product_id'])->field('id,name,logo')->find();
                    $info['image'] = $info['logo'];
                    $list[$key]['store_name'] = $info['name'];
                    $list[$key]['type'] = '品牌';
                    $list[$key]['price'] = 0;
                    $list[$key]['url'] = 'brand/details';
                    $list[$key]['color'] = '#66ffff';
                    break;
                case 'dealers':
                    $info = Dealers::where('id',$val['product_id'])->field('id,title,logo')->find();
                    $info['image'] = $info['logo'];
                    $list[$key]['store_name'] = $info['title'];
                    $list[$key]['type'] = '经销商';
                    $list[$key]['price'] = 0;
                    $list[$key]['url'] = 'dealers/details';
                    $list[$key]['color'] = '#6633ff';
                    break;
                case 'supplier':
                    $info = Supplier::where('id',$val['product_id'])->field('id,name,logo')->find();
                    $info['image'] = $info['logo'];
                    $list[$key]['store_name'] = $info['name'];
                    $list[$key]['type'] = '供应商';
                    $list[$key]['price'] = 0;
                    $list[$key]['url'] = 'supplier/details';
                    $list[$key]['color'] = '#660000';
                    break;
                case 'job':
                    $info = JobPosition::where('id',$val['product_id'])->field('id,uid,address,position,salary')->find();
                    $company = Company::where('uid',$info['uid'])->field('id,title,logo')->find();

                    $position_info = Position::where(array('id'=>$info['position']))->find();
                    if($position_info['pid']) $positionOne = Position::where(array('id'=>$position_info['pid']))->find();

                    $info['image'] = $company['logo'];
                    $list[$key]['store_name'] = $positionOne ? $positionOne['name'].' · '.$position_info['name']:$position_info['name'];
                    $list[$key]['type'] = '招聘';
                    $list[$key]['price'] = $info['salary'];
                    $list[$key]['url'] = 'job/details';
                    $list[$key]['color'] = '#0066ff';
                    break;
                case 'resume':
                    $info = Resume::where('id',$val['product_id'])->field('id,uid,status')->find();
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

                    $info['image'] = $user['avatar'];
                    $list[$key]['store_name'] = $info['position'];
                    $list[$key]['type'] = '求职';
                    $list[$key]['price'] = $expect['salary'];
                    $list[$key]['url'] = 'resume/details';
                    $list[$key]['color'] = '#00cc33';
                    break;
                default:
                    $info = StoreProduct::where('id',$val['product_id'])->field('id,store_name,image,price')->find();
                    $list[$key]['store_name'] = $info['store_name'];
                    $list[$key]['type'] = '积分商品';
                    $list[$key]['price'] = $info['price'];
                    $list[$key]['url'] = 'goods_details';
                    $list[$key]['color'] = '#8DB6CD';
            }
            $list[$key]['pid'] = $info['id'];
            $list[$key]['image'] = $info['image'];
        }
        return JsonService::successful($list);
    }

    /**
     * 获取收藏产品删除
     * @param int $first
     * @param int $limit
     * @return \think\response\Json
     */
    public function get_user_collect_product_del($id=0)
    {
        if($id){
            $data = StoreProductRelation::where('uid',$this->userInfo['uid'])->where('id',$id)->find();
            if($data){
                StoreProductRelation::where('id',$data['id'])->delete();
                return JsonService::successful($data);
            } else {
                return JsonService::fail('缺少参数');
            }
        } else {
            return JsonService::fail('缺少参数');
        }
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

    /*
    * 获取产品海报
    * @param int $id 产品id
    * */
    public function poster($id = 0){
//        if(!$id) return JsonService::fail('参数错误');
//        $productInfo = StoreProduct::getValidProduct($id,'store_name,id,price,image,code_path');
//        if(empty($productInfo)) return JsonService::fail('参数错误');
//        if(strlen($productInfo['code_path'])< 10) {
//            $path = 'public'.DS.'uploads'.DS.'codepath'.DS.'product';
//            $codePath = $path.DS.$productInfo['id'].'.jpg';
//            if(!file_exists($codePath)){
//                if(!is_dir($path)) mkdir($path,0777,true);
//                $res = file_put_contents($codePath,RoutineCode::getPages('pages/goods_details/index?id='.$productInfo['id']));
//            }
//            $res = StoreProduct::edit(['code_path'=>$codePath],$id);
//            if($res) $productInfo['code_path'] = $codePath;
//            else return JsonService::fail('没有查看权限');
//        }
//        $posterPath = createPoster($productInfo);
//        return JsonService::successful($posterPath);
    }

    /**
     * 产品海报二维码
     * @param int $id
     * pages/goods_details/index
     */
    public function product_promotion_code($id = 0){
        if(!$id) return JsonService::fail('参数错误ID不存在');
        $count = StoreProduct::validWhere()->count();
        if(!$count) return JsonService::fail('参数错误');
        $path = makePathToUrl('routine/product/',4);
        if($path == '') return JsonService::fail('生成上传目录失败,请检查权限!');
        $codePath = $path.$id.'_'.$this->userInfo['uid'].'_product.jpg';
        $domain = SystemConfigService::get('site_url').'/';
        if(!file_exists($codePath)){
            if(!is_dir($path)) mkdir($path,0777,true);
            $data='?id='.$id;
            if($this->userInfo['is_promoter'] || SystemConfigService::get('store_brokerage_statu')==2) $data.='&pid='.$this->uid;
            $res = RoutineCode::getPageCode('',$data,280);
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

    public function edit_user_goods()
    {

        $request = Request::instance();
        if (!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['cate_id', ''],
            ['comment', ''],
            ['description', ''],
            ['keyword', ''],
            ['store_info', ''],
            ['store_name', ''],
            ['unit_name', ''],
            ['pics', []],
            ['is_show', 1],
            ['type', 1],
            ['add_time', time()],
            ['id', 0]
        ], $request);

        $data['mer_id'] = $this->userInfo['uid'];
        $data['image'] = $data['pics'][0];

        $data['slider_image'] = json_encode($data['pics']);
        unset($data['region_arr']);

        if ($data['id'] && StoreProduct::be(['id' => $data['id'], 'mer_id' => $this->userInfo['uid'], 'is_show' => 1])) {
            $id = $data['id'];
            unset($data['id']);
            if (StoreProduct::edit($data, $id, 'id')) {
                return JsonService::successful();
            } else
                return JsonService::fail('编辑产品失败!');
        } else {
            if ($res = StoreProduct::set($data)) {
                return JsonService::successful(['id' => $res->id]);
            } else
                return JsonService::fail('添加产品失败!');
        }
    }

    public function get_del_product($id = 0){
        $data = StoreProduct::where(array('id'=>$id))->find();
        if($data){
            $delete = StoreProduct::where(array('id'=>$data['id']))->delete();
            if($delete){
                return JsonService::successful('删除产品成功！');
            } else {
                return JsonService::fail('删除产品失败!');
            }

            if($data['image']=='') return $this->fail('缺少删除资源');
            $type=['php','js','css','html','ttf','otf'];
            $data['image'] = substr($data['image'],1);
            $ext=substr($data['image'],-3);
            if(in_array($ext,$type)) return $this->fail('非法操作');
            if(strstr($data['image'],'uploads')===false) return $this->fail('非法操作');
            try{
                if(file_exists($data['image'])) unlink($data['image']);
                if(strstr($data['image'],'s_')!==false){
                    $pic=str_replace(['s_'],'',$data['image']);
                    if(file_exists($pic)) unlink($pic);
                }
                return $this->successful('删除成功');
            }catch (\Exception $e){
                return $this->fail('刪除失败',['line'=>$e->getLine(),'message'=>$e->getMessage()]);
            }

        } else {
            return JsonService::fail('删除产品失败!');
        }

    }

    /**
     * 品牌菜单
     * ================================================================================================ 品牌 E
     */
    public function get_pid_brand(){
        $where = UtilService::getMore([
            ['mer_id',0]
        ],$this->request);
        $data = StoreBrand::pidByBrand(0,'id,cate_name',0,$where);//一级分类
        if(Cache::has('one_pid_brand_list'))
            return JsonService::successful(Cache::get('one_pid_brand_list'));
        else{
            Cache::set('one_pid_brand_list',$data);
            return JsonService::successful($data);
        }
    }

    /**
     * 品牌列表
     */
    public function get_brand()
    {
        $where = UtilService::getMore([
            ['mer_id',0]
        ],$this->request);

        $data = StoreBrand::getProductBrand($where);
        return JsonService::successful($data);
    }

    /**
     * 品牌一级类目
     */
    public function get_cate_brand(){
        $data = StoreBrand::pidByBrand(0,'id,cate_name');//一级分类
        if(Cache::has('get_cate_brand'))
            return JsonService::successful(Cache::get('get_cate_brand'));
        else{
            Cache::set('get_cate_brand',$data);
            return JsonService::successful($data);
        }
    }

    /**
     * 品牌二级类目
     * @param Request $request
     */
    public function get_two_cate_brand(Request $request){
        $data = UtilService::getMore([['id',0]],$request);
        $dataCateA = [];
        $dataCateA[0]['id'] = $data['id'];
        $dataCateA[0]['cate_name'] = '默认';
        $dataCateA[0]['pid'] = 0;
        $dataCateE = StoreBrand::pidBySidList($data['id']);//根据一级分类获取二级分类
        if($dataCateE) $dataCateE = $dataCateE->toArray();
        $dataCate = [];
        $dataCate = array_merge_recursive($dataCateA,$dataCateE);
        return JsonService::successful($dataCate);
    }

    /**
     * 品牌详情页
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_brand_details($id=0){
        if(!$id || !($data = StoreBrand::where(array('id'=>$id))->find())) return JsonService::fail('品牌不存在或已下架');
        $data['slider_image'] = $data['slider_image'] ? json_decode($data['slider_image']) : [];
        return JsonService::successful($data);
    }

    /**
     * 品牌列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_brand_list()
    {
        $data = UtilService::getMore([
            ['mer_id',$this->userInfo['uid']],
            ['keyword',''],
            ['page',0],
            ['limit',0]
        ],$this->request);
        $keyword = $data['keyword'];
        $page       = $data['page'];
        $limit      = $data['limit'];
        unset($data['page']);
        unset($data['limit']);
        unset($data['keyword']);
        $list = StoreBrand::where($data)->where('cate_name|description','LIKE',htmlspecialchars("%$keyword%"))->page(intval($page),intval($limit))->select();
        foreach ($list as $item=>$value){
            $info = StoreBrand::where('id',$value['pid'])->find();
            $list[$item]['pname'] = $info['cate_name'];
            $list[$item]['add_time'] = date('Y-m-d',$value['add_time']);
            $list[$item]['slider_image'] = $value['slider_image'];
        }
        return JsonService::successful($list);
    }

    /**
     * 删除品牌
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_del_brand($id = 0){
        $data = StoreBrand::where(array('id'=>$id))->find();
        if($data){
            $delete = StoreBrand::where(array('id'=>$data['id']))->delete();
            if($delete){
                return JsonService::successful('删除产品成功！');
            } else {
                return JsonService::fail('删除产品失败!');
            }

            if($data['pic'] == '') return $this->fail('缺少删除资源');
            $type = ['php','js','css','html','ttf','otf'];
            $data['pic'] = substr($data['pic'],1);
            $ext = substr($data['pic'],-3);
            if(in_array($ext,$type)) return $this->fail('非法操作');
            if(strstr($data['pic'],'uploads')===false) return $this->fail('非法操作');
            try{
                if(file_exists($data['pic'])) unlink($data['pic']);
                if(strstr($data['pic'],'s_')!==false){
                    $pic=str_replace(['s_'],'',$data['pic']);
                    if(file_exists($pic)) unlink($pic);
                }
                return $this->successful('删除成功');
            }catch (\Exception $e){
                return $this->fail('刪除失败',['line'=>$e->getLine(),'message'=>$e->getMessage()]);
            }

        } else {
            return JsonService::fail('删除失败!');
        }

    }

    /**
     * 添加编辑品牌
     * ================================================================================================ 品牌 E
     */
    public function edit_user_brand()
    {
        $request = Request::instance();
        if (!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['pid', ''],
            ['cate_name', ''],
            ['price', 0],
            ['comment', ''],
            ['description', ''],
            ['pics', []],
            ['is_show', 1],
            ['add_time', time()],
            ['id', 0]
        ], $request);

        $data['mer_id'] = $this->userInfo['uid'];
        $data['pic'] = $data['pics'][0];
        $data['slider_image'] = json_encode($data['pics']);
        unset($data['pics']);

        if ($data['id'] && StoreBrand::be(['id' => $data['id'], 'mer_id' => $this->userInfo['uid'], 'is_show' => 1])) {
            $id = $data['id'];
            unset($data['id']);
            if (StoreBrand::edit($data, $id, 'id')) {
                return JsonService::successful('编辑成功!');
            } else
                return JsonService::fail('编辑失败!');
        } else {
            if ($res = StoreBrand::set($data)) {
                return JsonService::successful(['id' => $res->id]);
            } else
                return JsonService::fail('添加失败!');
        }
    }

}