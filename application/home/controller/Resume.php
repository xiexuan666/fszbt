<?php
/**
 *
 * @author: 招宝通
 */

namespace app\home\controller;

use app\ebapi\model\position\Position;
use app\ebapi\model\industry\Industry;
use app\ebapi\model\resume\Resume as ResumeModel;
use app\ebapi\model\resume\ResumeExpect;
use app\ebapi\model\resume\ResumeEducation;
use app\ebapi\model\resume\ResumeWork;
use app\ebapi\model\resume\ResumeProject;
use app\ebapi\model\resume\ResumeRelation;
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

class Resume extends AuthController
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
            ['uid',$this->userInfo['uid']]
        ],$this->request);

        $data = ResumeModel::where($where)->order('id desc')->find();
        if($data) $data = $data->toArray();

        $expect = ResumeExpect::where(array('uid'=>$data['uid']))->order('id asc')->select();
        if($expect) {
            $expect = $expect->toArray();
            foreach ($expect as $item=>$value){
                $position = Position::where(array('id'=>$value['position']))->find();
                $industry = Industry::where(array('id'=>$value['industry']))->find();
                if($position['pid']) {
                    $positionOne = Position::where(array('id'=>$position['pid']))->find();
                    if($positionOne){
                        $expect[$item]['position'] = $positionOne['name'].' · '.$position['name'];
                    }
                } else {
                    $expect[$item]['position'] = $position['name'];
                }
                $expect[$item]['industry'] = $industry['name'];
            }
        }

        $work = ResumeWork::where(array('uid'=>$data['uid']))->select();
        if($work) {
            $work = $work->toArray();
            foreach ($work as $key=>$value){
                $position = Position::where(array('id'=>$value['position']))->find();
                $industry = Industry::where(array('id'=>$value['industry']))->find();
                if($position['pid']) {
                    $positionOne = Position::where(array('id'=>$position['pid']))->find();
                    if($positionOne){
                        $work[$key]['position'] = $positionOne['name'].' · '.$position['name'];
                    }
                } else {
                    $work[$key]['position'] = $position['name'];
                }

                $work[$key]['start_time'] = date('Y.m',strtotime($value['start_time']));
                $work[$key]['stop_time'] = date('Y.m',strtotime($value['stop_time']));
                $work[$key]['industry'] = $industry['name'];
            }
        }

        $education = ResumeEducation::where(array('uid'=>$data['uid']))->order('id asc')->select();
        if($education) {
            $education = $education->toArray();
            foreach ($education as $item=>$value){
                $education[$item]['start_time'] = date('Y',strtotime($value['start_time']));
                $education[$item]['stop_time'] = date('Y',strtotime($value['stop_time']));
            }
        }

        $user = User::where(array('uid'=>$data['uid']))->field('nickname,avatar,birthday')->find();
        if($user) $user = $user->toArray();

        $data['expect'] = $expect;
        $data['work'] = $work;
        $data['education'] = $education;
        $data['user'] = $user;

        $resume_birthday = self::diffDate($user['birthday'],date('Y-m-d',time()));
        $data['birthday'] = intval($resume_birthday['y']);

        if($data['work_time']){
            $resume_work_time = self::diffDate($data['work_time'],date('Y-m-d',time()));
            $data['work_time'] = intval($resume_work_time['y'])?intval($resume_work_time['y']).'年':intval($resume_work_time['m']).'个月';
        }

        if(count($expect)){
            $data['price'] = $expect[0]['salary'];
            $data['position'] = $expect[0]['position'];
        } else {
            $data['price'] = 0;
            $data['position'] = '暂无';
        }

        $data = array($data);

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
     * 添加 编辑 求职状态
     */
    public function create(){
        $where = UtilService::getMore([
            ['uid',$this->userInfo['uid']]
        ],$this->request);
        $data = ResumeModel::where($where)->find();
        if($data) $data = $data->toArray();
        if($data){
            if(!is_array($data['slider_image'])) $data['slider_image'] = json_decode($data['slider_image'],true);
            $data['slider_image_data'] = implode(",",$data['slider_image']);
        } else {
            $data = array(
                'is_show'=>1,
                'sex'=>1,
                'name'=>'',
                'status'=>'',
                'phone'=>'',
                'work_time'=>'',
                'description'=>'',
                'slider_image'=>[],
                'slider_image_data'=>'',
                'is_consent'=>0,
                'id'=>0
            );
        }

        $status = array('离职-随时到岗', '离职-1周内到岗', '在职-考虑机会', '在职-1月内到岗');

        $this->assign(compact('data','status'));
        return $this->fetch();
    }

    /**
     * 添加 编辑 保存求职状态
     */
    public function save(){
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['name',''],
            ['phone',''],
            ['description',''],
            ['slider_image',''],
            ['work_time',0],
            ['is_show',1],
            ['status',''],
            ['sex',1],
            ['is_consent',0],
            ['id',0]
        ],$request);

        if($data['slider_image']) $slider_image = array_filter(explode(',',$data['slider_image']));
        if(count($slider_image)) {
            $data['image'] = $slider_image[1];
            $data['slider_image'] = json_encode($slider_image);
        }

        $data['uid'] = $this->userInfo['uid'];
        $data['add_time'] = time();
        if($data['id'] && ResumeModel::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid']])){
            $id = $data['id'];
            unset($data['id']);
            if(ResumeModel::edit($data,$id,'id')){
                return JsonService::successful('编辑成功!');
            } else {
                return JsonService::fail('编辑失败!');
            }
        } else {
            if($address = ResumeModel::set($data)){
                return JsonService::successful('添加成功!');
            } else {
                return JsonService::fail('添加失败!');
            }
        }
    }

    /**
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 工作期望
     */
    public function expect(){
        $where = UtilService::getMore([
            ['uid',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        $data = ResumeExpect::where($where)->find();

        if($data){
            $data = $data->toArray();
        } else {
            $data = array(
                'salary'=>'',
                'city'=>'',
                'industry'=>'',
                'is_consent'=>0,
                'id'=>0
            );
        }

        //TODO 部门
        $industry_arr = Industry::pidBy(0,'id,name');
        if($industry_arr) $industry_arr = $industry_arr->toArray();

        $this->assign(compact('data','industry_arr'));
        return $this->fetch();
    }

    /**
     * 保存工作期望
     */
    public function expect_save()
    {
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['drpprovince', 0],
            ['drpcity', 0],
            ['drparea', 0],

            ['industry',0],
            ['city',''],
            ['salary',0],
            ['id',0]
        ],$request);

        if($data['drpprovince'])$data['position'] = $data['drpprovince'];
        if($data['drpcity'])    $data['position'] = $data['drpcity'];
        if($data['drparea'])    $data['position'] = $data['drparea'];

        unset($data['drpprovince']);
        unset($data['drpcity']);
        unset($data['drparea']);

        $data['uid'] = $this->userInfo['uid'];
        $data['add_time'] = time();
        if($data['id'] && ResumeExpect::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid']])){
            $id = $data['id'];
            unset($data['id']);
            if(ResumeExpect::edit($data,$id,'id')){
                return JsonService::successful('编辑成功!');
            } else {
                return JsonService::fail('编辑失败!');
            }
        } else {
            if($address = ResumeExpect::set($data)){
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
     * 删除期望
     */
    public function expect_del(){
        $where = UtilService::getMore([
            ['uid',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        $data = ResumeExpect::where($where)->find();
        if(!$data) return $this->failed('删除的数据丢失了!');
        $delData = ResumeExpect::where($where)->delete();
        if(!$delData) return $this->failed('网络忙，稍后再试!');
        return $this->successful('数据删除成功',empty($ref) ? Url::build('Resume/admin') : $ref);
    }

    /**
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 添加 编辑 工作经验
     */
    public function work(){
        $where = UtilService::getMore([
            ['uid',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        $data = ResumeWork::where($where)->find();

        if($data){
            $data = $data->toArray();
        } else {
            $data = array(
                'name'=>'',
                'start_time'=>'',
                'stop_time'=>'',
                'description'=>'',
                'industry'=>'',
                'is_consent'=>0,
                'id'=>0
            );
        }

        //TODO 部门
        $industry_arr = Industry::pidBy(0,'id,name');
        if($industry_arr) $industry_arr = $industry_arr->toArray();

        $this->assign(compact('data','industry_arr'));
        return $this->fetch();
    }

    /**
     * 添加 编辑 工作经验保存
     */
    public function work_save()
    {
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['drpprovince', 0],
            ['drpcity', 0],
            ['drparea', 0],

            ['industry',0],
            ['description',''],
            ['start_time',''],
            ['stop_time',''],
            ['name',''],
            ['id',0]
        ],$request);

        if($data['drpprovince'])$data['position'] = $data['drpprovince'];
        if($data['drpcity'])    $data['position'] = $data['drpcity'];
        if($data['drparea'])    $data['position'] = $data['drparea'];

        unset($data['drpprovince']);
        unset($data['drpcity']);
        unset($data['drparea']);

        $data['uid'] = $this->userInfo['uid'];
        $data['add_time'] = time();
        if($data['id'] && ResumeWork::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid']])){
            $id = $data['id'];
            unset($data['id']);
            if(ResumeWork::edit($data,$id,'id')){
                return JsonService::successful('编辑成功!');
            } else {
                return JsonService::fail('编辑失败!');
            }
        } else {
            if($address = ResumeWork::set($data)){
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
     * 删除工作经验
     */
    public function work_del(){
        $where = UtilService::getMore([
            ['uid',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        $data = ResumeWork::where($where)->find();
        if(!$data) return $this->failed('删除的数据丢失了!');
        $delData = ResumeWork::where($where)->delete();
        if(!$delData) return $this->failed('网络忙，稍后再试!');
        return $this->successful('数据删除成功',empty($ref) ? Url::build('Resume/admin') : $ref);
    }

    /**
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 添加 编辑 教育经历
     */
    public function education(){
        $where = UtilService::getMore([
            ['uid',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        $data = ResumeEducation::where($where)->find();

        if($data){
            $data = $data->toArray();
        } else {
            $data = array(
                'name'=>'',
                'education'=>'',
                'professional'=>'',
                'start_time'=>'',
                'stop_time'=>'',
                'description'=>'',
                'is_consent'=>0,
                'id'=>0
            );
        }

        $this->assign(compact('data'));
        return $this->fetch();
    }

    /**
     * 添加 编辑 教育经历保存
     */
    public function education_save() {
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['education',''],
            ['description',''],
            ['professional',''],
            ['name',''],
            ['start_time',''],
            ['stop_time',''],
            ['id',0]
        ],$request);

        $data['uid'] = $this->userInfo['uid'];
        $data['add_time'] = time();
        if($data['id'] && ResumeEducation::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid']])){
            $id = $data['id'];
            unset($data['id']);
            if(ResumeEducation::edit($data,$id,'id')){
                return JsonService::successful('编辑成功!');
            } else {
                return JsonService::fail('编辑失败!');
            }
        } else {
            if($address = ResumeEducation::set($data)){
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
     * 删除教育
     */
    public function education_del(){
        $where = UtilService::getMore([
            ['uid',$this->userInfo['uid']],
            ['id',0]
        ],$this->request);
        $data = ResumeEducation::where($where)->find();
        if(!$data) return $this->failed('删除的数据丢失了!');
        $delData = ResumeEducation::where($where)->delete();
        if(!$delData) return $this->failed('网络忙，稍后再试!');
        return $this->successful('数据删除成功',empty($ref) ? Url::build('Resume/admin') : $ref);
    }

    /**
     * function：计算两个日期相隔多少年，多少月，多少天
     * param string $date1[格式如：2011-11-5]
     * param string $date2[格式如：2012-12-01]
     * return array array('年','月','日');
     */
    public function diffDate($date1,$date2) {
        $datetime1 = new \DateTime($date1);
        $datetime2 = new \DateTime($date2);
        $interval = $datetime1->diff($datetime2);
        $time['y']         = $interval->format('%Y');
        $time['m']         = $interval->format('%m');
        $time['d']         = $interval->format('%d');
        $time['h']         = $interval->format('%H');
        $time['i']         = $interval->format('%i');
        $time['s']         = $interval->format('%s');
        $time['a']         = $interval->format('%a');    // 两个时间相差总天数
        return $time;
    }

}