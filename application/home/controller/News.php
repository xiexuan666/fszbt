<?php

namespace app\home\controller;

use app\ebapi\model\news\News AS NewsModel;
use app\ebapi\model\news\NewsCategory;
use think\Db;

/**
 * 文章分类控制器
 * Class Article
 * @package app\wap\controller
 */
class News extends WapBasic {

    public function index($cid = ''){
        $title = '新闻列表';
        if($cid){
            $cateInfo = NewsCategory::where('status',1)->where('is_del',0)->where('id',$cid)->find()->toArray();
            if(!$cateInfo) return $this->failed('文章分类不存在!');
            $title = $cateInfo['title'];
        }
        $this->assign(compact('title','cid'));
       return $this->fetch();
    }

    public function video_school()
    {
        return $this->fetch();
    }

    public function guide()
    {
        return $this->fetch();
    }

    public function visit($id = '')
    {
        $content = NewsModel::where('status',1)->where('hide',0)->where('id',$id)->find();
        if(!$content || !$content["status"]) return $this->failed('此文章已经不存在!');
        $content["content"] = Db::name('articleContent')->where('nid',$content["id"])->value('content');
        //增加浏览次数
        $content["visit"] = $content["visit"] + 1;
        NewsModel::where('id',$id)->update(["visit"=>$content["visit"]]);
        $this->assign(compact('content'));
        return $this->fetch();
    }

    public function lists()
    {
        $list = NewsModel::where('status',1)->select();
        if(!$list) {
            return $this->failed('动态暂无文章可读!');
        } else {
            $list = $list->toArray();
        }

        $this->assign(compact('list'));
        return $this->fetch();
    }

    public function show($id = '')
    {
        $data = NewsModel::where('status',1)->where('hide',0)->where('id',$id)->find();
        if(!$data || !$data["status"]) return $this->failed('此文章已经不存在!');
        //增加浏览次数
        $data["visit"] = $data["visit"] + 1;
        NewsModel::where('id',$id)->update(["visit"=>$data["visit"]]);

        $this->assign(compact('data'));
        return $this->fetch();
    }
}