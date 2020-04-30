<?php
/**
 *
 * @author: 招宝通
 */

namespace app\home\controller;

use app\home\model\store\StoreCombination;
use app\home\model\store\StoreSeckill;
use app\home\model\store\StoreOrder;
use app\home\model\store\StorePink;
use app\home\model\store\StoreProduct;
use app\home\model\user\User;
use app\home\model\user\UserNotice;
use app\home\model\user\WechatUser;

use app\core\util\GroupDataService;
use app\core\util\QrcodeService;
use app\core\util\SystemConfigService;

use app\admin\model\company\CompanyCategory;

use app\admin\model\knowledge\KnowledgeCategory;

use app\ebapi\model\store\StoreCategory;
use app\ebapi\model\knowledge\Knowledge;
use app\ebapi\model\news\News AS NewsModel;
use app\ebapi\model\agreement\Agreement;
use app\ebapi\model\news\NewsCategory;
use app\ebapi\model\article\Article AS ArticleModel;
use app\core\model\routine\RoutineFormId;//待完善
use app\ebapi\model\store\StoreCouponIssue;
use app\ebapi\model\store\NewProduct;
use app\ebapi\model\company\Company;
use app\ebapi\model\job\JobPosition;
use app\ebapi\model\job\Job;
use app\ebapi\model\store\Brand;
use app\ebapi\model\position\Position;
use app\ebapi\model\industry\Industry;
use app\ebapi\model\supply\Supply;
use app\ebapi\model\resume\Resume;

use think\Url;
use basic\WapBasic;

class Index extends AuthController
//class Index extends WapBasic
{
    public function index()
    {
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

        $news = NewsModel::where('is_banner',1)->where('is_show',1)->field('id,title,cid,add_time,image')->select();
        foreach ($news as $item=>$value){
            $cate = NewsCategory::where(array('id'=>$value['cid']))->field('id,title')->find();
            $news[$item]['ctitle'] = $cate['title'];
            $news[$item]['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
        }
        $this->assign(compact('banner','menus','roll','info','activity','lovely','likeInfo','logoUrl','routineUrl','couponList','news'));
        return $this->fetch();






        try{
            $uid = User::getActiveUid();
            $notice = UserNotice::getNotice($uid);
        }catch (\Exception $e){
            $notice = 0;
        }
        $storePink = StorePink::where('p.add_time','GT',time()-86300)->alias('p')->where('p.status',1)->join('User u','u.uid=p.uid')->field('u.nickname,u.avatar as src,p.add_time')->order('p.add_time desc')->limit(20)->select();
        if($storePink){
            foreach ($storePink as $k=>$v){
                $remain = $v['add_time']%86400;
                $hour = floor($remain/3600);
                $storePink[$k]['nickname'] = $v['nickname'].$hour.'小时之前拼单';
            }
        }
        $seckillnum=(int)GroupDataService::getData('store_seckill');
        $storeSeckill=StoreSeckill::where('is_del',0)->where('status',1)
               ->where('start_time','<',time())->where('stop_time','>',time())
               ->limit($seckillnum)->order('sort desc')->select()->toArray();
        foreach($storeSeckill as $key=>$value){
            if($value['stock']>0)
            $round = round($value['sales']/$value['stock'],2)*100;
            else $round = 100;
            if($round<100){
                $storeSeckill[$key]['round']=$round;
            }else{
                $storeSeckill[$key]['round']=100;
            }
        }

        //热门推荐
        $getHotProduct = StoreProduct::getHotProduct();

        //人气企业
        $CompanyCategory = CompanyCategory::getTierList();
        $company = Company::where(array('is_hot'=>1))->select();
        foreach ($company as $item=>$value){
            //$company[$item]['tag'] = explode(',',$value['tag']);
        }

        //知识推荐
        $KnowledgeCategory = KnowledgeCategory::getTierList();
        $knowledge = Knowledge::where(array('is_hot'=>1))->select();

        $StoreCategory = StoreCategory::pidByCategory(0,'id,cate_name,pid',8);
        foreach ($StoreCategory as $item=>$value){
            $tmenu = StoreCategory::pidByCategory($value['id'],'id,cate_name,pid');
            foreach ($tmenu as $key=>$val){
                $tmenu[$key]['sub_menu'] = StoreProduct::cateIdBySimilarityProduct($val['id']);
            }
            $StoreCategory[$item]['tmenu'] = $tmenu;
        }

        $pc_class_in_ad = GroupDataService::getData('pc_class_in_ad');
        $pc_class_in_ad = array($pc_class_in_ad[0]);

        $this->assign([
            'banner'=>GroupDataService::getData('pc_banner')?:[],
            'pc_class_in_ad'=>$pc_class_in_ad?:[],
            'pc_home_top_ad'=>GroupDataService::getData('pc_home_top_ad')?:[],
            'pc_home_company'=>GroupDataService::getData('pc_home_company')?:[],
            'pc_home_job'=>GroupDataService::getData('pc_home_job')?:[],
            'pc_home_talent'=>GroupDataService::getData('pc_home_talent')?:[],
            'pc_home_knowledge'=>GroupDataService::getData('pc_home_knowledge')?:[],
            'menus'=>GroupDataService::getData('store_home_menus')?:[],
            'roll_news'=>GroupDataService::getData('store_home_roll_news')?:[],
            'category'=>$StoreCategory,
            'pinkImage'=>SystemConfigService::get('store_home_pink'),
            'notice'=>$notice,
            'storeSeckill'=>$storeSeckill,
            'storePink'=>$storePink,
            'getHotProduct'=>$getHotProduct,
            'CompanyCategory'=>$CompanyCategory,
            'company'=>$company,
            'KnowledgeCategory'=>$KnowledgeCategory,
            'knowledge'=>$knowledge
        ]);

        return $this->fetch();
    }

    public function about()
    {

        return $this->fetch();
    }

    public function spread($uni = '')
    {
        if(!$uni || $uni == 'now') $this->redirect(Url::build('spread',['uni'=>$this->oauth()]));
        $wechatUser = WechatUser::getWechatInfo($uni);
        $statu = (int)SystemConfigService::get('store_brokerage_statu');
        if($statu == 1){
            if(!User::be(['uid'=>$this->userInfo['uid'],'is_promoter'=>1]))
                return $this->failed('没有权限访问!');
        }
        $qrInfo = QrcodeService::getTemporaryQrcode('spread',$wechatUser['uid']);
        $this->assign([
            'qrInfo'=>$qrInfo,
            'wechatUser'=>$wechatUser
        ]);
        return $this->fetch();
    }

}