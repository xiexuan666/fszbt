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
use service\UtilService;
use think\Cache;
use think\Request;
use think\Url;
use service\JsonService;

class Goods extends AuthController
{
    /**
     * @return mixed
     * 产品管理
     */
    public function admin(){
        return $this->fetch();
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 请求产品列表
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

        $data = CompanyProduct::where($where)->where('is_show',1)->where('cate_id','LIKE',htmlspecialchars("%$keyword%"))->order('id desc')->page((int)$page,(int)$limit)->select();
        foreach ($data as $item=>$value){
            $cate_name = CompanyClass::where(array('id'=>$value['cate_id']))->find();
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
     * 添加 编辑 产品
     */
    public function create(){
        $list = CompanyClass::where('mer_id',$this->uid)->select()->toArray();
        $where = UtilService::getMore([
            ['mer_id',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        if($where['id']){
            $data = CompanyProduct::where($where)->find()->toArray();
            if($data) {
                $data['slider_image_data'] = implode(",",$data['slider_image']);
            }
        } else {
            $data = array(
                'cate_id'=>0,
                'image'=>'',
                'slider_image'=>[],
                'slider_image_data'=>'',
                'is_consent'=>0,
                'id'=>0
            );
        }

        $this->assign(compact('list','data'));
        return $this->fetch();
    }

    /**
     * 添加 编辑 保存产品
     */
    public function create_save(){
        $request = Request::instance();
        if (!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['cate_id', 0],
            ['image',''],
            ['slider_image', ''],
            ['is_show', 1],
            ['add_time', time()],
            ['is_consent',0],
            ['id', 0]
        ], $request);

        $data['mer_id'] = $this->userInfo['uid'];
        if($data['slider_image']) $data['slider_image'] = json_encode(array_filter(explode(',',$data['slider_image'])));
        if($data['cate_id'] == 0) return JsonService::fail('请选择系列!');
        if($data['image'] == '') return JsonService::fail('请上传产品封面!');
        if($data['slider_image'] == '') return JsonService::fail('请上传产品详情!');
        if($data['is_consent']) {
            $data['is_consent'] = 1;
        } else {
            return JsonService::fail('请阅读并同意发布信息协议!');
        }
        if ($data['id'] && CompanyProduct::be(['id' => $data['id'], 'mer_id' => $this->userInfo['uid'], 'is_show' => 1])) {
            $id = $data['id'];
            unset($data['id']);
            if (CompanyProduct::edit($data, $id, 'id')) {
                return JsonService::successful('编辑成功!');
            } else
                return JsonService::fail('编辑失败!');
        } else {
            if ($res = CompanyProduct::set($data)) {
                return JsonService::successful('添加成功!');
            } else
                return JsonService::fail('添加失败!');
        }
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 删除产品
     */
    public function del(){
        $where = UtilService::getMore([
            ['mer_id',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        $data = CompanyProduct::where($where)->find();
        if(!$data) return JsonService::fail('删除的数据丢失了!');
        $delData = CompanyProduct::where($where)->delete();
        if(!$delData) return JsonService::fail('网络忙，稍后再试!');
        return JsonService::successful('数据删除成功!');
    }

    /**
     * @return mixed
     * 产品系列管理
     * =================================================================
     * ||
     * || 产品系列管理
     * ||
     * =================================================================
     */
    public function series(){
        return $this->fetch();
    }

    /**
     * 请求产品系列列表
     */
    public function get_series_list(){
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

        $data = CompanyClass::where($where)->where('is_show',1)->where('cate_name','LIKE',htmlspecialchars("%$keyword%"))->order('id desc')->page((int)$page,(int)$limit)->select();

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
     * 添加 编辑产品系列
     */
    public function series_create(){
        $where = UtilService::getMore([
            ['mer_id',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        if($where['id']){
            $data = CompanyClass::where($where)->find()->toArray();
        } else {
            $data = array(
                'cate_name'=>'',
                'id'=>0
            );
        }
        $this->assign(compact('data'));
        return $this->fetch();
    }

    /**
     * 添加 编辑 保存产品系列
     */
    public function series_create_save(){

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
                return JsonService::successful('编辑成功！');
            } else
                return JsonService::fail('编辑失败!');
        } else {
            if ($res = CompanyClass::set($data)) {
                return JsonService::successful('添加成功！');
            } else
                return JsonService::fail('添加失败!');
        }

    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 删除 产品系列
     */
    public function series_del(){
        $where = UtilService::getMore([
            ['mer_id',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        $data = CompanyClass::where($where)->find();
        if(!$data) return JsonService::fail('删除的数据丢失了!');
        $delData = CompanyClass::where($where)->delete();
        if(!$delData) return JsonService::fail('网络忙，稍后再试!');
        return JsonService::successful('数据删除成功!');
    }
}