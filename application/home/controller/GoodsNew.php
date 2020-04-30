<?php
/**
 *
 * @author: 招宝通
 */

namespace app\home\controller;


use app\admin\model\system\SystemConfig;
use app\home\model\store\StoreBargain;
use app\home\model\store\StoreBargainUser;
use app\home\model\store\StoreBargainUserHelp;
use app\home\model\store\StoreCategory;
use app\home\model\store\StoreCoupon;
use app\home\model\store\StoreSeckill;
use app\home\model\store\StoreCouponIssue;
use app\home\model\store\StoreCouponIssueUser;
use app\home\model\store\StoreCouponUser;
use app\home\model\store\StorePink;
use app\home\model\store\StoreProductReply;
use app\home\model\store\StoreCart;
use app\home\model\store\StoreOrder;
use app\home\model\store\StoreProduct;
use app\home\model\store\StoreProductAttr;
use app\home\model\store\StoreProductRelation;
use app\home\model\user\User;
use app\home\model\user\WechatUser;
use app\home\model\store\StoreCombination;
use app\core\util\GroupDataService;
use app\core\util\SystemConfigService;

use app\ebapi\model\store\CompanyClass;
use app\ebapi\model\store\CompanyProduct;
use app\ebapi\model\store\NewCategory;
use app\ebapi\model\store\NewProduct;

use service\UtilService;
use think\Cache;
use think\Request;
use think\Url;
use service\JsonService;

class GoodsNew extends AuthController
{
    /**
     * @return mixed
     * 新品管理
     */
    public function admin(){
        return $this->fetch();
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 请求新品列表
     */
    public function get_list(){
        $where = UtilService::getMore([
            ['mer_id',$this->userInfo['uid']],
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

        $data = NewProduct::where($where)->where('is_show',1)->where('cate_id','LIKE',htmlspecialchars("%$keyword%"))->order('id desc')->page((int)$page,(int)$limit)->select();
        foreach ($data as $item=>$value){
            $cate_name = NewCategory::where(array('id'=>$value['cate_id']))->find();
            $data[$item]['cname'] = $cate_name['cate_name'];
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
     * 添加 编辑 新品
     */
    public function create(){
        $list = NewCategory::where('pid',0)->select()->toArray();
        $where = UtilService::getMore([
            ['mer_id',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        if($where['id']){
            $data = NewProduct::where($where)->find()->toArray();
            if($data) {
                $data['poster_image_data'] = implode(",",$data['poster_image']);
                $data['slider_image_data'] = implode(",",$data['slider_image']);
            }
        } else {
            $data = array(
                'name'=>'',
                'poster_image'=>[],
                'slider_image'=>[],
                'poster_image_data'=>'',
                'slider_image_data'=>'',
                'is_consent'=>0,
                'id'=>0
            );
        }

        $this->assign(compact('list','data'));
        return $this->fetch();
    }

    /**
     * 添加 编辑 保存新品
     */
    public function save(){
        $request = Request::instance();
        if (!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['drpprovince', 0],
            ['drpcity', 0],
            ['drparea', 0],
            ['name',''],
            ['poster_image', ''],
            ['slider_image', ''],
            ['is_show', 1],
            ['add_time', time()],
            ['is_consent',0],
            ['cate_id', 0],
            ['id', 0]
        ], $request);

        $data['mer_id'] = $this->userInfo['uid'];
        if($data['drpprovince'])$data['cate_id'] = $data['drpprovince'];
        if($data['drpcity'])    $data['cate_id'] = $data['drpcity'];
        if($data['drparea'])    $data['cate_id'] = $data['drparea'];
        if($data['cate_id'] == 0)       return JsonService::fail('请选择分类!');

        if($data['poster_image']) $poster_image = array_filter(explode(',',$data['poster_image']));
        if($data['slider_image']) $slider_image = array_filter(explode(',',$data['slider_image']));

        if(count($poster_image)) {
            $data['image'] = $poster_image[1];
            $data['poster_image'] = json_encode($poster_image);
        } else {
            return JsonService::fail('请上传新品海报!');
        }
        
        if(count($slider_image)) {
            $data['slider_image'] = json_encode($slider_image);
        } else {
            return JsonService::fail('请上传新品详情!');
        }

        if($data['is_consent']) {
            $data['is_consent'] = 1;
        } else {
            return JsonService::fail('请阅读并同意发布信息协议!');
        }

        if ($data['id'] && NewProduct::be(['id' => $data['id'], 'mer_id' => $this->userInfo['uid'], 'is_show' => 1])) {
            $id = $data['id'];
            unset($data['id']);
            if (NewProduct::edit($data, $id, 'id')) {
                return JsonService::successful('编辑成功!');
            } else
                return JsonService::fail('编辑失败!');
        } else {
            if ($res = NewProduct::set($data)) {
                return JsonService::successful('添加成功!');
            } else
                return JsonService::fail('添加失败!');
        }
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 删除新品
     */
    public function del(){
        $where = UtilService::getMore([
            ['mer_id',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        $data = NewProduct::where($where)->find();
        if(!$data) return JsonService::fail('删除的数据丢失了!');
        $delData = NewProduct::where($where)->delete();
        if(!$delData) return JsonService::fail('网络忙，稍后再试!');
        return JsonService::successful('数据删除成功!');
    }
}