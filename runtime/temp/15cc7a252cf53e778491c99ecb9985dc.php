<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:51:"D:\zbt/application/home/view/layui/login\index.html";i:1576810112;}*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>用户-登录</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <link rel="stylesheet" href="/public/home/layui/layui/css/layui.css">
  <link rel="stylesheet" href="/public/home/layui/static/css/index.css">
</head>
<body>

<div class="layui-fulid" id="house-login">
  <form method="post" action="<?php echo Url('check'); ?>">
  <div class="layui-form">
    <p>手机号登录</p>
    <div class="layui-input-block login">
      <i class="layui-icon layui-icon-house-mobile"></i>
      <input type="text" required lay-verify="required" name="account" placeholder="请输入手机号" class="layui-input">
    </div>
    <div class="layui-input-block login">
      <i class="layui-icon layui-icon-more"></i>
      <input type="password" required lay-verify="required" name="pwd" placeholder="请输入密码" class="layui-input">
    </div>
    <div class="layui-input-block getCode">
      <input type="text" required lay-verify="required" name="verify" placeholder="请输入验证码" class="layui-input">
      <img class="layui-btn" id="verify_img" src="<?php echo Url('captcha'); ?>" alt="验证码" style="">
    </div>
    <button class="layui-btn" lay-submit lay-filter="user-login">登录</button>
  </div>
  </form>
</div>

<script src="/public/home/layui/layui/layui.js"></script>
<!-- 让IE8/9支持媒体查询，从而兼容栅格 -->
<!--[if lt IE 9]>
  <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
  <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->
<script>
  layui.config({
    base: '/public/home/layui/static/js/' 
  }).use('house');
</script>

<script src="/public/static/plug/jquery-1.10.2.min.js"></script>
<script>
  var $captcha = $('#verify_img'),src = $captcha[0].src;
  $captcha.on('click',function(){
    this.src = src+'?'+Date.parse(new Date());
  });
</script>

<style>
  body{
    background:#fff url("/public/home/layui/static/img/load_bg.jpg") no-repeat center center;
  }
</style>
  
</body>
</html>