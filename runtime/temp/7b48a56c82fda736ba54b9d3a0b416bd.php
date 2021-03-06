<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:51:"D:\zbt/application/home/view/layui/index\index.html";i:1588645722;}*/ ?>
﻿<!DOCTYPE html>
<html lang="zh-cn">

<head>
    <meta charset="UTF-8" />
    <title>招宝通</title>
    <meta name="keywords" content="招宝通" />
    <meta name="description" content="招宝通" />
    <meta name="viewport"
        content="width=device-width,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no" />
    <meta name="copyright" content="Copyright www.tanmzh.com 版权所有" />
    <link href="/public/home/layui/index/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/">
    <link href="/public/home/layui/index/css/index.min.css" rel="stylesheet" />
    <!-- <link href="/public/home/css/index.css" rel="stylesheet" /> -->
    <!--[if lt IE 9]>
    <script src="/public/home/layui/index/js/html5shiv.min.js"></script>
    <![endif]-->
    <script src="/public/home/layui/index/js/jquery.min.js"></script>
    <script src="/public/home/layui/index/js/index.min.js"></script>
    <script src="/public/home/layui/index/js/index.js"></script>
</head>

<body>
    <header>
        <div class="logo">
            <img src="<?php echo $siteData['routine_logo']; ?>" alt="<?php echo $siteData['site_name']; ?>" class="img-responsive" />
        </div>
        <nav class="menu">
            <ul class="list-inline">
                <li class="active"><a>首页</a></li>
                <li><a>动态</a></li>
                <!-- <li><a>合作</a></li> -->
                <!-- <li><a>服务</a></li> -->
                <li><a>关于</a></li>
                <!-- <li><a>联系</a></li> -->
                <!--<li><a>业务</a></li>-->
                <!--<li><a>增值</a></li>-->
                <li><a href="/application/home/view/layui/templates/login.html">用户中心</a></li>
            </ul>
        </nav>
        <!--<div class="hotline">
        <a href="tel:13760929390" title="信息服务免费咨询热线"><span>13760929390</span></a><u></u>
    </div>-->
        <div class="menu-icon">
            <a href="tel:13760929390" title="点击直拨信息服务热线"><span class="glyphicon glyphicon-earphone"></span></a>
            <span class="glyphicon glyphicon-th-large"></span>
        </div>
    </header>

    <div class="welcome">
        <p><u>Loading . . .</u></p>

    </div>

    <section class="video">
        <div class="swiper-container">
            <div class="swiper-wrapper">
                <div class="swiper-slide nth1">
                    <div class="box">
                        <div class="left"></div>
                        <div class="right">
                            <span>年专注，信息服务</span><i></i>
                            <p>始于 2013 - 2020 展望未来<br />专注于家居行业，专业于解决方案</p>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide nth2">
                    <div class="box">
                        <span>设计控，也醉了</span><i></i>
                        <p>不是非要高大上，只是醉心于设计<br />我们想，再上一个好案例</p>
                    </div>
                </div>
                <div class="swiper-slide nth3">
                    <div class="box">
                        <div class="top">技术派，论研发</div>
                        <div class="mid"></div>
                        <div class="bottom">我说，业界没有最好的技术，只有最棒的开发者<br />找家具，找服务商因有尽有</div>
                    </div>
                </div>
                <div class="swiper-slide nth4">
                    <div class="box">
                        <div class="top"><span>先入为主，布局未来</span><i></i></div>
                        <div class="bottom">全面布局<u>找家具</u>找人才<u>找服务</u>找采购<br />抢占头等商机<br />基于<u>家居</u>360°全方位服务</div>
                    </div>
                </div>
                <div class="swiper-slide nth5">
                    <!-- <div class="box">
                        <div class="top"><span>先入为主，布局未来</span><i></i></div>
                        <div class="bottom">全面布局<u>找家具</u>找人才<u>找服务</u>找采购<br />抢占头等商机<br />基于<u>家居</u>360°全方位服务</div>
                    </div> -->
                </div>
            </div>
        </div>
        <div class="innerBox">
            <div class="news">
                <span>NEWS :</span>
                <a href="/home/news/lists" title="更多文章动态" class="more" target="_blank">more</a>
                <ul>
                    <?php if(is_array($news) || $news instanceof \think\Collection || $news instanceof \think\Paginator): $i = 0; $__LIST__ = $news;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): $mod = ($i % 2 );++$i;?>
                    <li><a href="/home/news/show?id=<?php echo $item['id']; ?>" target="_blank" title="<?php echo $item['title']; ?>"><?php echo $item['title']; ?></a>
                    </li>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                </ul>
            </div>
            <!-- <div class="guide"></div>
            <a class="movedown"></a> -->
        </div>
    </section>

    <section class="cases">
        <div class="box">
            <div class="caption">
                <i></i><span>企业动态</span>
                <br class="clear" />
            </div>
            <div class="swiper-container items">
                <div class="swiper-wrapper">
                    <?php if(is_array($news) || $news instanceof \think\Collection || $news instanceof \think\Paginator): $index = 0; $__LIST__ = $news;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): $mod = ($index % 2 );++$index;if($index <= 3): ?> <div class="swiper-slide">
                        <a href="/home/news/show?id=<?php echo $item['id']; ?>" target="_blank">
                            <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['title']; ?>" />
                            <p><?php echo $item['ctitle']; ?><?php echo $index; ?><br />
                                <strong><?php echo $item['title']; ?></strong><br />
                                <?php echo $item['add_time']; ?>
                            </p>
                        </a>
                </div>
                <?php endif; endforeach; endif; else: echo "" ;endif; ?>
            </div>
        </div>
        <a href="/home/news/lists" title="查看更多动态" class="more" target="_blank">MORE</a>
        </div>
    </section>

    <!-- <section class="clients"> -->
    <!-- <div class="box">
            <div class="caption">
                <i></i><span>他们与招宝通长期合作</span>
                <br class="clear" />
            </div>
            <ul class="items list-inline">
                <li class="cctv"><span>CCTV影响力视频</span></li>
                <li class="unicom"><span>中国联通电信卡</span></li>
                <li class="tsinghua"><span>清华大学国际预科学院</span></li>
                <li class="cas"><span>中科院力学研究所</span></li>
                <li class="sipo"><span>国家知识产权局</span></li>
                <li class="apple"><span>中航苹果官方</span></li>
                <li class="das"><span>一汽大众汽车门户</span></li>
                <li class="hunantv"><span>湖南卫视全媒体</span></li>
                <li class="sino"><span>中环球船务官方</span></li>
                <li class="report"><span>中国报道信息门户</span></li>
                <li class="gedu"><span>环球雅思教育门户</span></li>
                <li class="bgg"><span>京粮集团</span></li>
                <li class="bsec"><span>北赛电工官方</span></li>
                <li class="huadan"><span>华丹乳业官方</span></li>
                <li class="zd"><span>中东集团</span></li>
            </ul>
        </div> -->
    <!-- </section> -->
    <section style="display: none;" class="quality">
        <div class="box">
            <div class="caption">
                <i></i><span>不同媒介，同样精彩</span>
                <br class="clear" />
            </div>
            <div class="swiper-container items">
                <div class="swiper-wrapper">
                    <div class="swiper-slide nth1">
                        <ul class="list-inline">
                            <li class="mobi"><span>招宝通</span></li>
                            <li class="pad"><span>信息交互</span></li>
                            <li class="pc"><span>服务平台</span></li>
                        </ul>
                        <p>服务一<br />服务一<br />服务一<br />服务一</p>
                    </div>
                    <div class="swiper-slide nth2">
                        <ul class="list-inline">
                            <li class="ie"><span>招宝通</span></li>
                            <li class="chrome"><span>信息交互</span></li>
                            <li class="firefox"><span>服务平台</span></li>
                        </ul>
                        <p>服务二<br />服务二<br />服务二<br />服务二</p>
                    </div>
                    <div class="swiper-slide nth3">
                        <ul class="list-inline">
                            <li class="windows"><span>招宝通</span></li>
                            <li class="ios"><span>信息交互</span></li>
                            <li class="andriod"><span>服务平台</span></li>
                        </ul>
                        <p>服务三<br />服务三<br />服务三<br />服务三</p>
                    </div>
                </div>
            </div>
            <a href="/home/my/index.html" class="lookall">去用户中心管理您的数据</a>
        </div>
    </section>



    <section class="aboutus">
        <ul class="menu">
            <li>思想</li>
            <li>关于</li>
            <li>荣誉</li>
        </ul>
        <div class="swiper-container items">
            <div class="swiper-wrapper">
                <div class="swiper-slide nth1">
                    <strong>厚积薄发</strong>
                    <p>登上峰顶，不是为了饱览风光，是为了寻找更高的山峰<br />日出东方，告别了昨天的荣耀，将光芒照向更远的地方<br />一路上，我们更在意如何积累和沉淀</p>
                    <u>下一秒，让你看，我们到底有多强</u>
                </div>
                <div class="swiper-slide nth2">
                    <strong>招宝通</strong>
                    <p>从2019年到至今，坐落于广东·佛山顺德，是一家专为本土企业打造信息交互综合服务平台。
                        <p>我们始终坚持以客户需求为导向，为追求用户体验设计，提供有针对性的项目解决方案，招宝通将不断地超越自我，挑战险峰！</p>
                </div>
                <div class="swiper-slide nth3">
                    <strong>只有你们想不到的，没有我们做不到的</strong>
                    <ul>
                        <li>2019年<u>-</u>我们用行动去证明</li>
                        <li>2020年<u>-</u>未来的路还很遥远</li>
                        <li>2021年<u>-</u>只要您给我一个见面的机会，绝不辜负您的期望</li>
                        <li>2022年<u>-</u>……</li>
                        <li>2023年<u>-</u>……</li>
                    </ul>
                </div>
            </div>
        </div>
        <table class="exp">
            <tr>
                <td><u>……</u>信息交互服务平台</td>
                <td><u>……</u>身边看得见的案例</td>
                <td><u>……</u>我们用行动去证明</td>
                <td><u>……</u>未来的路还很遥远</td>
                <td><u>……</u>绝不辜负您的期望</td>
            </tr>
        </table>
    </section>

    <section class="contact">
        <div class="box">
            <div class="above">


                <img src="/application/home/view/layui/templates/static/images/QRcode.png" alt="扫描关注招宝通微信小程序" />
                <img src="/application/home/view/layui/templates/static/images/GZH.png" alt="扫描关注招宝通微信公众号" />

                <div class="photograph">
                    <p>客服电话<br />0757-23387517<br />工作时间：周一至周六 早上8:30-下午5:30</p>
                </div>


                <div class="left">
                    <a href="tel:13760929390" title="信息服务咨询热线" class="tel"></a>
                    <p> Email：wshbin512@163.com<br/>域名：https://www.zbtmini.com<br/><?php echo $siteData['site_name']; ?>版权所有<br />
                    </p>
                    <p>广东<u></u>顺德 <u>联系电话：13760929390</u><br />地址：佛山市顺德区龙江镇仙塘村委会沙龙路11号友邦中心二楼A室
                        <br />邮编：528303<a href="" target="_blank" class="job">[ 工作机会 ]</a></p>

                </div>
                <!-- <div class="right">

                </div> -->
            </div>
        </div>
    </section>

    <section style="display: none;" class="business">
        <div class="box">
            <div class="caption">
                <i></i><span>我们能做什么</span>
                <br class="clear" />
            </div>
            <ul class="items list-inline">
                <li class="pc">
                    <i></i><strong>找家具</strong>
                    <p>家具个性定制<br />家具超低优惠购</p>
                </li>
                <li class="mobi">
                    <i></i><strong>找人才</strong>
                    <p>专注家具行业<br />对口人才招聘汇</p>
                </li>
                <li class="sys">
                    <i></i><strong>找服务商</strong>
                    <p>牵线搭桥<br />为的是您中意的服务伙伴</p>
                </li>
                <li class="app">
                    <i></i><strong>找供应</strong>
                    <p>百家供应<br />任您挑选</p>
                </li>
                <li class="host">
                    <i></i><strong>找咨询</strong>
                    <p>别人没有的，我们有<br />我们只做专业的</p>
                </li>
            </ul>
        </div>
    </section>

    <section style="display: none;" class="marketing">
        <div class="box">
            <div class="caption">
                <i></i><span>整合营销，抢占商机</span>
                <br class="clear" />
            </div>
            <ul class="items list-inline">
                <li class="se">
                    <i></i><strong>找家具</strong>
                    <p>全国上下<br />数一数二家具在龙江</p>
                </li>
                <li class="weixin">
                    <i></i><strong>找人才</strong>
                    <p>对口人才<br />永不落幕的招聘现场</p>
                </li>
                <li class="weibo">
                    <i></i><strong>找资讯</strong>
                    <p>信息对接<br />只专注于家具的资讯</p>
                </li>
                <li class="sms">
                    <i></i><strong>找供应</strong>
                    <p>家具供应<br />我汇集了所有供应商</p>
                </li>
                <li class="pay">
                    <i></i><strong>找知识</strong>
                    <p>专业对接<br />招宝通信息服务平台</p>
                </li>
                <li class="bbs">
                    <i></i><strong>找新品</strong>
                    <p>最新最全<br />招宝通第一时间汇集</p>
                </li>
            </ul>
        </div>
    </section>



    <div class="dock">
        <ul class="icons">
            <li class="up"><i></i></li>
            <li class="im">
                <i></i>
                <p><img src="<?php echo $siteData['site_mini_code']; ?>" alt="扫描关注招宝通微信小程序" /></p>
            </li>
            <li class="wechat">
                <i></i>
                <p><img src="<?php echo $siteData['site_public_code']; ?>" alt="扫描关注招宝通微信公众号" /></p>
            </li>
            <li class="tel">
                <i></i>
                <p>客服电话<br /><?php echo $siteData['site_service_phone']; ?><br />工作时间：周一至周六 早上8:30-下午5:30</p>
            </li>
            <li class="down"><i></i></li>
        </ul>
        <a class="switch"></a>
    </div>

    <script>

        var _hmt = _hmt || [];
        (function () {
            var hm = document.createElement("script");
            hm.src = "//hm.baidu.com/hm.js?a821a161aa4214f5ff5b8ca372960ebb";
            var s = document.getElementsByTagName("script")[0];
            s.parentNode.insertBefore(hm, s);
        })();



    </script>
</body>

</html>