<?php
/**
 *
 * @author: 招宝通
 */

namespace app\ebapi\model\store;


use basic\ModelBasic;
use service\UtilService;
use traits\ModelTrait;

class StoreCourseReply extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['add_time'];

    protected function setAddTimeAttr()
    {
        return time();
    }

    protected function setPicsAttr($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    protected function getPicsAttr($value)
    {
        return json_decode($value,true);
    }

    public static function reply($group,$type = 'course')
    {
        $group['reply_type'] = $type;
        return self::set($group);
    }

    public static function courseValidWhere($alias = '')
    {
        $model = new self;
        if($alias){
            $model->alias($alias);
            $alias .= '.';
        }
        return $model->where("{$alias}is_del",0)->where("{$alias}reply_type",'course');
    }

    /*
     * 设置查询产品评论条件
     * @param int $courseId 产品id
     * @param string $order 排序方式
     * @return object
     * */
    public static function setCourseReplyWhere($courseId,$type=0,$alias='A')
    {
        $model = self::courseValidWhere($alias)->where('A.course_id',$courseId)
            ->field('A.course_score,A.service_score,A.comment,A.merchant_reply_content,A.merchant_reply_time,A.pics,A.add_time,B.nickname,B.avatar,C.cart_info,A.merchant_reply_content')
            ->join('__USER__ B','A.uid = B.uid')
            ->join('__STORE_ORDER_CART_INFO__ C','A.unique = C.unique');
        switch ($type){
            case 1:
                $model=$model->where('A.course_score',5);//好评
                break;
            case 2:
                $model=$model->where('A.course_score',['<',5],['>',2]);//中评
                break;
            case 3:
                $model=$model->where('A.course_score','<',2);//差评
                break;
        }
        return $model;
    }

    public static function getCourseReplyList($courseId,$order = 0,$page = 0,$limit = 8)
    {
        $list = self::setCourseReplyWhere($courseId,$order)->page((int)$page,(int)$limit)->select()->toArray()?:[];
        foreach ($list as $k=>$reply){
            $list[$k] = self::tidyCourseReply($reply);
        }
        return $list;
    }

    public static function tidyCourseReply($res)
    {
        $res['cart_info'] = json_decode($res['cart_info'],true)?:[];
        $res['suk'] = isset($res['cart_info']['courseInfo']['attrInfo']) ? $res['cart_info']['courseInfo']['attrInfo']['suk'] : '';
        $res['nickname'] = UtilService::anonymity($res['nickname']);
        $res['merchant_reply_time'] = date('Y-m-d H:i',$res['merchant_reply_time']);
        $res['add_time'] = date('Y-m-d H:i',$res['add_time']);
        $res['star'] = bcadd($res['course_score'],$res['service_score'],2);
        $res['star'] =bcdiv($res['star'],2,0);
        $res['comment'] = $res['comment'] ? :'此用户没有填写评价';
        unset($res['cart_info']);
        return $res;
    }

    public static function isReply($unique,$reply_type = 'course')
    {
        return self::be(['unique'=>$unique,'reply_type'=>$reply_type]);
    }

    public static function getRecCourseReply($courseId)
    {
        $res = self::courseValidWhere('A')->where('A.course_id',$courseId)
            ->field('A.course_score,A.service_score,A.comment,A.merchant_reply_content,A.merchant_reply_time,A.pics,A.add_time,B.nickname,B.avatar,C.cart_info')
            ->join('__USER__ B','A.uid = B.uid')
            ->join('__STORE_ORDER_CART_INFO__ C','A.unique = C.unique')
            ->order('A.add_time DESC,A.course_score DESC, A.service_score DESC, A.add_time DESC')->find();
        if(!$res) return null;
        return self::tidyCourseReply($res->toArray());
    }

    public static function courseReplyCount($courseId)
    {
        $data['sum_count']=self::setCourseReplyWhere($courseId)->count();
        $data['good_count']=self::setCourseReplyWhere($courseId,1)->count();
        $data['in_count']=self::setCourseReplyWhere($courseId,2)->count();
        $data['poor_count']=self::setCourseReplyWhere($courseId,3)->count();
        $data['reply_chance']=bcdiv($data['good_count'],$data['sum_count'],2);
        $data['reply_star']=bcmul($data['reply_chance'],5,0);
        $data['reply_chance']=bcmul($data['reply_chance'],100,2);
        return $data;
    }

}