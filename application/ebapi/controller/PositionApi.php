<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-25
 * Time: 18:04
 */

namespace app\ebapi\controller;

use app\ebapi\model\position\Position;
use service\JsonService;
use service\UtilService;
use think\Request;

class PositionApi extends AuthController
{
    /**
     * 一级分类
     * @return \think\response\Json
     */
    public function get_one(){
        $data = Position::pidBy(0,'id,name');//一级分类
        return JsonService::successful($data);
    }

    /**
     * 二级分类
     * @param Request $request
     * @return \think\response\Json
     */
    public function get_two(Request $request){
        $data = UtilService::getMore([['id',0]],$request);
        $dataCateA = [];
        $dataCateE = Position::pidByList($data['id']);//根据一级分类获取二级分类
        if($dataCateE) $dataCateE = $dataCateE->toArray();
        $dataCate = array_merge_recursive($dataCateA,$dataCateE);
        return JsonService::successful($dataCate);
    }
}