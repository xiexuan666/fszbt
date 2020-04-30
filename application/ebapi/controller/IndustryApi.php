<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-25
 * Time: 18:47
 */

namespace app\ebapi\controller;

use app\ebapi\model\industry\Industry;
use service\JsonService;
use service\UtilService;
use think\Request;

class IndustryApi extends AuthController
{
    /**
     * 一级分类
     */
    public function get_one(){
        $data = Industry::pidBy(0,'id,name');//一级分类
        return JsonService::successful($data);
    }

    /**
     * 二级分类
     * @param Request $request
     */
    public function get_two(Request $request){
        $data = UtilService::getMore([['id',0]],$request);
        $dataCateA = [];
        $dataCateE = Industry::pidByList($data['id']);//根据一级分类获取二级分类
        if($dataCateE) $dataCateE = $dataCateE->toArray();
        $dataCate = array_merge_recursive($dataCateA,$dataCateE);
        return JsonService::successful($dataCate);
    }
}