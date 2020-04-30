<?php

namespace app\ebapi\controller;

use app\ebapi\model\news\News AS NewsModel;
use app\ebapi\model\news\NewsCategory;

/**
 * TODO 小程序文章api接口
 * Class ArticleApi
 * @package app\ebapi\controller
 */
class NewsApi extends Basic
{

    /**
     * TODO 获取文章分类
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_article_cate(){
        $cateInfo = NewsCategory::getArticleCategory();
        if($cateInfo) $cateInfo = $cateInfo->toArray();
        else $cateInfo = [];
        array_unshift($cateInfo,['id'=>0,'title'=>'最新']);
        return $this->successful($cateInfo);
    }
    /**
     * TODO 文章列表
     * @param int $cid
     * @param int $first
     * @param int $limit
     */
    public function get_cid_article($cid = 0,$first = 0,$limit = 8){
        $list = NewsModel::cidByArticleList($cid,$first,$limit,"id,title,image,slider_image,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url")?:[];
        foreach ($list as $item=>$value){
            $list[$item]['slider_image'] = json_decode($value['slider_image']);
        }
        return $this->successful($list);
    }

    /**
     * TODO 获取热门文章
     * @return json
     */
    public function get_article_hot()
    {
        $list = NewsModel::getArticleListHot("id,title,image,slider_image,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url");
        foreach ($list as $item=>$value){
            $list[$item]['slider_image'] = json_decode($value['slider_image']);
        }
        return $this->successful($list);
    }

    /**
     * TODO 获取热门banner文章
     * @return json
     */
    public function get_article_banner()
    {
        $list = NewsModel::getArticleListBanner("id,title,image,slider_image,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url");
        foreach ($list as $item=>$value){
            $list[$item]['slider_image'] = json_decode($value['slider_image']);
        }
        return $this->successful($list);
    }

    /**
     * TODO 获取文章详情
     * @param int $id
     * @return json
     */
    public function visit($id = 0)
    {
        $content = NewsModel::getArticleOne($id);
        if(!$content || !$content["is_show"]) return $this->fail('此文章已经不存在!');
        $content["visit"] = $content["visit"] + 1;
        $content["cart_name"] = NewsCategory::getArticleCategoryField($content['cid']);
        $content['add_time'] = date('Y-m-d H:i:s',$content['add_time']);
        $content['slider_image'] = json_decode($content['slider_image']);
        NewsModel::edit(['visit'=>$content["visit"]],$id);//增加浏览次数
        return $this->successful($content);
    }
}