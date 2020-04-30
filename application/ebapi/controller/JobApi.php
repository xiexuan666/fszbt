<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-25
 * Time: 19:44
 */

namespace app\ebapi\controller;

use app\ebapi\model\user\User;
use app\ebapi\model\user\UserSubion;
use app\ebapi\model\job\Job;
use app\ebapi\model\resume\Resume;
use app\ebapi\model\job\JobPosition;
use app\ebapi\model\welfare\Welfare;
use app\ebapi\model\company\Company;

use app\ebapi\model\position\Position;
use app\ebapi\model\industry\Industry;

use app\ebapi\model\store\StoreProductRelation;

use app\core\model\routine\RoutineCode;
use app\core\util\SystemConfigService;
use service\JsonService;
use service\UtilService;
use think\Request;

class JobApi extends AuthController
{
    public static function whiteList()
    {
        return [
            'get_data',
            'get_list',
            'get_details',
            'get_user_job',
            'get_position_data',
            'get_del_position',
            'edit_user_job',
            'edit_position',
            'get_collect',
            'collect_job',
            'uncollect_job',
            'get_user_collect_job',
            'get_welfare_data'
        ];
    }

    /**
     * 获取内容
     * 2019-07-27
     */
    public function get_data(){
        $data = Job::where(array('uid'=>$this->userInfo['uid']))->find();
        $position = JobPosition::where(array('uid'=>$this->userInfo['uid']))->select();
        $positionOne = $industryOne = 0;
        foreach ($position as $item=>$value){
            $position_info = Position::where(array('id'=>$value['position']))->find();
            $industry_info = Industry::where(array('id'=>$value['industry']))->find();

            if($position_info['pid']) $positionOne = Position::where(array('id'=>$position_info['pid']))->find();
            if($industry_info['pid']) $industryOne = Industry::where(array('id'=>$industry_info['pid']))->find();

            $position[$item]['position_data'] = $positionOne ? $positionOne['name'].' · '.$position_info['name']:$position_info['name'];
            $position[$item]['industry_data'] = $industryOne ? $industryOne['name'].' · '.$industry_info['name']:$industry_info['name'];
        }

        //统计已付费招聘
        $subion = UserSubion::where('type','resume')->where('uid',$this->userInfo['uid'])->where('paid',1)->select();
        $subion_data['list'] = $subion;
        $subion_data['count'] = count($subion);
        $data['subion'] = $subion_data;
        //统计别人已付费招聘
        $subionMer = UserSubion::where('type','job')->where('mer_id',$this->userInfo['uid'])->where('paid',1)->select();

        $array = [];
        foreach ($subionMer as $item=>$val){
            $dataResume = Resume::where(array('uid'=>$val['uid']))->find();
            if($dataResume){
                $array[] = $dataResume;
            }
        }

        $subion_mer_data['list'] = $subionMer;
        $subion_mer_data['count'] = count($array);
        $data['subion_mer'] = $subion_mer_data;
        //统计收藏
        $relation = StoreProductRelation::where('category','resume')->where('uid',$this->userInfo['uid'])->select();
        $relation_data['list'] = $relation;
        $relation_data['count'] = count($relation);
        $data['relation'] = $relation_data;

        $data['positionList'] = $position;

        return JsonService::successful($data);
    }

