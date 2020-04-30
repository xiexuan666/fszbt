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
use app\ebapi\model\resume\Resume;
use app\ebapi\model\resume\ResumeExpect;
use app\ebapi\model\resume\ResumeEducation;
use app\ebapi\model\resume\ResumeWork;
use app\ebapi\model\resume\ResumeProject;
use app\ebapi\model\resume\ResumeRelation;

use app\ebapi\model\position\Position;
use app\ebapi\model\industry\Industry;

use app\core\model\routine\RoutineCode;
use app\core\util\SystemConfigService;

use app\ebapi\model\store\StoreProductRelation;

use service\JsonService;
use service\UtilService;
use think\Request;

class ResumeApi extends AuthController
{
    public static function whiteList()
    {
        return [
            'get_data',
            'get_list',
            'get_details',
            'get_user_resume',
            'get_expect_data',
            'get_work_data',
            'get_project_data',
            'get_education_data',
            'get_del_expect',
            'get_del_work',
            'get_del_project',
            'get_del_education',
            'edit_user_resume',
            'edit_expect',
            'edit_work',
            'edit_project',
            'edit_education',
            'get_promotion_code',
            'get_collect',
            'collect_resume',
            'uncollect_resume',
        ];
    }

    /**
     * 获取内容
     */
    public function get_data(){
        $expect = ResumeExpect::where(array('uid'=>$this->userInfo['uid']))->select();

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

        $work = ResumeWork::where(array('uid'=>$this->userInfo['uid']))->select();
        foreach ($work as $item=>$value){
            $position = Position::where(array('id'=>$value['position']))->find();
            $industry = Industry::where(array('id'=>$value['industry']))->find();
            if($position['pid']) $positionOne = Position::where(array('id'=>$position['pid']))->find();

            $work[$item]['start_time'] = date('Y.m',strtotime($value['start_time']));
            $work[$item]['stop_time'] = date('Y.m',strtotime($value['stop_time']));
            $work[$item]['position'] = $positionOne ? $positionOne['name'].' · '.$position['name']:$position['name'];
            $work[$item]['industry'] = $industry['name'];
        }

        $data_education = null;
        $education = ResumeEducation::where(array('uid'=>$this->userInfo['uid']))->select();
        foreach ($education as $item=>$value){
            $education[$item]['start_time'] = date('Y',strtotime($value['start_time']));
            $education[$item]['stop_time'] = date('Y',strtotime($value['stop_time']));
            $data_education = $value['education'];
        }

        $resume = Resume::where(array('uid'=>$this->userInfo['uid']))->find();
        $resume_work_time = $this->diffDate($resume['work_time'],date('Y-m-d',time()));
        $resume['work_time'] = intval($resume_work_time['y'])?intval($resume_work_time['y']).'年':intval($resume_work_time['m']).'个月';
        $resume['education'] = $data_education ? $data_education : null;
        $resume_birthday = $this->diffDate($this->userInfo['birthday'],date('Y-m-d',time()));
        $resume['birthday'] = intval($resume_birthday['y']);

        //统计已付费招聘
        $subion = UserSubion::where('type','job')->where('uid',$this->userInfo['uid'])->where('paid',1)->select();
        $subion_data['list'] = $subion;
        $subion_data['count'] = count($subion);
        $data['subion'] = $subion_data;
        //统计收藏
        $relation = StoreProductRelation::where('category','job')->where('uid',$this->userInfo['uid'])->select();
        $relation_data['list'] = $relation;
        $relation_data['count'] = count($relation);
        $data['relation'] = $relation_data;


        $data['resume'] = $resume;
        $data['education'] = $education;
        $data['work'] = $work;
        $data['expect'] = $expect;
        return JsonService::successful($data);
    }

    /**
     * 获取投递我的简历列表
     */
    public function get_member_list(){
        $where = UtilService::getMore([
            ['page',0],
            ['limit',0]
        ],$this->request);

        $lists = UserSubion::where('type','job')->where('mer_id',$this->userInfo['uid'])->where('paid',1)->page((int)$where['page'],(int)$where['limit'])->field('id,uid,subion_id')->select();

        $array = [];
        foreach ($lists as $item=>$val){
            $data = Resume::where(array('uid'=>$val['uid']))->find();

            $expect = ResumeExpect::where(array('uid'=>$val['uid']))->order('id asc')->select();
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
            $user = User::where(array('uid'=>$val['uid']))->field('nickname,avatar,birthday')->find();
            $data['user'] = $user;
            $data['expect'] = $expect;

            if($data['work_time']){
                $resume_work_time = $this->diffDate($data['work_time'],date('Y-m-d',time()));
                $data['work_time'] = intval($resume_work_time['y'])?intval($resume_work_time['y']).'年':intval($resume_work_time['m']).'个月';
            }

            if(count($expect)){
                $data['price'] = $expect[0]['salary'];
                $data['position'] = $expect[0]['position'];
            } else {
                $data['price'] = 0;
                $data['position'] = '暂无';
            }
            $array[] = $data;
        }

        return JsonService::successful($array ? $array : []);
    }

