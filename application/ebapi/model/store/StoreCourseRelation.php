<?php
/**
 *
 * @author: 招宝通
 */

namespace app\ebapi\model\store;

use app\core\behavior\GoodsBehavior;
use service\HookService;
use traits\ModelTrait;
use basic\ModelBasic;

/**
 * 点赞收藏model
 * Class StoreCourseRelation
 * @package app\ebapi\model\store
 */
class StoreCourseRelation extends ModelBasic
{
    use ModelTrait;

    /**
     * 获取用户点赞所有产品的个数
     * @param $uid
     * @return int|string
     */
    public static function getUserIdLike($uid = 0){
        $count = self::where('uid',$uid)->where('type','like')->count();
        return $count;
    }

    /**
     * 获取用户收藏所有产品的个数
     * @param $uid
     * @return int|string
     */
    public static function getUserIdCollect($uid = 0){
        $count = self::where('uid',$uid)->where('type','collect')->count();
        return $count;
    }

    /**
     * 添加点赞 收藏
     * @param $courseId
     * @param $uid
     * @param $relationType
     * @param string $category
     * @return bool
     */
    public static function courseRelation($courseId,$uid,$relationType,$category = 'course')
    {
        if(!$courseId) return self::setErrorInfo('产品不存在!');
        $relationType = strtolower($relationType);
        $category = strtolower($category);
        $data = ['uid'=>$uid,'course_id'=>$courseId,'type'=>$relationType,'category'=>$category];
        if(self::be($data)) return true;
        $data['add_time'] = time();
        self::set($data);
        HookService::afterListen('store_'.$category.'_'.$relationType,$courseId,$uid,false,GoodsBehavior::class);
        return true;
    }

    /**
     * 批量 添加点赞 收藏
     * @param $courseIdS
     * @param $uid
     * @param $relationType
     * @param string $category
     * @return bool
     */
    public static function courseRelationAll($courseIdS,$uid,$relationType,$category = 'course'){
        $res = true;
        if(is_array($courseIdS)){
            self::beginTrans();
            foreach ($courseIdS as $courseId){
                $res = $res && self::courseRelation($courseId,$uid,$relationType,$category);
            }
            self::checkTrans($res);
            return $res;
        }
        return $res;
    }

    /**
     * 取消 点赞 收藏
     * @param $courseId
     * @param $uid
     * @param $relationType
     * @param string $category
     * @return bool
     */
    public static function unCourseRelation($courseId,$uid,$relationType,$category = 'course')
    {
        if(!$courseId) return self::setErrorInfo('产品不存在!');
        $relationType = strtolower($relationType);
        $category = strtolower($category);
        self::where(['uid'=>$uid,'course_id'=>$courseId,'type'=>$relationType,'category'=>$category])->delete();
        HookService::afterListen('store_'.$category.'_un_'.$relationType,$courseId,$uid,false,GoodsBehavior::class);
        return true;
    }

    public static function courseRelationNum($courseId,$relationType,$category = 'course')
    {
        $relationType = strtolower($relationType);
        $category = strtolower($category);
        return self::where('type',$relationType)->where('course_id',$courseId)->where('category',$category)->count();
    }

    public static function isCourseRelation($course_id,$uid,$relationType,$category = 'course')
    {
        $type = strtolower($relationType);
        $category = strtolower($category);
        return self::be(compact('course_id','uid','type','category'));
    }

    /*
     * 获取某个用户收藏产品
     * @param int uid 用户id
     * @param int $first 行数
     * @param int $limit 展示行数
     * @return array
     * */
    public static function getUserCollectCourse($uid,$page,$limit)
    {
        $list = self::where('A.uid',$uid)
            ->field('B.id pid,B.store_name,B.price,B.ot_price,B.sales,B.image,B.is_del,B.is_show')->alias('A')
            ->where('A.type','collect')->where('A.category','course')
            ->order('A.add_time DESC')->join('__STORE_PRODUCT__ B','A.course_id = B.id')
            ->page((int)$page,(int)$limit)->select()->toArray();
        foreach ($list as $k=>$course){
            if($course['pid']){
                $list[$k]['is_fail'] = $course['is_del'] && $course['is_show'];
            }else{
                unset($list[$k]);
            }
        }
        return $list;
    }

}