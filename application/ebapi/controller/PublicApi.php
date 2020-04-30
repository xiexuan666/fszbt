<?php
namespace app\ebapi\controller;

use app\core\model\user\UserBill;
use app\core\model\system\SystemUserLevel;
use app\core\model\system\SystemUserTask;
use app\core\model\user\UserTaskFinish;
use app\core\model\user\UserLevel;
use app\ebapi\model\knowledge\Knowledge;
use app\ebapi\model\news\News AS NewsModel;
use app\ebapi\model\agreement\Agreement;
use app\ebapi\model\news\NewsCategory;
use app\ebapi\model\article\Article AS ArticleModel;
use app\ebapi\model\store\StoreCategory;
use app\core\model\routine\RoutineFormId;//待完善
use app\ebapi\model\store\StoreCouponIssue;
use app\ebapi\model\store\StoreProduct;
use app\ebapi\model\store\NewProduct;
use app\ebapi\model\company\Company;
use app\ebapi\model\job\JobPosition;
use app\ebapi\model\job\Job;
use app\ebapi\model\store\Brand;
use app\ebapi\model\position\Position;
use app\ebapi\model\industry\Industry;
use app\ebapi\model\supply\Supply;
use app\ebapi\model\user\User;
use app\ebapi\model\resume\Resume;
use app\core\util\GroupDataService;
use service\HttpService;
use service\JsonService;
use app\core\util\SystemConfigService;
use service\UploadService;
use service\UtilService;
use think\Cache;

/**
 * 小程序公共接口
 * Class PublicApi
 * @package app\ebapi\controller
 *
 */
class PublicApi extends AuthController
{
    /*
     * 白名单不验证token 如果传入token执行验证获取信息，没有获取到用户信息
     * */
    public static function whiteList()
    {
        return [
            'index',
            'get_index_groom_list',
            'get_company',
            'get_hot_product',
            'refresh_cache',
            'clear_cache',
            'get_logo_url',
            'get_my_naviga',
            'get_level_list',
            'get_level_my',
            'get_giving_supply_num'
        ];
    }

    /*
     * 获取个人中心菜单
     * */
    public function get_my_naviga($level = 0)
    {
        $list = GroupDataService::getData('routine_my_menus');
        foreach ($list as $item => $value){
            if(in_array($level,$value['level'])){
                $list[$item]['isLevel'] = true;
            } else {
                $list[$item]['isLevel'] = false;
            }
        }
        return JsonService::successful(['routine_my_menus'=>$list,'vip_user'=>SystemConfigService::get('vip_user'),'agree'=>SystemConfigService::get('agree')]);
    }
    /*
     * 获取授权登录log
     * */
    public function get_logo_url()
    {
        $routine_logo=SystemConfigService::get('routine_logo');
        return JsonService::successful(['logo_url'=>str_replace('\\','/',$routine_logo)]);
    }

    /**
     * TODO 获取首页推荐不同类型产品的轮播图和产品
     * @param int $type
     */
    public function get_index_groom_list($type = 1){
        $info['banner'] = [];
        $info['list'] = [];
        if($type == 1){//TODO 精品推荐
            $info['banner'] = GroupDataService::getData('routine_home_bast_banner')?:[];//TODO 首页精品推荐图片
            $info['list'] = StoreProduct::getBestProduct('id,image,ficti,keyword,store_name,cate_id,price,ot_price,IFNULL(sales,0) + IFNULL(ficti,0) as sales,unit_name,sort');//TODO 精品推荐个数
        }else if($type == 2){//TODO 热门榜单
            $info['banner'] = GroupDataService::getData('routine_home_hot_banner')?:[];//TODO 热门榜单 猜你喜欢推荐图片
            $info['list'] = StoreProduct::getHotProduct('id,image,store_name,cate_id,price,ot_price,unit_name,sort,IFNULL(sales,0) + IFNULL(ficti,0) as sales',0,$this->uid);//TODO 热门榜单 猜你喜欢
        }else if($type == 3){//TODO 首发新品
            //$info['banner'] = GroupDataService::getData('routine_home_new_banner')?:[];//TODO 首发新品推荐图片
            $info['banner'] = NewProduct::getHotProductLoading('*');//TODO 置顶新品
            $info['list'] = NewProduct::getNewProduct('*',0,$this->uid);//TODO 首发新品
        }else if($type == 4){//TODO 促销单品
            $info['banner'] = GroupDataService::getData('routine_home_benefit_banner')?:[];//TODO 促销单品推荐图片
            $info['list'] = StoreProduct::getBenefitProduct('id,image,ficti,keyword,store_name,cate_id,price,ot_price,stock,unit_name,sort');//TODO 促销单品
        }
        return JsonService::successful($info);
    }

