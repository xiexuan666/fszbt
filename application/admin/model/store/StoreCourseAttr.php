<?php
/**
 *
 * @author: 招宝通
 */

namespace app\admin\model\store;


use basic\ModelBasic;
use traits\ModelTrait;

class StoreCourseAttr extends ModelBasic
{
    use ModelTrait;

    protected function setAttrValuesAttr($value)
    {
        return is_array($value) ? implode(',',$value) : $value;
    }

    protected function getAttrValuesAttr($value)
    {
        return explode(',',$value);
    }


    public static function createCourseAttr($attrList,$valueList,$courseId)
    {
        $result = ['attr'=>$attrList,'value'=>$valueList];
        $attrValueList = [];
        $attrNameList = [];
        foreach ($attrList as $index=>$attr){
            if(!isset($attr['value'])) return self::setErrorInfo('请输入规则名称!');
            $attr['value'] = trim($attr['value']);
            if(!isset($attr['value'])) return self::setErrorInfo('请输入规则名称!!');
            if(!isset($attr['detail']) || !count($attr['detail'])) return self::setErrorInfo('请输入属性名称!');
            foreach ($attr['detail'] as $k=>$attrValue){
                $attrValue = trim($attrValue);
                if(empty($attrValue)) return self::setErrorInfo('请输入正确的属性');
                $attr['detail'][$k] = $attrValue;
                $attrValueList[] = $attrValue;
                $attr['detail'][$k] = $attrValue;
            }
            $attrNameList[] = $attr['value'];
            $attrList[$index] = $attr;
        }
        $attrCount = count($attrList);
        foreach ($valueList as $index=>$value){
            if(!isset($value['detail']) || count($value['detail']) != $attrCount) return self::setErrorInfo('请填写正确的课程信息');
            if(!isset($value['price']) || !is_numeric($value['price']) || floatval($value['price']) != $value['price'])
                return self::setErrorInfo('请填写正确的课程价格');
            if(!isset($value['sales']) || !is_numeric($value['sales']) || intval($value['sales']) != $value['sales'])
                return self::setErrorInfo('请填写正确的人数区间');
            if(!isset($value['since']) || !is_numeric($value['since']) || floatval($value['since']) != $value['since'])
                return self::setErrorInfo('请填写正确的人数区间');
            if(!isset($value['pic']) || empty($value['pic']))
                return self::setErrorInfo('请上传商品图片');
            foreach ($value['detail'] as $attrName=>$attrValue){
                $attrName = trim($attrName);
                $attrValue = trim($attrValue);
                if(!in_array($attrName,$attrNameList,true)) return self::setErrorInfo($attrName.'规则不存在');
                if(!in_array($attrValue,$attrValueList,true)) return self::setErrorInfo($attrName.'属性不存在');
                if(empty($attrName)) return self::setErrorInfo('请输入正确的属性');
                $value['detail'][$attrName] = $attrValue;
            }
            $valueList[$index] = $value;
        }
        $attrGroup = [];
        $valueGroup = [];
        foreach ($attrList as $k=>$value){
            $attrGroup[] = [
                'course_id'=>$courseId,
                'attr_name'=>$value['value'],
                'attr_values'=>$value['detail']
            ];
        }
        foreach ($valueList as $k=>$value){
            sort($value['detail'],SORT_STRING);
            $suk = implode(',',$value['detail']);
            $valueGroup[$suk] = [
                'course_id'=>$courseId,
                'suk'=>$suk,
                'price'=>$value['price'],
                'since'=>$value['since'],
                'stock'=>$value['sales'],
                'image'=>$value['pic']
            ];
        }
        if(!count($attrGroup) || !count($valueGroup)) return self::setErrorInfo('请设置至少一个属性!');
        $attrModel = new self;
        $attrValueModel = new StoreCourseAttrValue;
        self::beginTrans();
        if(!self::clearCourseAttr($courseId)) return false;
        $res = false !== $attrModel->saveAll($attrGroup)
            && false !== $attrValueModel->saveAll($valueGroup)
        && false !== StoreCourseAttrResult::setResult($result,$courseId);
        self::checkTrans($res);
        if($res)
            return true;
        else
            return self::setErrorInfo('编辑课程属性失败!');
    }

    public static function clearCourseAttr($courseId)
    {
        if (empty($courseId) && $courseId != 0) return self::setErrorInfo('课程不存在!');
        $res = false !== self::where('course_id',$courseId)->delete()
            && false !== StoreCourseAttrValue::clearCourseAttrValue($courseId);
        if(!$res)
            return self::setErrorInfo('编辑属性失败,清除旧属性失败!');
        else
            return true;
    }

}