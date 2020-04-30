<?php
/**
 *
 * @author: 招宝通
 */

namespace app\home\controller;

use app\ebapi\model\store\Brand as BrandModel;
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
use service\UtilService;
use think\Cache;
use think\Request;
use think\Url;
use service\JsonService;

/**
 * Class Brand
 * @package app\home\controller
 * 品牌商 控制器
 */
class Brand extends AuthController
{
    /**
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 完善品牌商信息
     */
    public function admin()
    {
        $where = UtilService::getMore([
            ['mer_id',$this->userInfo['uid']]
        ],$this->request);
        $data = BrandModel::where($where)->find();
        if($data) $data = $data->toArray();
        if($data) {
            if(!is_array($data['poster_image'])) $data['poster_image'] = json_decode($data['poster_image'],true);
            if(!is_array($data['slider_image'])) $data['slider_image'] = json_decode($data['slider_image'],true);
            $data['poster_image_data'] = implode(",",$data['poster_image']);
            $data['slider_image_data'] = implode(",",$data['slider_image']);
        } else {
            $data = array(
                'logo'=>'',
                'name'=>'',
                'recruitment'=>'',
                'poster_image'=>[],
                'poster_image_data'=>'',
                'classification'=>'',
                'slider_image'=>[],
                'slider_image_data'=>'',
                'dot'=>'',
                'contact'=>'',
                'about'=>'',
                'is_consent'=>0,
                'id'=>0
            );
        }

        $this->assign(compact('data'));
        return $this->fetch();
    }

    /**
     * 完善品牌商信息 提交
     */
    public function save()
    {
        $request = Request::instance();
        if (!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['name',''],
            ['logo', ''],
            ['recruitment', ''],
            ['poster_image', ''],
            ['classification', ''],
            ['about', ''],
            ['slider_image', ''],
            ['dot', ''],
            ['contact', ''],
            ['is_show', 1],
            ['is_consent',0],
            ['add_time', time()],
            ['id', 0]
        ], $request);

        $data['mer_id'] = $this->userInfo['uid'];

        if($data['poster_image']) $data['poster_image'] = json_encode(array_filter(explode(',',$data['poster_image'])));
        if($data['slider_image']) $data['slider_image'] = json_encode(array_filter(explode(',',$data['slider_image'])));

        if($data['is_consent']) {
            $data['is_consent'] = 1;
        } else {
            return JsonService::fail('请阅读并同意发布信息协议!');
        }

        if ($data['id'] && BrandModel::be(['id' => $data['id'], 'mer_id' => $this->userInfo['uid']])) {
            $id = $data['id'];
            unset($data['id']);
            if (BrandModel::edit($data, $id, 'id')) {
                return JsonService::successful('编辑成功!');
            } else
                return JsonService::fail('编辑失败!');
        } else {
            if ($res = BrandModel::set($data)) {
                return JsonService::successful('添加成功!');
            } else
                return JsonService::fail('添加失败!');
        }
    }
}