    /**
     * 获取个人简历列表
     */
    public function get_list(){
        $where = UtilService::getMore([
            ['keyword',''],
            ['page',0],
            ['limit',0]
        ],$this->request);

        $list = Resume::getList($where);
        $array = [];
        foreach ($list as $item=>$val){
            $data = Resume::where(array('id'=>$val['id']))->find();

            $expect = ResumeExpect::where(array('uid'=>$val['uid']))->order('id asc')->select();
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

            $work = ResumeWork::where(array('uid'=>$val['uid']))->select();
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

            $education = ResumeEducation::where(array('uid'=>$val['uid']))->order('id asc')->select();
            foreach ($education as $item=>$value){
                $education[$item]['start_time'] = date('Y',strtotime($value['start_time']));
                $education[$item]['stop_time'] = date('Y',strtotime($value['stop_time']));
            }

            $user = User::where(array('uid'=>$val['uid']))->field('nickname,avatar,birthday')->find();

            $data['user'] = $user;
            $data['education_arr'] = $education;
            $data['work'] = $work;
            $data['expect'] = $expect;
            $data['slider_image'] = json_decode($val['slider_image'],1);


            $resume_birthday = $this->diffDate($user['birthday'],date('Y-m-d',time()));
            $data['birthday'] = intval($resume_birthday['y']);

            if($val['work_time']){
                $resume_work_time = $this->diffDate($val['work_time'],date('Y-m-d',time()));
                $data['work_time'] = intval($resume_work_time['y'])?intval($resume_work_time['y']).'年':intval($resume_work_time['m']).'个月';
            }

            if(count($expect)){
                $data['price'] = $expect[0]['salary'];
                $data['position'] = $expect[0]['position'];
            } else {
                $data['price'] = 0;
                $data['position'] = '暂无';
            }


            $array[] = $data;
        }

        return JsonService::successful($array ? $array : []);
    }

    /**
     * 根据ID获取个人简历内容
     */
    public function get_details($id = 0){
        $data = Resume::where(array('id'=>$id))->find();

        $expect = ResumeExpect::where(array('uid'=>$data['uid']))->order('id asc')->select();
        foreach ($expect as $item=>$value){
            $position = Position::where(array('id'=>$value['position']))->find();
            if($position['pid']) {
                $positionOne = Position::where(array('id'=>$position['pid']))->find();
                if($positionOne){
                    $expect[$item]['position'] = $positionOne['name'].' · '.$position['name'];
                }
            } else {
                $expect[$item]['position'] = $position['name'];
            }
            $industry = Industry::where(array('id'=>$value['industry']))->find();
            $expect[$item]['industry'] = $industry['name'];
        }

        $work = ResumeWork::where(array('uid'=>$data['uid']))->order('id asc')->select();
        foreach ($work as $item=>$value){
            $position = Position::where(array('id'=>$value['position']))->find();
            if($position['pid']) {
                $positionOne = Position::where(array('id'=>$position['pid']))->find();
                if($positionOne){
                    $work[$item]['position'] = $positionOne['name'].' · '.$position['name'];
                }
            } else {
                $work[$item]['position'] = $position['name'];
            }
            $industry = Industry::where(array('id'=>$value['industry']))->find();

            $work[$item]['start_time'] = date('Y.m',strtotime($value['start_time']));
            $work[$item]['stop_time'] = date('Y.m',strtotime($value['stop_time']));
            $work[$item]['industry'] = $industry['name'];
        }

        $education = ResumeEducation::where(array('uid'=>$data['uid']))->order('id asc')->select();
        foreach ($education as $item=>$value){
            $education[$item]['start_time'] = date('Y',strtotime($value['start_time']));
            $education[$item]['stop_time'] = date('Y',strtotime($value['stop_time']));
        }

        $user = User::where(array('uid'=>$data['uid']))->field('nickname,avatar,phone,sex')->find();
        $data['user'] = $user;

        $data['education_arr'] = $education;
        $data['work'] = $work;
        $data['expect'] = $expect;
        $data['slider_image'] = json_decode($data['slider_image'],1);

        if($data['work_time']){
            $resume_work_time = $this->diffDate($data['work_time'],date('Y-m-d',time()));
            $data['work_time'] = intval($resume_work_time['y'])?intval($resume_work_time['y']).'年':intval($resume_work_time['m']).'个月';
        }

        if(count($expect)){
            $data['price'] = $expect[0]['salary'];
            $data['position'] = $expect[0]['position'];
        } else {
            $data['price'] = 0;
            $data['position'] = '暂无';
        }

        $data['resume_price'] = SystemConfigService::get('resume_price');

        $count = UserSubion::where('uid',$this->userInfo['uid'])->where('subion_id',$data['id'])->where('paid',1)->where('type','resume')->count();
        $data['paycount'] = $count?$count:0;

        return JsonService::successful($data ? $data : []);
    }

