<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-29
 * Time: 17:57
 */

namespace app\ebapi\model\job;

use basic\ModelBasic;
use traits\ModelTrait;

class JobRelation extends ModelBasic
{
    use  ModelTrait;

    /*
     * 获取某个用户收藏产品
     * @param int uid 用户id
     * @param int $first 行数
     * @param int $limit 展示行数
     * @return array
     * */
    public static function getUserCollectProduct($uid,$page,$limit)
    {
        $list = self::where('A.uid',$uid)
            ->field('B.id pid,B.name,B.skills,B.experience,B.education,B.salary,B.address,B.is_del,B.is_show,B.position,P.name pname')->alias('A')
            ->where('A.type','collect')
            ->order('A.add_time DESC')->join('__JOB_POSITION__ B','A.job_id = B.id')
            ->join('__POSITION__ P','B.position = P.id')
            ->page((int)$page,(int)$limit)->select()->toArray();
        foreach ($list as $k=>$product){
            if($product['pid']){
                $list[$k]['is_fail'] = $product['is_del'] && $product['is_show'];
            }else{
                unset($list[$k]);
            }
        }
        return $list;
    }
}