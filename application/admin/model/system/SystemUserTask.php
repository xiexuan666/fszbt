<?php
/**
 *
 * @author: 招宝通
 */

namespace app\admin\model\system;

use app\admin\model\order\StoreOrder;
use app\admin\model\user\User;
use app\admin\model\user\UserBill;
use app\admin\model\user\UserLevel;
use app\admin\model\user\UserTaskFinish;
use traits\ModelTrait;
use basic\ModelBasic;

/**
 * 设置等级任务 model
 * Class SystemVipTask
 * @package app\admin\model\system
 */
class SystemUserTask extends ModelBasic
{
    use ModelTrait;

    /*
     * 任务类型
     * type 记录在数据库中用来区分任务
     * name 任务名 (任务名中的{$num}会自动替换成设置的数字 + 单位)
     * max_number 最大设定数值 0为不限定
     * min_number 最小设定数值
     * unit 单位
     * */
    protected static $TaskType=[

        [
            'type'=>'NumberAddGoods',
            'name'=>'赠送发布新品信息{$num}',
            'real_name'=>'赠送发布新品信息条数',
            'max_number'=>100000000,
            'min_number'=>1,
            'unit'=>'条'
        ],
        [
            'type'=>'NumberAddSupply',
            'name'=>'赠送发布招商信息{$num}',
            'real_name'=>'赠送发布招商信息条数',
            'max_number'=>100000000,
            'min_number'=>1,
            'unit'=>'条'
        ],
        [
            'type'=>'NumberResume',
            'name'=>'赠送订阅人才简历信息{$num}',
            'real_name'=>'赠送订阅人才简历信息条数',
            'max_number'=>100000000,
            'min_number'=>1,
            'unit'=>'条'
        ],
        [
            'type'=>'NumberJob',
            'name'=>'赠送订阅职位招聘信息{$num}',
            'real_name'=>'赠送订阅职位招聘信息条数',
            'max_number'=>100000000,
            'min_number'=>1,
            'unit'=>'条'
        ],
        [
            'type'=>'NumberMerchants',
            'name'=>'赠送订阅招商信息{$num}',
            'real_name'=>'赠送订阅招商信息条数',
            'max_number'=>100000000,
            'min_number'=>1,
            'unit'=>'条'
        ],


        

        [
            'type'=>'NumberAddJob',
            'name'=>'赠送发布求职信息{$num}',
            'real_name'=>'赠送发布求职信息条数',
            'max_number'=>100000000,
            'min_number'=>0,
            'unit'=>'条'
        ],
        [
            'type'=>'NumberAddRecruitment',
            'name'=>'赠送发布招聘信息{$num}',
            'real_name'=>'赠送发布招聘信息条数',
            'max_number'=>100000000,
            'min_number'=>0,
            'unit'=>'条'
        ],
        [
            'type'=>'NumberAddArticle',
            'name'=>'赠送发布资讯信息{$num}',
            'real_name'=>'赠送发布资讯信息条数',
            'max_number'=>100000000,
            'min_number'=>0,
            'unit'=>'条'
        ],
        [
            'type'=>'NumberAddGoods',
            'name'=>'赠送发布干货信息{$num}',
            'real_name'=>'赠送发布干货信息条数',
            'max_number'=>100000000,
            'min_number'=>0,
            'unit'=>'条'
        ],
        [
            'type'=>'NumberBook',
            'name'=>'赠送课程信息{$num}',
            'real_name'=>'赠送课程信息次数',
            'max_number'=>100000000,
            'min_number'=>1,
            'unit'=>'次'
        ],
        [
            'type'=>'PresentIntegral',
            'name'=>'消费赠送积分{$num}',
            'real_name'=>'分',
            'max_number'=>100000000,
            'min_number'=>0,
            'unit'=>'分'
        ],
        [
            'type'=>'SatisfactionIntegral',
            'name'=>'满足积分{$num}',
            'real_name'=>'积分数',
            'max_number'=>100000000,
            'min_number'=>0,
            'unit'=>'分'
        ],
        [
            'type'=>'ConsumptionAmount',
            'name'=>'消费满{$num}',
            'real_name'=>'消费金额',
            'max_number'=>100000000,
            'min_number'=>0,
            'unit'=>'元'
        ],
        [
            'type'=>'ConsumptionFrequency',
            'name'=>'消费{$num}',
            'real_name'=>'消费次数',
            'max_number'=>100000000,
            'min_number'=>0,
            'unit'=>'次'
        ],
        [
            'type'=>'CumulativeAttendance',
            'name'=>'累计签到{$num}',
            'real_name'=>'累计签到',
            'max_number'=>365,
            'min_number'=>1,
            'unit'=>'天'
        ],
        [
            'type'=>'SharingTimes',
            'name'=>'分享给朋友{$num}',
            'real_name'=>'分享给朋友',
            'max_number'=>100000000,
            'min_number'=>1,
            'unit'=>'次'
        ],
        [
            'type'=>'InviteGoodFriends',
            'name'=>'邀请好友{$num}成为下线',
            'real_name'=>'邀请好友成为下线',
            'max_number'=>100000000,
            'min_number'=>1,
            'unit'=>'人'
        ],
        [
            'type'=>'InviteGoodFriendsLevel',
            'name'=>'邀请好友{$num}成为会员',
            'real_name'=>'邀请好友成为会员',
            'max_number'=>100000000,
            'min_number'=>1,
            'unit'=>'人'
        ]
    ];

    public function profile()
    {
        return $this->hasOne('SystemUserLevel','level_id','id')->field('name');
    }

    public static function getTaskTypeAll()
    {
        return self::$TaskType;
    }

    /*
     * 获取某个任务
     * */
    public static function getTaskType($type)
    {
        foreach (self::$TaskType as $item){
            if($item['type']==$type) return $item;
        }
    }

    /*
     * 设置任务名
     * */
    public static function setTaskName($type,$num)
    {
        $systemType=self::getTaskType($type);
        return str_replace('{$num}',$num.$systemType['unit'],$systemType['name']);
    }
    /*
     * 获取等级会员任务列表
     * */
    public static function getTashList($level_id,$page,$limit)
    {
        $data=self::where('level_id',$level_id)->order('sort desc,add_time desc')->page($page,$limit)->select();
        $data=count($data) ? $data->toArray() : [];
        foreach ($data as &$item){
            $item['level_name']=SystemUserLevel::where('id',$item['level_id'])->value('name');
        }
        $count=self::where('level_id',$level_id)->count();
        return compact('data','count');
    }

}