    /**
     * 获取个人简历内容
     */
    public function get_user_resume(){
        $data = Resume::where(array('uid'=>$this->userInfo['uid']))->find();
        $data['slider_image'] = json_decode($data['slider_image'],1);
        return JsonService::successful($data ? $data : []);
    }

    /**
     * 获取求职意向内容
     */
    public function get_expect_data($id = 0){
        $data = ResumeExpect::where(array('id'=>$id))->find();
        $position = Position::where(array('id'=>$data['position']))->find();
        $industry = Industry::where(array('id'=>$data['industry']))->find();

        if($position['pid']) {
            $positionOne = Position::where(array('id'=>$position['pid']))->find();
            if($positionOne){
                $data['position_data'] = $positionOne['name'].' · '.$position['name'];
            }
        } else {
            $data['position_data'] = $position['name'];
        }
        $data['industry_data'] = $industry['name'];
        return JsonService::successful($data);
    }

    /**
     * 获取工作经历内容
     */
    public function get_work_data($id = 0){
        $data = ResumeWork::where(array('id'=>$id))->find();
        $position = Position::where(array('id'=>$data['position']))->find();
        $industry = Industry::where(array('id'=>$data['industry']))->find();

        if($position['pid']) $positionOne = Position::where(array('id'=>$position['pid']))->find();

        $data['position_data'] = $positionOne ? $positionOne['name'].' · '.$position['name']:$position['name'];
        $data['industry_data'] = $industry['name'];
        return JsonService::successful($data);
    }

    /**
     * 获取项目经历内容
     */
    public function get_project_data($id = 0){
        $data = ResumeProject::where(array('id'=>$id))->find();
        return JsonService::successful($data);
    }

    /**
     * 获取教育经历内容
     */
    public function get_education_data($id = 0){
        $data = ResumeEducation::where(array('id'=>$id))->find();
        return JsonService::successful($data);
    }

    /**
     * 删除求职意向
     */
    public function get_del_expect($id = 0){
        $data = ResumeExpect::where(array('id'=>$id))->find();
        if($data){
            ResumeExpect::where(array('id'=>$data['id']))->delete();
            return JsonService::successful('删除成功！');
        } else {
            return JsonService::fail('删除错成功!');
        }
    }

    /**
     * 删除工作经历
     */
    public function get_del_work($id = 0){
        $data = ResumeWork::where(array('id'=>$id))->find();
        if($data){
            ResumeWork::where(array('id'=>$data['id']))->delete();
            return JsonService::successful('删除成功！');
        } else {
            return JsonService::fail('删除错成功!');
        }
    }

    /**
     * 删除项目经历
     */
    public function get_del_project($id = 0){
        $data = ResumeProject::where(array('id'=>$id))->find();
        if($data){
            ResumeProject::where(array('id'=>$data['id']))->delete();
            return JsonService::successful('删除成功！');
        } else {
            return JsonService::fail('删除错成功!');
        }
    }

    /**
     * 删除教育经历
     */
    public function get_del_education($id = 0){
        $data = ResumeEducation::where(array('id'=>$id))->find();
        if($data){
            ResumeEducation::where(array('id'=>$data['id']))->delete();
            return JsonService::successful('删除成功！');
        } else {
            return JsonService::fail('删除错成功!');
        }
    }