    /**
     * 首页
     */
    public function index(){
        $banner = GroupDataService::getData('routine_home_banner')?:[];//TODO 首页banner图
        $menus = GroupDataService::getData('routine_home_menus')?:[];//TODO 首页按钮
        $roll = GroupDataService::getData('routine_home_roll_news')?:[];//TODO 首页滚动新闻
        $activity = GroupDataService::getData('routine_home_activity',3)?:[];//TODO 首页活动区域图片
        $info['fastInfo'] = SystemConfigService::get('fast_info');//TODO 快速选择简介
        $info['firstInfo'] = SystemConfigService::get('first_info');//TODO 首发新品简介
        $info['salesInfo'] = SystemConfigService::get('sales_info');//TODO 促销单品简介

        $info['companyInfo'] = SystemConfigService::get('company_info');//TODO 人气企业
        $info['knowledgeInfo'] = SystemConfigService::get('knowledge_info');//TODO 干货分享

        $routineUrl = SystemConfigService::get('routine_index_logo');//TODO 促销单品简介
        if(strstr($routineUrl,'http')===false) $routineUrl=SystemConfigService::get('site_url').$routineUrl;
        $routineUrl = str_replace('\\','/',$routineUrl);

        $logoUrl = SystemConfigService::get('routine_logo');//TODO 促销单品简介
        if(strstr($logoUrl,'http')===false) $logoUrl=SystemConfigService::get('site_url').$logoUrl;
        $logoUrl=str_replace('\\','/',$logoUrl);

        $fastNumber = (int)SystemConfigService::get('fast_number');//TODO 宝通严选个数
        $bastNumber = (int)SystemConfigService::get('bast_number');//TODO 人气企业个数
        $firstNumber = (int)SystemConfigService::get('first_number');//TODO 首发新品个数
        $knowledge_number = (int)SystemConfigService::get('knowledge_number');//TODO 干货分享个数

        $info['fastList'] = StoreCategory::byIndexList($fastNumber);//TODO 快速选择分类个数
        $info['firstList'] = NewProduct::getNewProduct('*',$firstNumber);//TODO 首发新品个数
        $info['brandList'] = Brand::where('is_hot',1)->where('is_show',1)->order('is_hot desc,browse desc')->limit($fastNumber)->select();//TODO 宝通严选

        $info['companyList'] = Company::getCompanyListHot('*',$bastNumber)?:[];//TODO 人气企业个数

        $jobList = JobPosition::getJobList();
        $positionOne = $industryOne = 0;
        foreach ($jobList as $item=>$value){
            $jobInfo = Job::where(array('uid'=>$value['uid']))->find();
            $companyInfo = Company::where(array('uid'=>$value['uid']))->find();

            $jobList[$item]['jobInfo'] = $jobInfo;
            $jobList[$item]['companyInfo'] = $companyInfo;

            $position_info = Position::where(array('id'=>$value['position']))->find();
            $industry_info = Industry::where(array('id'=>$value['industry']))->find();

            if($position_info['pid']) $positionOne = Position::where(array('id'=>$position_info['pid']))->find();
            if($industry_info['pid']) $industryOne = Industry::where(array('id'=>$industry_info['pid']))->find();

            $jobList[$item]['position_data'] = $positionOne ? $positionOne['name'].' · '.$position_info['name']:$position_info['name'];
            $jobList[$item]['industry_data'] = $industryOne ? $industryOne['name'].' · '.$industry_info['name']:$industry_info['name'];

            $user = User::where(array('uid'=>$value['uid']))->field('nickname,avatar')->find();
            $jobList[$item]['user'] = $user;
            $jobList[$item]['skills'] = explode(',',$value['skills']);
        }

        $info['jobList'] = $jobList?:[];
        $info['resumeList'] = Resume::getResumeList()?:[];


        $knowledgeList = Knowledge::getArticleList($knowledge_number);
        foreach ($knowledgeList as $item=>$value){
            $user = User::where(array('uid'=>$value['uid']))->field('nickname,avatar')->find();
            $knowledgeList[$item]['user'] = $user;
            $knowledgeList[$item]['comments'] = 0;
            $knowledgeList[$item]['likes'] = 0;
            $knowledgeList[$item]['add_time'] = date('Y-m-d',$value['add_time']);
            $knowledgeList[$item]['posters'] = json_decode($value['posters'],1);
            $knowledgeList[$item]['slider_image'] = json_decode($value['slider_image'],1);
        }
        $info['knowledgeList'] = $knowledgeList?:[];

        $articleList = ArticleModel::cidByList(0,0,8,"id,title,image,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url");
        $info['articleList'] = $articleList?:[];//TODO 知识推荐个数

        $info['bastBanner'] = GroupDataService::getData('routine_home_bast_banner')?:[];//TODO 首页精品推荐图片
        $info['companyBanner'] = GroupDataService::getData('company_home_banner')?:[];//TODO 首页人气企业图片

        $lovely =[];//TODO 首发新品顶部图
        $likeInfo = StoreProduct::getHotProduct('id,image,store_name,give_integral,cate_id,price,unit_name,sort',3);//TODO 热门榜单 猜你喜欢
        $couponList=StoreCouponIssue::getIssueCouponList($this->uid,3);

        $news = NewsModel::where('is_banner',1)->where('is_show',1)->field('id,title,cid')->select();
        foreach ($news as $item=>$value){
            $cate = NewsCategory::where(array('id'=>$value['cid']))->field('id,title')->find();
            $news[$item]['ctitle'] = $cate['title'];
        }

        return $this->successful(compact('banner','menus','roll','info','activity','lovely','likeInfo','logoUrl','routineUrl','couponList','news'));
    }

