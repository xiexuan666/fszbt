<?php

namespace app\ebapi\controller;

use app\ebapi\model\agreement\Agreement AS DataModel;

/**
 * TODO 小程序隐私协议api接口
 * Class ArticleApi
 * @package app\ebapi\controller
 */
class AgreementApi extends Basic
{
    /**
     * TODO 文章列表
     * @param int $first
     * @param int $limit
     */
    public function getList($first = 0,$limit = 8){
        $list = DataModel::getList($first,$limit,"id,title,image,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time")?:[];
        return $this->successful($list);
    }

    /**
     * TODO 获取文章详情
     * @param int $id
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDetails($id = 0)
    {
        $content = DataModel::getOne($id);
        if(!$content || !$content["is_show"]) return $this->fail('此文章已经不存在!');
        $content["visit"] = $content["visit"] + 1;
        $content['add_time'] = date('Y-m-d H:i:s',$content['add_time']);
        DataModel::edit(['visit'=>$content["visit"]],$id);//增加浏览次数
        return $this->successful($content);
    }
}