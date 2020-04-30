<?php
namespace app\ebapi\controller;

use app\ebapi\model\store\Brand as BrandModel;
use app\ebapi\model\store\BrandCategory;
use app\core\util\SystemConfigService;
use service\JsonService;
use service\UtilService;
use think\Request;
use think\Cache;

/**
 * 小程序产品和产品分类api接口
 * Class BrandApi
 * @package app\ebapi\controller
 *
 */
class BrandApi extends AuthController
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

            'get_brand_list',
            'get_product_news_list',
            'details',
            'get_two',
            'get_id_cate',
            'get_my_product_list',
            'edit_user_goods',
            'get_user_brand'
        ];
    }

    /**
     * 品牌菜单
     * ================================================================================================ 品牌 E
     */
    public function get_pid_brand(){
        $data = BrandCategory::where('pid','<>',0)->select();
        return JsonService::successful($data);
    }

    /**
     * 品牌列表
     */
    public function get_brand()
    {
        $where = UtilService::getMore([
            ['mer_id',$this->userInfo['uid']],
            ['keyword','']
        ],$this->request);
        $keyword = $where['keyword'];
        unset($where['keyword']);
        $list = BrandCategory::where('pid','<>',0)->select();
        foreach ($list as $item=>$value){
            $data = BrandModel::where('is_show',1)->where('cate_id',$value['id'])->where('name','LIKE',htmlspecialchars("%$keyword%"))->order('browse DESC')->select();
            $list[$item]['child'] = $data;
        }


        return JsonService::successful($list);
    }

    /**
     * 品牌详情页
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_brand_details($id=0){
        if(!$id || !($data = BrandModel::where(array('id'=>$id))->find())) return JsonService::fail('品牌不存在或已下架');
        $data["browse"] = $data["browse"] + 1;
        BrandModel::edit(['browse'=>$data["browse"]],$id);//增加浏览次数
        return JsonService::successful($data);
    }

    /**
     * 品牌详情页
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_user_brand(){
        $data = BrandModel::where('mer_id',$this->userInfo['uid'])->find();
        if($data){
            return JsonService::successful($data);
        } else {
            return JsonService::fail('没有数据!');
        }
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
            ['page',0],
            ['limit',0]
        ],$this->request);
        $page       = $data['page'];
        $limit      = $data['limit'];
        unset($data['page']);
        unset($data['limit']);
        $list = BrandModel::where($data)->where('is_show',1)->page(intval($page),intval($limit))->select();
        foreach ($list as $item=>$value){
            $list[$item]['add_time'] = date('Y-m-d H:i',$value['add_time']);
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
        $data = BrandModel::where(array('id'=>$id))->find();
        if($data){
            $delete = BrandModel::where(array('id'=>$data['id']))->delete();
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
            ['name',''],
            ['logo', []],
            ['recruitment', []],
            ['poster', []],
            ['classification', []],
            ['about', []],
            ['series', []],
            ['dot', []],
            ['contact', []],
            ['is_show', 1],
            ['is_consent',0],
            ['add_time', time()],
            ['id', 0]
        ], $request);

        $data['mer_id'] = $this->userInfo['uid'];
        $data['logo'] = $data['logo'][0];
        $data['recruitment'] = $data['recruitment'][0];
        $data['poster_image'] = json_encode($data['poster']);
        $data['classification'] = $data['classification'][0];
        $data['about'] = $data['about'][0];
        $data['slider_image'] = json_encode($data['series']);
        $data['dot'] = $data['dot'][0];
        $data['contact'] = $data['contact'][0];

        if ($data['id'] && BrandModel::be(['id' => $data['id'], 'mer_id' => $this->userInfo['uid'], 'is_show' => 1])) {
            $id = $data['id'];
            unset($data['id']);
            if (BrandModel::edit($data, $id, 'id')) {
                return JsonService::successful('编辑成功!');
            } else
                return JsonService::fail('编辑失败!');
        } else {
            if ($res = BrandModel::set($data)) {
                return JsonService::successful(['id' => $res->id]);
            } else
                return JsonService::fail('添加失败!');
        }
    }

    /**
     * 产品海报二维码
     * @param int $id
     */
    public function product_promotion_code($id = 0){
        if(!$id) return JsonService::fail('参数错误ID不存在');
        $count = BrandModel::validWhere()->count();
        if(!$count) return JsonService::fail('参数错误');
        $path = makePathToUrl('routine/product/',4);
        if($path == '') return JsonService::fail('生成上传目录失败,请检查权限!');
        $codePath = $path.$id.'_'.$this->userInfo['uid'].'_product.jpg';
        $domain = SystemConfigService::get('site_url').'/';
        if(!file_exists($codePath)){
            if(!is_dir($path)) mkdir($path,0777,true);
            $data='id='.$id;
            if($this->userInfo['is_promoter'] || SystemConfigService::get('store_brokerage_statu')==2) $data.='&pid='.$this->uid;
            $res = RoutineCode::getPageCode('pages/brand/details/index',$data,280);
            if($res) file_put_contents($codePath,$res);
            else return JsonService::fail('二维码生成失败');
        }
        return JsonService::successful($domain.$codePath);
    }

}