    /**
     * 企业列表  加载
     */
    public function get_company(){
        $data = UtilService::getMore([['offset',0],['limit',0]],$this->request);
        $hot = Company::getCompanyLoading('id,title,author,image,slider_image,share_title,share_synopsis,visit,sort,add_time,mer_id,is_hot',$data['offset'],$data['limit']);
        return $this->successful($hot);
    }

    /**
     * 猜你喜欢  加载
     */
    public function get_hot_product(){
        $data = UtilService::getMore([['offset',0],['limit',0]],$this->request);
        $hot = StoreProduct::getHotProductLoading('id,image,store_name,cate_id,price,give_integral,unit_name,sort',$data['offset'],$data['limit']);//猜你喜欢
        return $this->successful($hot);
    }

    /*
     * 根据经纬度获取当前地理位置
     * */
    public function getlocation($latitude='',$longitude=''){
        $location=HttpService::getRequest('https://apis.map.qq.com/ws/geocoder/v1/',
            ['location'=>$latitude.','.$longitude,'key'=>'U65BZ-F2IHX-CGZ4I-73I7L-M6FZF-TEFCH']);
        $location=$location ? json_decode($location,true) : [];
        if($location && isset($location['result']['address'])){
            try{
                $address=$location['result']['address_component']['street'];
                return $this->successful(['address'=>$address]);
            }catch (\Exception $e){
                return $this->fail('获取位置信息失败!');
            }
        }else{
            return $this->fail('获取位置信息失败!');
        }
    }

