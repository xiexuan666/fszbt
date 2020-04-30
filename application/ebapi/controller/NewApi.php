<?php
namespace app\ebapi\controller;


use app\core\model\routine\RoutineCode;//待完善
use app\ebapi\model\company\Company;
use app\ebapi\model\store\Brand;
use app\ebapi\model\store\NewCategory;
use app\ebapi\model\store\StoreBrand;
use app\ebapi\model\store\StoreOrderCartInfo;
use app\ebapi\model\store\NewProduct;
use app\ebapi\model\store\NewProductAttr;
use app\ebapi\model\store\NewProductRelation;
use app\ebapi\model\store\NewProductReply;
use app\core\util\GroupDataService;
use service\JsonService;
use app\core\util\SystemConfigService;
use service\UtilService;
use service\CacheService;
use app\core\util\MiniProgramService;
use app\core\model\user\UserLevel;
use app\core\model\system\SystemUserTask;
use app\core\model\user\UserTaskFinish;
use think\Request;
use think\Cache;

/**
 * 新品管理api接口
 * Class NewApi
 * @package app\ebapi\controller
 *
 */
class NewApi extends AuthController
{

    public static function whiteList()
    {
        return [
            'goods_search',
            'get_routine_hot_search',
            'get_pid_cate',
            'get_product_category',
            'get_product_list',
            'get_product_news_list',
            'details',
            'get_two',
            'get_id_cate',
            'get_my_product_list',
            'edit_user_goods'
        ];
    }

    /**
     * 搜索页面
     */
    public function goods_search()
    {
        list($keyword) = UtilService::getMore([['keyword',0]],null,true);
        return JsonService::successful(NewProduct::getSearchStorePage($keyword,$this->uid));
    }

    /**
     * 一级分类
     */
    public function get_pid_cate(){
        $data = NewCategory::pidByCategory(0,'id,cate_name');//一级分类
        if(Cache::has('new_pid_cate_list'))
            return JsonService::successful(Cache::get('new_pid_cate_list'));
        else{
            Cache::set('new_pid_cate_list',$data);
            return JsonService::successful($data);
        }
    }

    /**
     * 二级分类
     * @param Request $request
     */
    public function get_id_cate(Request $request){
        $data = UtilService::postMore([['id',0]],$request);
        $dataCateA = [];
        $dataCateA[0]['id'] = $data['id'];
        $dataCateA[0]['cate_name'] = '全部商品';
        $dataCateA[0]['pid'] = 0;
        $dataCateE = NewCategory::pidBySidList($data['id']);//根据一级分类获取二级分类
        if($dataCateE) $dataCateE = $dataCateE->toArray();
        $dataCate = [];
        $dataCate = array_merge_recursive($dataCateA,$dataCateE);
        return JsonService::successful($dataCate);
    }

    /**
     * 获取一级和二级分类
     */
    public function get_product_category()
    {
        $data = NewCategory::getProductCategory();
        return JsonService::successful($data);
    }

    /**
     * 一级分类
     */
    public function get_one(){
        $data = NewCategory::pidByCategory(0,'id,cate_name');//一级分类
        if(Cache::has('new_pid_cate_list'))
            return JsonService::successful(Cache::get('new_pid_cate_list'));
        else{
            Cache::set('new_pid_cate_list',$data);
            return JsonService::successful($data);
        }
    }

    /**
     * 二级分类
     * @param Request $request
     */
    public function get_two(Request $request){
        $data = UtilService::getMore([['id',0]],$request);
        $dataCateA = [];
        /*$dataCateA[0]['id'] = $data['id'];
        $dataCateA[0]['cate_name'] = '全部分类';
        $dataCateA[0]['pid'] = 0;*/
        $dataCateE = NewCategory::pidBySidList($data['id']);//根据一级分类获取二级分类
        if($dataCateE) $dataCateE = $dataCateE->toArray();
        $dataCate = [];
        $dataCate = array_merge_recursive($dataCateA,$dataCateE);
        return JsonService::successful($dataCate);
    }