    /**
     * 编辑个人简历信息
     */
    public function edit_user_resume(){
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['name',''],
            ['phone',''],
            ['description',''],
            ['pics',[]],
            ['work_time',0],
            ['is_show',1],
            ['status',''],
            ['sex',1],
            ['is_consent',0],
            ['id',0]
        ],$request);

        if(count($data['pics'])) $data['image'] = $data['pics'][0];
        $data['slider_image'] = json_encode($data['pics']);

        $data['uid'] = $this->userInfo['uid'];
        unset($data['pics']);
        $data['add_time'] = time();
        if($data['id'] && Resume::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid']])){
            $id = $data['id'];
            unset($data['id']);
            if(Resume::edit($data,$id,'id')){
                return JsonService::successful('编辑成功!');
            } else {
                return JsonService::fail('编辑失败!');
            }
        } else {
            if($address = Resume::set($data)){
                return JsonService::successful('添加成功!');
            } else {
                return JsonService::fail('添加失败!');
            }
        }
    }

    /**
     * 隐藏简历
     */
    public function edit_user_resume_c(){
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['is_show',0],
            ['id',0]
        ],$request);
        $data['add_time'] = time();
        if($data['id'] && Resume::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid']])){
            $id = $data['id'];
            unset($data['id']);
            if(Resume::edit($data,$id,'id')){
                return JsonService::successful('隐藏成功!');
            } else {
                return JsonService::fail('隐藏失败!');
            }
        } else {
            return JsonService::fail('隐藏失败!');
        }
    }

    /**
     * 显示简历
     */
    public function edit_user_resume_o(){
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['is_show',1],
            ['id',0]
        ],$request);
        $data['add_time'] = time();
        if($data['id'] && Resume::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid']])){
            $id = $data['id'];
            unset($data['id']);
            if(Resume::edit($data,$id,'id')){
                return JsonService::successful('显示成功!');
            } else {
                return JsonService::fail('显示失败!');
            }
        } else {
            return JsonService::fail('显示失败!');
        }
    }

    /**
     * 刷新简历
     */
    public function edit_user_resume_r(){
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['is_show',1],
            ['id',0]
        ],$request);
        $data['add_time'] = time();
        if($data['id'] && Resume::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid']])){
            $id = $data['id'];
            unset($data['id']);
            if(Resume::edit($data,$id,'id')){
                return JsonService::successful('刷新成功!');
            } else {
                return JsonService::fail('刷新失败!');
            }
        } else {
            return JsonService::fail('刷新失败!');
        }
    }

    /**
     * 编辑求职意向
     */
    public function edit_expect()
    {
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['position',0],
            ['industry',0],
            ['region_arr',[]],
            ['salary',0],
            ['id',0]
        ],$request);

        $data['province'] = $data['region_arr']['province'];
        $data['city'] = $data['region_arr']['city'];
        $data['district'] = $data['region_arr']['district'];

        $data['uid'] = $this->userInfo['uid'];
        unset($data['region_arr']);
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
     * 编辑工作经验
     */
    public function edit_work()
    {
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['position',0],
            ['industry',0],
            ['department',''],
            ['description',''],
            ['name',''],
            ['post_name',''],
            ['start_time',''],
            ['stop_time',''],
            ['is_show',1],
            ['id',0]
        ],$request);

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
     * 编辑项目经历
     */
    public function edit_project()
    {
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail('参数错误!');
        $data = UtilService::postMore([
            ['role',''],
            ['description',''],
            ['results',''],
            ['name',''],
            ['start_time',''],
            ['stop_time',''],
            ['link',''],
            ['id',0]
        ],$request);

        $data['uid'] = $this->userInfo['uid'];

        if($data['id'] && ResumeProject::be(['id'=>$data['id'],'uid'=>$this->userInfo['uid']])){
            $id = $data['id'];
            unset($data['id']);
            if(ResumeProject::edit($data,$id,'id')){
                return JsonService::successful('编辑成功!');
            } else {
                return JsonService::fail('编辑失败!');
            }
        } else {
            if($address = ResumeProject::set($data)){
                return JsonService::successful('添加成功!');
            } else {
                return JsonService::fail('添加失败!');
            }
        }
    }

    /**
     * 编辑教育经历
     */
    public function edit_education()
    {
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
     * 简历海报二维码
     * @param int $id
     * @throws \think\Exception
     */
    public function get_promotion_code($id = 0){
        if(!$id) return JsonService::fail('参数错误ID不存在');
        $count = Resume::validWhere()->count();
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
     * 获取是否收藏
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_collect($id=0)
    {
        $data = ResumeRelation::where(array('resume_id'=>$id))->where('uid',$this->userInfo['uid'])->where('type','collect')->find();
        return JsonService::successful($data);
    }

    /**
     * 添加收藏
     * @param $productId
     * @param string $category
     * @return \think\response\Json
     */
    public function collect_resume($resumeId,$type = 'collect'){
        if(!$resumeId || !is_numeric($resumeId)) return JsonService::fail('参数错误');
        $data['uid'] = $this->userInfo['uid'];
        $data['resume_id'] = $resumeId;
        $data['type'] = $type;
        $data['add_time'] = time();
        if($address = ResumeRelation::set($data)){
            return JsonService::successful('添加成功!');
        } else {
            return JsonService::fail('添加失败!');
        }
    }

    /**
     * 取消收藏
     * @param $productId
     * @param string $category
     * @return \think\response\Json
     */
    public function uncollect_resume($resumeId,$type = 'collect'){
        if(!$resumeId || !is_numeric($resumeId)) return JsonService::fail('参数错误');
        $data['uid'] = $this->userInfo['uid'];
        $data['resume_id'] = $resumeId;
        $data['type'] = $type;
        if($del = ResumeRelation::where($data)->delete()){
            return JsonService::successful('取消成功!');
        } else {
            return JsonService::fail('取消失败!');
        }
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