    /*
     * 根据key来取系统的值
     * */
    public function get_system_config_value($name=''){
        if($name=='') return JsonService::fail('缺少参数');
        $name=str_replace(SystemConfigService::$ProtectedKey,'',$name);
        if(strstr($name,',')!==false){
            return $this->successful(SystemConfigService::more($name));
        }else{
            $value=SystemConfigService::get($name);
            $value=is_array($value) ? $value[0] : $value;
            return $this->successful([$name=>$value]);
        }
    }

    /*
     * 获取系统
     * */
    public function get_system_group_data_value($name='',$multi=0){
        if($name=='') return $this->successful([$name=>[]]);
        if($multi==1){
            $name=json_decode($name,true);
            $value=[];
            foreach ($name as $item){
                $value[$item]=GroupDataService::getData($item)?:[];
            }
            return $this->successful($value);
        }else{
            $value= GroupDataService::getData($name)?:[];
            return $this->successful([$name=>$value]);
        }
    }
    /*
     * 删除指定资源
     *
     * */
    public function delete_image(){
        $post=UtilService::postMore([
            ['pic',''],
        ]);
        if($post['pic']=='') return $this->fail('缺少删除资源');
        $type=['php','js','css','html','ttf','otf'];
        $post['pic']=substr($post['pic'],1);
        $ext=substr($post['pic'],-3);
        if(in_array($ext,$type)) return $this->fail('非法操作');
        if(strstr($post['pic'],'uploads')===false) return $this->fail('非法操作');
        try{
            if(file_exists($post['pic'])) unlink($post['pic']);
            if(strstr($post['pic'],'s_')!==false){
                $pic=str_replace(['s_'],'',$post['pic']);
                if(file_exists($pic)) unlink($pic);
            }
            return $this->successful('删除成功');
        }catch (\Exception $e){
            return $this->fail('刪除失败',['line'=>$e->getLine(),'message'=>$e->getMessage()]);
        }
    }

    /**
     * 上传图片
     * @param string $filename
     * @return \think\response\Json
     */
    public function upload($dir='')
    {
        $data = UtilService::postMore([
            ['filename',''],
        ],$this->request);
        if(Cache::has('start_uploads_'.$this->uid) && Cache::get('start_uploads_'.$this->uid) >= 100) return $this->fail('非法操作');
        $res = UploadService::image($data['filename'],$dir ? $dir: 'store/comment');
        if($res->status == 200){
           if(Cache::has('start_uploads_'.$this->uid))
               $start_uploads=(int)Cache::get('start_uploads_'.$this->uid);
           else
               $start_uploads=0;
            $start_uploads++;
            Cache::set('start_uploads_'.$this->uid,$start_uploads,86400);
            return $this->successful('图片上传成功!', ['name' => $res->fileInfo->getSaveName(), 'url' => UploadService::pathToUrl($res->dir)]);
        }else
            return $this->fail($res->error);
    }

    /**
     * 获取退款理由
     */
    public function get_refund_reason(){
        $reason = SystemConfigService::get('stor_reason')?:[];//退款理由
        $reason = str_replace("\r\n","\n",$reason);//防止不兼容
        $reason = explode("\n",$reason);
        return $this->successful($reason);
    }

    /**
     * 获取提现银行
     */
    public function get_user_extract_bank(){
        $extractBank = SystemConfigService::get('user_extract_bank')?:[];//提现银行
        $extractBank = str_replace("\r\n","\n",$extractBank);//防止不兼容
        $data['extractBank'] = explode("\n",$extractBank);
        $data['minPrice'] = SystemConfigService::get('user_extract_min_price');//提现最低金额
        return $this->successful($data);
    }

