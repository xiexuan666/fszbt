<?php if (!defined('THINK_PATH')) exit(); /*a:5:{s:48:"D:\zbt/application/home/view/layui/my\index.html";i:1577677340;s:56:"D:\zbt\application\home\view\layui\public\container.html";i:1576804782;s:53:"D:\zbt\application\home\view\layui\public\header.html";i:1578013654;s:54:"D:\zbt\application\home\view\layui\public\my_menu.html";i:1577676092;s:53:"D:\zbt\application\home\view\layui\public\footer.html";i:1577436268;}*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>
<?php \app\core\util\SystemConfigService::get('site_name') ?>
</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="/public/home/layui/layui/css/layui.css">
    <link rel="stylesheet" href="/public/home/layui/static/css/index.css">
</head>
<body>

<!-- nav部分 -->
<link rel="stylesheet" href="/public/home/layui/news/static/css/index.css">
<div class="nav">
    <div class="layui-container">
        <!-- 公司logo -->
        <div class="nav-logo">
            <a href="/home/index/index">
                <img src="<?php echo $siteData['routine_logo']; ?>" alt="<?php echo $siteData['site_name']; ?>" style="max-height: 40px;">
            </a>
        </div>
        <div class="nav-list">
            <button>
                <span></span><span></span><span></span>
            </button>
            <ul class="layui-nav" lay-filter="">
                <li class="layui-nav-item"><a href="/home/index/index">首页</a></li>
                <li class="layui-nav-item"><a href="/home/news/lists">动态</a></li>
                <li class="layui-nav-item layui-this"><a href="/home/my/index">用户中心</a></li>
                <li class="layui-nav-item"><a href="/home/login/logout">退出登录</a></li>
            </ul>
        </div>
    </div>
</div>




<div class="layui-container userpublic house-userPer">
  <div class="layui-row layui-col-space20">
    <p class="layui-hide-xs title">个人中心 <i class="layui-icon layui-icon-right"></i> <span>用户面板</span></p>
    <div class="layui-col-sm2">
    <ul class="layui-nav layui-nav-tree layui-inline" lay-filter="user" style="width: 180px;">

        <?php if(is_array($my_menus) || $my_menus instanceof \think\Collection || $my_menus instanceof \think\Paginator): $index = 0; $__LIST__ = $my_menus;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): $mod = ($index % 2 );++$index;if($item['isLevel'] == true){ ?>
            <li class="layui-nav-item <?php if($get_key == $index){ echo 'layui-this'; } ?>">
                <a href="<?php echo $item['url_pc']; ?>?get_key=<?php echo $index; ?>">
                    <i class="layui-icon <?php echo $item['img_pc']; ?>"></i> <?php echo $item['name']; ?>
                </a>
            </li>
        <?php } endforeach; endif; else: echo "" ;endif; ?>


        <!--<li class="layui-nav-item <?php if($controller == 'My' && $action == 'index'){ echo 'layui-this'; } ?>">
            <a href="/home/my/index">
                <i class="layui-icon layui-icon-username"></i> 个人中心</a></li>
        <li class="layui-nav-item <?php if($controller == 'Resume' && $action == 'admin'){ echo 'layui-this'; } ?>">
            <a href="/home/resume/admin">
                <i class="layui-icon layui-icon-note"></i> 简历管理</a></li>
        <li class="layui-nav-item <?php if($controller == 'Job' && $action == 'admin'){ echo 'layui-this'; } ?>">
            <a href="/home/job/admin">
                <i class="layui-icon layui-icon-user"></i> 招聘管理</a></li>
        <li class="layui-nav-item <?php if($controller == 'Company' && $action == 'admin'){ echo 'layui-this'; } ?>">
            <a href="/home/company/admin">
                <i class="layui-icon layui-icon-template-1"></i> 企业管理</a>
        </li>
        <li class="layui-nav-item <?php if($controller == 'Brand' && $action == 'admin'){ echo 'layui-this'; } ?>">
            <a href="/home/brand/admin">
                <i class="layui-icon layui-icon-template"></i> 品牌商管理</a></li>
        <li class="layui-nav-item <?php if($controller == 'Dealers' && $action == 'admin'){ echo 'layui-this'; } ?>">
            <a href="/home/dealers/admin">
                <i class="layui-icon layui-icon-component"></i> 经销商管理</a>
        </li>
        <li class="layui-nav-item <?php if($controller == 'Supplier' && $action == 'admin'){ echo 'layui-this'; } ?>">
            <a href="/home/supplier/admin">
                <i class="layui-icon layui-icon-link"></i> 供应商管理</a>
        </li>
        <li class="layui-nav-item <?php if($controller == 'Supply' && $action == 'admin'){ echo 'layui-this'; } ?>">
            <a href="/home/supply/admin">
                <i class="layui-icon layui-icon-tabs"></i> 招商管理</a>
        </li>
        <li class="layui-nav-item <?php if($controller == 'GoodsNew' && $action == 'admin'){ echo 'layui-this'; } ?>">
            <a href="/home/goods_new/admin">
                <i class="layui-icon layui-icon-form"></i> 新品管理</a>
        </li>
        <li class="layui-nav-item <?php if($controller == 'Goods' && $action == 'admin'){ echo 'layui-this'; } ?>">
            <a href="/home/goods/admin">
                <i class="layui-icon layui-icon-release"></i> 产品管理</a>
        </li>
        <li class="layui-nav-item <?php if($controller == 'My' && $action == 'article'){ echo 'layui-this'; } ?>">
            <a href="/home/my/article">
                <i class="layui-icon layui-icon-reply-fill"></i> 资讯管理</a>
        </li>
        <li class="layui-nav-item <?php if($controller == 'My' && $action == 'knowledge'){ echo 'layui-this'; } ?>">
            <a href="/home/my/knowledge">
                <i class="layui-icon layui-icon-chart-screen"></i> 干货管理</a>
        </li>-->
        <span class="layui-nav-bar" style="top: 257.5px; height: 0px; opacity: 0;"></span>
    </ul>


    <!--<ul class="user-list">
        <li class="<?php if($controller == 'My' && $action == 'index'){ echo 'active'; } ?>"><a href="/home/my/index.html">个人中心</a></li>
        <li class="<?php if($controller == 'Resume' && $action == 'admin'){ echo 'active'; } ?>"><a href="/home/resume/admin.html">简历管理</a></li>
        <li class="<?php if($controller == 'Job' && $action == 'admin'){ echo 'active'; } ?>"><a href="/home/job/admin.html">招聘管理</a></li>
        <li class="<?php if($controller == 'Company' && $action == 'admin'){ echo 'active'; } ?>"><a href="/home/company/admin.html">企业管理</a></li>
        <li class="<?php if($controller == 'Brand' && $action == 'admin'){ echo 'active'; } ?>"><a href="/home/brand/admin.html">品牌商管理</a></li>
        <li class="<?php if($controller == 'Dealers' && $action == 'admin'){ echo 'active'; } ?>"><a href="/home/dealers/admin.html">经销商管理</a></li
        <li class="<?php if($controller == 'Supplier' && $action == 'admin'){ echo 'active'; } ?>"><a href="/home/supplier/admin.html">供应商管理</a></li>
        <li class="<?php if($controller == 'Supply' && $action == 'admin'){ echo 'active'; } ?>"><a href="/home/supply/admin.html">招商管理</a></li>
        <li class="<?php if($controller == 'GoodsNew' && $action == 'admin'){ echo 'active'; } ?>"><a href="/home/goods_new/admin.html">新品管理</a></li>
        <li class="<?php if($controller == 'Goods' && $action == 'admin'){ echo 'active'; } ?>"><a href="/home/goods/admin.html">产品管理</a></li>
        <li class="<?php if($controller == 'My' && $action == 'article'){ echo 'active'; } ?>"><a href="/home/my/article.html">资讯管理</a></li>
        <li class="<?php if($controller == 'My' && $action == 'knowledge'){ echo 'active'; } ?>"><a href="/home/my/knowledge.html">干货管理</a></li>
        <li class="<?php if($controller == 'My' && $action == 'subion'){ echo 'active'; } ?>"><a href="/home/my/subion.html">我的订阅</a></li>
        <li class="<?php if($controller == 'My' && $action == 'collect'){ echo 'active'; } ?>"><a href="/home/my/collect.html">我的收藏</a></li>
        <li class="<?php if($controller == 'My' && $action == 'address'){ echo 'active'; } ?>"><a href="/home/my/address.html">地址管理</a></li>
    </ul>-->
</div>
    <div class="layui-col-sm10">
      <div class="user-person">
        <div class="person">
          <img src="<?php echo $userInfo['avatar']; ?>">
          <div class="name">
            <p><?php echo $userInfo['nickname']; ?></p>
            <span><i><?php echo $userInfo['vip_name']; ?></i></span>
          </div>
        </div>
        <ul>
          <li>余额<span><?php echo $userInfo['now_money']; ?></span></li>
          <li>佣金<span><?php echo $userInfo['brokerage']; ?></span></li>
          <li>积分<span><?php echo $userInfo['integral']; ?></span></li>
          <li>订单<span><?php echo $userInfo['orderNum']; ?></span></li>
          <li>订阅<span><?php echo $userInfo['subionNum']; ?></span></li>
          <li>收藏<span><?php echo $userInfo['relationNum']; ?></span></li>
        </ul>
      </div> 
    </div>
    <!--<div class="layui-col-sm10">
      <div class="layui-tab layui-tab-brief">
        <ul class="layui-tab-title">
          <li class="layui-this">全部订单</li>
          <li>等待发货</li>
          <li>已发货</li>
          <li>交易完成</li>
        </ul>
        <div class="layui-tab-content">
          <div class="layui-tab-item layui-show">
            <table id="house-user-order" lay-filter="house-user-order"></table>
          </div>
          <div class="layui-tab-item">

          </div>
        </div>
      </div>
    </div>-->
  </div>
</div>

<script type="text/html" id="orderTpl">
  <div style="text-align: left; display: inline-block; vertical-align: middle;">
    <p>订单号：{{d.order_id}}</p>
    <p>交易时间：{{d.add_time}}</p>
  </div>
</script>
<script type="text/html" id="imgTpl">
  <img src="{{d.cart_info.productInfo.image}}">
</script>

<script type="text/html" id="numberTpl">
  {{d.total_num}}
</script>

<script type="text/html" id="priceTpl">
  <div style="display: inline-block; vertical-align: middle;">
    <p>￥{{d.total_price}}</p>
    <p>免运费</p>
  </div>
</script>
<script type="text/html" id="stateTpl">
  {{#  if(d.status == 3){ }}
    <span style="color: #999;">已完成</span>
  {{#  } else if(d.status == 2){ }}
    <span style="color: #ee715f;">待收货</span>
  {{#  } else if(d.status == 1){ }}
    <span style="color: #73c292;">已发货</span>
  {{#  } else { }}
    {{#  if(d.paid == 1){ }}
    <span style="color: #e09b4e;">待发货</span>
    {{#  } else { }}
    <span style="color: #e09b4e;">未支付</span>
    {{#  } }}
  {{#  } }}
</script>
<script type="text/html" id="handleTpl">
  <div style="display: inline-block; vertical-align: middle;">
    {{#  if(d.paid == 1){ }}
    <a class="layui-btn layui-btn-xs" lay-event="evaluate"><i class="layui-icon layui-icon-ok"></i>确认收货</a>
    {{#  } }}
    <a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="check"><i class="layui-icon layui-icon-close"></i>取消订单</a>
  </div>
</script>

<script src="/public/home/layui/layui/layui.js"></script>
<!-- 让IE8/9支持媒体查询，从而兼容栅格 -->
<!--[if lt IE 9]>
<script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
<script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->

<script>
  /**
   @Name: 个人中心资讯管理
   @Author: wshbin
   @Copyright: wshbin
   */
  layui.define(['element', 'carousel', 'table', 'util'], function(exports){
    var $ = layui.$
            ,form = layui.form
            ,util = layui.util
            ,table = layui.table;

    //TODO 请求资讯列表
    table.render({
      elem: '#house-user-order'
      ,url:  '/home/my/get_order_list'
      ,skin: 'line'
      ,cols: [[
        {title:'订单信息', align:'center', templet: '#orderTpl'}
        ,{field:'avatar', title:'订购商品', templet: '#imgTpl', align:'center'}
        ,{field:'number', title:'件数', templet: '#numberTpl',align:'center', width:80}
        ,{title:'价格', align:'center', templet: '#priceTpl', width:100}
        ,{title:'订单状态', align:'center', templet: '#stateTpl', width:100}
        ,{title:'订单操作', align:'center', templet: '#handleTpl'}
      ]]
    });

    //TODO 监听事件 编辑 删除
    table.on('tool(house-user-order)', function(obj){
      var data = obj.data;
      if(obj.event === 'evaluate'){
        var url = '/home/my/user_take_order';
        $.getJSON(url, data, function(res){
          if(res.code == 200){
            layer.msg(res.msg,{
              time: 1000,
              end: function () {
                obj.del();
                layer.close(index);
              }
            })
          } else {
            alert(res.msg);
            return false;
          }
        });
      } else if(obj.event === 'check') {
        layer.confirm('确定取消？', function(index){
          var url = '/home/my/user_remove_order';
          $.getJSON(url, data, function(res){
            if(res.code == 200){
              layer.msg(res.msg,{
                time: 1000,
                end: function () {
                  obj.del();
                  layer.close(index);
                }
              })
            } else {
              alert(res.msg);
              return false;
            }
          });
        });
      }
    });

    //TODO 监听多选
    $(".house-usershop").find("#batchDel").on('click', function(){
      var checkStatus = table.checkStatus('house-usershop-table')
              ,checkData = checkStatus.data;
      if(checkData.length === 0){
        layer.msg('请选择数据');
      }else{
        //执行 Ajax 操作之后再重载
        table.reload('house-usershop-table');
        $(".house-usershop-table-num").children("input")[0].checked = false;
        form.render('checkbox');
        $(".house-usershop-table-num").children(".numal").html('已选 0 件')
        copyWith[0].innerHTML = goodsVal[0].innerHTML = '￥0.00';
        copyTips.css("display", "none");
        layer.msg('已删除');
      }
    });


    //固定 bar
    util.fixbar({
      click: function(type){
        if(type === 'bar1'){
          //
        }
      }
    });

    exports('house', {});
  })
</script>


<div class="footer">
    <div class="layui-container">
        <div class="layui-row footer-contact" style="text-align: center;">
            <span class="right"><a href=""><?php echo $siteData['site_name']; ?></a>版权所有</span><span class="right">&nbsp;©&nbsp;2020-2030</span>
        </div>
    </div>
</div>

</body>
</html>