    /**
     * 获取职位列表
     * 2019-07-27
     */
    public function get_list(){
        $where = UtilService::getMore([
            ['keyword',''],
            ['mer_id',0],
            ['page',0],
            ['limit',0]
        ],$this->request);

        $list = JobPosition::getList($where);

        $array = [];
        $positionOne = $industryOne = 0;
        foreach ($list as $item=>$value){
            $data = $value;
            $jobInfo = Job::where(array('uid'=>$value['uid']))->find();
            $companyInfo = Company::where(array('uid'=>$value['uid']))->find();

            $data['jobInfo'] = $jobInfo;
            $data['companyInfo'] = $companyInfo;

            $position_info = Position::where(array('id'=>$value['position']))->find();
            $industry_info = Industry::where(array('id'=>$value['industry']))->find();

            if($position_info['pid']) $positionOne = Position::where(array('id'=>$position_info['pid']))->find();
            if($industry_info['pid']) $industryOne = Industry::where(array('id'=>$industry_info['pid']))->find();

            $data['position_data'] = $positionOne ? $positionOne['name'].' · '.$position_info['name']:$position_info['name'];
            $data['industry_data'] = $industryOne ? $industryOne['name'].' · '.$industry_info['name']:$industry_info['name'];

            $user = User::where(array('uid'=>$value['uid']))->field('nickname,avatar')->find();
            $data['user'] = $user;
            $data['skills'] = explode(',',$value['skills']);
            $data['add_time'] = date('Y-m-d H:i',$value['add_time']);
            $array[] = $data;
        }

        if($array){
            return JsonService::successful($array);
        } else {
            return JsonService::fail('暂无数据');
        }
    }

    /**
     * 根据ID获取个人职位内容
     * 2019-07-27
     */
    public function get_details($id = 0){

        $data = JobPosition::where(array('id'=>$id))->where('is_show',1)->find();
        $positionOne = $industryOne = 0;
        if($data){
            $jobInfo = Job::where(array('uid'=>$data['uid']))->find();
            $jobInfo['slider_image'] = json_decode($jobInfo['slider_image'],true);
            $jobInfo['about_image'] = json_decode($jobInfo['about_image'],true);
            $companyInfo = Company::where(array('uid'=>$data['uid']))->find();

            $data['jobInfo'] = $jobInfo;
            $data['companyInfo'] = $companyInfo;

            $position_info = Position::where(array('id'=>$data['position']))->find();
            $industry_info = Industry::where(array('id'=>$data['industry']))->find();

            if($position_info['pid']) $positionOne = Position::where(array('id'=>$position_info['pid']))->find();
            if($industry_info['pid']) $industryOne = Industry::where(array('id'=>$industry_info['pid']))->find();

            $data['position_data'] = $positionOne ? $positionOne['name'].' · '.$position_info['name']:$position_info['name'];
            $data['industry_data'] = $industryOne ? $industryOne['name'].' · '.$industry_info['name']:$industry_info['name'];

            $user = User::where(array('uid'=>$data['uid']))->field('nickname,avatar,phone')->find();
            $data['user'] = $user;
            $data['skills'] = explode(',',$data['skills']);
            $data['recruitment_price'] = SystemConfigService::get('recruitment_price');

            $count = UserSubion::where('uid',$this->userInfo['uid'])->where('subion_id',$data['id'])->where('paid',1)->where('type','job')->count();
            $data['paycount'] = $count?$count:0;

            $welfare = json_decode($data['welfare'],true);

            $welfaredata = [];
            if($welfare){
                foreach ($welfare as $value){
                    $welfareinfo = Welfare::getDatas($value);
                    $welfaredata[$value] = $welfareinfo;
                }
            }
            $data['welfare'] = $welfaredata;
            $data['add_time'] = date('Y-m-d H:i',$data['add_time']);
            return JsonService::successful($data);
        } else {
            return JsonService::fail('暂无数据');
        }
    }

    /**
     * 获取个人信息
     * 2019-07-27
     */
    public function get_user_job(){
        $data = Job::where(array('uid'=>$this->userInfo['uid']))->find();
        $data['slider_image'] = json_decode($data['slider_image'],1);
        $data['about_image'] = $data['about_image']?json_decode($data['about_image'],1):[];
        return JsonService::successful($data ? $data : []);
    }

