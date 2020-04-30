<?php
/**
 *
 * @author: 招宝通
 */

namespace app\home\controller;


use app\home\model\user\User;
use app\home\model\user\WechatUser;
use app\core\util\GroupDataService;
use service\UtilService;
use think\Cookie;
use think\Request;
use think\Session;
use think\Url;

class Login extends WapBasic
{
    public function index($ref = '')
    {
        Cookie::set('is_bg',1);
        $ref && $ref=htmlspecialchars_decode(base64_decode($ref));
        if(UtilService::isWechatBrowser()){
            $this->_logout();
            $openid = $this->oauth();
            Cookie::delete('_oen');
            exit($this->redirect(empty($ref) ? Url::build('Index/index') : $ref));
        }
        $banner = GroupDataService::getData('store_home_banner');

        $this->assign('ref',$ref);
        $this->assign('banner',array($banner[0]));

        return $this->fetch();
    }

    public function check(Request $request)
    {
        list($account,$pwd,$ref,$verify) = UtilService::postMore(['account','pwd','ref','verify'],$request,true);
        if(!$account || !$pwd) return $this->failed('请输入登录账号');
        if(!$pwd) return $this->failed('请输入登录密码');
        //检验验证码
        if(!captcha_check($verify)) return $this->failed('验证码错误，请重新输入');

        if(preg_match("/^1[345678]{1}\d{9}$/",$account)){
            if(!User::be(['phone'=>$account])) return $this->failed('手机号码不存在!');
            $userInfo = User::where('phone',$account)->find();
        }else{
            if(!User::be(['account'=>$account])) return $this->failed('登录账号不存在!');
            $userInfo = User::where('account',$account)->find();
        }

        $errorInfo = Session::get('login_error_info','wap')?:['num'=>0];
        $now = time();
        if($errorInfo['num'] > 5 && $errorInfo['time'] < ($now - 900))
            return $this->failed('错误次数过多,请稍候再试!');
        if($userInfo['pwd'] != md5($pwd)){
            Session::set('login_error_info',['num'=>$errorInfo['num']+1,'time'=>$now],'wap');
            return $this->failed('账号或密码输入错误!');
        }
        if(!$userInfo['status']) return $this->failed('账号已被锁定,无法登陆!');
        $this->_logout();
        Session::set('loginUid',$userInfo['uid'],'wap');
        $userInfo['last_time'] = time();
        $userInfo['last_ip'] = $request->ip();
        $userInfo->save();
        Session::delete('login_error_info','wap');
        Cookie::set('is_login',1);

        //return json_encode(array('status'=>1,'msg'=>'登录成功','url'=>empty($ref) ? Url::build('Index/index') : $ref));
        return $this->successful('登录成功',empty($ref) ? Url::build('My/index') : $ref);
        exit($this->redirect(empty($ref) ? Url::build('My/index') : $ref));
    }

    public function reg(){
        return $this->fetch();
    }

    public function captcha()
    {
        ob_clean();
        $captcha = new \think\captcha\Captcha([
            'codeSet'=>'0123456789',
            'length'=>4,
            'fontSize'=>30
        ]);
        return $captcha->entry();
    }

    public function logout()
    {
        $this->_logout();
        $this->successful('退出登录成功',Url::build('Index/index'));
    }

    private function _logout()
    {
        Session::clear('wap');
        Cookie::delete('is_login');
    }

}