    /**
     * 收集发送模板信息的formID
     * @param string $formId
     */
    public function get_form_id($formId = ''){
        if($formId==''){
            list($formIds)=UtilService::postMore([
                ['formIds',[]]
            ],$this->request,true);
            foreach ($formIds as $formId){
                RoutineFormId::SetFormId($formId,$this->uid);
            }
        }else
            RoutineFormId::SetFormId($formId,$this->uid);
        return $this->successful('');
    }

    /**
     * 刷新数据缓存
     */
    public function refresh_cache(){
        `php think optimize:schema`;
        `php think optimize:autoload`;
        `php think optimize:route`;
        `php think optimize:config`;
    }

    /*
    * 清除系统全部缓存
    * @return
    * */
    public function clear_cache()
    {
        \think\Cache::clear();
    }

    /*
     * 获取会员等级
     * */
    public function get_level_list()
    {
        return JsonService::successful(SystemUserLevel::getLevelList($this->uid));
    }

    /**
     * 个人vip
     */
    public function get_level_my()
    {
        return JsonService::successful(SystemUserLevel::getLevelMy($this->uid));
    }

    /**
     * 注册-获取会员等级
     */
    public function reg_get_level_list()
    {
        return JsonService::successful(SystemUserLevel::regGetLevelList($this->uid));
    }

    /*
     * 获取某个等级的任务
     * @param int $level_id 等级id
     * @return json
     * */
    public function get_task($level_id=''){
        return JsonService::successful(SystemUserTask::getTashList($level_id,$this->uid));
    }

    /*
     * 检测用户是否可以成为会员
     * */
    public function set_level_complete()
    {
        return JsonService::successful(UserLevel::setLevelComplete($this->uid));
    }

    /*
     * 记录用户分享次数
     * */
    public function set_user_share()
    {
        return JsonService::successful(UserBill::setUserShare($this->uid));
    }

    /**
     * 获取固定支付金额-新品
     */
    public function get_pay_price()
    {
        $data['add_goods_price'] = SystemConfigService::get('add_goods_price');
        return JsonService::successful($data);
    }

    /**
     * 获取固定支付金额-招商
     */
    public function get_pay_supply_price()
    {
        $data['add_supply_price'] = SystemConfigService::get('add_supply_price');
        return JsonService::successful($data);
    }

    /**
     * 获取发布招商赠送条数
     */
    public function get_supply_num()
    {
        $count = Supply::where('uid',$this->uid)->count();
        $data['count'] = $count;
        $data['add_supply_num'] = intval(SystemConfigService::get('add_supply_num'));
        $data['remaining'] = intval(bcsub($data['add_supply_num'],$data['count']));
        return JsonService::successful($data);
    }

    /**
     * 获取招商信息赠送条数
     */
    public function get_merchants_pumping_num()
    {
        $data['merchants_pumping_num'] = SystemConfigService::get('merchants_pumping_num');
        return JsonService::successful($data);
    }

    /**
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 检查-招商-赠送剩余额度
     */
    public function get_giving_supply_num(){

        $level_id = UserLevel::getUserLevel($this->userInfo['uid']);
        $level = UserLevel::getUserLevelInfo($level_id);
        $data = SystemUserTask::where('level_id',$level['level_id'])->where('task_type','NumberMerchants')->find();

        $count = UserTaskFinish::where('task_id',$data['id'])->where('uid',$this->userInfo['uid'])->where('status',1)->count();
        $number = bcsub($data['number'],$count);

        $data['count'] = $count;
        $data['new_number'] = $number;
        if($number > 0){
            return JsonService::successful($data);
        } else {
            return JsonService::successful(array('code'=>201,'msg'=>'您的赠送额度已用完','new_number'=>$number,'count'=>$count));
        }
    }