    /**
     * 获取职位内容
     * 2019-07-27
     */
    public function get_position_data($id = 0){
        $positionOne = $industryOne = 0;
        $data = JobPosition::where(array('id'=>$id))->find();
        $position = Position::where(array('id'=>$data['position']))->find();
        $industry = Industry::where(array('id'=>$data['industry']))->find();

        if($position['pid']) $positionOne = Position::where(array('id'=>$position['pid']))->find();
        if($industry['pid']) $industryOne = Industry::where(array('id'=>$industry['pid']))->find();

        $data['position_data'] = $positionOne ? $positionOne['name'].' · '.$position['name']:$position['name'];
        $data['industry_data'] = $industryOne ? $industryOne['name'].' · '.$industry['name']:$industry['name'];
        $data['welfare'] = json_decode($data['welfare'],true);

        $welfarelist = Welfare::getList();
        foreach ($welfarelist as $item=>$value){
            if(count($data['welfare'])){
                $isin = in_array($value['id'],$data['welfare']);
                if($isin) $welfarelist[$item]['isSelected'] = true;
            }
        }
        $datas['datas'] = $data;
        $datas['welfare'] = $welfarelist;
        return JsonService::successful($datas);
    }

    /**
     * 删除职位
     * 2019-07-27
     */
    public function get_del_position($id = 0){
        $data = JobPosition::where(array('id'=>$id))->find();
        if($data){
            JobPosition::where(array('id'=>$data['id']))->delete();
            return JsonService::successful('删除成功！');
        } else {
            return JsonService::fail('删除错成功!');
        }
    }

    /**
     * 编辑个人信息
     * 2019-07-27
     */
    public function edit_user_job(){
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['comment',''],
            ['pics',[]],
            ['about',[]],
            ['name',''],
            ['phone',''],
            ['wechat',''],
            ['email',''],
            ['company',''],
            ['position',''],
            ['team_tag',''],
            ['description',''],
            ['is_default',0],
            ['id',0]
        ],$request);
        if(count($data['pics'])) $data['image'] = $data['pics'][0];
        $data['slider_image'] = json_encode($data['pics']);
        $data['about_image'] = json_encode($data['about']);
        $data['add_time'] = time();
        $data['uid'] = $this->userInfo['uid'];
        unset($data['pics']);

        if($data['id'] && Job::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid']])){
            $id = $data['id'];
            unset($data['id']);
            if(Job::edit($data,$id,'id')){
                return JsonService::successful('编辑成功!');
            } else {
                return JsonService::fail('编辑失败!');
            }
        } else {
            if($address = Job::set($data)){
                return JsonService::successful('添加成功!');
            } else {
                return JsonService::fail('添加失败!');
            }
        }
    }

    /**
     * 编辑职位
     * 2019-07-27
     */
    public function edit_position()
    {
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['name',''],
            ['is_show',''],
            ['is_sex',0],
            ['skills',''],
            ['duty',''],
            ['age_for',''],
            ['education',''],
            ['salary',''],
            ['address',''],
            ['description',''],
            ['position',0],
            ['industry',0],
            ['welfare',[]],
            ['id',0]
        ],$request);

        $data['uid'] = $this->userInfo['uid'];
        $data['welfare'] = json_encode($data['welfare'],true);
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
            if($address = JobPosition::set($data)){
                return JsonService::successful('添加成功!');
            } else {
                return JsonService::fail('添加失败!');
            }
        }
    }

    /**
     * 简历海报二维码
     * @param int $id
     * @throws \think\Exception
     */
    public function get_promotion_code($id = 0){
        if(!$id) return JsonService::fail('参数错误ID不存在');
        $count = JobPosition::validWhere()->count();
        if(!$count) return JsonService::fail('参数错误');
        $path = makePathToUrl('routine/resume/',4);
        if($path == '') return JsonService::fail('生成上传目录失败,请检查权限!');
        $codePath = $path.$id.'_'.$this->userInfo['uid'].'_resume.jpg';
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
     * 获取福利配置
     * 2019-10-19
     */
    public function get_welfare_data(){
        $data = Welfare::getList();
        return JsonService::successful($data);
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