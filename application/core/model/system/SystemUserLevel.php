<?php


namespace app\core\model\system;

use app\core\model\user\User;
use app\core\model\user\UserLevel;
use app\core\util\SystemConfigService;
use think\Cache;
use traits\ModelTrait;
use basic\ModelBasic;

/**
 * 设置会员vip model
 * Class SystemVip
 * @package app\core\model\system
 */
class SystemUserLevel extends ModelBasic
{
    use ModelTrait;

    public static function getAddTimeAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }
    public static function getDiscountAttr($value)
    {
        return (int)$value;
    }
    /*
     * 获取查询条件
     * @param string $alert 别名
     * @param object $model 模型
     * @return object
     * */
    public static function setWhere($alert='',$model=null)
    {
        $model=$model===null ? new self() : $model;
        if($alert) $model=$model->alias($alert);
        $alert=$alert ? $alert.'.': '';
        return $model->where("{$alert}is_show",1)->where("{$alert}is_del",0);
    }

    /*
     * 获取某个等级的折扣
     * */
    public static function getLevelDiscount($id=0)
    {
        $model=self::setWhere();
        if($id) $model=$model->where('id',$id);
        else $model=$model->order('grade asc');
        return $model->value('discount');
    }

    /*
     * 获取用户等级和当前等级
     * @param int $uid 用户uid
     * @param Boolean $isArray 是否查找任务列表
     * @return array
     * */
    public static function getLevelInfo($uid,$isArray=false){
        $level=['id'=>0];$task=[];
        $id=UserLevel::getUserLevel($uid);
        if($id!==false) $level=UserLevel::getUserLevelInfo($id);
        $list = self::getLevelListAndGrade($level['id'],$isArray);
        if(isset($list[0]) && $isArray) $task = SystemUserTask::getTashList($list[0]['id'],$uid,$level);
        $user = User::where('uid',$uid)->find();

        $levelData = self::where('id',$list[0]['id'])->find();
        $agree = SystemConfigService::get('agree');
        $registration_agreement = SystemConfigService::get('registration_agreement');
        $add_information = SystemConfigService::get('add_information');
        if($isArray) return [$list,$task,$user,$levelData,$agree,$registration_agreement,$add_information];
        else return $level['id'] && $id !== false ? $level : false;
    }

    /*
     * 获取会员等级级别
     * @param int $leval_id 等级id
     * @return Array
     * */
    public static function getLevelGrade($leval_id)
    {
        return self::setWhere()->where('id',$leval_id)->value('grade');
    }
    /*
     * 获取会员等级列表
     * @param int $levael_id 用户等级
     * @param Boolean $isArray 是否查找任务列表
     * @return Array
     * */
    public static function getLevelListAndGrade($leval_id,$isArray,$expire=1400)
    {
        $grade=0;
        $list = self::setWhere()->field(['name','discount','image','icon','explain','id','grade'])->order('grade asc')->select();
        $list = count($list) ? $list->toArray() : [];
        foreach ($list as &$item){
            if($item['id']==$leval_id) $grade=$item['grade'];
            if($isArray) $item['task_list'] = SystemUserTask::getTashList($item['id']);
        }
        foreach ($list as &$item){
            if($grade <> $item['grade']) $item['is_clear']=true;
            else $item['is_clear']=false;
        }
        return $list;
    }

    public static function getClear($leval_id,$list=null)
    {
        $list=$list===null ?  self::getLevelListAndGrade($leval_id,false) : $list;
        foreach ($list as $item){
            if($item['id']==$leval_id) return $item['is_clear'];
        }
        return false;
    }

    /*
     * 获取当前vipid 的下一个会员id
     * @param int $leval_id 当前用户的会员id
     * @return int
     * */
    public static function getNextLevelId($leval_id)
    {
        $list=self::getLevelListAndGrade($leval_id,false);
        $grade=0;
        $leveal=[];
        foreach ($list as $item){
            if($item['id']==$leval_id) $grade=$item['grade'];
        }
        foreach ($list as $item){
            if($grade < $item['grade']) array_push($leveal,$item['id']);
        }
        return isset($leveal[0]) ? $leveal[0] : 0;
    }

    /*
     * 获取会员等级列表
     * @parma int $uid 用户uid
     * @return Array
     * */
    public static function getLevelList($uid){
        list($list,$task,$user,$levelData,$agree,$registration_agreement,$add_information) = self::getLevelInfo($uid,true);
        return ['list'=>$list,'task'=>$task,'user'=>$user,'levelData'=>$levelData,'agree'=>$agree,'registration_agreement'=>$registration_agreement,'add_information'=>$add_information];
    }

    /**
     * @param $uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 个人权限
     */
    public static function getLevelMy($uid){
        list($list,$task,$user,$levelData,$agree,$registration_agreement,$add_information) = self::getLevelMyInfo($uid,true);
        return ['list'=>$list,'task'=>$task,'user'=>$user,'levelData'=>$levelData,'agree'=>$agree,'registration_agreement'=>$registration_agreement,'add_information'=>$add_information];
    }

    /**
     * @param $uid
     * @param bool $isArray
     * @return array|bool|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取用户等级和当前等级
     */
    public static function getLevelMyInfo($uid,$isArray=false){

        $level=['id'=>0];$task=[];$levelData=[];
        $id = UserLevel::getUserLevel($uid);
        if($id!==false) $level = UserLevel::getUserLevelInfo($id);
        $list = self::getLevelMyAndGrade($level['id'],$isArray);
        if(isset($list[0]) && $isArray) $task = SystemUserTask::getTashList($list[0]['id'],$uid,$level);

        $user = User::where('uid',$uid)->find();

        if(isset($list[0])) $levelData = self::where('id',$list[0]['id'])->find();
        $agree = SystemConfigService::get('agree');
        $registration_agreement = SystemConfigService::get('registration_agreement');
        $add_information = SystemConfigService::get('add_information');
        if($isArray) return [$list,$task,$user,$levelData,$agree,$registration_agreement,$add_information];
        else return $level['id'] && $id !== false ? $level : false;
    }

    /**
     * @param $leval_id
     * @param $isArray
     * @param int $expire
     * @return array|false|mixed|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取会员等级列表
     */
    public static function getLevelMyAndGrade($leval_id,$isArray,$expire=1400)
    {

        $grade=0;
        $list = self::setWhere()->where('id',$leval_id)->field(['name','discount','image','icon','explain','id','grade'])->order('grade asc')->select();
        $list = count($list) ? $list->toArray() : [];
        foreach ($list as &$item){
            if($item['id']==$leval_id) $grade=$item['grade'];
            if($isArray) $item['task_list'] = SystemUserTask::getTashList($item['id']);
        }
        foreach ($list as &$item){
            if($grade < $item['grade']) $item['is_clear']=true;
            else $item['is_clear']=false;
        }

        return $list;
    }

}