    /**
     * 赠送发布招商信息-剩余额度
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_number_add_supply(){
        $level_id = UserLevel::getUserLevel($this->userInfo['uid']);
        $level = UserLevel::getUserLevelInfo($level_id);
        $data = SystemUserTask::where('level_id',$level['level_id'])->where('task_type','NumberAddSupply')->find();
        if($data){
            $count = UserTaskFinish::where('task_id',$data['id'])->where('uid',$this->userInfo['uid'])->where('status',1)->count();
            $number = bcsub($data['number'],$count);

            $data['count'] = $count;
            $data['add_supply_num'] = intval($data['number']);
            $data['remaining'] = intval($number);

            if($number > 0){
                return JsonService::successful($data);
            } else {
                return JsonService::successful(array('code'=>201,'msg'=>'您的赠送额度已用完','new_number'=>$number,'count'=>$count));
            }
        } else {
            return JsonService::successful(array('code'=>201,'msg'=>'您暂无优惠信息，如有疑问请联系客服！'));
        }
    }

    /**
     * 赠送发布新品信息-剩余额度
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_number_add_goods(){
        $level_id = UserLevel::getUserLevel($this->userInfo['uid']);
        $level = UserLevel::getUserLevelInfo($level_id);
        $data = SystemUserTask::where('level_id',$level['level_id'])->where('task_type','NumberAddGoods')->find();
        if($data){
            $count = UserTaskFinish::where('task_id',$data['id'])->where('uid',$this->userInfo['uid'])->where('status',1)->count();
            $number = bcsub($data['number'],$count);

            $data['count'] = $count;
            $data['add_supply_num'] = intval($data['number']);
            $data['remaining'] = intval($number);

            if($number > 0){
                return JsonService::successful($data);
            } else {
                return JsonService::successful(array('code'=>201,'msg'=>'您的赠送额度已用完','new_number'=>$number,'count'=>$count));
            }
        } else {
            return JsonService::successful(array('code'=>201,'msg'=>'您暂无优惠信息，如有疑问请联系客服！'));
        }
    }

    /**
     * 赠送人才信息-剩余额度
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_number_resume(){
        $level_id = UserLevel::getUserLevel($this->userInfo['uid']);
        $level = UserLevel::getUserLevelInfo($level_id);
        $data = SystemUserTask::where('level_id',$level['level_id'])->where('task_type','NumberResume')->find();

        $count = UserTaskFinish::where('task_id',$data['id'])->where('uid',$this->userInfo['uid'])->where('status',1)->count();
        $number = bcsub($data['number'],$count);

        $data['count'] = $count;
        $data['add_supply_num'] = intval($data['number']);
        $data['remaining'] = intval($number);

        if($number > 0){
            return JsonService::successful($data);
        } else {
            return JsonService::successful(array('code'=>201,'msg'=>'您的赠送额度已用完','new_number'=>$number,'count'=>$count));
        }
    }

    /**
     * 赠送招聘信息-剩余额度
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_number_job(){
        $level_id = UserLevel::getUserLevel($this->userInfo['uid']);
        $level = UserLevel::getUserLevelInfo($level_id);
        $data = SystemUserTask::where('level_id',$level['level_id'])->where('task_type','NumberJob')->find();
        if($data){
            $count = UserTaskFinish::where('task_id',$data['id'])->where('uid',$this->userInfo['uid'])->where('status',1)->count();
            $number = bcsub($data['number'],$count);

            $data['count'] = $count;
            $data['add_supply_num'] = intval($data['number']);
            $data['remaining'] = intval($number);

            if($number > 0){
                return JsonService::successful($data);
            } else {
                return JsonService::successful(array('code'=>201,'msg'=>'您的赠送额度已用完','remaining'=>$number,'count'=>$count));
            }
        } else {
            return JsonService::successful(array('code'=>201,'msg'=>'您的赠送额度已用完','remaining'=>0,'count'=>0));
        }

    }

    public function get_user_recharge(){
        $recharge = GroupDataService::getData('member_card')?:[];//TODO 获取充值活动列表
        return $this->successful(compact('recharge'));
    }

    /**
     * 协议文章
     * @param int $id
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function agreement($id = 0){
        $data = Agreement::getOne($id);
        if(!$data || !$data["is_show"]) return $this->fail('协议文章不存在!');
        return $this->successful($data);
    }

}