    /**
     * 页面产品
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
        return JsonService::successful(NewProduct::getProductList($data,$this->uid));
    }

    /**
     * 页面产品
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_product_news_list(Request $request)
    {
        $datas = UtilService::getMore([['id',0]],$request);
        $where = UtilService::getMore([
            ['cid',0],
            ['mer_id',0],
            ['keyword',''],
            ['is_all',0],
            ['is_news',0],
            ['is_hot',0],
            ['page',0],
            ['limit',0]
        ],$this->request);

        $dataCateA = [];
        $dataCateA[0]['id'] = $datas['id'];
        $dataCateA[0]['cate_name'] = '全部';
        $dataCateA[0]['pid'] = 0;

        $dataCateE = NewCategory::where('pid','<>',0)->select();
        if($dataCateE) $dataCateE = $dataCateE->toArray();
        $dataCate = array_merge_recursive($dataCateA,$dataCateE);

        $data['category']   = $dataCate;
        $data['list']       = NewProduct::getProductList($where,$this->uid);

        return JsonService::successful($data);
    }

    /**
     * 页面产品
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_my_product_list()
    {
        $data = UtilService::getMore([
            ['mer_id',$this->userInfo['uid']],
            ['name',''],
            ['page',0],
            ['limit',0]
        ],$this->request);
        $keyword = $data['name'];
        $page = $data['page'];
        $limit = $data['limit'];
        unset($data['page']);
        unset($data['limit']);
        unset($data['name']);
        $list = NewProduct::where($data)->where('name','LIKE',htmlspecialchars("%$keyword%"))->page((int)$page,(int)$limit)->select();
        foreach ($list as $item=>$value){
            $cate_nam = NewCategory::where(array('id'=>$value['cate_id']))->find();
            $list[$item]['cate_name'] = $cate_nam['cate_name'];
        }
        return JsonService::successful($list);
    }

    /**
     * 商品详情页
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function details($id=0){
        if(!$id || !($data = NewProduct::getValidProduct($id))) return JsonService::fail('商品不存在或已下架');
        $data["browse"] = $data["browse"] + 1;
        NewProduct::edit(['browse'=>$data["browse"]],$id);//增加浏览次数
        $company = Company::where(array('uid'=>$data['mer_id']))->field('id,title')->find();
        if($company){
            $data['is_shoping'] = 'company';
            $data['shoping_id'] = $company['id'];
        }
        $brand = Brand::where(array('mer_id'=>$data['mer_id']))->field('id,name')->find();
        if($brand){
            $data['is_shoping'] = 'brand';
            $data['shoping_id'] = $brand['id'];
        }

        $cate_nam = NewCategory::where(array('id'=>$data['cate_id']))->find();
        $data['cate_name'] = $cate_nam['cate_name'];
        return JsonService::successful($data);
    }

    /**
     * 获取产品海报
     * @param int $id
     */
    public function poster($id = 0){
        if(!$id) return JsonService::fail('参数错误');
        $productInfo = NewProduct::getValidProduct($id,'store_name,id,price,image,code_path');
        if(empty($productInfo)) return JsonService::fail('参数错误');
        if(strlen($productInfo['code_path'])< 10) {
            $path = 'public'.DS.'uploads'.DS.'codepath'.DS.'product';
            $codePath = $path.DS.$productInfo['id'].'.jpg';
            if(!file_exists($codePath)){
                if(!is_dir($path)) mkdir($path,0777,true);
                $res = file_put_contents($codePath,RoutineCode::getPages('pages/goods_details/index?id='.$productInfo['id']));
            }
            $res = NewProduct::edit(['code_path'=>$codePath],$id);
            if($res) $productInfo['code_path'] = $codePath;
            else return JsonService::fail('没有查看权限');
        }
        $posterPath = createPoster($productInfo);
        return JsonService::successful($posterPath);
    }

    /**
     * 产品海报二维码
     * @param int $id
     * @throws \think\Exception
     */
    public function product_promotion_code($id = 0){
        if(!$id) return JsonService::fail('参数错误ID不存在');
        $count = NewProduct::validWhere()->count();
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

    /**
     * 发布商品
     */
    public function edit_user_goods()
    {
        $request = Request::instance();
        if (!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['cate_id', 0],
            ['poster',[]],
            ['name', ''],
            ['pics', []],
            ['is_show', 1],
            ['num',0],
            ['add_time', time()],
            ['is_consent',0],
            ['id', 0]
        ], $request);

        $data['mer_id'] = $this->userInfo['uid'];
        $data['image'] = $data['poster'][0];

        $data['poster_image'] = json_encode($data['poster']);
        unset($data['poster']);

        $data['slider_image'] = json_encode($data['pics']);
        unset($data['pics']);

        if ($data['id'] && NewProduct::be(['id' => $data['id'], 'mer_id' => $this->userInfo['uid'], 'is_show' => 1])) {
            $id = $data['id'];
            unset($data['id']);
            if (NewProduct::edit($data, $id, 'id')) {
                return JsonService::successful(array('id'=>$id,'msg'=>'编辑成功！'));
            } else
                return JsonService::fail('编辑新品失败!');
        } else {
            
            $cacheName = $this->userInfo['uid'].'newsgoods';
            $result = CacheService::get($cacheName,null);
            if(bcadd($result['add_time'],10,0) > time()) return JsonService::fail('不能重复提交!');

            if($res = NewProduct::set($data)){
                if($data['num'] > 0){
                    $this->NumberAddGoods();
                }
                $cacheTime = 1800;
                CacheService::set($cacheName,$res,$cacheTime);
                return JsonService::successful(array('id'=>$res->id,'msg'=>'发布成功！'));
            } else {
                return JsonService::fail('添加失败!');
            }

        }
    }

    /**
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 检查-招商-赠送剩余额度
     */
    public function NumberAddGoods(){
        $level_id = UserLevel::getUserLevel($this->userInfo['uid']);
        $level = UserLevel::getUserLevelInfo($level_id);
        $data = SystemUserTask::where('level_id',$level['level_id'])->where('task_type','NumberAddGoods')->find();
        $count = UserTaskFinish::where('task_id',$data['id'])->where('uid',$this->userInfo['uid'])->where('status',1)->count();
        if($data['number'] >= $count){
            $set['uid'] = $this->userInfo['uid'];
            $set['task_id'] = $data['id'];
            $set['status'] = 1;
            $set['add_time'] = time();
            UserTaskFinish::set($set);
        }
    }

    /**
     * 删除商品
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_del_product($id = 0){
        $data = NewProduct::where(array('id'=>$id))->find();
        if($data){
            $delete = NewProduct::where(array('id'=>$data['id']))->delete();
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

}