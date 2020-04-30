<?php
/**
 *
 * @author: 招宝通
 */

namespace app\home\controller;

use app\admin\model\position\Position;
use app\ebapi\model\job\Job as JosModel;
use app\ebapi\model\job\JobPosition;
use app\ebapi\model\industry\Industry;
use app\ebapi\model\welfare\Welfare;
use app\ebapi\model\company\Company;

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
 * Class Job
 * @package app\home\controller
 * 招聘管理 控制器
 */
class Job extends AuthController
{

    /**
     * @return mixed
     * 招聘管理
     */
     public function admin(){
         return $this->fetch();
     }

    /**
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 请求数据列表
     */
    public function get_list(){
        $where = UtilService::getMore([
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

        if($keyword){
            $dataPosition = Position::where($where)->where('name','LIKE',htmlspecialchars("%$keyword%"))->select();
            $arrPosition = array();
            foreach ($dataPosition as $key=>$val){
                $arrPosition[] = $val['id'];
            }
            $implode = implode(",",$arrPosition);
            $data = JobPosition::where($where)->where('position','in',$implode)->order('id desc')->page((int)$page,(int)$limit)->field('id,uid,name,skills,age_for,education,salary,address,position,industry,add_time,is_show')->select();
            if($data) $data->toArray();
        } else {
            $data = JobPosition::where($where)->order('id desc')->page((int)$page,(int)$limit)->field('id,uid,name,skills,age_for,education,salary,address,position,industry,add_time,is_show')->select();
            if($data) $data->toArray();
        }

        $positionOne = $industryOne = 0;
        foreach ($data as $item=>$value){
            $data[$item] = $value;
            $jobInfo = JosModel::where(array('uid'=>$value['uid']))->field('id,name,phone')->find();
            if($jobInfo) $jobInfo->toArray();
            $companyInfo = Company::where(array('uid'=>$value['uid']))->find();

            $data[$item]['jobInfo'] = $jobInfo;
            $data[$item]['companyInfo'] = $companyInfo;

            $position_info = Position::where(array('id'=>$value['position']))->find();
            $industry_info = Industry::where(array('id'=>$value['industry']))->find();

            if($position_info['pid']) $positionOne = Position::where(array('id'=>$position_info['pid']))->find();
            if($industry_info['pid']) $industryOne = Industry::where(array('id'=>$industry_info['pid']))->find();

            $data[$item]['position_data'] = $positionOne ? $positionOne['name'].' · '.$position_info['name']:$position_info['name'];
            $data[$item]['industry_data'] = $industryOne ? $industryOne['name'].' · '.$industry_info['name']:$industry_info['name'];

            $user = User::where(array('uid'=>$value['uid']))->field('nickname,avatar')->find();
            if($user) $user->toArray();
            $data[$item]['user'] = $user;
            $data[$item]['add_time'] = date('Y-m-d H:i',$value['add_time']);
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
     * 添加 编辑 信息
     */
    public function create(){
        $where = UtilService::getMore([
            ['uid',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        if($where['id']){
            $data = JobPosition::where($where)->find()->toArray();
            if($data){
                $data['welfare'] = implode(",",json_decode($data['welfare'],true));
            }
        } else {
            $data = array(
                'is_show'=>1,
                'is_sex'=>1,
                'skills'=>'',
                'duty'=>'',
                'age_for'=>'',
                'education'=>'',
                'salary'=>'',
                'address'=>'',
                'description'=>'',
                'industry'=>'',
                'welfare'=>'1,2,3',
                'is_consent'=>0,
                'id'=>0
            );
        }

        //TODO 部门
        $industry = Industry::pidBy(0,'id,name');
        //TODO 福利
        $welfare = Welfare::getList()->toArray();

        $this->assign(compact('data','industry','welfare'));
        return $this->fetch();
    }

    /**
     * 添加 编辑 提交招聘信息
     */
    public function save()
    {
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['drpprovince', 0],
            ['drpcity', 0],
            ['drparea', 0],
            ['is_show',''],
            ['is_sex',0],
            ['skills',''],
            ['duty',''],
            ['age_for',''],
            ['education',''],
            ['salary',''],
            ['address',''],
            ['description',''],
            ['industry',0],
            ['welfare',[]],
            ['id',0]
        ],$request);

        $data['uid'] = $this->userInfo['uid'];
        if($data['drpprovince'])$data['position'] = $data['drpprovince'];
        if($data['drpcity'])    $data['position'] = $data['drpcity'];
        if($data['drparea'])    $data['position'] = $data['drparea'];

        unset($data['drpprovince']);
        unset($data['drpcity']);
        unset($data['drparea']);

        if(count($data['welfare'])){
            $welfare_arr = array();
            foreach ($data['welfare'] as $key=>$val){
                $welfare_arr[] = $key;
            }
            $data['welfare'] = json_encode($welfare_arr,true);
        }

        $data['add_time'] = time();
        if($data['id'] && JobPosition::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid']])){
            $id = $data['id'];
            unset($data['id']);
            if(JobPosition::edit($data,$id,'id')){
                return JsonService::successful('编辑成功!');
            } else {
                return JsonService::fail('编辑失败!');
            }
        } else {
            unset($data['id']);
            if($jobPosition = JobPosition::set($data)){
                return JsonService::successful('添加成功!');
            } else {
                return JsonService::fail('添加失败!');
            }
        }
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 删除 招聘信息
     */
    public function del(){
        $where = UtilService::getMore([
            ['uid',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        $data = JobPosition::where($where)->find();
        if(!$data) return JsonService::fail('删除的数据丢失了!');
        $delData = JobPosition::where($where)->delete();
        if(!$delData) return JsonService::fail('网络忙，稍后再试!');
        return JsonService::successful('数据删除成功!');
    }

    /**
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 完善个人信息
     */
     public function publisher(){
         $where = UtilService::getMore([
             ['uid',$this->userInfo['uid']]
         ],$this->request);
         $data = JosModel::where($where)->find();
         if($data) {
             $data = $data->toArray();
             $data['slider_image_data'] = implode(",",json_decode($data['slider_image'],true));
             $data['slider_image'] = json_decode($data['slider_image'],true);
             $data['about_image_data'] = implode(",",json_decode($data['about_image'],true));
             $data['about_image'] = json_decode($data['about_image'],true);
         } else {
             $data = array(
                 'name'=>'',
                 'phone'=>'',
                 'wechat'=>'',
                 'email'=>'',
                 'company'=>'',
                 'position'=>'',
                 'team_tag'=>'',
                 'description'=>'',
                 'about_image'=>[],
                 'slider_image'=>[],
                 'about_image_data'=>'',
                 'slider_image_data'=>'',
                 'is_consent'=>0,
                 'id'=>0
             );
         }
         $this->assign(compact('data'));
         return $this->fetch();
     }

    /**
     * 添加 编辑 保存发布者信息
     */
    public function publisher_save(){
        $request = Request::instance();
        if (!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['slider_image',''],
            ['about_image',''],
            ['name',''],
            ['phone',''],
            ['wechat',''],
            ['email',''],
            ['company',''],
            ['position',''],
            ['team_tag',''],
            ['description',''],
            ['is_consent',0],
            ['id',0]
        ], $request);

        $data['uid'] = $this->userInfo['uid'];

        if(!$data['name']) return JsonService::fail('请输入姓名!');
        if(!$data['phone'])    return JsonService::fail('请输入手机号!');
        if($data['slider_image']) $slider_image = array_filter(explode(',',$data['slider_image']));
        if($data['about_image']) $about_image = array_filter(explode(',',$data['about_image']));

        if(count($slider_image)) {
            $data['image'] = $slider_image[1];
            $data['slider_image'] = json_encode($slider_image);
        } else {
            return JsonService::fail('请上传企业实力、企业文化、企业风采图片!');
        }

        if(count($about_image)) {
            $data['about_image'] = json_encode($about_image);
        } else {
            return JsonService::fail('请上传公司概况（简介、经验品类）图片!');
        }

        if($data['is_consent']) {
            $data['is_consent'] = 1;
        } else {
            return JsonService::fail('请阅读并同意发布信息协议!');
        }

        if ($data['id'] && JosModel::be(['id' => $data['id'], 'uid' => $this->userInfo['uid']])) {
            $id = $data['id'];
            unset($data['id']);
            if (JosModel::edit($data, $id, 'id')) {
                return JsonService::successful('编辑成功!');
            } else
                return JsonService::fail('编辑失败!');
        } else {
            if ($res = JosModel::set($data)) {
                return JsonService::successful('添加成功!');
            } else
                return JsonService::fail('添加失败!');
        }
    }
}